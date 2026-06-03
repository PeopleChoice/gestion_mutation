<?php

namespace App\Http\Controllers;

use App\Models\Commune;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function index(Request $request)
    {
        $query = Commune::withCount('projets');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhere('departement', 'like', "%{$s}%")
                  ->orWhere('region', 'like', "%{$s}%");
            });
        }

        $communes = $query->orderBy('nom')->get();

        $stats = [
            'total' => Commune::count(),
            'avec_projets' => Commune::has('projets')->count(),
            'departements' => Commune::whereNotNull('departement')->distinct('departement')->count('departement'),
            'regions' => Commune::whereNotNull('region')->distinct('region')->count('region'),
        ];

        return view('communes.index', compact('communes', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:communes,nom',
            'departement' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
        ]);

        Commune::create($validated);

        return redirect()->route('communes.index')->with('success', 'Commune ajoutée.');
    }

    public function update(Request $request, Commune $commune)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:communes,nom,' . $commune->id,
            'departement' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
        ]);

        $commune->update($validated);

        return redirect()->route('communes.index')->with('success', 'Commune mise à jour.');
    }

    public function destroy(Commune $commune)
    {
        if ($commune->projets()->exists()) {
            return back()->with('error', 'Impossible de supprimer une commune avec des projets.');
        }
        $commune->delete();
        return redirect()->route('communes.index')->with('success', 'Commune supprimée.');
    }
}
