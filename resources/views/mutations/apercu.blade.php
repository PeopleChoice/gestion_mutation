@extends('layouts.app')
@section('title', $mutation->type_libelle . ' - Notification N°' . $mutation->numero_notification)

@push('styles')
<style>
    .apercu-container {
        display: flex;
        gap: 20px;
        height: calc(100vh - 140px);
    }
    .apercu-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .apercu-main {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .apercu-toolbar {
        background: #fff;
        border-radius: 10px 10px 0 0;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e0e0e0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .apercu-iframe-wrapper {
        flex: 1;
        background: #525659;
        border-radius: 0 0 10px 10px;
        padding: 0;
        overflow: hidden;
    }
    .apercu-iframe-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    .info-item {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .info-item:last-child { border-bottom: none; }
    .info-label { color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-weight: 600; color: #333; }
</style>
@endpush

@section('content')
<div class="apercu-container">
    <!-- Sidebar infos -->
    <div class="apercu-sidebar">
        <div class="card mb-3">
            <div class="card-header {{ $mutation->est_attribution ? '' : 'bg-success bg-opacity-10' }}" style="{{ $mutation->est_attribution ? 'background:#dbeafe;' : '' }}">
                <h6 class="mb-0 {{ $mutation->est_attribution ? '' : 'text-success' }}" style="{{ $mutation->est_attribution ? 'color:#1e40af;' : '' }}">
                    <i class="bi bi-file-earmark-text"></i>
                    Notification d'{{ strtolower($mutation->type_libelle) }}
                </h6>
            </div>
            <div class="card-body py-2">
                <div class="info-item">
                    <div class="info-label">N° Notification</div>
                    <div class="info-value">{{ $mutation->numero_notification }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date</div>
                    <div class="info-value">{{ $mutation->date_mutation?->format('d/m/Y') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Lot</div>
                    <div class="info-value">{{ $mutation->parcelle->numero_lot }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Projet</div>
                    <div class="info-value">{{ $mutation->parcelle->projet->nom }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Commune</div>
                    <div class="info-value">{{ $mutation->parcelle->projet->commune->nom }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person-check"></i> Nouveau attributaire</h6>
            </div>
            <div class="card-body py-2">
                @if($mutation->nouveauProprietaire)
                <div class="info-item">
                    <div class="info-label">Prénom</div>
                    <div class="info-value">{{ $mutation->nouveauProprietaire->prenom ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nom</div>
                    <div class="info-value">{{ $mutation->nouveauProprietaire->nom ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value">{{ $mutation->nouveauProprietaire->telephone ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pièce</div>
                    <div class="info-value">{{ $mutation->piece_formatee ?: 'N/A' }}</div>
                </div>
                @endif
            </div>
        </div>

        @if(!$mutation->est_attribution)
        <div class="card mb-3">
            <div class="card-header" style="background:#eff6ff;">
                <h6 class="mb-0" style="color:#3b82f6;"><i class="bi bi-person-lines-fill"></i> Demandeur</h6>
            </div>
            <div class="card-body py-2">
                <div class="info-item">
                    <div class="info-label">Prénom</div>
                    <div class="info-value">{{ $mutation->demandeur_prenom ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nom</div>
                    <div class="info-value">{{ $mutation->demandeur_nom ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value">{{ $mutation->demandeur_telephone ?: '-' }}</div>
                </div>
            </div>
        </div>
        @endif

        <div class="d-grid gap-2">
            <a href="{{ route('mutations.show', $mutation) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour à l'{{ strtolower($mutation->type_libelle) }}
            </a>
        </div>
    </div>

    <!-- Zone principale : toolbar + iframe PDF -->
    <div class="apercu-main">
        <div class="apercu-toolbar">
            <div>
                <span class="fw-bold">Notification d'{{ strtolower($mutation->type_libelle) }}</span>
                <span class="text-muted ms-2">N°{{ $mutation->numero_notification }}</span>
                <span class="badge ms-2" style="background:{{ $mutation->est_attribution ? '#3b82f6' : '#d97706' }}; color:#fff;">
                    {{ strtoupper($mutation->type_libelle) }}
                </span>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="imprimerPdf()">
                    <i class="bi bi-printer"></i> Imprimer
                </button>
                <a href="{{ route('mutations.telecharger', $mutation) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-download"></i> Télécharger PDF
                </a>
                <button class="btn btn-outline-secondary btn-sm" onclick="ouvrirOnglet()">
                    <i class="bi bi-box-arrow-up-right"></i> Nouvel onglet
                </button>
            </div>
        </div>
        <div class="apercu-iframe-wrapper">
            <iframe id="pdfFrame" src="{{ route('mutations.pdf', $mutation) }}"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
function imprimerPdf() {
    const iframe = document.getElementById('pdfFrame');
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
}

function ouvrirOnglet() {
    window.open('{{ route('mutations.pdf', $mutation) }}', '_blank');
}
</script>
@endpush
@endsection
