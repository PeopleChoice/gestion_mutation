@extends('layouts.app')
@section('title', 'Mutation - Lot ' . $mutation->parcelle->numero_lot)

@push('styles')
<style>
    .info-card { border-radius: 14px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
    .info-card .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; border-radius: 14px 14px 0 0; }
    .info-item { padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
    .info-item:last-child { border-bottom: none; }
    .info-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
    .info-value { font-size: 14px; color: #334155; font-weight: 600; margin-top: 2px; }

    .statut-hero { padding: 16px; border-radius: 12px; margin-bottom: 16px; }
    .statut-hero.en_attente { background: linear-gradient(135deg, #fef3c7, #fef6ec); border-left: 4px solid #d97706; }
    .statut-hero.validee { background: linear-gradient(135deg, #dcfce7, #f0fdf4); border-left: 4px solid #16a34a; }
    .statut-hero.refusee { background: linear-gradient(135deg, #fee2e2, #fff1f1); border-left: 4px solid #dc2626; }
    .statut-hero.annulee { background: linear-gradient(135deg, #e5e7eb, #f8fafc); border-left: 4px solid #6b7280; }

    .prop-card {
        border-radius: 12px; padding: 16px; border: 2px solid;
    }
    .prop-card.ancien { border-color: #d97706; background: linear-gradient(to bottom right, #fef3c7 0%, #fff 50%); }
    .prop-card.nouveau { border-color: #16a34a; background: linear-gradient(to bottom right, #dcfce7 0%, #fff 50%); }
    .prop-card .title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 8px; }
    .prop-card .name { font-weight: 800; color: #1e293b; font-size: 16px; margin-bottom: 8px; }
    .prop-card .detail { font-size: 12px; color: #64748b; display: flex; justify-content: space-between; padding: 3px 0; }
    .prop-card .detail strong { color: #334155; }

    .arrow-big { text-align: center; color: #C8A951; font-size: 28px; margin-top: 44px; }

    .statut-badge-big {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 10px; font-size: 13px; font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi {{ $mutation->est_attribution ? 'bi-person-plus' : 'bi-arrow-left-right' }}"></i>
            {{ $mutation->type_libelle }} {{ $mutation->numero_notification ? '#' . $mutation->numero_notification : '#' . $mutation->id }}
            <span class="badge ms-2" style="background:{{ $mutation->est_attribution ? '#3b82f6' : '#d97706' }}; color:#fff; font-size:11px;">
                {{ strtoupper($mutation->type_libelle) }}
            </span>
        </h4>
        <p class="text-muted mb-0 small">Lot {{ $mutation->parcelle->numero_lot }} — {{ $mutation->parcelle->projet->nom }}</p>
    </div>
    <a href="{{ route('mutations.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Statut hero --}}
<div class="statut-hero {{ $mutation->statut }}">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            @if($mutation->statut === 'en_attente')
                <h5 class="mb-1"><i class="bi bi-hourglass-split text-warning"></i> {{ $mutation->type_libelle }} en attente de validation</h5>
                <p class="mb-0 small text-muted">Un administrateur doit approuver ou refuser cette demande avant qu'elle ne soit effective.</p>
            @elseif($mutation->statut === 'validee')
                <h5 class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> {{ $mutation->type_libelle }} validée</h5>
                <p class="mb-0 small text-muted">Validée par {{ $mutation->validateur?->name ?? '-' }} le {{ $mutation->validated_at?->format('d/m/Y H:i') ?? '-' }}</p>
            @elseif($mutation->statut === 'refusee')
                <h5 class="mb-1"><i class="bi bi-x-circle-fill text-danger"></i> {{ $mutation->type_libelle }} refusée</h5>
                @if($mutation->motif_refus)<p class="mb-0 small"><strong>Motif :</strong> {{ $mutation->motif_refus }}</p>@endif
            @elseif($mutation->statut === 'annulee')
                <h5 class="mb-1"><i class="bi bi-slash-circle-fill text-secondary"></i> {{ $mutation->type_libelle }} annulée</h5>
            @endif
        </div>

        @if(auth()->user()->hasRole('admin') && $mutation->statut === 'en_attente')
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('mutations.valider-directe', $mutation) }}" data-confirm="Valider cette mutation ?<br><small class='text-muted'>Le nouveau propriétaire sera attribué et un QR code sera généré.</small>" data-confirm-type="success" data-confirm-label="Valider" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success fw-bold" style="border-radius:10px;">
                        <i class="bi bi-check-lg"></i> Valider
                    </button>
                </form>
                <button type="button" class="btn btn-outline-danger fw-bold" style="border-radius:10px;" data-bs-toggle="modal" data-bs-target="#modalRefuser">
                    <i class="bi bi-x-lg"></i> Refuser
                </button>
            </div>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        {{-- Proprietaires --}}
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <div class="prop-card ancien h-100">
                    <div class="title"><i class="bi bi-person"></i> Ancien proprietaire</div>
                    @if($mutation->ancienProprietaire)
                        <div class="name">{{ $mutation->ancienProprietaire->nom_complet }}</div>
                        <div class="detail"><span>Prénom</span><strong>{{ $mutation->ancienProprietaire->prenom ?? '-' }}</strong></div>
                        <div class="detail"><span>Nom</span><strong>{{ $mutation->ancienProprietaire->nom ?? '-' }}</strong></div>
                        <div class="detail"><span>Pièce</span><strong>{{ $mutation->ancienProprietaire->type_piece ?? '-' }}</strong></div>
                        <div class="detail"><span>N° pièce</span><strong>{{ $mutation->ancienProprietaire->cni_passport ?? '-' }}</strong></div>
                        <div class="detail"><span>Téléphone</span><strong>{{ $mutation->ancienProprietaire->telephone ?? '-' }}</strong></div>
                        @if($mutation->ancienProprietaire->adresse)
                            <div class="detail"><span>Adresse</span><strong>{{ $mutation->ancienProprietaire->adresse }}</strong></div>
                        @endif
                    @else
                        <p class="text-muted small mb-0">Aucun (premiere attribution)</p>
                    @endif
                </div>
            </div>
            <div class="col-md-2 arrow-big"><i class="bi bi-arrow-right"></i></div>
            <div class="col-md-5">
                <div class="prop-card nouveau h-100">
                    <div class="title"><i class="bi bi-person-check"></i> Nouveau attributaire</div>
                    @if($mutation->nouveauProprietaire)
                        <div class="name">{{ $mutation->nouveauProprietaire->nom_complet }}</div>
                        <div class="detail"><span>Prénom</span><strong>{{ $mutation->nouveauProprietaire->prenom ?? '-' }}</strong></div>
                        <div class="detail"><span>Nom</span><strong>{{ $mutation->nouveauProprietaire->nom ?? '-' }}</strong></div>
                        <div class="detail"><span>Téléphone</span><strong>{{ $mutation->nouveauProprietaire->telephone ?? '-' }}</strong></div>
                        <div class="detail"><span>Pièce</span><strong>{{ $mutation->piece_formatee ?: '-' }}</strong></div>
                        @if($mutation->nouveauProprietaire->adresse)
                            <div class="detail"><span>Adresse</span><strong>{{ $mutation->nouveauProprietaire->adresse }}</strong></div>
                        @endif
                    @else
                        <p class="text-muted small mb-0">-</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Demandeur --}}
        @php
            $demandeurFull = trim(($mutation->demandeur_prenom ?? '') . ' ' . ($mutation->demandeur_nom ?? ''));
            $hasDemandeur = $demandeurFull !== '' || !empty($mutation->demandeur_telephone);
        @endphp
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card info-card" style="border-left:4px solid #3b82f6;">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-person-lines-fill" style="color:#3b82f6; font-size:24px;"></i>
                            <div class="flex-fill">
                                <div class="info-label">Demandeur (dépose la demande)</div>
                                @if($hasDemandeur)
                                    <div class="info-value">
                                        {{ $demandeurFull ?: '-' }}
                                        @if($mutation->demandeur_telephone)
                                            <span class="text-muted ms-3"><i class="bi bi-telephone"></i> {{ $mutation->demandeur_telephone }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="info-value text-muted fst-italic">Non renseigné (mutation créée avant l'ajout du champ demandeur)</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="card info-card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Details de la mutation</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">N° Notification</div>
                            <div class="info-value"><code>{{ $mutation->numero_notification ?? 'N/A' }}</code></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date de mutation</div>
                            <div class="info-value">{{ $mutation->date_mutation?->format('d/m/Y') ?? '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Ref. lettre</div>
                            <div class="info-value">{{ $mutation->ref_lettre ?? '-' }}</div>
                        </div>
                        @if($mutation->code_paye)
                        <div class="info-item">
                            <div class="info-label">Code payé</div>
                            <div class="info-value">{{ $mutation->code_paye }}</div>
                        </div>
                        @endif
                        @if($mutation->piece_formatee)
                        <div class="info-item">
                            <div class="info-label">Pièce (format imprimé)</div>
                            <div class="info-value"><code>{{ $mutation->piece_formatee }}</code></div>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Lot / Projet</div>
                            <div class="info-value"><strong>{{ $mutation->parcelle->numero_lot }}</strong> — {{ $mutation->parcelle->projet->nom }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Commune</div>
                            <div class="info-value">{{ $mutation->parcelle->projet->commune->nom }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Type piece</div>
                            <div class="info-value">{{ $mutation->type_piece ?? '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Origine</div>
                            <div class="info-value">
                                @if($mutation->import_id)
                                    <i class="bi bi-file-earmark-excel"></i> Import Excel
                                @else
                                    <i class="bi bi-person-plus"></i> Saisie manuelle
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @if($mutation->observation)
                    <hr>
                    <div class="info-label">Observation</div>
                    <div class="info-value mt-1">{{ $mutation->observation }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card info-card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-lightning"></i> Actions</h6></div>
            <div class="card-body">
                @if($mutation->statut === 'validee')
                    <a href="{{ route('mutations.apercu', $mutation) }}" class="btn btn-primary w-100 mb-2" style="border-radius:10px;">
                        <i class="bi bi-eye"></i> Visualiser le document
                    </a>
                    <a href="{{ route('mutations.telecharger', $mutation) }}" class="btn btn-outline-danger w-100 mb-2" style="border-radius:10px;">
                        <i class="bi bi-download"></i> Telecharger PDF
                    </a>

                    @if(!$mutation->annulation || $mutation->annulation->statut === 'rejetee')
                    <hr>
                    <h6 class="text-muted small">Demander l'annulation</h6>
                    <form action="{{ route('mutations.demander-annulation', $mutation) }}" method="POST" data-confirm="Confirmer la demande d'annulation ?" data-confirm-label="Confirmer">
                        @csrf
                        <div class="mb-2">
                            <textarea name="motif" class="form-control" rows="2" placeholder="Motif de l'annulation..." required style="border-radius:10px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-warning w-100" style="border-radius:10px;">
                            <i class="bi bi-arrow-counterclockwise"></i> Demander annulation
                        </button>
                    </form>
                    @endif

                    @if($mutation->annulation && $mutation->annulation->statut === 'en_attente')
                        <div class="alert alert-warning mt-2 mb-0 small" style="border-radius:10px;">
                            <i class="bi bi-hourglass"></i> Annulation en attente d'approbation.
                        </div>
                    @endif
                @elseif($mutation->statut === 'en_attente')
                    @if(auth()->user()->hasRole('admin'))
                        <p class="text-muted small mb-0"><i class="bi bi-info-circle"></i> Utilisez les boutons <strong>Valider</strong> / <strong>Refuser</strong> en haut de la page pour traiter cette mutation.</p>
                    @else
                        <p class="text-muted small mb-0"><i class="bi bi-info-circle"></i> Cette mutation est en attente de validation par un administrateur.</p>
                    @endif
                @else
                    <p class="text-muted small mb-0">Aucune action disponible pour ce statut.</p>
                @endif
            </div>
        </div>

        {{-- Historique --}}
        <div class="card info-card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history"></i> Historique du terrain</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead style="background:#f8fafc;"><tr><th>Date</th><th>Proprietaire</th><th>Statut</th></tr></thead>
                    <tbody>
                        @foreach($mutation->parcelle->mutations()->with('nouveauProprietaire')->latest()->get() as $m)
                        <tr class="{{ $m->id === $mutation->id ? 'table-active' : '' }}">
                            <td><small>{{ $m->date_mutation?->format('d/m/Y') }}</small></td>
                            <td><small>{{ Str::limit($m->nouveauProprietaire?->nom_complet ?? '-', 25) }}</small></td>
                            <td><span class="badge badge-{{ $m->statut }}" style="font-size:10px;">{{ $m->statut }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Refuser --}}
@if(auth()->user()->hasRole('admin') && $mutation->statut === 'en_attente')
<div class="modal fade" id="modalRefuser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px;">
            <form method="POST" action="{{ route('mutations.refuser-directe', $mutation) }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-x-circle"></i> Refuser la mutation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small py-2" style="border-radius:10px;">
                        <i class="bi bi-exclamation-triangle"></i> La mutation sera marquee comme refusee et ne pourra plus etre traitee.
                    </div>
                    <label class="form-label fw-semibold small">Motif du refus <span class="text-danger">*</span></label>
                    <textarea name="motif_refus" class="form-control" rows="4" required minlength="3" maxlength="500" placeholder="Ex: Dossier incomplet, documents manquants, incoherence dans les informations..." style="border-radius:10px;"></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:10px;">Retour</button>
                    <button type="submit" class="btn btn-danger fw-bold" style="border-radius:10px;"><i class="bi bi-x-lg"></i> Confirmer le refus</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
