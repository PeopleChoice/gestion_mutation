<?php

namespace App\Http\Controllers;

use App\Exports\AttributionTemplateExport;
use App\Exports\ProjetParcellesExport;
use App\Imports\ParcelleInitialeImport;
use App\Models\Commune;
use App\Models\Projet;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProjetController extends Controller
{
    public function index(Request $request)
    {
        $query = Projet::with('commune')->withCount('parcelles');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhere('type_lotissement', 'like', "%{$s}%")
                  ->orWhereHas('commune', fn($c) => $c->where('nom', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->commune_id);
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        $projets = $query->latest()->paginate($perPage)->withQueryString();
        $communes = Commune::orderBy('nom')->get();

        $stats = [
            'total' => Projet::count(),
            'total_parcelles' => \App\Models\Parcelle::count(),
            'avec_carte' => Projet::whereNotNull('latitude')->whereNotNull('longitude')->count(),
            'avec_plan' => Projet::whereNotNull('fichier_dxf')->count(),
        ];

        return view('projets.index', compact('projets', 'communes', 'stats'));
    }

    public function create()
    {
        $communes = Commune::orderBy('nom')->get();
        return view('projets.create', compact('communes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'commune_id' => 'required|exists:communes,id',
            'type_lotissement' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'fichier_initial' => 'nullable|file|mimes:xls,xlsx|max:10240',
        ]);

        $projet = Projet::create($request->only('nom', 'commune_id', 'type_lotissement', 'description', 'latitude', 'longitude'));

        // Import initial des parcelles (optionnel)
        if ($request->hasFile('fichier_initial')) {
            $import = new ParcelleInitialeImport($projet);

            try {
                Excel::import($import, $request->file('fichier_initial'));

                $msg = "Projet créé. {$import->getCreated()} parcelle(s) importée(s).";
                if ($import->getSkipped() > 0) {
                    $msg .= " {$import->getSkipped()} doublon(s) ignoré(s).";
                }
                if (count($import->getErrors()) > 0) {
                    $msg .= " Erreurs : " . implode(', ', $import->getErrors());
                }

                return redirect()->route('projets.index')->with('success', $msg);
            } catch (\Exception $e) {
                return redirect()->route('projets.index')
                    ->with('success', 'Projet créé.')
                    ->with('error', "Erreur lors de l'import initial : {$e->getMessage()}");
            }
        }

        return redirect()->route('projets.index')->with('success', 'Projet créé avec succès.');
    }

    public function show(Projet $projet)
    {
        $projet->load('commune');

        $parcelles = $projet->parcelles()
            ->with('proprietaire')
            ->orderByRaw("CAST(numero_lot AS INTEGER) ASC, numero_lot ASC")
            ->get();

        $stats = [
            'total' => $parcelles->count(),
            'attribuees' => $parcelles->whereNotNull('proprietaire_id')->count(),
            'non_attribuees' => $parcelles->whereNull('proprietaire_id')->count(),
            'mutations' => \App\Models\Mutation::whereHas('parcelle', fn($q) => $q->where('projet_id', $projet->id))->count(),
        ];

        $alertes = self::alertesProjet($projet);

        return view('projets.show', compact('projet', 'parcelles', 'stats', 'alertes'));
    }

    public function edit(Projet $projet)
    {
        $communes = Commune::orderBy('nom')->get();
        return view('projets.edit', compact('projet', 'communes'));
    }

    public function update(Request $request, Projet $projet)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'commune_id' => 'required|exists:communes,id',
            'type_lotissement' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $projet->update($request->only('nom', 'commune_id', 'type_lotissement', 'description', 'latitude', 'longitude'));

        return redirect()->route('projets.index')->with('success', 'Projet mis à jour.');
    }

    /**
     * Import de parcelles supplémentaires sur un projet existant.
     */
    public function importerParcelles(Request $request, Projet $projet)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:xls,xlsx|max:10240',
        ]);

        $import = new ParcelleInitialeImport($projet);

        try {
            Excel::import($import, $request->file('fichier'));

            $msg = "{$import->getCreated()} parcelle(s) créée(s).";
            if ($import->getSkipped() > 0) {
                $msg .= " {$import->getSkipped()} lot(s) ignoré(s) (déjà existants dans ce projet — règle d'unicité).";
            }

            return redirect()->route('projets.show', $projet)->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', "Erreur : {$e->getMessage()}");
        }
    }

    /**
     * Page dédiée d'initialisation des parcelles (similaire à /imports/create).
     */
    public function initParcellesForm(Projet $projet)
    {
        $projet->load('commune');
        return view('projets.init-parcelles', compact('projet'));
    }

    /**
     * Alertes/notifications pour un projet (utilisé dans la vue show).
     */
    public static function alertesProjet(Projet $projet): array
    {
        $alertes = [];

        // 1. Imports avec lignes en attente de validation
        $imports = \App\Models\Import::where('projet_id', $projet->id)
            ->where('statut', 'termine')
            ->withCount(['lignes as lignes_attente' => fn($q) => $q->where('statut', 'en_attente')->where('matched', true)])
            ->get()
            ->filter(fn($i) => $i->lignes_attente > 0);

        if ($imports->isNotEmpty()) {
            $totalAttente = $imports->sum('lignes_attente');
            $alertes[] = [
                'type'    => 'warning',
                'icon'    => 'bi-hourglass-split',
                'titre'   => "{$totalAttente} mutation(s) en attente de validation",
                'message' => "Réparties sur " . $imports->count() . " import(s).",
                'cta'     => ['label' => 'Voir les imports', 'url' => route('imports.index')],
            ];
        }

        // 2. Parcelles vierges (à attribuer)
        $vierges = $projet->parcelles()->whereNull('proprietaire_id')->count();
        if ($vierges > 0) {
            $alertes[] = [
                'type'    => 'info',
                'icon'    => 'bi-person-plus',
                'titre'   => "{$vierges} parcelle(s) sans propriétaire",
                'message' => 'Utilisez le bouton 🟢 « Attribuer » pour les attribuer manuellement.',
                'cta'     => null,
            ];
        }

        // 3. Annulations en attente d'approbation (admin)
        if (auth()->user()?->hasRole('admin')) {
            $annulations = \App\Models\MutationAnnulation::whereHas('mutation', fn($q) => $q->whereHas('parcelle', fn($p) => $p->where('projet_id', $projet->id)))
                ->where('statut', 'en_attente')
                ->count();
            if ($annulations > 0) {
                $alertes[] = [
                    'type'    => 'danger',
                    'icon'    => 'bi-exclamation-octagon',
                    'titre'   => "{$annulations} demande(s) d'annulation en attente",
                    'message' => 'Une action administrateur est requise.',
                    'cta'     => ['label' => 'Traiter', 'url' => route('annulations.index')],
                ];
            }
        }

        // 4. Aucun lot encore créé
        if ($projet->parcelles()->count() === 0) {
            $alertes[] = [
                'type'    => 'primary',
                'icon'    => 'bi-info-circle',
                'titre'   => 'Aucune parcelle dans ce projet',
                'message' => 'Importez le fichier d\'attribution initiale pour créer les lots.',
                'cta'     => ['label' => 'Initialiser parcelles', 'url' => route('projets.init-parcelles', $projet)],
            ];
        }

        return $alertes;
    }

    /**
     * Pré-vérification AJAX d'un fichier d'initialisation de parcelles.
     */
    public function verifierInitParcelles(Request $request, Projet $projet)
    {
        $request->validate(['fichier' => 'required|file|mimes:xls,xlsx|max:10240']);

        $erreurs = [];
        $avertissements = [];
        $infos = [];

        try {
            $rawRows = Excel::toCollection(null, $request->file('fichier'))->first();
        } catch (\Throwable $e) {
            return response()->json([
                'erreurs' => ["Impossible de lire le fichier : {$e->getMessage()}"],
                'avertissements' => [], 'infos' => [], 'valid' => false,
            ]);
        }

        if (!$rawRows || $rawRows->count() < 2) {
            return response()->json([
                'erreurs' => ["Fichier vide ou ne contient qu'un en-tête."],
                'avertissements' => [], 'infos' => [], 'valid' => false,
            ]);
        }

        // Détection auto de la ligne d'en-têtes (la plus remplie sur les 3 premières)
        $headerRowIdx = 0;
        $maxCols = 0;
        for ($r = 0; $r < min(3, $rawRows->count()); $r++) {
            $cells = $rawRows->get($r)->values()->filter(fn($v) => trim((string) $v) !== '')->count();
            if ($cells > $maxCols) { $maxCols = $cells; $headerRowIdx = $r; }
        }
        $headers = $rawRows->get($headerRowIdx)->values()->map(fn($v) => strtolower(trim((string) $v)))->toArray();

        $lotIdx = null;
        $nomIdx = null;
        $prenomIdx = null;
        foreach ($headers as $i => $h) {
            if ($h === 'lot') $lotIdx = $i;
            if ($h === 'nom') $nomIdx = $i;
            if (in_array($h, ['prénom', 'prenom'])) $prenomIdx = $i;
        }

        if ($lotIdx === null) {
            return response()->json([
                'erreurs' => ["Colonne 'LOT' introuvable. Colonnes trouvées : " . implode(', ', array_filter($headers))],
                'avertissements' => [], 'infos' => [], 'valid' => false,
            ]);
        }

        $infos[] = "Projet : {$projet->nom} - Commune de {$projet->commune->nom}";
        $infos[] = "Colonnes détectées : " . implode(', ', array_filter($headers));

        $totalLignes = 0;
        $lignesAvecProprio = 0;
        $lignesSansProprio = 0;
        $lotsExistants = [];
        $lotsDupliques = [];
        $vus = [];

        foreach ($rawRows->slice($headerRowIdx + 1) as $row) {
            $vals = $row->values()->toArray();
            $lot = trim((string) ($vals[$lotIdx] ?? ''));
            if (preg_match('/^\d+\.0$/', $lot)) $lot = (string) intval($lot);
            if (!$lot) continue;

            $totalLignes++;
            if (in_array($lot, $vus)) $lotsDupliques[] = $lot;
            $vus[] = $lot;

            // Lot déjà existant dans le projet ?
            if (\App\Models\Parcelle::where('numero_lot', $lot)->where('projet_id', $projet->id)->exists()) {
                $lotsExistants[] = $lot;
            }

            $nom = $nomIdx !== null ? trim((string) ($vals[$nomIdx] ?? '')) : '';
            $prenom = $prenomIdx !== null ? trim((string) ($vals[$prenomIdx] ?? '')) : '';
            if ($nom || $prenom) $lignesAvecProprio++; else $lignesSansProprio++;
        }

        $infos[] = "Fichier : {$request->file('fichier')->getClientOriginalName()}";
        $infos[] = "Total lots à créer : {$totalLignes}";
        $infos[] = "Avec attribution directe : {$lignesAvecProprio}";
        $infos[] = "Sans attributaire (vierges, à attribuer ensuite) : {$lignesSansProprio}";

        if (count($lotsExistants) > 0) {
            $avertissements[] = count($lotsExistants) . " lot(s) déjà existant(s) dans le projet — règle d'unicité (ignorés à l'import) : " . implode(', ', array_slice($lotsExistants, 0, 10)) . (count($lotsExistants) > 10 ? '...' : '');
        }
        if (count($lotsDupliques) > 0) {
            $erreurs[] = "Doublons dans le fichier (un même lot apparaît plusieurs fois) : " . implode(', ', array_unique($lotsDupliques));
        }
        if ($totalLignes === 0) {
            $erreurs[] = "Aucune ligne exploitable.";
        }

        return response()->json([
            'erreurs' => $erreurs,
            'avertissements' => $avertissements,
            'infos' => $infos,
            'valid' => empty($erreurs) && $totalLignes > 0,
            'stats' => [
                'total' => $totalLignes,
                'avec_proprio' => $lignesAvecProprio,
                'sans_proprio' => $lignesSansProprio,
                'existants' => count($lotsExistants),
            ],
        ]);
    }

    /**
     * Téléchargement d'un template Excel d'attribution pour ce projet.
     */
    public function telechargerTemplateAttribution(Projet $projet)
    {
        $filename = "attribution_{$projet->nom}_" . now()->format('d-m-Y') . ".xlsx";
        return Excel::download(new AttributionTemplateExport($projet), $filename);
    }

    /**
     * Exporter les parcelles du projet en Excel.
     */
    public function exporter(Projet $projet)
    {
        $projet->load('commune');
        $filename = "Parcelles_{$projet->nom}_{$projet->commune->nom}_" . now()->format('d-m-Y') . ".xlsx";

        return Excel::download(new ProjetParcellesExport($projet), $filename);
    }

    /**
     * Page carte de tous les projets.
     */
    public function carte()
    {
        $projets = Projet::with('commune')
            ->withCount('parcelles')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return view('projets.carte', compact('projets'));
    }
}
