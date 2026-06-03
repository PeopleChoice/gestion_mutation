@extends('layouts.app')
@section('title', 'Rapports')

@push('styles')
<style>
    .rapport-card {
        background: #fff; border-radius: 14px; padding: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.04);
        transition: all .2s;
    }
    .rapport-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.07); }
    .rapport-hero {
        background: linear-gradient(135deg, #5D4E37 0%, #3A2F1E 100%);
        color: #fff; border-radius: 14px; padding: 28px; margin-bottom: 20px;
    }
    .rapport-hero h4 { margin-bottom: 6px; font-weight: 700; }
    .rapport-hero p { margin-bottom: 0; opacity: 0.85; font-size: 14px; }
    .section-title {
        font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;
        color: #94a3b8; font-weight: 700; margin-bottom: 12px;
    }
    .option-card {
        background: #fafafa; border: 1px solid #eee; border-radius: 10px;
        padding: 14px; cursor: pointer; transition: all .15s; text-decoration: none;
        color: #5D4E37; display: block;
    }
    .option-card:hover { border-color: #C8A951; background: #fef6ec; color: #5D4E37; }
    .option-card i { font-size: 22px; color: #C8A951; margin-bottom: 8px; display: block; }
    .option-card .title { font-weight: 700; font-size: 14px; }
    .option-card .sub { font-size: 12px; color: #999; margin-top: 2px; }
</style>
@endpush

@section('content')
<div class="rapport-hero">
    <h4><i class="bi bi-file-earmark-bar-graph"></i> Rapports</h4>
    <p>Generez des rapports de mutations filtres par import, statut ou periode, et exportez-les en PDF.</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="rapport-card">
            <div class="section-title"><i class="bi bi-funnel"></i> Filtres du rapport</div>
            <form action="{{ route('rapports.generer') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold small">Import <span class="text-muted">(optionnel)</span></label>
                        <select name="import_id" class="form-select" style="border-radius:10px;">
                            <option value="">Tous les imports</option>
                            @foreach($imports as $import)
                                <option value="{{ $import->id }}">
                                    {{ $import->projet->nom }} — {{ $import->created_at->format('d/m/Y') }} ({{ Str::limit($import->nom_fichier, 30) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Statut</label>
                        <select name="statut" class="form-select" style="border-radius:10px;">
                            <option value="">Tous statuts</option>
                            <option value="en_attente">En attente</option>
                            <option value="validee">Validees</option>
                            <option value="refusee">Refusees</option>
                            <option value="annulee">Annulees</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Date debut</label>
                        <input type="date" name="date_debut" class="form-control" style="border-radius:10px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Date fin</label>
                        <input type="date" name="date_fin" class="form-control" style="border-radius:10px;">
                    </div>
                </div>
                <hr class="my-4">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary fw-bold" style="background:#5D4E37; border-color:#5D4E37; border-radius:10px;">
                        <i class="bi bi-table"></i> Afficher a l'ecran
                    </button>
                    <button type="submit" name="format" value="pdf" class="btn btn-danger fw-bold" style="border-radius:10px;">
                        <i class="bi bi-file-pdf"></i> Telecharger PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="rapport-card">
            <div class="section-title"><i class="bi bi-lightning"></i> Acces rapides</div>
            <form method="POST" action="{{ route('rapports.generer') }}" class="mb-2">
                @csrf
                <button type="submit" class="option-card w-100 text-start border-0">
                    <i class="bi bi-list-check"></i>
                    <div class="title">Toutes les mutations</div>
                    <div class="sub">Vue complete sans filtre</div>
                </button>
            </form>
            <form method="POST" action="{{ route('rapports.generer') }}" class="mb-2">
                @csrf
                <input type="hidden" name="statut" value="validee">
                <button type="submit" class="option-card w-100 text-start border-0">
                    <i class="bi bi-check-circle"></i>
                    <div class="title">Mutations validees</div>
                    <div class="sub">Uniquement celles approuvees</div>
                </button>
            </form>
            <form method="POST" action="{{ route('rapports.generer') }}" class="mb-2">
                @csrf
                <input type="hidden" name="statut" value="en_attente">
                <button type="submit" class="option-card w-100 text-start border-0">
                    <i class="bi bi-hourglass-split"></i>
                    <div class="title">En attente</div>
                    <div class="sub">Dossiers a traiter</div>
                </button>
            </form>
            <form method="POST" action="{{ route('rapports.generer') }}">
                @csrf
                <input type="hidden" name="statut" value="validee">
                <input type="hidden" name="date_debut" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                <input type="hidden" name="date_fin" value="{{ now()->format('Y-m-d') }}">
                <button type="submit" class="option-card w-100 text-start border-0">
                    <i class="bi bi-calendar-month"></i>
                    <div class="title">Ce mois</div>
                    <div class="sub">Mutations du mois en cours</div>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
