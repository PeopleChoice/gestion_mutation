@extends('layouts.app')
@section('title', 'Projets / Lotissements')

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
    .kpi-card .kpi-value { font-size: 22px; font-weight: 800; line-height: 1; margin-top: 10px; color: #5D4E37; }
    .kpi-card .kpi-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    .projet-row:hover { background: #faf6ec !important; }
    .chip {
        display: inline-block; padding: 2px 8px; border-radius: 6px;
        font-size: 11px; font-weight: 600; background: #fef6ec; color: #C8A951;
    }
    .pagination .page-link { color: #5D4E37; border-radius: 8px !important; margin: 0 2px; border-color: #e2e8f0; font-weight: 600; }
    .pagination .page-item.active .page-link { background: #C8A951; border-color: #C8A951; color: #fff; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-buildings"></i> Projets / Lotissements</h4>
        <p class="text-muted mb-0 small">Gestion des lotissements et de leurs parcelles</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('projets.carte') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;"><i class="bi bi-map"></i> Voir la carte</a>
        <a href="{{ route('projets.create') }}" class="btn btn-sm" style="background:#C8A951; color:#fff; border-radius:10px;"><i class="bi bi-plus-lg"></i> Nouveau projet</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef6ec; color:#C8A951;"><i class="bi bi-buildings"></i></div>
            <div class="kpi-value">{{ $stats['total'] }}</div>
            <div class="kpi-label">Projets</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="bi bi-grid-3x3"></i></div>
            <div class="kpi-value">{{ number_format($stats['total_parcelles'], 0, ',', ' ') }}</div>
            <div class="kpi-label">Parcelles</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#dcfce7; color:#16a34a;"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="kpi-value">{{ $stats['avec_carte'] }}</div>
            <div class="kpi-label">Geolocalises</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-file-earmark-ruled"></i></div>
            <div class="kpi-value">{{ $stats['avec_plan'] }}</div>
            <div class="kpi-label">Avec plan cadastral</div>
        </div>
    </div>
</div>

<div class="card mb-3" style="border-radius:12px; border:none; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('projets.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Nom projet, commune, type..." value="{{ request('search') }}" style="border-radius:0 8px 8px 0;">
                </div>
            </div>
            <div class="col-md-3">
                <select name="commune_id" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Toutes les communes</option>
                    @foreach($communes as $c)
                        <option value="{{ $c->id }}" {{ request('commune_id') == $c->id ? 'selected' : '' }}>{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="per_page" class="form-select form-select-sm" style="border-radius:8px;">
                    @foreach([10, 20, 50, 100] as $n)
                        <option value="{{ $n }}" {{ request('per_page', 20) == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" style="border-radius:8px;"><i class="bi bi-funnel"></i> Filtrer</button>
                <a href="{{ route('projets.index') }}" class="btn btn-sm btn-outline-secondary" title="Reinitialiser" style="border-radius:8px;"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card" style="border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.04);">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>Projet</th>
                    <th>Commune</th>
                    <th>Type</th>
                    <th class="text-center">Parcelles</th>
                    <th>Carte</th>
                    <th>Plan</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projets as $projet)
                <tr class="projet-row">
                    <td>
                        @if($projet->code)
                            <span class="badge me-2" style="background:#5D4E37; color:#fff; font-family:'Courier New',monospace;">{{ $projet->code }}</span>
                        @endif
                        <a href="{{ route('projets.show', $projet) }}" class="text-decoration-none" style="color:#5D4E37;">
                            <strong>{{ $projet->nom }}</strong>
                        </a>
                        @if($projet->description)<br><small class="text-muted">{{ Str::limit($projet->description, 50) }}</small>@endif
                    </td>
                    <td><i class="bi bi-geo-alt text-muted"></i> {{ $projet->commune->nom ?? '-' }}</td>
                    <td>@if($projet->type_lotissement)<span class="chip">{{ $projet->type_lotissement }}</span>@else<small class="text-muted">-</small>@endif</td>
                    <td class="text-center"><span class="badge" style="background:#5D4E37;">{{ $projet->parcelles_count }}</span></td>
                    <td>
                        @if($projet->latitude && $projet->longitude)
                            <i class="bi bi-geo-alt-fill" style="color:#16a34a;" title="{{ $projet->latitude }}, {{ $projet->longitude }}"></i>
                        @else
                            <i class="bi bi-geo-alt text-muted"></i>
                        @endif
                    </td>
                    <td>
                        @if($projet->fichier_dxf)
                            <i class="bi bi-file-earmark-ruled-fill" style="color:#d97706;" title="Plan cadastral charge"></i>
                        @else
                            <small class="text-muted">-</small>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('projets.show', $projet) }}" class="btn btn-sm btn-outline-primary" title="Voir" style="border-radius:8px;"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('projets.edit', $projet) }}" class="btn btn-sm btn-outline-secondary" title="Modifier" style="border-radius:8px;"><i class="bi bi-pencil"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> Aucun projet trouve.
                    @if(request('search') || request('commune_id'))<br><a href="{{ route('projets.index') }}" class="small">Reinitialiser les filtres</a>@endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
    <div class="small text-muted">
        @if($projets->total() > 0)
            Affichage <strong>{{ $projets->firstItem() }}</strong>-<strong>{{ $projets->lastItem() }}</strong> sur <strong>{{ number_format($projets->total(), 0, ',', ' ') }}</strong>
        @endif
    </div>
    <div>{{ $projets->onEachSide(1)->links() }}</div>
</div>
@endsection
