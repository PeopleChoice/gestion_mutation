<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ExportImportController extends Controller
{
    public function index()
    {
        // Fichiers dans /export
        $exports = collect(glob(base_path('export/*.{zip,sql}'), GLOB_BRACE))
            ->map(fn($f) => [
                'nom' => basename($f),
                'taille' => filesize($f),
                'date' => filemtime($f),
                'type' => pathinfo($f, PATHINFO_EXTENSION),
                'path' => $f,
            ])
            ->sortByDesc('date')
            ->values();

        // Fichiers dans /import (en attente)
        $imports = collect(glob(base_path('import/*.{zip,sql}'), GLOB_BRACE))
            ->map(fn($f) => [
                'nom' => basename($f),
                'taille' => filesize($f),
                'date' => filemtime($f),
                'type' => pathinfo($f, PATHINFO_EXTENSION),
            ])
            ->sortByDesc('date')
            ->values();

        // Fichiers traités
        $traites = collect(glob(base_path('import/traites/*.{zip,sql}'), GLOB_BRACE))
            ->map(fn($f) => [
                'nom' => basename($f),
                'taille' => filesize($f),
                'date' => filemtime($f),
            ])
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return view('admin.export-import', compact('exports', 'imports', 'traites'));
    }

    /**
     * Lancer l'export CSV zip.
     */
    public function exportCsv()
    {
        Artisan::call('db:export', ['--format' => 'csv']);
        return back()->with('success', 'Export CSV zip généré avec succès.');
    }

    /**
     * Lancer l'export SQL.
     */
    public function exportSql()
    {
        Artisan::call('db:export', ['--format' => 'sql']);
        return back()->with('success', 'Export SQL généré avec succès.');
    }

    /**
     * Lancer l'export complet (CSV + SQL).
     */
    public function exportTout()
    {
        Artisan::call('db:export', ['--format' => 'both']);
        return back()->with('success', 'Export complet (CSV + SQL) généré avec succès.');
    }

    /**
     * Télécharger un fichier d'export.
     */
    public function telecharger(string $filename)
    {
        $filename = basename($filename); // Protection path traversal
        $path = base_path("export/{$filename}");
        if (!file_exists($path) || !str_starts_with(realpath($path), realpath(base_path('export')))) {
            return back()->with('error', 'Fichier introuvable.');
        }
        return response()->download($path);
    }

    /**
     * Supprimer un fichier d'export.
     */
    public function supprimer(string $filename)
    {
        $filename = basename($filename); // Protection path traversal
        $path = base_path("export/{$filename}");
        if (file_exists($path) && str_starts_with(realpath($path), realpath(base_path('export')))) {
            unlink($path);
        }
        return back()->with('success', 'Fichier supprimé.');
    }

    /**
     * Lancer l'import manuellement.
     */
    public function importerMaintenant()
    {
        Artisan::call('db:import');
        $output = Artisan::output();
        return back()->with('success', "Import exécuté.\n" . $output);
    }

    /**
     * Upload d'un fichier à importer.
     */
    public function uploadImport(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:zip,sql|max:51200', // Max 50MB
        ]);

        $file = $request->file('fichier');
        $ext = $file->getClientOriginalExtension();
        $safeName = 'import_' . now()->format('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
        $file->move(base_path('import'), $safeName);

        return back()->with('success', "Fichier déposé ({$safeName}). Il sera traité au prochain cycle ou cliquez 'Importer maintenant'.");
    }
}
