<?php

namespace App\Http\Controllers;

use App\Models\Mutation;
use App\Models\Parcelle;
use App\Models\Proprietaire;
use App\Models\Projet;
use App\Models\Commune;
use Illuminate\Http\Request;

class RechercheController extends Controller
{
    /**
     * Page de recherche complète avec résultats détaillés.
     */
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));
        $type = $request->input('type', 'tout'); // tout, mutation, parcelle, proprietaire, projet
        $statut = $request->input('statut');
        $projet_id = $request->input('projet_id');
        $commune_id = $request->input('commune_id');
        $date_debut = $request->input('date_debut');
        $date_fin = $request->input('date_fin');

        $resultats = [
            'mutations' => collect(),
            'parcelles' => collect(),
            'proprietaires' => collect(),
            'projets' => collect(),
        ];

        $projets = Projet::with('commune')->orderBy('nom')->get();
        $communes = Commune::orderBy('nom')->get();

        if (strlen($q) < 1 && !$statut && !$projet_id && !$commune_id && !$date_debut) {
            return view('recherche.index', compact('resultats', 'q', 'type', 'projets', 'communes'));
        }

        $like = "%{$q}%";

        // --- Recherche Mutations ---
        if (in_array($type, ['tout', 'mutation'])) {
            $mutQuery = Mutation::with([
                'parcelle.projet.commune',
                'ancienProprietaire',
                'nouveauProprietaire',
                'validateur',
                'annulation',
                'import',
            ]);

            if ($q) {
                $mutQuery->where(function ($query) use ($like, $q) {
                    $query->where('numero_notification', 'like', $like)
                        ->orWhere('ref_lettre', 'like', $like)
                        ->orWhere('observation', 'like', $like)
                        ->orWhere('motif_refus', 'like', $like)
                        // Recherche par numéro de lot
                        ->orWhereHas('parcelle', function ($pq) use ($like) {
                            $pq->where('numero_lot', 'like', $like);
                        })
                        // Recherche par nom du projet
                        ->orWhereHas('parcelle.projet', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like);
                        })
                        // Recherche par commune
                        ->orWhereHas('parcelle.projet.commune', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like);
                        })
                        // Recherche par nom/prénom du nouveau propriétaire
                        ->orWhereHas('nouveauProprietaire', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like)
                               ->orWhere('prenom', 'like', $like)
                               ->orWhere('cni_passport', 'like', $like)
                               ->orWhere('nin', 'like', $like)
                               ->orWhere('ninea', 'like', $like)
                               ->orWhere('telephone', 'like', $like);
                        })
                        // Recherche par ancien propriétaire
                        ->orWhereHas('ancienProprietaire', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like)
                               ->orWhere('prenom', 'like', $like)
                               ->orWhere('cni_passport', 'like', $like)
                               ->orWhere('nin', 'like', $like);
                        });
                });
            }

            if ($statut) {
                $mutQuery->where('statut', $statut);
            }
            if ($projet_id) {
                $mutQuery->whereHas('parcelle', fn ($pq) => $pq->where('projet_id', $projet_id));
            }
            if ($commune_id) {
                $mutQuery->whereHas('parcelle.projet', fn ($pq) => $pq->where('commune_id', $commune_id));
            }
            if ($date_debut) {
                $mutQuery->whereDate('date_mutation', '>=', $date_debut);
            }
            if ($date_fin) {
                $mutQuery->whereDate('date_mutation', '<=', $date_fin);
            }

            $resultats['mutations'] = $mutQuery->latest('date_mutation')->limit(50)->get();
        }

        // --- Recherche Parcelles ---
        if (in_array($type, ['tout', 'parcelle'])) {
            $parQuery = Parcelle::with('projet.commune', 'proprietaire', 'mutations');

            if ($q) {
                $parQuery->where(function ($query) use ($like) {
                    $query->where('numero_lot', 'like', $like)
                        ->orWhere('ref_lettre', 'like', $like)
                        ->orWhere('observation', 'like', $like)
                        ->orWhereHas('projet', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like);
                        })
                        ->orWhereHas('projet.commune', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like);
                        })
                        ->orWhereHas('proprietaire', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like)
                               ->orWhere('prenom', 'like', $like)
                               ->orWhere('cni_passport', 'like', $like)
                               ->orWhere('nin', 'like', $like)
                               ->orWhere('telephone', 'like', $like);
                        });
                });
            }

            if ($projet_id) {
                $parQuery->where('projet_id', $projet_id);
            }
            if ($commune_id) {
                $parQuery->whereHas('projet', fn ($pq) => $pq->where('commune_id', $commune_id));
            }

            $resultats['parcelles'] = $parQuery->orderBy('numero_lot')->limit(50)->get();
        }

        // --- Recherche Propriétaires ---
        if (in_array($type, ['tout', 'proprietaire'])) {
            $propQuery = Proprietaire::with('parcelles.projet.commune');

            if ($q) {
                $propQuery->where(function ($query) use ($like) {
                    $query->where('nom', 'like', $like)
                        ->orWhere('prenom', 'like', $like)
                        ->orWhere('cni_passport', 'like', $like)
                        ->orWhere('nin', 'like', $like)
                        ->orWhere('ninea', 'like', $like)
                        ->orWhere('telephone', 'like', $like)
                        ->orWhere('adresse', 'like', $like);
                });
            }

            $resultats['proprietaires'] = $propQuery->orderBy('nom')->limit(50)->get();
        }

        // --- Recherche Projets ---
        if (in_array($type, ['tout', 'projet'])) {
            $projQuery = Projet::with('commune')->withCount('parcelles');

            if ($q) {
                $projQuery->where(function ($query) use ($like) {
                    $query->where('nom', 'like', $like)
                        ->orWhere('type_lotissement', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('commune', function ($pq) use ($like) {
                            $pq->where('nom', 'like', $like);
                        });
                });
            }

            if ($commune_id) {
                $projQuery->where('commune_id', $commune_id);
            }

            $resultats['projets'] = $projQuery->orderBy('nom')->limit(50)->get();
        }

        $totalResultats = $resultats['mutations']->count()
            + $resultats['parcelles']->count()
            + $resultats['proprietaires']->count()
            + $resultats['projets']->count();

        return view('recherche.index', compact('resultats', 'q', 'type', 'projets', 'communes', 'totalResultats'));
    }

    /**
     * API de recherche rapide (autocomplétion) - retourne JSON.
     */
    public function rapide(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['resultats' => []]);
        }

        $like = "%{$q}%";
        $resultats = [];

        // Mutations par numéro de notification
        Mutation::with('parcelle.projet', 'nouveauProprietaire')
            ->where('numero_notification', 'like', $like)
            ->limit(5)
            ->get()
            ->each(function ($m) use (&$resultats) {
                $resultats[] = [
                    'type' => 'mutation',
                    'icon' => 'bi-arrow-left-right',
                    'titre' => "Mutation N°{$m->numero_notification}",
                    'sous_titre' => "Lot {$m->parcelle->numero_lot} - {$m->parcelle->projet->nom}",
                    'badge' => $m->statut,
                    'url' => route('mutations.show', $m->id),
                ];
            });

        // Parcelles par numéro de lot
        Parcelle::with('projet.commune', 'proprietaire')
            ->where('numero_lot', 'like', $like)
            ->limit(5)
            ->get()
            ->each(function ($p) use (&$resultats) {
                $resultats[] = [
                    'type' => 'parcelle',
                    'icon' => 'bi-geo-alt-fill',
                    'titre' => "Lot {$p->numero_lot}",
                    'sous_titre' => "{$p->projet->nom} - {$p->projet->commune->nom}" .
                        ($p->proprietaire ? " | {$p->proprietaire->nom_complet}" : ''),
                    'badge' => null,
                    'url' => route('parcelles.show', $p->id),
                ];
            });

        // Propriétaires par nom, prénom, CNI, NIN, téléphone
        Proprietaire::where('nom', 'like', $like)
            ->orWhere('prenom', 'like', $like)
            ->orWhere('cni_passport', 'like', $like)
            ->orWhere('nin', 'like', $like)
            ->orWhere('telephone', 'like', $like)
            ->limit(5)
            ->get()
            ->each(function ($p) use (&$resultats) {
                $resultats[] = [
                    'type' => 'proprietaire',
                    'icon' => 'bi-person-fill',
                    'titre' => $p->nom_complet,
                    'sous_titre' => "CNI: " . ($p->cni_passport ?? 'N/A') . " | Tel: " . ($p->telephone ?? 'N/A'),
                    'badge' => null,
                    'url' => route('recherche', ['q' => $p->nom, 'type' => 'proprietaire']),
                ];
            });

        // Projets par nom
        Projet::with('commune')
            ->where('nom', 'like', $like)
            ->orWhereHas('commune', fn ($cq) => $cq->where('nom', 'like', $like))
            ->limit(3)
            ->get()
            ->each(function ($p) use (&$resultats) {
                $resultats[] = [
                    'type' => 'projet',
                    'icon' => 'bi-building',
                    'titre' => $p->nom,
                    'sous_titre' => "Commune de {$p->commune->nom}",
                    'badge' => null,
                    'url' => route('recherche', ['q' => $p->nom, 'type' => 'tout']),
                ];
            });

        return response()->json(['resultats' => $resultats]);
    }
}
