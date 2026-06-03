@extends('layouts.app')
@section('title', 'Parcelles')

@push('styles')
<style>
    .kpi-card {
        background: #fff; border-radius: 14px; padding: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.04);
        transition: all .2s; height: 100%;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.07); }
    .kpi-card .kpi-icon {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .kpi-card .kpi-value { font-size: 22px; font-weight: 800; line-height: 1; margin-top: 10px; color: #1e3a5f; }
    .kpi-card .kpi-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

    .parcelle-row:hover { background: #fefbf6 !important; }
    .sort-link { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .sort-link:hover { color: #1e3a5f; }
    .sort-link.active { color: #1e3a5f; font-weight: 700; }

    .chip-usage {
        display: inline-block; padding: 2px 8px; border-radius: 6px;
        font-size: 11px; font-weight: 600;
        background: #e0e7ff; color: #4f46e5;
    }
    .badge-mut {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 600;
        background: #fef3c7; color: #d97706;
    }
    .pagination-wrap {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 10px; margin-top: 16px;
    }
    .pagination-info { font-size: 13px; color: #64748b; }
    .per-page-wrap { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; }
    .pagination .page-link {
        color: #1e3a5f; border-radius: 8px !important; margin: 0 2px;
        border-color: #e2e8f0; padding: 6px 12px; font-weight: 600;
    }
    .pagination .page-item.active .page-link { background: #1e3a5f; border-color: #1e3a5f; color: #fff; }
    .pagination .page-link:hover { background: #f0f6ff; color: #1e3a5f; }
    .pagination .page-item.disabled .page-link { color: #cbd5e1; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-geo-alt"></i> Parcelles</h4>
        <p class="text-muted mb-0 small">Gestion des lots et proprietaires</p>
    </div>
    <a href="{{ route('parcelles.create') }}" class="btn btn-primary" style="border-radius:10px;">
        <i class="bi bi-plus-lg"></i> Nouvelle parcelle
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="bi bi-grid-3x3"></i></div>
            <div class="kpi-value">{{ number_format($stats['total'], 0, ',', ' ') }}</div>
            <div class="kpi-label">Parcelles</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#dcfce7; color:#16a34a;"><i class="bi bi-bounding-box"></i></div>
            <div class="kpi-value">{{ number_format($stats['superficie_totale'] ?? 0, 0, ',', ' ') }}</div>
            <div class="kpi-label">Superficie (m²)</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-arrow-left-right"></i></div>
            <div class="kpi-value" style="color:#d97706;">{{ $stats['avec_mutations'] }}</div>
            <div class="kpi-label">Avec mutations</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fee2e2; color:#dc2626;"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-value" style="color:#dc2626;">{{ $stats['sans_proprietaire'] }}</div>
            <div class="kpi-label">Sans proprietaire</div>
        </div>
    </div>
</div>

{{-- Filtres --}}
<div class="card mb-3" style="border-radius:12px; border:none; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('parcelles.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Lot, ref lettre, proprietaire, CNI, tel..." value="{{ request('search') }}" style="border-radius:0 8px 8px 0;">
                </div>
            </div>
            <div class="col-md-3">
                <select name="projet_id" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Tous les projets</option>
                    @foreach($projets as $projet)
                        <option value="{{ $projet->id }}" {{ request('projet_id') == $projet->id ? 'selected' : '' }}>
                            {{ $projet->nom }} — {{ $projet->commune->nom ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="usage" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Tous usages</option>
                    @foreach($usages as $u)
                        <option value="{{ $u }}" {{ request('usage') === $u ? 'selected' : '' }}>{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <select name="per_page" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', 20) == '20' ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" style="border-radius:8px;"><i class="bi bi-funnel"></i> Filtrer</button>
                <a href="{{ route('parcelles.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;" title="Reinitialiser"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Tableau --}}
@php
    function sortLink($col, $label, $icon, $currentSort, $currentDir) {
        $dir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
        $qs = request()->except(['sort', 'direction', 'page']);
        $qs['sort'] = $col; $qs['direction'] = $dir;
        $url = route('parcelles.index') . '?' . http_build_query($qs);
        $active = $currentSort === $col ? 'active' : '';
        $arrow = '';
        if ($currentSort === $col) {
            $arrow = $currentDir === 'asc' ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>';
        }
        return '<a class="sort-link '.$active.'" href="'.e($url).'"><i class="bi '.$icon.'"></i> '.$label.' '.$arrow.'</a>';
    }
@endphp

<div class="card" style="border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.04);">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>{!! sortLink('numero_lot', 'Lot', 'bi-hash', $sort, $direction) !!}</th>
                    <th>Projet / Commune</th>
                    <th>Proprietaire</th>
                    <th>Contact</th>
                    <th>{!! sortLink('superficie', 'Sup. (m²)', 'bi-bounding-box', $sort, $direction) !!}</th>
                    <th>Usage</th>
                    <th>{!! sortLink('date_attribution', 'Attribue le', 'bi-calendar', $sort, $direction) !!}</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parcelles as $parcelle)
                <tr class="parcelle-row">
                    <td>
                        <strong style="color:#1e3a5f;">{{ $parcelle->numero_lot }}</strong>
                        @if($parcelle->ref_lettre)<br><small class="text-muted">{{ $parcelle->ref_lettre }}</small>@endif
                        @if($parcelle->mutations_count > 0)
                            <br><span class="badge-mut"><i class="bi bi-arrow-left-right"></i> {{ $parcelle->mutations_count }} mut.</span>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $parcelle->projet->nom ?? '-' }}</div>
                        <small class="text-muted"><i class="bi bi-geo-alt"></i> {{ $parcelle->projet->commune->nom ?? '-' }}</small>
                    </td>
                    <td>
                        @if($parcelle->proprietaire)
                            <div class="fw-semibold">{{ $parcelle->proprietaire->nom_complet }}</div>
                            <small class="text-muted">{{ $parcelle->proprietaire->cni_passport ?? '' }}</small>
                        @else
                            <span class="text-danger small"><i class="bi bi-exclamation-circle"></i> Non attribue</span>
                        @endif
                    </td>
                    <td>
                        <small class="text-muted">{{ $parcelle->proprietaire?->telephone ?? '-' }}</small>
                    </td>
                    <td class="text-end">{{ $parcelle->superficie ? number_format($parcelle->superficie, 0, ',', ' ') : '-' }}</td>
                    <td>@if($parcelle->usage)<span class="chip-usage">{{ $parcelle->usage }}</span>@else<small class="text-muted">-</small>@endif</td>
                    <td><small class="text-muted">{{ $parcelle->date_attribution?->format('d/m/Y') ?? '-' }}</small></td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('parcelles.show', $parcelle) }}" class="btn btn-sm btn-outline-primary" title="Voir" style="border-radius:8px;"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('parcelles.edit', $parcelle) }}" class="btn btn-sm btn-outline-secondary" title="Modifier" style="border-radius:8px;"><i class="bi bi-pencil"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> Aucune parcelle trouvee.
                    @if(request('search') || request('projet_id') || request('usage'))
                        <br><a href="{{ route('parcelles.index') }}" class="small">Reinitialiser les filtres</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div class="pagination-wrap">
    <div class="pagination-info">
        @if($parcelles->total() > 0)
            Affichage <strong>{{ $parcelles->firstItem() }}</strong>-<strong>{{ $parcelles->lastItem() }}</strong> sur <strong>{{ number_format($parcelles->total(), 0, ',', ' ') }}</strong> parcelle{{ $parcelles->total() > 1 ? 's' : '' }}
        @else
            Aucun resultat
        @endif
    </div>
    <div>
        {{ $parcelles->onEachSide(1)->links() }}
    </div>
</div>
@endsection
