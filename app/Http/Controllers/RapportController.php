<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\Mutation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    public function index()
    {
        $imports = Import::with('projet')->where('statut', 'termine')->latest()->get();
        return view('rapports.index', compact('imports'));
    }

    public function generer(Request $request)
    {
        $request->validate([
            'import_id' => 'nullable|exists:imports,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'statut' => 'nullable|in:validee,refusee,annulee,en_attente',
        ]);

        $query = Mutation::with('parcelle.projet.commune', 'ancienProprietaire', 'nouveauProprietaire', 'validateur');

        if ($request->filled('import_id')) {
            $query->where('import_id', $request->import_id);
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_mutation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_mutation', '<=', $request->date_fin);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $mutations = $query->orderBy('date_mutation')->get();

        $stats = [
            'total' => $mutations->count(),
            'validees' => $mutations->where('statut', 'validee')->count(),
            'refusees' => $mutations->where('statut', 'refusee')->count(),
            'annulees' => $mutations->where('statut', 'annulee')->count(),
        ];

        if ($request->has('format') && $request->format === 'pdf') {
            $pdf = Pdf::loadView('rapports.pdf', compact('mutations', 'stats'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('rapport_mutations_' . now()->format('Y-m-d') . '.pdf');
        }

        return view('rapports.resultat', compact('mutations', 'stats'));
    }
}
