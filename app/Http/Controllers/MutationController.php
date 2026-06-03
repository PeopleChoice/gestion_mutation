<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DocumentTemplate;
use App\Models\Import;
use App\Models\ImportLigne;
use App\Models\Mutation;
use App\Models\MutationAnnulation;
use App\Models\Proprietaire;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MutationController extends Controller
{
    public function index(Request $request)
    {
        $query = Mutation::with('parcelle.projet.commune', 'ancienProprietaire', 'nouveauProprietaire', 'validateur');

        $showRefusees = $request->boolean('show_refusees');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        } elseif (!$showRefusees) {
            // Par défaut : on masque les refusées (sauf si l'utilisateur active le toggle)
            $query->where('statut', '!=', 'refusee');
        }
        if ($request->filled('import_id')) {
            $query->where('import_id', $request->import_id);
        }
        if ($request->filled('projet_id')) {
            $query->whereHas('parcelle', fn($q) => $q->where('projet_id', $request->projet_id));
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_mutation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_mutation', '<=', $request->date_fin);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('numero_notification', 'like', "%{$s}%")
                  ->orWhereHas('parcelle', fn($p) => $p->where('numero_lot', 'like', "%{$s}%"))
                  ->orWhereHas('ancienProprietaire', fn($p) => $p->where('nom', 'like', "%{$s}%")->orWhere('prenom', 'like', "%{$s}%"))
                  ->orWhereHas('nouveauProprietaire', fn($p) => $p->where('nom', 'like', "%{$s}%")->orWhere('prenom', 'like', "%{$s}%"));
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        $mutations = $query->latest()->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Mutation::count(),
            'en_attente' => Mutation::where('statut', 'en_attente')->count(),
            'validees' => Mutation::where('statut', 'validee')->count(),
            'refusees' => Mutation::where('statut', 'refusee')->count(),
        ];

        $projets = \App\Models\Projet::with('commune')->orderBy('nom')->get();

        return view('mutations.index', compact('mutations', 'stats', 'projets', 'showRefusees'));
    }

    public function create(Request $request)
    {
        $projets = \App\Models\Projet::with('commune')->orderBy('nom')->get();
        $parcelleId = $request->input('parcelle_id');
        $parcelle = null;

        if ($parcelleId) {
            $parcelle = \App\Models\Parcelle::with('proprietaire', 'projet')->find($parcelleId);
        }

        $dernierNumero = Mutation::whereNotNull('numero_notification')
            ->orderByDesc('id')
            ->value('numero_notification');

        return view('mutations.create', compact('projets', 'parcelle', 'dernierNumero'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parcelle_id' => 'required|exists:parcelles,id',
            'date_mutation' => 'required|date',
            'ref_lettre' => 'nullable|string|max:255',
            'code_paye' => 'nullable|string|max:255',
            'type_piece' => 'nullable|string|max:255',
            'observation' => 'nullable|string|max:1000',

            // Nouveau proprietaire
            'nouveau_proprietaire_id' => 'nullable|exists:proprietaires,id',
            'civilite' => 'required_without:nouveau_proprietaire_id|nullable|in:Monsieur,Madame,Société',
            'prenom' => 'required_without:nouveau_proprietaire_id|nullable|string|max:255',
            'nom' => 'nullable|string|max:255',
            'cni_passport' => 'nullable|string|max:255',
            'ninea' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:500',

            // Demandeur (souvent = précédent propriétaire)
            'demandeur_prenom' => 'nullable|string|max:255',
            'demandeur_nom' => 'nullable|string|max:255',
            'demandeur_telephone' => 'nullable|string|max:255',

            'numero_notification' => 'nullable|string|max:50',
            'validation' => 'nullable|in:en_attente,validee',
        ]);

        $parcelle = \App\Models\Parcelle::with('proprietaire')->findOrFail($validated['parcelle_id']);
        $ancienProprietaire = $parcelle->proprietaire;

        // RÈGLE : on ne peut pas muter une parcelle non attribuée
        if (!$ancienProprietaire) {
            return back()->withInput()->with('error',
                "Le lot {$parcelle->numero_lot} n'a pas de propriétaire actuel. " .
                "Une mutation suppose un changement de propriétaire — utilisez d'abord 'Attribuer' sur cette parcelle vierge.");
        }

        // Selection ou creation du nouveau proprietaire
        if (!empty($validated['nouveau_proprietaire_id'])) {
            $nouveauProprietaire = Proprietaire::findOrFail($validated['nouveau_proprietaire_id']);
        } else {
            $nouveauProprietaire = Proprietaire::create([
                'civilite' => $validated['civilite'],
                'prenom' => $validated['prenom'],
                'nom' => $validated['nom'] ?? null,
                'cni_passport' => $validated['cni_passport'] ?? null,
                'type_piece' => $validated['type_piece'] ?? null,
                'ninea' => $validated['ninea'] ?? null,
                'telephone' => $validated['telephone'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
            ]);
        }

        if ($ancienProprietaire && $ancienProprietaire->id === $nouveauProprietaire->id) {
            return back()->withInput()->with('error', "Le nouveau proprietaire est identique a l'ancien.");
        }

        $statut = $validated['validation'] ?? 'en_attente';
        $estAdmin = auth()->user()->hasRole('admin');
        // Les non-admins ne peuvent creer que "en_attente"
        if (!$estAdmin) $statut = 'en_attente';

        $mutation = Mutation::create([
            'parcelle_id' => $parcelle->id,
            'ancien_proprietaire_id' => $ancienProprietaire?->id,
            'nouveau_proprietaire_id' => $nouveauProprietaire->id,
            'import_id' => null,
            'statut' => $statut,
            'ref_lettre' => $validated['ref_lettre'] ?? null,
            'code_paye' => $validated['code_paye'] ?? null,
            'type_piece' => $validated['type_piece'] ?? null,
            'demandeur_prenom' => $validated['demandeur_prenom'] ?? null,
            'demandeur_nom' => $validated['demandeur_nom'] ?? null,
            'demandeur_telephone' => $validated['demandeur_telephone'] ?? null,
            'numero_notification' => $validated['numero_notification'] ?: Mutation::genererNumeroNotification($parcelle->projet_id),
            'date_mutation' => $validated['date_mutation'],
            'observation' => $validated['observation'] ?? null,
            'validated_by' => $statut === 'validee' ? auth()->id() : null,
            'validated_at' => $statut === 'validee' ? now() : null,
        ]);

        // Si directement validee : mettre a jour la parcelle + QR
        if ($statut === 'validee') {
            $mutation->genererCodeVerification();
            $parcelle->update(['proprietaire_id' => $nouveauProprietaire->id]);
        }

        ActivityLog::log('mutation_creee_manuellement', 'mutation',
            "Mutation manuelle - Lot {$parcelle->numero_lot} -> {$nouveauProprietaire->nom_complet} (statut: {$statut})",
            [
                'mutation_id' => $mutation->id,
                'lot' => $parcelle->numero_lot,
                'statut' => $statut,
            ]
        );

        $msg = $statut === 'validee'
            ? 'Mutation creee et validee avec succes.'
            : 'Mutation creee. En attente de validation par un administrateur.';

        return redirect()->route('mutations.show', $mutation)->with('success', $msg);
    }

    public function validerMutation(Request $request, Mutation $mutation)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Seuls les administrateurs peuvent valider.');
        }
        if ($mutation->statut !== 'en_attente') {
            return back()->with('error', 'Seules les mutations en attente peuvent etre validees.');
        }

        $mutation->load('parcelle', 'nouveauProprietaire');

        $mutation->update([
            'statut' => 'validee',
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);
        $mutation->genererCodeVerification();

        // Mettre a jour la parcelle avec le nouveau proprietaire
        $mutation->parcelle->update(['proprietaire_id' => $mutation->nouveau_proprietaire_id]);

        ActivityLog::log('mutation_validee', 'mutation',
            "Mutation validee - Lot {$mutation->parcelle->numero_lot} -> " . ($mutation->nouveauProprietaire?->nom_complet ?? '-'),
            ['mutation_id' => $mutation->id]
        );

        return redirect()->route('mutations.show', $mutation)
            ->with('success', 'Mutation validee. Le QR code a ete genere et la parcelle est mise a jour.');
    }

    public function refuserMutation(Request $request, Mutation $mutation)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Seuls les administrateurs peuvent refuser.');
        }
        if ($mutation->statut !== 'en_attente') {
            return back()->with('error', 'Seules les mutations en attente peuvent etre refusees.');
        }

        $validated = $request->validate([
            'motif_refus' => 'required|string|min:3|max:500',
        ]);

        $mutation->update([
            'statut' => 'refusee',
            'motif_refus' => $validated['motif_refus'],
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        ActivityLog::log('mutation_refusee', 'mutation',
            "Mutation refusee - Lot " . ($mutation->parcelle->numero_lot ?? '-'),
            ['mutation_id' => $mutation->id, 'motif' => $validated['motif_refus']]
        );

        return redirect()->route('mutations.show', $mutation)
            ->with('success', 'Mutation refusee.');
    }

    public function rechercheParcelles(Request $request)
    {
        $q = $request->input('q', '');
        $projet = $request->input('projet_id');

        $query = \App\Models\Parcelle::with('proprietaire', 'projet.commune')
            ->whereNotNull('proprietaire_id');

        if ($projet) $query->where('projet_id', $projet);

        if ($q) {
            $query->where(function ($qr) use ($q) {
                $qr->where('numero_lot', 'like', "%{$q}%")
                   ->orWhereHas('proprietaire', fn($p) => $p->where('nom', 'like', "%{$q}%")->orWhere('prenom', 'like', "%{$q}%")->orWhere('cni_passport', 'like', "%{$q}%"));
            });
        }

        return response()->json(
            $query->orderBy('numero_lot')->limit(20)->get()->map(fn($p) => [
                'id' => $p->id,
                'numero_lot' => $p->numero_lot,
                'projet' => $p->projet->nom ?? '',
                'commune' => $p->projet->commune->nom ?? '',
                'proprietaire' => $p->proprietaire?->nom_complet ?? 'Sans proprietaire',
                'cni' => $p->proprietaire?->cni_passport ?? '',
            ])
        );
    }

    public function rechercheProprietaires(Request $request)
    {
        $q = $request->input('q', '');
        if (!$q) return response()->json([]);

        return response()->json(
            Proprietaire::where('nom', 'like', "%{$q}%")
                ->orWhere('prenom', 'like', "%{$q}%")
                ->orWhere('cni_passport', 'like', "%{$q}%")
                ->limit(15)->get()->map(fn($p) => [
                    'id' => $p->id,
                    'nom_complet' => $p->nom_complet,
                    'cni' => $p->cni_passport,
                    'telephone' => $p->telephone,
                ])
        );
    }

    public function traiterImport(Import $import)
    {
        $import->load('projet.commune', 'lignes.parcelle.proprietaire');
        return view('mutations.traiter', compact('import'));
    }

    public function valider(Request $request, ImportLigne $ligne)
    {
        $request->validate([
            'civilite' => 'required|in:Monsieur,Madame,Société',
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'cni_passport' => 'required|string|max:255',
            'type_piece' => 'nullable|string|max:255',
            'ninea' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:500',
            'code_paye' => 'nullable|string|max:255',
            'ref_lettre' => 'nullable|string|max:255',
            'numero_notification' => 'nullable|string|max:50',
            'demandeur_prenom' => 'nullable|string|max:255',
            'demandeur_nom' => 'nullable|string|max:255',
            'demandeur_telephone' => 'nullable|string|max:255',
        ]);

        $parcelle = $ligne->parcelle;
        $ancienProprietaire = $parcelle?->proprietaire;

        // RÈGLE : on ne peut pas muter une parcelle non attribuée
        if (!$parcelle || !$ancienProprietaire) {
            return response()->json([
                'success' => false,
                'message' => "Lot {$ligne->numero_lot} sans propriétaire actuel — impossible de muter une parcelle vierge. Attribuez-la d'abord.",
            ], 422);
        }

        // Créer le nouveau propriétaire
        $nouveauProprietaire = Proprietaire::create([
            'civilite' => $request->civilite,
            'prenom' => $request->prenom,
            'nom' => $request->nom,
            'cni_passport' => $request->cni_passport,
            'type_piece' => $request->type_piece,
            'ninea' => $request->ninea,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
        ]);

        // Créer la mutation (numero_notification saisi manuellement, sinon auto-généré)
        $mutation = Mutation::create([
            'parcelle_id' => $parcelle->id,
            'ancien_proprietaire_id' => $ancienProprietaire?->id,
            'nouveau_proprietaire_id' => $nouveauProprietaire->id,
            'import_id' => $ligne->import_id,
            'statut' => 'validee',
            'ref_lettre' => $request->ref_lettre ?? $ligne->ref_lettre,
            'code_paye' => $request->code_paye,
            'type_piece' => $request->type_piece,
            'demandeur_prenom' => $request->demandeur_prenom ?? $ligne->demandeur_prenom,
            'demandeur_nom' => $request->demandeur_nom ?? $ligne->demandeur_nom,
            'demandeur_telephone' => $request->demandeur_telephone ?? $ligne->demandeur_telephone,
            'numero_notification' => $request->numero_notification ?: Mutation::genererNumeroNotification($parcelle->projet_id),
            'date_mutation' => now(),
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        // Générer le code de vérification pour le QR code
        $mutation->genererCodeVerification();

        // Mettre à jour le propriétaire de la parcelle
        $parcelle->update(['proprietaire_id' => $nouveauProprietaire->id]);

        // Mettre à jour le statut de la ligne d'import
        $ligne->update(['statut' => 'validee']);

        ActivityLog::log('mutation_validee', 'mutation', "Mutation validée - Lot {$parcelle->numero_lot} -> {$nouveauProprietaire->nom_complet}", [
            'mutation_id' => $mutation->id,
            'lot' => $parcelle->numero_lot,
            'nouveau_proprietaire' => $nouveauProprietaire->nom_complet,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mutation validée avec succès.',
            'mutation_id' => $mutation->id,
            'numero_notification' => $mutation->numero_notification,
        ]);
    }

    public function refuser(Request $request, ImportLigne $ligne)
    {
        $request->validate([
            'motif_refus' => 'required|string|max:1000',
        ]);

        $parcelle = $ligne->parcelle;

        // Si la ligne n'a pas de parcelle matched, on ne crée pas de Mutation
        // (parcelle_id est NOT NULL via la FK) → on se contente de marquer la ligne refusée.
        if ($parcelle) {
            Mutation::create([
                'parcelle_id' => $parcelle->id,
                'ancien_proprietaire_id' => $parcelle->proprietaire?->id,
                'import_id' => $ligne->import_id,
                'statut' => 'refusee',
                'motif_refus' => $request->motif_refus,
                'date_mutation' => now(),
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);
        }

        $ligne->update([
            'statut' => 'refusee',
            'observation' => ($ligne->observation ? $ligne->observation . ' | ' : '') . 'Refusé : ' . $request->motif_refus,
        ]);

        ActivityLog::log('mutation_refusee', 'mutation', "Mutation refusée - Lot {$ligne->numero_lot}", [
            'lot' => $ligne->numero_lot,
            'motif' => $request->motif_refus,
            'parcelle_matched' => $parcelle !== null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mutation refusée.',
        ]);
    }

    public function demanderAnnulation(Request $request, Mutation $mutation)
    {
        $request->validate([
            'motif' => 'required|string|max:1000',
        ]);

        MutationAnnulation::create([
            'mutation_id' => $mutation->id,
            'demande_par' => auth()->id(),
            'motif' => $request->motif,
        ]);

        ActivityLog::log('demande_annulation', 'mutation', "Demande d'annulation - Mutation #{$mutation->id}", [
            'mutation_id' => $mutation->id,
            'motif' => $request->motif,
        ]);

        return back()->with('success', 'Demande d\'annulation envoyée à l\'administrateur.');
    }

    public function annulationsEnAttente()
    {
        $annulations = MutationAnnulation::with('mutation.parcelle.projet', 'mutation.nouveauProprietaire', 'demandeur')
            ->where('statut', 'en_attente')
            ->latest()
            ->paginate(15);

        return view('mutations.annulations', compact('annulations'));
    }

    public function traiterAnnulation(Request $request, MutationAnnulation $annulation)
    {
        $request->validate([
            'action' => 'required|in:approuver,rejeter',
            'motif_rejet' => 'required_if:action,rejeter|nullable|string',
        ]);

        if ($request->action === 'approuver') {
            $mutation = $annulation->mutation;
            $parcelle = $mutation->parcelle;

            // Remettre l'ancien propriétaire
            $parcelle->update(['proprietaire_id' => $mutation->ancien_proprietaire_id]);
            $mutation->update(['statut' => 'annulee']);

            $annulation->update([
                'statut' => 'approuvee',
                'approuve_par' => auth()->id(),
                'approuve_at' => now(),
            ]);

            return back()->with('success', 'Annulation approuvée. Le terrain a été remis à l\'ancien propriétaire.');
        }

        $annulation->update([
            'statut' => 'rejetee',
            'approuve_par' => auth()->id(),
            'motif_rejet' => $request->motif_rejet,
            'approuve_at' => now(),
        ]);

        return back()->with('success', 'Annulation rejetée.');
    }

    public function show(Mutation $mutation)
    {
        $mutation->load('parcelle.projet.commune', 'ancienProprietaire', 'nouveauProprietaire', 'validateur', 'annulation');
        return view('mutations.show', compact('mutation'));
    }

    /**
     * Validation globale de toutes les lignes en attente (matchées).
     */
    public function validerTout(Request $request, Import $import)
    {
        $request->validate([
            'civilite' => 'required|in:Monsieur,Madame,Société',
            'ref_lettre_globale' => 'nullable|string|max:255',
            'num_notif_depart' => 'nullable|string|max:50',
        ]);

        // Compteur séquentiel pour les N° notification
        $notifCounter = $request->filled('num_notif_depart')
            ? (int) preg_replace('/\D/', '', $request->input('num_notif_depart'))
            : null;
        $nextNotif = function() use (&$notifCounter, $import) {
            if ($notifCounter !== null) {
                $n = str_pad((string) $notifCounter, 7, '0', STR_PAD_LEFT);
                $notifCounter++;
                return $n;
            }
            return Mutation::genererNumeroNotification($import->projet_id);
        };

        $lignes = $import->lignes()
            ->where('statut', 'en_attente')
            ->where('matched', true)
            ->with('parcelle.proprietaire')
            ->get();

        if ($lignes->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Aucune ligne en attente à valider.']);
        }

        $resultats = [];
        $skippedVierges = [];

        foreach ($lignes as $ligne) {
            $parcelle = $ligne->parcelle;
            $ancienProprietaire = $parcelle?->proprietaire;

            // RÈGLE : on ne mute pas une parcelle vierge — on skip
            if (!$parcelle || !$ancienProprietaire) {
                $skippedVierges[] = $ligne->numero_lot;
                continue;
            }

            $nouveauProprietaire = Proprietaire::create([
                'civilite' => $ligne->civilite ?: $request->civilite,
                'prenom' => $ligne->prenom ?? 'N/A',
                'nom' => $ligne->nom ?? 'N/A',
                'type_piece' => $ligne->type_piece,
                'cni_passport' => $ligne->cni_passport,
                'ninea' => $ligne->ninea,
                'telephone' => $ligne->telephone,
            ]);

            $mutation = Mutation::create([
                'parcelle_id' => $parcelle->id,
                'ancien_proprietaire_id' => $ancienProprietaire?->id,
                'nouveau_proprietaire_id' => $nouveauProprietaire->id,
                'import_id' => $import->id,
                'statut' => 'validee',
                'ref_lettre' => $request->ref_lettre_globale ?? $ligne->ref_lettre,
                'type_piece' => $ligne->type_piece,
                'code_paye' => $ligne->code_paye,
                'demandeur_prenom' => $ligne->demandeur_prenom,
                'demandeur_nom' => $ligne->demandeur_nom,
                'demandeur_telephone' => $ligne->demandeur_telephone,
                'numero_notification' => $nextNotif(),
                'date_mutation' => now(),
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);

            $mutation->genererCodeVerification();
            $parcelle->update(['proprietaire_id' => $nouveauProprietaire->id]);
            $ligne->update(['statut' => 'validee']);

            $resultats[] = [
                'ligne_id' => $ligne->id,
                'mutation_id' => $mutation->id,
                'numero_notification' => $mutation->numero_notification,
            ];
        }

        $msg = count($resultats) . ' mutation(s) validée(s) avec succès.';
        if (!empty($skippedVierges)) {
            $msg .= ' ' . count($skippedVierges) . ' lot(s) ignoré(s) car vierge(s) : ' . implode(', ', array_slice($skippedVierges, 0, 5)) . (count($skippedVierges) > 5 ? '...' : '') . '.';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'resultats' => $resultats,
            'skipped_vierges' => $skippedVierges,
        ]);
    }

    /**
     * Refus global de toutes les lignes en attente (matchées).
     */
    public function refuserTout(Request $request, Import $import)
    {
        $request->validate([
            'motif_refus' => 'required|string|max:1000',
        ]);

        $lignes = $import->lignes()
            ->where('statut', 'en_attente')
            ->where('matched', true)
            ->with('parcelle.proprietaire')
            ->get();

        if ($lignes->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Aucune ligne en attente à refuser.']);
        }

        $count = 0;
        foreach ($lignes as $ligne) {
            Mutation::create([
                'parcelle_id' => $ligne->parcelle?->id,
                'ancien_proprietaire_id' => $ligne->parcelle?->proprietaire?->id,
                'import_id' => $import->id,
                'statut' => 'refusee',
                'motif_refus' => $request->motif_refus,
                'date_mutation' => now(),
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);
            $ligne->update(['statut' => 'refusee']);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => $count . ' mutation(s) refusée(s).',
            'count' => $count,
        ]);
    }

    /**
     * Validation de lignes sélectionnées (bulk partiel).
     */
    public function validerSelection(Request $request, Import $import)
    {
        $request->validate([
            'ligne_ids' => 'required|array|min:1',
            'ligne_ids.*' => 'exists:import_lignes,id',
            'num_notif_depart' => 'nullable|string|max:50',
        ]);

        $notifCounter = $request->filled('num_notif_depart')
            ? (int) preg_replace('/\D/', '', $request->input('num_notif_depart'))
            : null;
        $nextNotif = function() use (&$notifCounter, $import) {
            if ($notifCounter !== null) {
                $n = str_pad((string) $notifCounter, 7, '0', STR_PAD_LEFT);
                $notifCounter++;
                return $n;
            }
            return Mutation::genererNumeroNotification($import->projet_id);
        };

        $lignes = ImportLigne::whereIn('id', $request->ligne_ids)
            ->where('import_id', $import->id)
            ->where('statut', 'en_attente')
            ->where('matched', true)
            ->with('parcelle.proprietaire')
            ->get();

        if ($lignes->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Aucune ligne sélectionnée à traiter.']);
        }

        $resultats = [];
        $skippedVierges = [];
        foreach ($lignes as $ligne) {
            $parcelle = $ligne->parcelle;
            $ancienProprietaire = $parcelle?->proprietaire;

            // RÈGLE : on ne mute pas une parcelle vierge
            if (!$parcelle || !$ancienProprietaire) {
                $skippedVierges[] = $ligne->numero_lot;
                continue;
            }

            $nouveauProprietaire = Proprietaire::create([
                'civilite' => $ligne->civilite ?: 'Monsieur',
                'prenom' => $ligne->prenom ?? 'N/A',
                'nom' => $ligne->nom ?? 'N/A',
                'type_piece' => $ligne->type_piece,
                'cni_passport' => $ligne->cni_passport,
                'ninea' => $ligne->ninea,
                'telephone' => $ligne->telephone,
            ]);

            $mutation = Mutation::create([
                'parcelle_id' => $parcelle->id,
                'ancien_proprietaire_id' => $ancienProprietaire?->id,
                'nouveau_proprietaire_id' => $nouveauProprietaire->id,
                'import_id' => $import->id,
                'statut' => 'validee',
                'ref_lettre' => $ligne->ref_lettre,
                'type_piece' => $ligne->type_piece,
                'code_paye' => $ligne->code_paye,
                'demandeur_prenom' => $ligne->demandeur_prenom,
                'demandeur_nom' => $ligne->demandeur_nom,
                'demandeur_telephone' => $ligne->demandeur_telephone,
                'numero_notification' => $nextNotif(),
                'date_mutation' => now(),
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);

            $mutation->genererCodeVerification();
            $parcelle->update(['proprietaire_id' => $nouveauProprietaire->id]);
            $ligne->update(['statut' => 'validee']);

            $resultats[] = ['ligne_id' => $ligne->id, 'mutation_id' => $mutation->id, 'numero_notification' => $mutation->numero_notification];
        }

        $msg = count($resultats) . ' mutation(s) validée(s).';
        if (!empty($skippedVierges)) {
            $msg .= ' ' . count($skippedVierges) . ' lot(s) vierge(s) ignoré(s) : ' . implode(', ', array_slice($skippedVierges, 0, 5)) . '.';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'resultats' => $resultats,
            'skipped_vierges' => $skippedVierges,
        ]);
    }

    /**
     * Refus de lignes sélectionnées (bulk partiel).
     */
    public function refuserSelection(Request $request, Import $import)
    {
        $request->validate([
            'ligne_ids' => 'required|array|min:1',
            'ligne_ids.*' => 'exists:import_lignes,id',
            'motif_refus' => 'required|string|max:1000',
        ]);

        $lignes = ImportLigne::whereIn('id', $request->ligne_ids)
            ->where('import_id', $import->id)
            ->where('statut', 'en_attente')
            ->where('matched', true)
            ->with('parcelle.proprietaire')
            ->get();

        $count = 0;
        foreach ($lignes as $ligne) {
            Mutation::create([
                'parcelle_id' => $ligne->parcelle?->id,
                'ancien_proprietaire_id' => $ligne->parcelle?->proprietaire?->id,
                'import_id' => $import->id,
                'statut' => 'refusee',
                'motif_refus' => $request->motif_refus,
                'date_mutation' => now(),
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);
            $ligne->update(['statut' => 'refusee']);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => $count . ' mutation(s) refusée(s).',
            'count' => $count,
        ]);
    }

    /**
     * Afficher le PDF dans le navigateur (stream) pour visualisation puis impression.
     */
    public function genererPdf(Mutation $mutation)
    {
        $mutation->load('parcelle.projet.commune', 'nouveauProprietaire', 'ancienProprietaire', 'validateur');

        $template = DocumentTemplate::where('type', 'notification_attribution')
            ->where('actif', true)
            ->first();

        if (!$template) {
            return back()->with('error', 'Aucun template de document actif trouvé.');
        }

        $html = $template->renderPourMutation($mutation);

        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $filename = "notification_{$mutation->numero_notification}.pdf";

        // stream = afficher dans le navigateur au lieu de télécharger
        return $pdf->stream($filename);
    }

    /**
     * Télécharger le PDF directement.
     */
    public function telechargerPdf(Mutation $mutation)
    {
        $mutation->load('parcelle.projet.commune', 'nouveauProprietaire', 'ancienProprietaire', 'validateur');

        $template = DocumentTemplate::where('type', 'notification_attribution')
            ->where('actif', true)
            ->first();

        if (!$template) {
            return back()->with('error', 'Aucun template de document actif trouvé.');
        }

        $html = $template->renderPourMutation($mutation);

        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $filename = "notification_{$mutation->numero_notification}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Page d'aperçu du document avec iframe intégrée + boutons imprimer/télécharger.
     */
    public function apercu(Mutation $mutation)
    {
        $mutation->load('parcelle.projet.commune', 'nouveauProprietaire', 'ancienProprietaire', 'validateur');
        return view('mutations.apercu', compact('mutation'));
    }
}
