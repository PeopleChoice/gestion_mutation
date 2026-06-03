@extends('layouts.app')
@section('title', 'Tableau de bord')

@push('styles')
<style>
    .welcome-banner {
        background: linear-gradient(135deg, #5D4E37 0%, #3A2F1E 100%);
        border-radius: 12px;
        padding: 25px 30px;
        color: #fff;
        margin-bottom: 20px;
    }
    .welcome-banner h4 { margin: 0; font-weight: 300; }
    .welcome-banner h4 strong { font-weight: 700; }
    .welcome-banner p { margin: 5px 0 0; opacity: 0.7; font-size: 13px; }
    .stat-card-new {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all .2s;
        position: relative;
        overflow: hidden;
    }
    .stat-card-new:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
    .stat-card-new .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    .stat-card-new .stat-number { font-size: 28px; font-weight: 700; line-height: 1; }
    .stat-card-new .stat-label { font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-card-new .stat-sub { font-size: 11px; margin-top: 4px; }
    .donut-chart {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        position: relative;
        margin: 0 auto;
    }
    .donut-center {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    .donut-center .number { font-size: 24px; font-weight: 700; color: #5D4E37; }
    .donut-center .label { font-size: 10px; color: #999; }
    .projet-bar {
        height: 8px;
        border-radius: 4px;
        background: #f0ebe3;
        overflow: hidden;
    }
    .projet-bar-fill { height: 100%; border-radius: 4px; background: #C8A951; }
    .activity-item {
        display: flex;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-top: 5px;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .activity-dot.validee { background: #C8A951; }
    .activity-dot.refusee { background: #dc3545; }
    .activity-dot.en_attente { background: #fd7e14; }
    .activity-dot.annulee { background: #6c757d; }
    .quick-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #eee;
        text-decoration: none;
        color: #5D4E37;
        transition: all .2s;
        font-size: 12px;
        font-weight: 600;
    }
    .quick-action:hover { border-color: #C8A951; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(200,169,81,0.15); color: #5D4E37; }
    .quick-action i { font-size: 24px; margin-bottom: 6px; color: #C8A951; }
</style>
@endpush

@section('content')
<!-- Bannière de bienvenue -->
<div class="welcome-banner">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4>Bonjour, <strong>{{ auth()->user()->name }}</strong></h4>
            <p>{{ now()->translatedFormat('l d F Y') }} | Direction Generale des Impots et des Domaines</p>
        </div>
        <img src="{{ asset('images/dgid-logo.png') }}" style="height:45px; opacity:0.8;" alt="">
    </div>
</div>

<!-- Stats principales -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-new">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Parcelles</div>
                    <div class="stat-number" style="color:#5D4E37;">{{ number_format($totalParcelles) }}</div>
                    <div class="stat-sub">
                        <span style="color:#C8A951;">{{ $parcellesAttribuees }} attribuees</span> |
                        <span style="color:#fd7e14;">{{ $parcelleSansAttributaire }} libres</span>
                    </div>
                </div>
                <div class="stat-icon" style="background:#fdf6e3; color:#C8A951;"><i class="bi bi-geo-alt-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-new">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Mutations Validees</div>
                    <div class="stat-number" style="color:#C8A951;">{{ number_format($mutationsValidees) }}</div>
                    <div class="stat-sub text-muted">sur {{ $totalMutations }} au total</div>
                </div>
                <div class="stat-icon" style="background:#f0ebe3; color:#5D4E37;"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-new">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">En Attente</div>
                    <div class="stat-number" style="color:#fd7e14;">{{ number_format($mutationsEnAttente) }}</div>
                    <div class="stat-sub text-muted">
                        @if($annulationsEnAttente > 0)
                            <span class="text-danger">{{ $annulationsEnAttente }} annulation(s)</span>
                        @else
                            mutations a traiter
                        @endif
                    </div>
                </div>
                <div class="stat-icon" style="background:#fff3e0; color:#fd7e14;"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-new">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Refusees / Annulees</div>
                    <div class="stat-number" style="color:#dc3545;">{{ number_format($mutationsRefusees) }}</div>
                    <div class="stat-sub text-muted">{{ $mutationsAnnulees }} annulee(s)</div>
                </div>
                <div class="stat-icon" style="background:#ffebee; color:#dc3545;"><i class="bi bi-x-circle-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row g-3 mb-4">
    <div class="col">
        <a href="{{ route('imports.create') }}" class="quick-action">
            <i class="bi bi-file-earmark-excel"></i> Importer Excel
        </a>
    </div>
    <div class="col">
        <a href="{{ route('parcelles.create') }}" class="quick-action">
            <i class="bi bi-plus-circle"></i> Nouvelle parcelle
        </a>
    </div>
    <div class="col">
        <a href="{{ route('recherche') }}" class="quick-action">
            <i class="bi bi-search"></i> Rechercher
        </a>
    </div>
    <div class="col">
        <a href="{{ route('rapports.index') }}" class="quick-action">
            <i class="bi bi-file-earmark-bar-graph"></i> Rapports
        </a>
    </div>
    <div class="col">
        <a href="{{ route('scanner') }}" class="quick-action">
            <i class="bi bi-qr-code-scan"></i> Scanner QR
        </a>
    </div>
    <div class="col">
        <a href="{{ route('projets.carte') }}" class="quick-action">
            <i class="bi bi-map"></i> Carte
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne gauche -->
    <div class="col-md-8">
        <!-- Projets -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0" style="color:#5D4E37;"><i class="bi bi-building"></i> Projets</h6>
                <a href="{{ route('projets.index') }}" class="btn btn-sm btn-outline-secondary">Voir tout</a>
            </div>
            <div class="card-body">
                @foreach($mutationsParProjet as $projet)
                <div class="d-flex align-items-center mb-3">
                    <div style="width:140px;">
                        <a href="{{ route('projets.show', $projet) }}" class="text-decoration-none fw-bold" style="color:#5D4E37;">{{ $projet->nom }}</a>
                        <div style="font-size:11px; color:#999;">{{ $projet->commune->nom }}</div>
                    </div>
                    <div class="flex-grow-1 mx-3">
                        @php $pct = $projet->parcelles_count > 0 ? round(($projet->parcelles_attribuees_count / $projet->parcelles_count) * 100) : 0; @endphp
                        <div class="projet-bar">
                            <div class="projet-bar-fill" style="width:{{ $pct }}%;"></div>
                        </div>
                    </div>
                    <div style="width:120px; text-align:right; font-size:12px;">
                        <strong style="color:#C8A951;">{{ $projet->parcelles_attribuees_count }}</strong>
                        <span class="text-muted">/ {{ $projet->parcelles_count }}</span>
                        <span class="text-muted ms-1">({{ $pct }}%)</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Dernières mutations -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0" style="color:#5D4E37;"><i class="bi bi-arrow-left-right"></i> Activite recente</h6>
                <a href="{{ route('mutations.index') }}" class="btn btn-sm btn-outline-secondary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Lot</th>
                            <th>Projet</th>
                            <th>Nouveau prop.</th>
                            <th>Par</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dernieresMutations as $mutation)
                        <tr>
                            <td>
                                <a href="{{ route('mutations.show', $mutation) }}" class="text-decoration-none" style="color:#5D4E37;">
                                    <strong>{{ $mutation->parcelle->numero_lot }}</strong>
                                </a>
                            </td>
                            <td><small>{{ $mutation->parcelle->projet->nom }}</small></td>
                            <td><small>{{ Str::limit($mutation->nouveauProprietaire?->nom_complet ?? '-', 25) }}</small></td>
                            <td><small class="text-muted">{{ $mutation->validateur?->name ?? '-' }}</small></td>
                            <td><small class="text-muted">{{ $mutation->date_mutation?->format('d/m/Y') }}</small></td>
                            <td><span class="badge badge-{{ $mutation->statut }}">{{ $mutation->statut }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">Aucune mutation</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Colonne droite -->
    <div class="col-md-4">
        <!-- Graphique mutations -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0" style="color:#5D4E37;"><i class="bi bi-pie-chart"></i> Repartition des mutations</h6>
            </div>
            <div class="card-body text-center">
                @php
                    $total = max($totalMutations, 1);
                    $pctV = round(($mutationsValidees / $total) * 100);
                    $pctR = round(($mutationsRefusees / $total) * 100);
                    $pctA = round(($mutationsAnnulees / $total) * 100);
                    $pctE = 100 - $pctV - $pctR - $pctA;
                    $deg1 = $pctV * 3.6;
                    $deg2 = $deg1 + $pctR * 3.6;
                    $deg3 = $deg2 + $pctA * 3.6;
                @endphp
                <div class="donut-chart" style="background: conic-gradient(#C8A951 0deg {{ $deg1 }}deg, #dc3545 {{ $deg1 }}deg {{ $deg2 }}deg, #6c757d {{ $deg2 }}deg {{ $deg3 }}deg, #fd7e14 {{ $deg3 }}deg 360deg);">
                    <div class="donut-center" style="width:90px; height:90px; background:#fff; border-radius:50%;">
                        <div class="number">{{ $totalMutations }}</div>
                        <div class="label">Total</div>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-3" style="font-size:12px;">
                    <span><i class="bi bi-circle-fill" style="color:#C8A951;"></i> Validees ({{ $mutationsValidees }})</span>
                    <span><i class="bi bi-circle-fill" style="color:#dc3545;"></i> Refusees ({{ $mutationsRefusees }})</span>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-1" style="font-size:12px;">
                    <span><i class="bi bi-circle-fill" style="color:#fd7e14;"></i> Attente ({{ $mutationsEnAttente }})</span>
                    <span><i class="bi bi-circle-fill" style="color:#6c757d;"></i> Annulees ({{ $mutationsAnnulees }})</span>
                </div>
            </div>
        </div>

        <!-- Derniers imports -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0" style="color:#5D4E37;"><i class="bi bi-file-earmark-excel"></i> Derniers imports</h6>
                <a href="{{ route('imports.create') }}" class="btn btn-sm" style="background:#C8A951; color:#fff;">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($derniersImports as $import)
                <a href="{{ route('imports.show', $import) }}" class="activity-item text-decoration-none px-3" style="color:#333;">
                    <div class="activity-dot" style="background:#C8A951;"></div>
                    <div class="flex-grow-1">
                        <div style="font-size:13px; font-weight:600;">{{ $import->projet->nom }}</div>
                        <div style="font-size:11px; color:#999;">
                            {{ Str::limit($import->nom_fichier, 30) }}
                            | {{ $import->lignes_matchees }}/{{ $import->total_lignes }} matchs
                        </div>
                    </div>
                    <small class="text-muted">{{ $import->created_at->diffForHumans() }}</small>
                </a>
                @empty
                <div class="text-center text-muted py-3">Aucun import</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
