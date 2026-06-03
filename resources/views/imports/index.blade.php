@extends('layouts.app')
@section('title', 'Imports Excel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <a href="{{ route('imports.index', ['filtre' => 'en_cours']) }}"
           class="btn btn-sm {{ $filtre === 'en_cours' ? '' : 'btn-outline-secondary' }}"
           style="{{ $filtre === 'en_cours' ? 'background:#C8A951; color:#fff;' : '' }}">
            <i class="bi bi-hourglass-split"></i> A traiter
        </a>
        <a href="{{ route('imports.index', ['filtre' => 'tous']) }}"
           class="btn btn-sm {{ $filtre === 'tous' ? '' : 'btn-outline-secondary' }}"
           style="{{ $filtre === 'tous' ? 'background:#5D4E37; color:#fff;' : '' }}">
            <i class="bi bi-list"></i> Historique
        </a>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('imports.global') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-collection"></i> Import global multi-projets
        </a>
        <a href="{{ route('imports.create') }}" class="btn btn-sm" style="background:#C8A951; color:#fff;">
            <i class="bi bi-file-earmark-excel"></i> Nouvel import
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fichier</th>
                    <th>Projet</th>
                    <th>Importé par</th>
                    <th>Correspondances</th>
                    <th>En attente</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($imports as $import)
                <tr>
                    <td>{{ $import->id }}</td>
                    <td>
                        <a href="{{ route('imports.show', $import) }}" class="text-decoration-none" style="color:#5D4E37;">
                            {{ Str::limit($import->nom_fichier, 35) }}
                        </a>
                    </td>
                    <td>{{ $import->projet->nom }}</td>
                    <td><small>{{ $import->importeur->name }}</small></td>
                    <td>
                        <span style="color:#C8A951; font-weight:700;">{{ $import->lignes_matchees }}</span>
                        <span class="text-muted">/ {{ $import->total_lignes }}</span>
                    </td>
                    <td>
                        @if($import->lignes_en_attente > 0)
                            <span class="badge bg-warning text-dark">{{ $import->lignes_en_attente }} en attente</span>
                        @else
                            <span class="badge" style="background:#C8A951;">Tout traité</span>
                        @endif
                    </td>
                    <td><small class="text-muted">{{ $import->created_at->format('d/m/Y H:i') }}</small></td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($import->lignes_en_attente > 0)
                                <a href="{{ route('imports.show', $import) }}" class="btn btn-sm" style="background:#C8A951; color:#fff;" title="Traiter">
                                    <i class="bi bi-check2-all"></i> Traiter
                                </a>
                            @else
                                <a href="{{ route('imports.show', $import) }}" class="btn btn-sm btn-outline-secondary" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-import"
                                data-import-id="{{ $import->id }}"
                                data-fichier="{{ $import->nom_fichier }}"
                                data-validees="{{ $import->lignes_validees_count }}"
                                data-attente="{{ $import->lignes_en_attente }}"
                                title="Supprimer cet import">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        @if($filtre === 'en_cours')
                            <i class="bi bi-check-circle" style="font-size:24px; color:#C8A951;"></i>
                            <p class="mt-2 mb-0">Tous les imports sont traités</p>
                            <a href="{{ route('imports.index', ['filtre' => 'tous']) }}" class="text-muted small">Voir l'historique</a>
                        @else
                            Aucun import trouvé
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $imports->links() }}

{{-- Modal confirmation suppression --}}
<div class="modal fade" id="modalSupprimerImport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formSupprimerImport" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Supprimer l'import</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-info-circle"></i>
                        Vous êtes sur le point de supprimer l'import <strong id="suppFichier"></strong>.
                    </div>

                    <div class="mb-2">
                        <strong class="text-success"><i class="bi bi-check-circle"></i> Conservées :</strong>
                        <span id="suppValidees" class="badge bg-success">0</span>
                        <small class="text-muted d-block ms-3">
                            Les mutations/parcelles déjà validées restent intactes (elles sont indépendantes de l'import).
                        </small>
                    </div>
                    <div>
                        <strong class="text-danger"><i class="bi bi-x-circle"></i> Supprimées :</strong>
                        <span id="suppAttente" class="badge bg-warning text-dark">0</span>
                        <small class="text-muted d-block ms-3">
                            Les lignes en attente et refusées seront perdues. Vous pourrez réimporter le fichier si besoin.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Confirmer la suppression</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalSupprimerImport'));
    const form = document.getElementById('formSupprimerImport');
    document.querySelectorAll('.btn-supprimer-import').forEach(btn => {
        btn.addEventListener('click', function() {
            form.action = `/imports/${this.dataset.importId}`;
            document.getElementById('suppFichier').textContent = this.dataset.fichier;
            document.getElementById('suppValidees').textContent = this.dataset.validees + ' ligne(s) validée(s)';
            document.getElementById('suppAttente').textContent = this.dataset.attente + ' ligne(s) en attente';
            modal.show();
        });
    });
});
</script>
@endpush
@endsection
