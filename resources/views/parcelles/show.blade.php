@extends('layouts.app')
@section('title', 'Parcelle - Lot ' . $parcelle->numero_lot)

@push('styles')
<style>
    .owner-timeline { position: relative; padding: 20px 0; }
    .owner-timeline::before {
        content: '';
        position: absolute;
        left: 24px;
        top: 0; bottom: 0;
        width: 3px;
        background: linear-gradient(to bottom, #C8A951, #5D4E37);
        border-radius: 2px;
    }
    .owner-step {
        position: relative;
        padding-left: 60px;
        margin-bottom: 20px;
    }
    .owner-step:last-child { margin-bottom: 0; }
    .owner-dot {
        position: absolute;
        left: 12px;
        top: 8px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        z-index: 2;
    }
    .owner-dot.actuel { background: #C8A951; box-shadow: 0 0 0 4px rgba(200,169,81,0.2); }
    .owner-dot.ancien { background: #5D4E37; }
    .owner-dot.refuse { background: #dc3545; }
    .owner-dot.annule { background: #6c757d; }
    .owner-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        transition: all .2s;
    }
    .owner-card:hover { border-color: #C8A951; box-shadow: 0 3px 12px rgba(200,169,81,0.1); }
    .owner-card.actuel { border-color: #C8A951; border-width: 2px; background: #fffdf5; }
    .owner-card .owner-name { font-weight: 700; color: #5D4E37; font-size: 14px; }
    .owner-card .owner-meta { font-size: 11px; color: #999; }
    .owner-card .owner-meta span { margin-right: 12px; }
    .owner-card .mutation-info { font-size: 12px; color: #888; margin-top: 6px; padding-top: 6px; border-top: 1px dashed #eee; }
    .chain-summary {
        background: linear-gradient(135deg, #fdf6e3, #fff);
        border: 1px solid #e0d5b8;
        border-radius: 10px;
        padding: 16px;
    }
    .chain-item { display: inline-flex; align-items: center; font-size: 13px; }
    .chain-arrow { color: #C8A951; margin: 0 6px; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <!-- Infos parcelle -->
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header" style="background:#fdf6e3;">
                <h6 class="mb-0"><i class="bi bi-geo-alt-fill" style="color:#C8A951;"></i> Informations de la parcelle</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted">Numéro de lot</small>
                        <div class="fw-bold fs-5" style="color:#5D4E37;">{{ $parcelle->numero_lot }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Projet</small>
                        <div class="fw-bold">{{ $parcelle->projet->nom }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Commune</small>
                        <div>{{ $parcelle->projet->commune->nom }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Superficie</small>
                        <div>{{ $parcelle->superficie ? $parcelle->superficie . ' m²' : 'N/A' }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Usage</small>
                        <div>{{ $parcelle->usage ? ucfirst($parcelle->usage) : 'N/A' }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Attribution</small>
                        <div>{{ $parcelle->date_attribution?->format('d/m/Y') ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Propriétaire actuel -->
        @php
            // Trouver la mutation validée la plus récente pour ce proprio actuel
            $derniereMutationValidee = $parcelle->mutations
                ->where('statut', 'validee')
                ->where('nouveau_proprietaire_id', $parcelle->proprietaire_id)
                ->sortByDesc('id')
                ->first();
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#C8A951; color:#fff;">
                <h6 class="mb-0"><i class="bi bi-person-check-fill"></i> Propriétaire actuel</h6>
                @if($derniereMutationValidee)
                    <a href="{{ route('mutations.apercu', $derniereMutationValidee) }}" target="_blank"
                       class="btn btn-sm btn-light" title="Voir la notification PDF + QR code">
                        <i class="bi bi-file-earmark-pdf-fill" style="color:#dc2626;"></i>
                        <strong>PDF</strong>
                        <small class="text-muted ms-1">N°{{ $derniereMutationValidee->numero_notification }}</small>
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if($parcelle->proprietaire)
                    <h5 style="color:#5D4E37;">{{ $parcelle->proprietaire->nom_complet }}</h5>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <small class="text-muted">CNI / Passeport</small>
                            <div class="fw-bold">{{ $parcelle->proprietaire->cni_passport ?? 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Type pièce</small>
                            <div>{{ $parcelle->proprietaire->type_piece ?? 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">NINEA</small>
                            <div>{{ $parcelle->proprietaire->ninea ?? 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Téléphone</small>
                            <div>{{ $parcelle->proprietaire->telephone ?? 'N/A' }}</div>
                        </div>
                    </div>

                    @if($derniereMutationValidee)
                        <hr class="my-3">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('mutations.apercu', $derniereMutationValidee) }}" target="_blank" class="btn btn-sm" style="background:#dc2626; color:#fff;">
                                <i class="bi bi-eye"></i> Visualiser le document (PDF + QR code)
                            </a>
                            <a href="{{ route('mutations.telecharger', $derniereMutationValidee) }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-download"></i> Télécharger
                            </a>
                            <a href="{{ route('mutations.pdf', $derniereMutationValidee) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-up-right"></i> Nouvel onglet
                            </a>
                        </div>
                        <div class="small text-muted mt-2">
                            Notification N°<strong>{{ $derniereMutationValidee->numero_notification }}</strong>
                            — délivrée le {{ $derniereMutationValidee->date_mutation?->format('d/m/Y') }}
                        </div>
                    @else
                        <hr class="my-3">
                        <div class="alert alert-info mb-0 small py-2">
                            <i class="bi bi-info-circle"></i> Aucun document de notification généré pour ce propriétaire.
                        </div>
                    @endif
                @else
                    <p class="text-muted mb-0">Aucun propriétaire enregistré</p>
                @endif
            </div>
        </div>

        <!-- Résumé chaîne -->
        @php
            $mutationsValidees = $parcelle->mutations->where('statut', 'validee')->sortBy('date_mutation');
            $totalMutations = $parcelle->mutations->count();
            $totalProprietaires = $mutationsValidees->count() + 1; // +1 pour le premier
        @endphp
        <div class="chain-summary">
            <h6 class="mb-2" style="color:#5D4E37;"><i class="bi bi-diagram-3"></i> Chaîne de propriété</h6>
            <div>
                @if($mutationsValidees->count() > 0)
                    @php $premier = $mutationsValidees->first()?->ancienProprietaire; @endphp
                    @if($premier)
                        <span class="chain-item"><strong>{{ $premier->nom_complet }}</strong></span>
                    @endif
                    @foreach($mutationsValidees as $m)
                        <span class="chain-arrow"><i class="bi bi-arrow-right"></i></span>
                        <span class="chain-item"><strong>{{ $m->nouveauProprietaire?->nom_complet ?? '?' }}</strong></span>
                    @endforeach
                @else
                    <span class="text-muted">Aucune mutation enregistrée</span>
                @endif
            </div>
            <div class="mt-2">
                <small class="text-muted">{{ $totalMutations }} mutation(s) | {{ $totalProprietaires }} propriétaire(s) au total</small>
            </div>
        </div>
    </div>

    <!-- Timeline des propriétaires -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#fdf6e3;">
                <h6 class="mb-0"><i class="bi bi-clock-history" style="color:#C8A951;"></i> Historique complet des propriétaires</h6>
                <span class="badge" style="background:#5D4E37;">{{ $totalMutations }} mutation(s)</span>
            </div>
            <div class="card-body">
                @if($parcelle->mutations->count() > 0)
                <div class="owner-timeline">
                    {{-- Propriétaire actuel --}}
                    <div class="owner-step">
                        <div class="owner-dot actuel"><i class="bi bi-star-fill" style="font-size:10px;"></i></div>
                        <div class="owner-card actuel">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="owner-name">{{ $parcelle->proprietaire?->nom_complet ?? 'N/A' }}</div>
                                    <div class="owner-meta">
                                        <span><i class="bi bi-credit-card"></i> {{ $parcelle->proprietaire?->cni_passport ?? 'N/A' }}</span>
                                        <span><i class="bi bi-telephone"></i> {{ $parcelle->proprietaire?->telephone ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <span class="badge" style="background:#C8A951;">Actuel</span>
                            </div>
                        </div>
                    </div>

                    {{-- Mutations dans l'ordre inverse (plus récente en haut) --}}
                    @foreach($parcelle->mutations->sortByDesc('date_mutation') as $idx => $mutation)
                    <div class="owner-step">
                        <div class="owner-dot {{ $mutation->statut === 'refusee' ? 'refuse' : ($mutation->statut === 'annulee' ? 'annule' : 'ancien') }}">
                            {{ $parcelle->mutations->count() - $idx }}
                        </div>
                        <div class="owner-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    @if($mutation->statut === 'validee' || $mutation->statut === 'annulee')
                                        @if($mutation->est_attribution)
                                            <div class="owner-name">
                                                <span class="badge me-1" style="background:#3b82f6; color:#fff; font-size:10px;">ATTRIBUTION</span>
                                                {{ $mutation->nouveauProprietaire?->nom_complet ?? 'N/A' }}
                                            </div>
                                            <div class="owner-meta">
                                                <span><i class="bi bi-credit-card"></i> {{ $mutation->piece_formatee ?: 'N/A' }}</span>
                                                @if($mutation->nouveauProprietaire?->telephone)
                                                    <span><i class="bi bi-telephone"></i> {{ $mutation->nouveauProprietaire->telephone }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="owner-name">
                                                <span class="badge me-1" style="background:#d97706; color:#fff; font-size:10px;">MUTATION</span>
                                                {{ $mutation->ancienProprietaire?->nom_complet ?? 'N/A' }}
                                                <i class="bi bi-arrow-right" style="color:#C8A951; font-size:12px;"></i>
                                                {{ $mutation->nouveauProprietaire?->nom_complet ?? 'N/A' }}
                                            </div>
                                            <div class="owner-meta">
                                                @if($mutation->ancienProprietaire)
                                                    <span><i class="bi bi-credit-card"></i> {{ $mutation->ancienProprietaire->cni_passport ?? 'N/A' }}</span>
                                                    <span><i class="bi bi-telephone"></i> {{ $mutation->ancienProprietaire->telephone ?? 'N/A' }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="owner-name text-danger">{{ $mutation->type_libelle }} refusée</div>
                                    @endif
                                </div>
                                <span class="badge badge-{{ $mutation->statut }}">{{ ucfirst($mutation->statut) }}</span>
                            </div>
                            <div class="mutation-info">
                                <span><i class="bi bi-calendar"></i> {{ $mutation->date_mutation?->format('d/m/Y') }}</span>
                                @if($mutation->numero_notification)
                                    <span class="ms-3"><i class="bi bi-hash"></i> N°{{ $mutation->numero_notification }}</span>
                                @endif
                                @if($mutation->validateur)
                                    <span class="ms-3"><i class="bi bi-person-check"></i> {{ $mutation->validateur->name }}</span>
                                @endif
                                @if($mutation->motif_refus)
                                    <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> {{ $mutation->motif_refus }}</small>
                                @endif
                                <div class="mt-1">
                                    <a href="{{ route('mutations.show', $mutation) }}" class="btn btn-sm btn-outline-secondary" style="font-size:11px;"><i class="bi bi-eye"></i> Détails</a>
                                    @if($mutation->statut === 'validee')
                                        <a href="{{ route('mutations.apercu', $mutation) }}" class="btn btn-sm btn-outline-danger" style="font-size:11px;"><i class="bi bi-file-pdf"></i> Document</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history" style="font-size:36px; color:#C8A951; opacity:0.4;"></i>
                        <p class="text-muted mt-2">Aucune mutation enregistrée pour cette parcelle.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
