<?php

namespace App\Http\Controllers;

use App\Exports\TemplateImportExport;
use App\Exports\AttributionGlobalTemplate;
use App\Exports\MutationGlobalTemplate;
use App\Imports\AttributionGlobalImport;
use App\Imports\MutationGlobalImport;
use App\Imports\ParcelleImport;
use App\Mail\TemplateImportMail;
use App\Models\Import;
use App\Models\Projet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        $filtre = $request->input('filtre', 'en_cours');

        $query = Import::with('projet', 'importeur')->withCount([
            'lignes as lignes_en_attente' => function ($q) {
                $q->where('statut', 'en_attente')->where('matched', true);
            },
            'lignes as lignes_validees_count' => function ($q) {
                $q->where('statut', 'validee');
            },
        ])->latest();

        if ($filtre === 'en_cours') {
            // Imports qui ont encore des lignes matchées en attente
            $query->whereHas('lignes', function ($q) {
                $q->where('statut', 'en_attente')->where('matched', true);
            });
        }

        $imports = $query->paginate(15)->appends(['filtre' => $filtre]);

        return view('imports.index', compact('imports', 'filtre'));
    }

    public function create()
    {
        $projets = Projet::with('commune')->get();
        return view('imports.create', compact('projets'));
    }

    /**
     * Pré-vérification AJAX du fichier avant import.
     */
    public function verifier(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:xls,xlsx|max:10240',
            'projet_id' => 'required',
        ]);

        $erreurs = [];
        $avertissements = [];
        $infos = [];

        // 1. Vérifier que le projet existe
        $projet = Projet::with('commune')->find($request->projet_id);
        if (!$projet) {
            $erreurs[] = "Le projet sélectionné (ID: {$request->projet_id}) n'existe pas dans la base de données.";
            return response()->json(['erreurs' => $erreurs, 'avertissements' => $avertissements, 'infos' => $infos, 'valid' => false]);
        }

        $infos[] = "Projet : {$projet->nom} - Commune de {$projet->commune->nom}";

        // 2. Lire le fichier Excel
        try {
            $file = $request->file('fichier');
            $rawRows = Excel::toCollection(null, $file)->first();
        } catch (\Exception $e) {
            $erreurs[] = "Impossible de lire le fichier : {$e->getMessage()}";
            return response()->json(['erreurs' => $erreurs, 'avertissements' => $avertissements, 'infos' => $infos, 'valid' => false]);
        }

        if (!$rawRows || $rawRows->count() < 2) {
            $erreurs[] = "Le fichier est vide ou ne contient qu'un en-tête.";
            return response()->json(['erreurs' => $erreurs, 'avertissements' => $avertissements, 'infos' => $infos, 'valid' => false]);
        }

        // 3. Détecter la VRAIE ligne d'en-têtes (la ligne avec le plus de cellules
        //    non vides parmi les 3 premières — les templates récents ont 2 lignes :
        //    ligne 1 = groupes fusionnés, ligne 2 = colonnes détaillées).
        $headerRowIdx = 0;
        $maxCols = 0;
        $maxScan = min(3, $rawRows->count());
        for ($r = 0; $r < $maxScan; $r++) {
            $cells = $rawRows->get($r)->values()->filter(fn($v) => trim((string) $v) !== '')->count();
            if ($cells > $maxCols) {
                $maxCols = $cells;
                $headerRowIdx = $r;
            }
        }
        $headers = $rawRows->get($headerRowIdx)->values()->map(fn($v) => strtolower(trim((string) $v)))->toArray();

        // Trouver l'index de la colonne LOT
        $lotIndex = null;
        $nomIndex = null;
        $prenomIndex = null;

        foreach ($headers as $i => $h) {
            if (in_array($h, ['lot'])) $lotIndex = $i;
            if (in_array($h, ['nom', 'nom demandeur'])) $nomIndex = $i;
            if (in_array($h, ['prénom', 'prenom', 'prénom demandeur', 'prenom demandeur'])) $prenomIndex = $i;
        }

        if ($lotIndex === null) {
            $erreurs[] = "Colonne 'LOT' introuvable dans l'en-tête. Colonnes trouvées : " . implode(', ', array_map(fn($v) => '"' . $v . '"', array_filter($headers)));
            return response()->json(['erreurs' => $erreurs, 'avertissements' => $avertissements, 'infos' => $infos, 'valid' => false]);
        }

        $infos[] = "Colonnes détectées : " . implode(', ', array_map(fn($v) => ucfirst($v), array_filter($headers)));

        // 4. Analyser chaque ligne (en sautant l'en-tête détecté + les lignes au-dessus)
        $dataRows = $rawRows->slice($headerRowIdx + 1);
        $totalLignes = 0;
        $lignesVides = 0;
        $lotsTrouves = 0;
        $lotsNonTrouves = [];
        $lotsDupliques = [];
        $lotsSansNom = [];
        $lotsVierges = [];      // RÈGLE : lots existants mais non attribués
        $lotsVus = [];

        foreach ($dataRows as $row) {
            $vals = $row->values()->toArray();
            $lot = trim((string) ($vals[$lotIndex] ?? ''));
            if (preg_match('/^\d+\.0$/', $lot)) $lot = (string) intval($lot);

            if (!$lot) {
                $lignesVides++;
                continue;
            }

            $totalLignes++;

            // Doublons dans le fichier
            if (in_array($lot, $lotsVus)) {
                $lotsDupliques[] = $lot;
            }
            $lotsVus[] = $lot;

            // Lot existe dans le projet ?
            $parcelle = \App\Models\Parcelle::where('numero_lot', $lot)
                ->where('projet_id', $projet->id)
                ->first();

            if ($parcelle) {
                $lotsTrouves++;
                // RÈGLE : on ne mute pas une parcelle vierge
                if (!$parcelle->proprietaire_id) {
                    $lotsVierges[] = $lot;
                }
            } else {
                $lotsNonTrouves[] = $lot;
            }

            // Nom/prénom du demandeur
            $nom = $nomIndex !== null ? trim((string) ($vals[$nomIndex] ?? '')) : '';
            $prenom = $prenomIndex !== null ? trim((string) ($vals[$prenomIndex] ?? '')) : '';
            if (!$nom && !$prenom) {
                $lotsSansNom[] = $lot;
            }
        }

        $infos[] = "Fichier : {$request->file('fichier')->getClientOriginalName()}";
        $infos[] = "Total de lignes avec lot : {$totalLignes}";
        $infos[] = "Correspondances trouvées : {$lotsTrouves} / {$totalLignes}";

        if ($lignesVides > 0) {
            $avertissements[] = "{$lignesVides} ligne(s) sans numéro de lot (ignorées).";
        }

        if (count($lotsNonTrouves) > 0) {
            $avertissements[] = count($lotsNonTrouves) . " lot(s) non trouvé(s) dans le projet : " . implode(', ', array_slice($lotsNonTrouves, 0, 10)) . (count($lotsNonTrouves) > 10 ? '...' : '');
        }

        if (count($lotsDupliques) > 0) {
            $erreurs[] = "Lot(s) en doublon dans le fichier : " . implode(', ', array_unique($lotsDupliques));
        }

        if (count($lotsSansNom) > 0) {
            $avertissements[] = count($lotsSansNom) . " ligne(s) sans nom/prénom du demandeur (lots : " . implode(', ', array_slice($lotsSansNom, 0, 5)) . (count($lotsSansNom) > 5 ? '...' : '') . ")";
        }

        if (count($lotsVierges) > 0) {
            $avertissements[] = count($lotsVierges) . " lot(s) sans propriétaire actuel (parcelle vierge) seront ignorés à la validation : " . implode(', ', array_slice($lotsVierges, 0, 10)) . (count($lotsVierges) > 10 ? '...' : '') . ". Pour les muter, attribuez-les d'abord.";
        }

        if ($totalLignes === 0) {
            $erreurs[] = "Aucune ligne exploitable trouvée dans le fichier.";
        }

        $valid = count($erreurs) === 0 && $totalLignes > 0;

        return response()->json([
            'valid' => $valid,
            'erreurs' => $erreurs,
            'avertissements' => $avertissements,
            'infos' => $infos,
            'stats' => [
                'total' => $totalLignes,
                'trouves' => $lotsTrouves,
                'non_trouves' => count($lotsNonTrouves),
                'doublons' => count($lotsDupliques),
                'sans_nom' => count($lotsSansNom),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:xls,xlsx|max:10240',
            'projet_id' => 'required|exists:projets,id',
        ]);

        $file = $request->file('fichier');
        $path = $file->store('imports', 'public');

        $import = Import::create([
            'nom_fichier' => $file->getClientOriginalName(),
            'fichier_path' => $path,
            'projet_id' => $request->projet_id,
            'imported_by' => auth()->id(),
        ]);

        try {
            Excel::import(new ParcelleImport($import), $file);

            return redirect()->route('imports.show', $import)
                ->with('success', "Import terminé : {$import->lignes_matchees}/{$import->total_lignes} correspondances trouvées.");
        } catch (\Exception $e) {
            $import->update(['statut' => 'erreur']);
            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    public function show(Import $import)
    {
        $import->load('projet.commune', 'importeur', 'lignes.parcelle.proprietaire');
        return view('imports.show', compact('import'));
    }

    /**
     * Télécharger un template Excel vide ou pré-rempli pour un projet.
     */
    public function telechargerTemplate(Request $request, Projet $projet)
    {
        $avecLots = $request->boolean('avec_lots');
        $projet->load('commune');

        $filename = "Template_Import_{$projet->nom}_{$projet->commune->nom}.xlsx";

        return Excel::download(
            new TemplateImportExport($projet, $avecLots),
            $filename
        );
    }

    /**
     * Envoyer le template par email à un promoteur.
     */
    public function envoyerTemplate(Request $request, Projet $projet)
    {
        $request->validate([
            'email' => 'required|email',
            'nom_promoteur' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
            'avec_lots' => 'nullable|boolean',
        ]);

        $projet->load('commune');
        $avecLots = $request->boolean('avec_lots');

        // Générer le fichier temporaire
        $tempName = "temp_template_{$projet->id}_" . time() . ".xlsx";

        Excel::store(
            new TemplateImportExport($projet, $avecLots),
            $tempName,
            'local'
        );

        $tempPath = storage_path("app/{$tempName}");

        try {
            Mail::to($request->email)->send(new TemplateImportMail(
                projet: $projet,
                nomPromoteur: $request->nom_promoteur,
                messagePersonnalise: $request->message ?? '',
                fichierPath: $tempPath,
            ));
        } finally {
            @unlink($tempPath);
        }

        // Supprimer le fichier temporaire
        @unlink($tempPath);

        return back()->with('success', "Template envoyé par email à {$request->email}.");
    }

    // ==================================================================
    // IMPORTS GLOBAUX (multi-projets, basés sur le code projet)
    // ==================================================================

    public function globalForm()
    {
        $projets = Projet::with('commune')->orderBy('code')->get();
        return view('imports.global', compact('projets'));
    }

    public function globalAttribution(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:xls,xlsx|max:10240',
            'num_notif_depart' => 'nullable|string|max:50',
        ]);

        if ($request->filled('num_notif_depart')) {
            session(['num_notif_depart' => $request->input('num_notif_depart')]);
        }

        $file = $request->file('fichier');
        $storedPath = $file->store('imports-global', 'local');
        $import = new AttributionGlobalImport(auth()->id(), $file->getClientOriginalName(), $storedPath);

        try {
            Excel::import($import, $file);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lecture fichier : ' . $e->getMessage());
        }

        if (!empty($import->errors)) {
            session()->flash('warning', implode(' | ', array_slice($import->errors, 0, 5)));
        }

        if (empty($import->resultats)) {
            return back()->with('error', "Aucun import créé. Vérifiez la colonne 'Code projet'.");
        }

        $importIds = array_map(fn($r) => $r['import_id'], $import->resultats);

        return redirect()->route('imports.global.validation', ['ids' => implode(',', $importIds)])
            ->with('success', count($importIds) . " import(s) d'attribution créé(s). Validez chaque ligne ci-dessous.");
    }

    public function globalMutation(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:xls,xlsx|max:10240',
            'num_notif_depart' => 'nullable|string|max:50',
        ]);

        if ($request->filled('num_notif_depart')) {
            session(['num_notif_depart' => $request->input('num_notif_depart')]);
        }

        $file = $request->file('fichier');
        // Sauvegarder le fichier source (pour audit + champ NOT NULL imports.fichier_path)
        $storedPath = $file->store('imports-global', 'local');
        $import = new MutationGlobalImport(auth()->id(), $file->getClientOriginalName(), $storedPath);

        try {
            Excel::import($import, $file);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lecture fichier : ' . $e->getMessage());
        }

        if (!empty($import->errors)) {
            session()->flash('warning', implode(' | ', array_slice($import->errors, 0, 5)));
        }

        if (empty($import->resultats)) {
            return back()->with('error', "Aucun import créé. Vérifiez la colonne 'Code projet'.");
        }

        $importIds = array_map(fn($r) => $r['import_id'], $import->resultats);

        return redirect()->route('imports.global.validation', ['ids' => implode(',', $importIds)])
            ->with('success', count($importIds) . " import(s) créé(s). Validez les mutations ci-dessous.");
    }

    /**
     * Page de validation unifiée pour les imports créés via l'import global.
     */
    public function globalValidation(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->input('ids', ''))));

        $imports = Import::with([
            'projet.commune',
            'lignes.parcelle.proprietaire',
        ])->whereIn('id', $ids)->orderBy('id')->get();

        if ($imports->isEmpty()) {
            return redirect()->route('imports.global')->with('error', 'Aucun import à valider.');
        }

        // Statistiques globales
        $totalLignes = $imports->sum(fn($i) => $i->lignes->count());
        $enAttente = 0;
        $mutables = 0;
        $vierges = 0;
        $nonMatched = 0;
        $traites = 0;

        foreach ($imports as $imp) {
            foreach ($imp->lignes as $l) {
                if ($l->statut !== 'en_attente') { $traites++; continue; }
                $enAttente++;
                if (!$l->matched) { $nonMatched++; continue; }
                if (!$l->parcelle?->proprietaire_id) { $vierges++; continue; }
                $mutables++;
            }
        }

        return view('imports.global-validation', compact('imports', 'totalLignes', 'enAttente', 'mutables', 'vierges', 'nonMatched', 'traites'));
    }

    public function globalAttributionTemplate()
    {
        return Excel::download(new AttributionGlobalTemplate(), 'template-attribution-global.xlsx');
    }

    public function globalMutationTemplate()
    {
        return Excel::download(new MutationGlobalTemplate(), 'template-mutation-global.xlsx');
    }

    /**
     * Valider une ligne d'attribution (issue d'un import global type=attribution).
     * - Lot inexistant : crée la parcelle + le proprio
     * - Lot vierge existant : crée le proprio + l'attribue
     * - Lot déjà attribué : refus (utiliser une mutation à la place)
     */
    public function validerAttribution(Request $request, \App\Models\ImportLigne $ligne)
    {
        $request->validate([
            'civilite'     => 'required|in:Monsieur,Madame,Société',
            'prenom'       => 'required|string|max:255',
            'nom'          => 'nullable|string|max:255',
            'type_piece'   => 'nullable|string|max:50',
            'code_paye'    => 'nullable|string|max:20',
            'cni_passport' => 'nullable|string|max:100',
            'ninea'        => 'nullable|string|max:50',
            'telephone'    => 'nullable|string|max:50',
            'adresse'      => 'nullable|string|max:500',
            'numero_notification' => 'nullable|string|max:50',
            'ref_lettre'   => 'nullable|string|max:255',
        ]);

        $import = $ligne->import;
        if (!$import || $import->type !== 'attribution') {
            return response()->json(['success' => false, 'message' => "Cette ligne n'est pas une attribution."], 422);
        }

        $projet = $import->projet;
        $parcelle = $ligne->parcelle ?: \App\Models\Parcelle::where('numero_lot', $ligne->numero_lot)
            ->where('projet_id', $projet->id)
            ->first();

        // Cas 3 : lot existe déjà avec un propriétaire → refus (passer par une mutation)
        if ($parcelle && $parcelle->proprietaire_id) {
            return response()->json([
                'success' => false,
                'message' => "Le lot {$ligne->numero_lot} a déjà un propriétaire. Utilisez une mutation pour le changer.",
            ], 422);
        }

        // Créer le propriétaire
        $prop = \App\Models\Proprietaire::create([
            'civilite'     => $request->civilite,
            'prenom'       => $request->prenom,
            'nom'          => $request->nom ?: null,
            'type_piece'   => $request->type_piece,
            'cni_passport' => $request->cni_passport,
            'ninea'        => $request->ninea,
            'telephone'    => $request->telephone,
            'adresse'      => $request->adresse,
        ]);

        if ($parcelle) {
            // Cas 2 : attribuer un lot vierge existant
            $parcelle->update([
                'proprietaire_id'  => $prop->id,
                'date_attribution' => now(),
            ]);
            $action = 'attribution_vierge';
        } else {
            // Cas 1 : créer le lot + l'attribuer
            $parcelle = \App\Models\Parcelle::create([
                'numero_lot'       => $ligne->numero_lot,
                'projet_id'        => $projet->id,
                'proprietaire_id'  => $prop->id,
                'date_attribution' => now(),
                'observation'      => $ligne->observation,
            ]);
            $action = 'creation_attribution';
        }

        // Créer aussi une Mutation (avec ancien_proprietaire_id = null pour une 1ère attribution)
        // afin de pouvoir générer un PDF de notification avec QR code.
        $mutation = \App\Models\Mutation::create([
            'parcelle_id'            => $parcelle->id,
            'ancien_proprietaire_id' => null,
            'nouveau_proprietaire_id'=> $prop->id,
            'import_id'              => $ligne->import_id,
            'statut'                 => 'validee',
            'ref_lettre'             => $request->ref_lettre ?? $ligne->ref_lettre,
            'code_paye'              => $request->code_paye,
            'type_piece'             => $request->type_piece,
            'numero_notification'    => $request->numero_notification ?: \App\Models\Mutation::genererNumeroNotification($projet->id),
            'date_mutation'          => now(),
            'validated_by'           => auth()->id(),
            'validated_at'           => now(),
        ]);
        $mutation->genererCodeVerification();

        $ligne->update(['statut' => 'validee', 'parcelle_id' => $parcelle->id]);

        \App\Models\ActivityLog::log($action, 'parcelle',
            "Attribution validée — Lot {$parcelle->numero_lot} → {$prop->nom_complet} (N° {$mutation->numero_notification})",
            ['parcelle_id' => $parcelle->id, 'proprietaire_id' => $prop->id, 'mutation_id' => $mutation->id]
        );

        return response()->json([
            'success'             => true,
            'message'             => "Attribution validée pour le lot {$parcelle->numero_lot}.",
            'parcelle_id'         => $parcelle->id,
            'mutation_id'         => $mutation->id,
            'numero_notification' => $mutation->numero_notification,
            'proprietaire'        => $prop->nom_complet,
            'action'              => $action,
        ]);
    }

    /**
     * Refuser une ligne d'attribution.
     */
    public function refuserAttribution(Request $request, \App\Models\ImportLigne $ligne)
    {
        $request->validate(['motif_refus' => 'required|string|max:1000']);
        $ligne->update(['statut' => 'refusee', 'observation' => 'Refusé : ' . $request->motif_refus]);

        return response()->json(['success' => true, 'message' => 'Attribution refusée.']);
    }

    /**
     * Génère un seul PDF contenant tous les documents validés de cet import.
     */
    public function pdfsFusionnes(Import $import)
    {
        $import->load(['lignes' => fn($q) => $q->where('statut', 'validee'), 'projet.commune']);

        // Récupérer toutes les mutations validées issues de cet import
        $mutations = \App\Models\Mutation::with('parcelle.projet.commune', 'nouveauProprietaire', 'ancienProprietaire', 'validateur')
            ->where('import_id', $import->id)
            ->where('statut', 'validee')
            ->orderBy('id')
            ->get();

        if ($mutations->isEmpty()) {
            return back()->with('error', "Aucun document validé à imprimer pour cet import.");
        }

        $template = \App\Models\DocumentTemplate::where('type', 'notification_attribution')
            ->where('actif', true)
            ->first();

        if (!$template) {
            return back()->with('error', 'Aucun template actif trouvé.');
        }

        // Construire un HTML unique avec un saut de page entre chaque document
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            @page { margin: 0; }
            body { margin: 0; padding: 0; }
            .doc-wrap { page-break-after: always; }
            .doc-wrap:last-child { page-break-after: auto; }
        </style></head><body>';

        foreach ($mutations as $mut) {
            $rendered = $template->renderPourMutation($mut);
            // Extraire le contenu <body> (sinon on a plusieurs <html>)
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $rendered, $m)) {
                $body = $m[1];
            } else {
                $body = $rendered;
            }
            // Conserver le bloc <style> du document
            $styleBlock = '';
            if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $rendered, $sm)) {
                $styleBlock = '<style>' . $sm[1] . '</style>';
            }
            $html .= '<div class="doc-wrap">' . $styleBlock . $body . '</div>';
        }
        $html .= '</body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4');
        $filename = "documents-import-{$import->id}-{$import->projet->code}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Supprime un import.
     * Les mutations déjà validées restent (leur import_id devient NULL).
     * Les parcelles déjà créées par une attribution validée restent (lien indépendant).
     * Les ImportLignes en attente/refusées sont supprimées en cascade.
     */
    public function destroy(Import $import)
    {
        $stats = [
            'lignes_total' => $import->lignes()->count(),
            'lignes_validees' => $import->lignes()->where('statut', 'validee')->count(),
            'lignes_pendantes' => $import->lignes()->where('statut', 'en_attente')->count(),
            'lignes_refusees' => $import->lignes()->where('statut', 'refusee')->count(),
            'mutations_conservees' => $import->mutations()->count(),
        ];

        $nomFichier = $import->nom_fichier;
        $import->delete();

        \App\Models\ActivityLog::log('import_supprime', 'import',
            "Import « {$nomFichier} » supprimé. " .
            "{$stats['lignes_validees']} ligne(s) déjà validées conservées (mutations/parcelles intactes). " .
            "{$stats['lignes_pendantes']} en attente + {$stats['lignes_refusees']} refusées supprimées.",
            $stats
        );

        return redirect()->route('imports.index')->with('success',
            "Import supprimé. {$stats['mutations_conservees']} mutation(s) déjà validée(s) conservée(s). " .
            "{$stats['lignes_pendantes']} ligne(s) en attente + {$stats['lignes_refusees']} refusée(s) supprimée(s)."
        );
    }
}
