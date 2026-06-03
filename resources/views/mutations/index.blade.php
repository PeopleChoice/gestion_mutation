@extends('layouts.app')
@section('title', 'Mutations')

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

    .mutation-row:hover { background: #faf6ec !important; }
    .statut-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 600;
    }
    .statut-badge.en_attente { background: #fef3c7; color: #d97706; }
    .statut-badge.validee { background: #dcfce7; color: #16a34a; }
    .statut-badge.refusee { background: #fee2e2; color: #dc2626; }
    .statut-badge.annulee { background: #e5e7eb; color: #6b7280; }

    .filter-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 14px; border-radius: 20px; font-size: 12px;
        border: 1px solid #dee2e6; background: #fff; cursor: pointer;
        transition: all .15s; font-weight: 600; text-decoration: none; color: #555;
    }
    .filter-chip:hover { border-color: #5D4E37; color: #5D4E37; }
    .filter-chip.active { background: #5D4E37; color: #fff; border-color: #5D4E37; }

    .pagination .page-link { color: #5D4E37; border-radius: 8px !important; margin: 0 2px; border-color: #e2e8f0; font-weight: 600; }
    .pagination .page-item.active .page-link { background: #C8A951; border-color: #C8A951; color: #fff; }

    .arrow-icon { color: #C8A951; font-size: 16px; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-arrow-left-right"></i> Mutations</h4>
        <p class="text-muted mb-0 small">Historique et traitement des changements de proprietaires</p>
    </div>
    <div class="d-flex gap-2">
        @if(Route::has('annulations.index'))
        <a href="{{ route('annulations.index') }}" class="btn btn-sm btn-outline-danger" style="border-radius:10px;"><i class="bi bi-x-circle"></i> Annulations</a>
        @endif
        <a href="{{ route('mutations.create') }}" class="btn btn-sm fw-bold" style="background:#C8A951; color:#fff; border-radius:10px;"><i class="bi bi-plus-lg"></i> Nouvelle mutation</a>
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
            <div class="kpi-icon" style="background:#fef6ec; color:#C8A951;"><i class="bi bi-arrow-left-right"></i></div>
            <div class="kpi-value">{{ $stats['total'] }}</div>
            <div class="kpi-label">Total</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-hourglass-split"></i></div>
            <div class="kpi-value" style="color:#d97706;">{{ $stats['en_attente'] }}</div>
            <div class="kpi-label">En attente</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#dcfce7; color:#16a34a;"><i class="bi bi-check-circle"></i></div>
            <div class="kpi-value" style="color:#16a34a;">{{ $stats['validees'] }}</div>
            <div class="kpi-label">Validees</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fee2e2; color:#dc2626;"><i class="bi bi-x-circle"></i></div>
            <div class="kpi-value" style="color:#dc2626;">{{ $stats['refusees'] }}</div>
            <div class="kpi-label">Refusees</div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <a href="{{ route('mutations.index') }}" class="filter-chip {{ !request('statut') && !$showRefusees ? 'active' : '' }}"><i class="bi bi-list"></i> Toutes (sauf refusées)</a>
    <a href="{{ route('mutations.index', ['statut' => 'en_attente']) }}" class="filter-chip {{ request('statut') === 'en_attente' ? 'active' : '' }}"><i class="bi bi-hourglass-split"></i> En attente</a>
    <a href="{{ route('mutations.index', ['statut' => 'validee']) }}" class="filter-chip {{ request('statut') === 'validee' ? 'active' : '' }}"><i class="bi bi-check"></i> Validees</a>
    <a href="{{ route('mutations.index', ['statut' => 'annulee']) }}" class="filter-chip {{ request('statut') === 'annulee' ? 'active' : '' }}"><i class="bi bi-slash-circle"></i> Annulees</a>

    <span class="ms-auto"></span>

    @if($showRefusees || request('statut') === 'refusee')
        <a href="{{ route('mutations.index') }}" class="filter-chip" style="background:#fee2e2; color:#991b1b; border-color:#fca5a5;">
            <i class="bi bi-eye-slash"></i> Masquer les refusées
        </a>
    @else
        <a href="{{ route('mutations.index', ['show_refusees' => 1]) }}" class="filter-chip" style="border-style:dashed;">
            <i class="bi bi-eye"></i> Afficher les refusées ({{ $stats['refusees'] }})
        </a>
    @endif
</div>

<div class="card mb-3" style="border-radius:12px; border:none; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('mutations.index') }}" class="row g-2 align-items-end">
            @if(request('statut'))<input type="hidden" name="statut" value="{{ request('statut') }}">@endif
            @if($showRefusees)<input type="hidden" name="show_refusees" value="1">@endif
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="N° notif, lot, proprietaire..." value="{{ request('search') }}" style="border-radius:8px;">
            </div>
            <div class="col-md-2">
                <select name="projet_id" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Tous projets</option>
                    @foreach($projets as $p)
                        <option value="{{ $p->id }}" {{ request('projet_id') == $p->id ? 'selected' : '' }}>{{ $p->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_debut" class="form-control form-control-sm" value="{{ request('date_debut') }}" style="border-radius:8px;">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ request('date_fin') }}" style="border-radius:8px;">
            </div>
            <div class="col-md-1">
                <select name="per_page" class="form-select form-select-sm" style="border-radius:8px;">
                    @foreach([10,20,50,100] as $n)
                        <option value="{{ $n }}" {{ request('per_page',20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" style="border-radius:8px;"><i class="bi bi-funnel"></i> Filtrer</button>
                <a href="{{ route('mutations.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card" style="border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.04);">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th>N° Notif.</th>
                    <th>Lot / Projet</th>
                    <th>Ancien proprietaire</th>
                    <th></th>
                    <th>Nouveau proprietaire</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $mutation)
                <tr class="mutation-row">
                    <td><code>{{ $mutation->numero_notification ?? '-' }}</code></td>
                    <td>
                        <strong style="color:#5D4E37;">{{ $mutation->parcelle->numero_lot ?? '-' }}</strong><br>
                        <small class="text-muted">{{ $mutation->parcelle->projet->nom ?? '-' }}</small>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $mutation->ancienProprietaire?->nom_complet ?? '-' }}</div>
                        @if($mutation->ancienProprietaire?->cni_passport)<small class="text-muted">{{ $mutation->ancienProprietaire->cni_passport }}</small>@endif
                    </td>
                    <td class="text-center"><i class="bi bi-arrow-right arrow-icon"></i></td>
                    <td>
                        <div class="fw-semibold">{{ $mutation->nouveauProprietaire?->nom_complet ?? '-' }}</div>
                        @if($mutation->piece_formatee)<small class="text-muted d-block">{{ $mutation->piece_formatee }}</small>@endif
                        @if($mutation->nouveauProprietaire?->telephone)<small class="text-muted">Tél: {{ $mutation->nouveauProprietaire->telephone }}</small>@endif
                    </td>
                    <td><small class="text-muted">{{ $mutation->date_mutation?->format('d/m/Y') ?? '-' }}</small></td>
                    <td><span class="statut-badge {{ $mutation->statut }}">{{ ucfirst(str_replace('_',' ', $mutation->statut)) }}</span></td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('mutations.show', $mutation) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;" title="Voir"><i class="bi bi-eye"></i></a>
                            @if($mutation->statut === 'validee' && Route::has('mutations.pdf'))
                                <a href="{{ route('mutations.pdf', $mutation) }}" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" title="PDF"><i class="bi bi-file-pdf"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> Aucune mutation trouvee.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
    <div class="small text-muted">
        @if($mutations->total() > 0)
            Affichage <strong>{{ $mutations->firstItem() }}</strong>-<strong>{{ $mutations->lastItem() }}</strong> sur <strong>{{ number_format($mutations->total(), 0, ',', ' ') }}</strong>
        @endif
    </div>
    <div>{{ $mutations->onEachSide(1)->links() }}</div>
</div>
@endsection
