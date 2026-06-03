@extends('layouts.app')
@section('title', 'Communes')

@push('styles')
<style>
    .kpi-card {
        background: #fff; border-radius: 14px; padding: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.04);
        transition: all .2s; height: 100%;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.07); }
    .kpi-card .kpi-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .kpi-card .kpi-value { font-size: 22px; font-weight: 800; line-height: 1; margin-top: 10px; color: #5D4E37; }
    .kpi-card .kpi-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    .commune-row:hover { background: #faf6ec !important; }
    .card-form { border-radius:14px; border:none; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
    .card-form .card-header { background: #fef6ec; color:#5D4E37; border-bottom:1px solid #e9dfc1; border-radius:14px 14px 0 0; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-geo-alt-fill"></i> Communes</h4>
        <p class="text-muted mb-0 small">Referentiel des collectivites et leur portefeuille de projets</p>
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
            <div class="kpi-icon" style="background:#fef6ec; color:#C8A951;"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="kpi-value">{{ $stats['total'] }}</div>
            <div class="kpi-label">Communes</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#dcfce7; color:#16a34a;"><i class="bi bi-buildings"></i></div>
            <div class="kpi-value">{{ $stats['avec_projets'] }}</div>
            <div class="kpi-label">Avec projets</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="bi bi-diagram-3"></i></div>
            <div class="kpi-value">{{ $stats['departements'] }}</div>
            <div class="kpi-label">Departements</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fee2e2; color:#dc2626;"><i class="bi bi-map"></i></div>
            <div class="kpi-value">{{ $stats['regions'] }}</div>
            <div class="kpi-label">Regions</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card card-form">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-plus-circle"></i> Ajouter une commune</h6></div>
            <div class="card-body">
                <form action="{{ route('communes.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required style="border-radius:10px;" placeholder="Ex: Dakar-Plateau">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Departement</label>
                        <input type="text" name="departement" class="form-control" style="border-radius:10px;" placeholder="Ex: Dakar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Region</label>
                        <input type="text" name="region" class="form-control" style="border-radius:10px;" placeholder="Ex: Dakar">
                    </div>
                    <button type="submit" class="btn w-100 fw-bold" style="background:#C8A951; color:#fff; border-radius:10px;">
                        <i class="bi bi-plus-lg"></i> Ajouter
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-3" style="border-radius:12px; border:none; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('communes.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-9">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Rechercher par nom, departement, region..." value="{{ request('search') }}" style="border-radius:0 8px 8px 0;">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill" style="border-radius:8px;"><i class="bi bi-funnel"></i> Filtrer</button>
                        <a href="{{ route('communes.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-x"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" style="border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.04);">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#f8fafc;">
                        <tr><th>Nom</th><th>Departement</th><th>Region</th><th class="text-center">Projets</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($communes as $commune)
                        <tr class="commune-row">
                            <td><strong style="color:#5D4E37;">{{ $commune->nom }}</strong></td>
                            <td><small>{{ $commune->departement ?? '-' }}</small></td>
                            <td><small>{{ $commune->region ?? '-' }}</small></td>
                            <td class="text-center">
                                @if($commune->projets_count > 0)
                                    <span class="badge" style="background:#C8A951;">{{ $commune->projets_count }}</span>
                                @else
                                    <small class="text-muted">0</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('communes.destroy', $commune) }}" method="POST" class="d-inline" data-confirm="Supprimer la commune <strong>{{ $commune->nom }}</strong> ?" data-confirm-type="danger" data-confirm-label="Supprimer">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" {{ $commune->projets_count > 0 ? 'disabled title=Commune avec projets' : '' }}>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> Aucune commune</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
