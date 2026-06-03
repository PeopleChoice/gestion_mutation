<?php

namespace App\Http\Controllers;

use App\Models\Commune;
use App\Models\Import;
use App\Models\Mutation;
use App\Models\MutationAnnulation;
use App\Models\Parcelle;
use App\Models\Projet;
use App\Models\Proprietaire;

class DashboardController extends Controller
{
    public function index()
    {
        $totalParcelles = Parcelle::count();
        $parcellesAttribuees = Parcelle::whereNotNull('proprietaire_id')->count();
        $parcelleSansAttributaire = $totalParcelles - $parcellesAttribuees;

        $mutationsValidees = Mutation::where('statut', 'validee')->count();
        $mutationsRefusees = Mutation::where('statut', 'refusee')->count();
        $mutationsEnAttente = Mutation::where('statut', 'en_attente')->count();
        $mutationsAnnulees = Mutation::where('statut', 'annulee')->count();
        $totalMutations = $mutationsValidees + $mutationsRefusees + $mutationsEnAttente + $mutationsAnnulees;

        $annulationsEnAttente = MutationAnnulation::where('statut', 'en_attente')->count();

        // Mutations par projet (top 5)
        $mutationsParProjet = Projet::withCount(['parcelles', 'parcelles as parcelles_attribuees_count' => function ($q) {
            $q->whereNotNull('proprietaire_id');
        }])->with('commune')->withCount('imports')->orderByDesc('parcelles_count')->take(5)->get();

        // Derniers imports et mutations
        $derniersImports = Import::with('projet', 'importeur')->latest()->take(5)->get();
        $dernieresMutations = Mutation::with('parcelle.projet', 'nouveauProprietaire', 'validateur')
            ->latest()->take(8)->get();

        return view('dashboard', compact(
            'totalParcelles', 'parcellesAttribuees', 'parcelleSansAttributaire',
            'mutationsValidees', 'mutationsRefusees', 'mutationsEnAttente',
            'mutationsAnnulees', 'totalMutations', 'annulationsEnAttente',
            'mutationsParProjet', 'derniersImports', 'dernieresMutations',
        ));
    }
}
