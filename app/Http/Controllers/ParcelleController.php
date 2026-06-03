<?php

namespace App\Http\Controllers;

use App\Models\Parcelle;
use App\Models\Projet;
use App\Models\Proprietaire;
use Illuminate\Http\Request;

class ParcelleController extends Controller
{
    public function index(Request $request)
    {
        $query = Parcelle::with('projet.commune', 'proprietaire')->withCount('mutations');

        if ($request->filled('projet_id')) {
            $query->where('projet_id', $request->projet_id);
        }
        if ($request->filled('usage')) {
            $query->where('usage', $request->usage);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_lot', 'like', "%{$search}%")
                  ->orWhere('ref_lettre', 'like', "%{$search}%")
                  ->orWhereHas('proprietaire', function ($q2) use ($search) {
                      $q2->where('nom', 'like', "%{$search}%")
                         ->orWhere('prenom', 'like', "%{$search}%")
                         ->orWhere('cni_passport', 'like', "%{$search}%")
                         ->orWhere('telephone', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        $sort = $request->input('sort', 'numero_lot');
        $direction = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        if (!in_array($sort, ['numero_lot', 'date_attribution', 'superficie', 'created_at'])) {
            $sort = 'numero_lot';
        }

        $parcelles = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
        $projets = Projet::with('commune')->orderBy('nom')->get();

        // Stats sur la selection (non paginee)
        $statsQuery = (clone $query);
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'superficie_totale' => (clone $statsQuery)->sum('superficie'),
            'avec_mutations' => (clone $statsQuery)->has('mutations')->count(),
            'sans_proprietaire' => (clone $statsQuery)->whereNull('proprietaire_id')->count(),
        ];

        $usages = Parcelle::whereNotNull('usage')->where('usage', '!=', '')
            ->distinct()->orderBy('usage')->pluck('usage');

        return view('parcelles.index', compact('parcelles', 'projets', 'stats', 'usages', 'sort', 'direction'));
    }

    public function create()
    {
        $projets = Projet::with('commune')->get();
        return view('parcelles.create', compact('projets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_lot' => 'required|string',
            'projet_id' => 'required|exists:projets,id',
            'civilite' => 'nullable|in:Monsieur,Madame,Société',
            'prenom' => 'required|string',
            'nom' => 'required|string',
            'nin' => 'nullable|string',
            'ninea' => 'nullable|string',
            'cni_passport' => 'nullable|string',
            'telephone' => 'nullable|string',
            'superficie' => 'nullable|numeric',
            'usage' => 'nullable|string',
        ]);

        // RÈGLE : un même numéro de lot ne peut pas exister deux fois dans le même projet
        $exists = Parcelle::where('numero_lot', $request->numero_lot)
            ->where('projet_id', $request->projet_id)
            ->exists();

        if ($exists) {
            $projet = Projet::find($request->projet_id);
            return back()->with('error',
                "Le lot « {$request->numero_lot} » existe déjà dans le projet " . ($projet?->nom ?? '#' . $request->projet_id) .
                ". Un même lot ne peut pas exister deux fois dans le même projet."
            )->withInput();
        }

        $proprietaire = Proprietaire::create($request->only(
            'civilite', 'prenom', 'nom', 'nin', 'ninea', 'cni_passport', 'telephone'
        ));

        Parcelle::create([
            'numero_lot' => $request->numero_lot,
            'projet_id' => $request->projet_id,
            'proprietaire_id' => $proprietaire->id,
            'superficie' => $request->superficie,
            'usage' => $request->usage,
            'date_attribution' => now(),
        ]);

        return redirect()->route('parcelles.index')->with('success', 'Parcelle enregistrée avec succès.');
    }

    public function show(Parcelle $parcelle)
    {
        $parcelle->load('projet.commune', 'proprietaire', 'mutations.ancienProprietaire', 'mutations.nouveauProprietaire');
        return view('parcelles.show', compact('parcelle'));
    }

    public function edit(Parcelle $parcelle)
    {
        $parcelle->load('proprietaire');
        $projets = Projet::with('commune')->get();
        return view('parcelles.edit', compact('parcelle', 'projets'));
    }

    public function update(Request $request, Parcelle $parcelle)
    {
        $request->validate([
            'numero_lot' => 'required|string',
            'projet_id' => 'required|exists:projets,id',
            'superficie' => 'nullable|numeric',
            'usage' => 'nullable|string',
        ]);

        $parcelle->update($request->only('numero_lot', 'projet_id', 'superficie', 'usage'));

        return redirect()->route('parcelles.show', $parcelle)->with('success', 'Parcelle mise à jour.');
    }

    /**
     * Attribuer une parcelle à un nouveau propriétaire.
     * - Parcelle libre (sans proprio) : attribution directe.
     * - Parcelle déjà attribuée : exige le mot de passe ATTRIBUTION_FORCE_PASSWORD
     *   (sinon il faut passer par une mutation).
     */
    public function attribuer(Request $request, Parcelle $parcelle)
    {
        $parcelle->load('proprietaire');

        $validated = $request->validate([
            'civilite' => 'required|in:Monsieur,Madame,Société',
            'prenom' => 'required|string|max:255',
            'nom' => 'nullable|string|max:255',
            'type_piece' => 'nullable|string|max:50',
            'code_paye' => 'nullable|string|max:20',
            'cni_passport' => 'nullable|string|max:100',
            'ninea' => 'nullable|string|max:50',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:500',
            'numero_notification' => 'required|string|max:50',
            'force_password' => 'nullable|string',
        ]);

        // Si la parcelle a déjà un propriétaire, exiger le mot de passe (comparaison timing-safe)
        if ($parcelle->proprietaire) {
            $expected = (string) config('app.attribution_force_password', '');
            $given    = (string) ($validated['force_password'] ?? '');
            if ($expected === '' || !hash_equals($expected, $given)) {
                \App\Models\ActivityLog::log('attribution_force_refusee', 'parcelle',
                    "Tentative de forçage refusée — lot {$parcelle->numero_lot}",
                    ['parcelle_id' => $parcelle->id, 'user' => auth()->id()]
                );
                return back()
                    ->with('error', 'Cette parcelle est déjà attribuée à ' . $parcelle->proprietaire->nom_complet .
                        '. Utilisez une mutation, ou saisissez le mot de passe administrateur pour forcer.')
                    ->withInput();
            }
        }

        $prop = Proprietaire::create([
            'civilite' => $validated['civilite'],
            'prenom' => $validated['prenom'],
            'nom' => $validated['nom'] ?? null,
            'type_piece' => $validated['type_piece'] ?? null,
            'cni_passport' => $validated['cni_passport'] ?? null,
            'ninea' => $validated['ninea'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
        ]);

        $ancienProprietaireId = $parcelle->proprietaire?->id; // peut être non-null si force
        $parcelle->update([
            'proprietaire_id' => $prop->id,
            'date_attribution' => now(),
        ]);

        // Créer une Mutation pour pouvoir générer le PDF de notification
        $mutation = \App\Models\Mutation::create([
            'parcelle_id'             => $parcelle->id,
            'ancien_proprietaire_id'  => $ancienProprietaireId,
            'nouveau_proprietaire_id' => $prop->id,
            'statut'                  => 'validee',
            'type_piece'              => $validated['type_piece'] ?? null,
            'code_paye'               => $validated['code_paye'] ?? null,
            'numero_notification'     => $validated['numero_notification'],
            'date_mutation'           => now(),
            'validated_by'            => auth()->id(),
            'validated_at'            => now(),
        ]);
        $mutation->genererCodeVerification();

        \App\Models\ActivityLog::log(
            $ancienProprietaireId ? 'attribution_forcee' : 'attribution',
            'parcelle',
            "Attribution parcelle lot {$parcelle->numero_lot} à {$prop->nom_complet} (N° {$mutation->numero_notification})",
            ['parcelle_id' => $parcelle->id, 'proprietaire_id' => $prop->id, 'mutation_id' => $mutation->id, 'force' => !empty($validated['force_password'])]
        );

        return redirect()
            ->route('mutations.apercu', $mutation)
            ->with('success', "Parcelle {$parcelle->numero_lot} attribuée à {$prop->nom_complet} (N° {$mutation->numero_notification}).");
    }
}
