@extends('layouts.app')
@section('title', 'Résultat du rapport')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card blue">
            <h6 class="text-muted">Total</h6>
            <h3>{{ $stats['total'] }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card green">
            <h6 class="text-muted">Validées</h6>
            <h3>{{ $stats['validees'] }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card red">
            <h6 class="text-muted">Refusées</h6>
            <h3>{{ $stats['refusees'] }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card orange">
            <h6 class="text-muted">Annulées</h6>
            <h3>{{ $stats['annulees'] }}</h3>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h6 class="mb-0">Détail des mutations</h6>
        <a href="{{ route('rapports.index') }}" class="btn btn-sm btn-secondary">Nouveau rapport</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>N° Notif.</th>
                    <th>Date</th>
                    <th>Lot</th>
                    <th>Projet</th>
                    <th>Commune</th>
                    <th>Ancien prop.</th>
                    <th>Nouveau prop.</th>
                    <th>Statut</th>
                    <th>Motif refus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mutations as $m)
                <tr>
                    <td>{{ $m->numero_notification ?? '-' }}</td>
                    <td>{{ $m->date_mutation?->format('d/m/Y') }}</td>
                    <td>{{ $m->parcelle->numero_lot }}</td>
                    <td>{{ $m->parcelle->projet->nom }}</td>
                    <td>{{ $m->parcelle->projet->commune->nom }}</td>
                    <td>{{ $m->ancienProprietaire?->nom_complet ?? '-' }}</td>
                    <td>{{ $m->nouveauProprietaire?->nom_complet ?? '-' }}</td>
                    <td><span class="badge badge-{{ $m->statut }}">{{ $m->statut }}</span></td>
                    <td>{{ Str::limit($m->motif_refus, 30) ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
