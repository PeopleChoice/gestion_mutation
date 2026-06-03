@extends('layouts.app')
@section('title', 'Export / Import Base de données')

@section('content')
<div class="row g-4">
    <!-- EXPORT -->
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header" style="background:#5D4E37; color:#fff;">
                <h6 class="mb-0"><i class="bi bi-database-down"></i> Exporter la base de données</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Exporter toutes les données (communes, projets, parcelles, propriétaires, mutations, etc.) dans le dossier <code>/export</code>.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <form action="{{ route('admin.export-csv') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background:#C8A951; color:#fff;">
                            <i class="bi bi-file-earmark-zip"></i> Export CSV (zip)
                        </button>
                    </form>
                    <form action="{{ route('admin.export-sql') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-filetype-sql"></i> Export SQL
                        </button>
                    </form>
                    <form action="{{ route('admin.export-tout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background:#5D4E37; color:#fff;">
                            <i class="bi bi-database"></i> Export complet (CSV + SQL)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fichiers exportés -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-folder2-open"></i> Fichiers exportés <span class="badge" style="background:#5D4E37;">{{ $exports->count() }}</span></h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Fichier</th><th>Type</th><th>Taille</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($exports as $file)
                        <tr>
                            <td><i class="bi {{ $file['type'] === 'zip' ? 'bi-file-earmark-zip text-warning' : 'bi-filetype-sql text-primary' }}"></i> {{ $file['nom'] }}</td>
                            <td><span class="badge {{ $file['type'] === 'zip' ? 'bg-warning text-dark' : 'bg-primary' }}">{{ strtoupper($file['type']) }}</span></td>
                            <td>{{ number_format($file['taille'] / 1024, 1) }} Ko</td>
                            <td>{{ date('d/m/Y H:i', $file['date']) }}</td>
                            <td>
                                <a href="{{ route('admin.export-telecharger', $file['nom']) }}" class="btn btn-sm btn-outline-success" title="Télécharger"><i class="bi bi-download"></i></a>
                                <form action="{{ route('admin.export-supprimer', $file['nom']) }}" method="POST" class="d-inline" data-confirm="Supprimer ce fichier ?" data-confirm-type="danger" data-confirm-label="Supprimer">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucun export</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- IMPORT -->
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header" style="background:#C8A951; color:#fff;">
                <h6 class="mb-0"><i class="bi bi-database-up"></i> Importer des données</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Déposez un fichier <strong>.zip</strong> (CSV) ou <strong>.sql</strong> dans le dossier <code>/import</code> ou uploadez-le ici. Le cron vérifie toutes les <strong>5 minutes</strong>.</p>

                <form action="{{ route('admin.import-upload') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="input-group input-group-sm">
                        <input type="file" name="fichier" class="form-control" accept=".zip,.sql" required>
                        <button type="submit" class="btn" style="background:#C8A951; color:#fff;">
                            <i class="bi bi-upload"></i> Déposer
                        </button>
                    </div>
                </form>

                <form action="{{ route('admin.import-maintenant') }}" method="POST" data-confirm="Lancer l'import maintenant ?<br><small class='text-muted'>Les données existantes seront écrasées.</small>" data-confirm-type="danger" data-confirm-label="Lancer">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-play-fill"></i> Importer maintenant
                    </button>
                </form>
            </div>
        </div>

        <!-- Fichiers en attente -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-hourglass-split"></i> En attente d'import <span class="badge bg-warning text-dark">{{ $imports->count() }}</span></h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($imports as $file)
                        <tr>
                            <td><i class="bi {{ $file['type'] === 'zip' ? 'bi-file-earmark-zip' : 'bi-filetype-sql' }}"></i> {{ $file['nom'] }}</td>
                            <td>{{ number_format($file['taille'] / 1024, 1) }} Ko</td>
                        </tr>
                        @empty
                        <tr><td class="text-center text-muted py-2">Aucun fichier en attente</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Historique traités -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-check-circle"></i> Derniers imports traités</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($traites as $file)
                        <tr>
                            <td><small>{{ $file['nom'] }}</small></td>
                            <td><small class="text-muted">{{ date('d/m/Y H:i', $file['date']) }}</small></td>
                        </tr>
                        @empty
                        <tr><td class="text-center text-muted py-2">Aucun import traité</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info cron -->
        <div class="alert alert-info mt-3 small">
            <i class="bi bi-clock"></i> <strong>Cron :</strong> Le système vérifie le dossier <code>/import</code> toutes les 5 minutes.
            <br>Pour activer le cron, ajoutez dans votre crontab :
            <br><code class="d-block mt-1 p-1 bg-light">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
        </div>
    </div>
</div>
@endsection
