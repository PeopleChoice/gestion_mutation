<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommuneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\ParcelleController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\ExportImportController;
use App\Http\Controllers\RechercheController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/', fn () => redirect('/login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Vérification publique (pas besoin d'être connecté)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/verification/{hash}', [VerificationController::class, 'verifier'])->name('verification');
    Route::post('/verification/manuel', [VerificationController::class, 'verifierManuel'])->name('verification.manuel');
});

// Routes protégées
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Recherche globale
    Route::get('/recherche', [RechercheController::class, 'index'])->name('recherche');
    Route::get('/recherche/rapide', [RechercheController::class, 'rapide'])->name('recherche.rapide');

    // Scanner QR code
    Route::get('/scanner', [VerificationController::class, 'scanner'])->name('scanner');

    // Communes — lecture libre ; modif/suppression admin
    Route::get('/communes', [CommuneController::class, 'index'])->name('communes.index');
    Route::middleware('role:admin')->group(function () {
        Route::post('/communes', [CommuneController::class, 'store'])->name('communes.store');
        Route::put('/communes/{commune}', [CommuneController::class, 'update'])->name('communes.update');
        Route::patch('/communes/{commune}', [CommuneController::class, 'update']);
        Route::delete('/communes/{commune}', [CommuneController::class, 'destroy'])->name('communes.destroy');
    });

    // Projets
    Route::resource('projets', ProjetController::class)->except(['destroy']);
    Route::get('/projets/{projet}/init-parcelles', [ProjetController::class, 'initParcellesForm'])->name('projets.init-parcelles');
    Route::post('/projets/{projet}/init-parcelles/verifier', [ProjetController::class, 'verifierInitParcelles'])->name('projets.init-parcelles.verifier');
    Route::get('/projets/{projet}/init-parcelles/template', [ProjetController::class, 'telechargerTemplateAttribution'])->name('projets.init-parcelles.template');
    Route::post('/projets/{projet}/importer-parcelles', [ProjetController::class, 'importerParcelles'])->name('projets.importer-parcelles');
    Route::get('/projets/{projet}/exporter', [ProjetController::class, 'exporter'])->name('projets.exporter');
    Route::get('/carte', [ProjetController::class, 'carte'])->name('projets.carte');

    // Parcelles — lecture libre ; écritures réservées
    Route::get('/parcelles',                  [ParcelleController::class, 'index'])->name('parcelles.index');
    Route::get('/parcelles/{parcelle}',       [ParcelleController::class, 'show'])->name('parcelles.show');
    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::get('/parcelles/create',             [ParcelleController::class, 'create'])->name('parcelles.create');
        Route::post('/parcelles',                   [ParcelleController::class, 'store'])->name('parcelles.store');
        Route::get('/parcelles/{parcelle}/edit',    [ParcelleController::class, 'edit'])->name('parcelles.edit');
        Route::put('/parcelles/{parcelle}',         [ParcelleController::class, 'update'])->name('parcelles.update');
        Route::patch('/parcelles/{parcelle}',       [ParcelleController::class, 'update']);
        Route::post('/parcelles/{parcelle}/attribuer', [ParcelleController::class, 'attribuer'])->name('parcelles.attribuer');
    });

    // Imports — DELETE réservé aux admins
    Route::resource('imports', ImportController::class)->only(['index', 'create', 'store', 'show']);
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('imports.destroy');
    Route::get('/imports/{import}/pdfs/fusionnes', [ImportController::class, 'pdfsFusionnes'])->name('imports.pdfs-fusionnes');
    Route::post('/imports/verifier', [ImportController::class, 'verifier'])->name('imports.verifier');

    // Imports globaux multi-projets (basé sur le code projet)
    Route::get('/imports-global',  [ImportController::class, 'globalForm'])->name('imports.global');
    Route::post('/imports-global/attribution', [ImportController::class, 'globalAttribution'])->name('imports.global.attribution');
    Route::post('/imports-global/mutation',    [ImportController::class, 'globalMutation'])->name('imports.global.mutation');
    Route::get('/imports-global/template/attribution', [ImportController::class, 'globalAttributionTemplate'])->name('imports.global.template.attribution');
    Route::get('/imports-global/template/mutation',    [ImportController::class, 'globalMutationTemplate'])->name('imports.global.template.mutation');
    Route::get('/imports-global/validation', [ImportController::class, 'globalValidation'])->name('imports.global.validation');
    Route::post('/attributions/valider/{ligne}', [ImportController::class, 'validerAttribution'])->name('attributions.valider');
    Route::post('/attributions/refuser/{ligne}', [ImportController::class, 'refuserAttribution'])->name('attributions.refuser');
    Route::get('/imports/template/{projet}', [ImportController::class, 'telechargerTemplate'])->name('imports.template');
    // Envoi mail réservé aux admins + throttle anti-spam
    Route::post('/imports/envoyer-template/{projet}', [ImportController::class, 'envoyerTemplate'])
        ->middleware(['role:admin|gestionnaire', 'throttle:10,60'])
        ->name('imports.envoyer-template');

    // Mutations
    Route::get('/mutations', [MutationController::class, 'index'])->name('mutations.index');
    // Creation manuelle (avant la route /mutations/{mutation} pour eviter la collision)
    Route::get('/mutations/create', [MutationController::class, 'create'])->name('mutations.create');
    Route::post('/mutations', [MutationController::class, 'store'])->name('mutations.store');
    Route::get('/mutations/api/parcelles', [MutationController::class, 'rechercheParcelles'])->name('mutations.api.parcelles');
    Route::get('/mutations/api/proprietaires', [MutationController::class, 'rechercheProprietaires'])->name('mutations.api.proprietaires');

    Route::get('/mutations/traiter/{import}', [MutationController::class, 'traiterImport'])->name('mutations.traiter');
    Route::get('/mutations/{mutation}', [MutationController::class, 'show'])->name('mutations.show');
    Route::post('/mutations/{mutation}/valider-directe', [MutationController::class, 'validerMutation'])->name('mutations.valider-directe');
    Route::post('/mutations/{mutation}/refuser-directe', [MutationController::class, 'refuserMutation'])->name('mutations.refuser-directe');
    // Validation/refus de mutations (admin + gestionnaire)
    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::post('/mutations/valider/{ligne}', [MutationController::class, 'valider'])->name('mutations.valider');
        Route::post('/mutations/refuser/{ligne}', [MutationController::class, 'refuser'])->name('mutations.refuser');
        Route::post('/mutations/valider-tout/{import}', [MutationController::class, 'validerTout'])->name('mutations.valider-tout');
        Route::post('/mutations/refuser-tout/{import}', [MutationController::class, 'refuserTout'])->name('mutations.refuser-tout');
        Route::post('/mutations/valider-selection/{import}', [MutationController::class, 'validerSelection'])->name('mutations.valider-selection');
        Route::post('/mutations/refuser-selection/{import}', [MutationController::class, 'refuserSelection'])->name('mutations.refuser-selection');
    });
    Route::get('/mutations/{mutation}/pdf', [MutationController::class, 'genererPdf'])->name('mutations.pdf');
    Route::get('/mutations/{mutation}/telecharger', [MutationController::class, 'telechargerPdf'])->name('mutations.telecharger');
    Route::get('/mutations/{mutation}/apercu', [MutationController::class, 'apercu'])->name('mutations.apercu');

    // Annulations (demande par tout utilisateur auth)
    Route::post('/mutations/{mutation}/annulation', [MutationController::class, 'demanderAnnulation'])->name('mutations.demander-annulation');

    // Rapports
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::post('/rapports/generer', [RapportController::class, 'generer'])->name('rapports.generer');

    // Templates de documents — admin uniquement (impact tous les PDFs)
    Route::middleware('role:admin')->group(function () {
        Route::resource('templates', DocumentTemplateController::class)->except(['show']);
        Route::get('/templates/{template}/preview',   [DocumentTemplateController::class, 'preview'])->name('templates.preview');
        Route::post('/templates/{template}/toggle',   [DocumentTemplateController::class, 'toggleActif'])->name('templates.toggle');
        Route::post('/templates/{template}/dupliquer',[DocumentTemplateController::class, 'dupliquer'])->name('templates.dupliquer');
    });

    // Export / Import base de données (admin uniquement)
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/export-import', [ExportImportController::class, 'index'])->name('admin.export-import');
        Route::post('/admin/export-csv', [ExportImportController::class, 'exportCsv'])->name('admin.export-csv');
        Route::post('/admin/export-sql', [ExportImportController::class, 'exportSql'])->name('admin.export-sql');
        Route::post('/admin/export-tout', [ExportImportController::class, 'exportTout'])->name('admin.export-tout');
        Route::get('/admin/export-telecharger/{filename}', [ExportImportController::class, 'telecharger'])->name('admin.export-telecharger');
        Route::delete('/admin/export-supprimer/{filename}', [ExportImportController::class, 'supprimer'])->name('admin.export-supprimer');
        Route::post('/admin/import-maintenant', [ExportImportController::class, 'importerMaintenant'])->name('admin.import-maintenant');
        Route::post('/admin/import-upload', [ExportImportController::class, 'uploadImport'])->name('admin.import-upload');
    });

    // Annulations (admin + gestionnaire)
    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::get('/annulations', [MutationController::class, 'annulationsEnAttente'])->name('annulations.index');
        Route::post('/annulations/{annulation}/traiter', [MutationController::class, 'traiterAnnulation'])->name('annulations.traiter');
    });
});
