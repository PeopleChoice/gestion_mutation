@extends('layouts.app')
@section('title', 'Recherche')

@push('styles')
<style>
    .search-page-input {
        border: 2px solid #C8A951;
        border-radius: 25px;
        padding: 14px 20px 14px 48px;
        font-size: 16px;
        box-shadow: 0 4px 15px rgba(200,169,81,0.1);
        transition: all .3s;
    }
    .search-page-input:focus {
        box-shadow: 0 4px 25px rgba(200,169,81,0.25);
        border-color: #5D4E37;
    }
    .search-page-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        color: #C8A951;
    }
    .filter-tabs .nav-link {
        color: #666;
        border: 1px solid #e0e0e0;
        border-radius: 20px;
        padding: 5px 16px;
        margin-right: 8px;
        font-size: 13px;
        transition: all .2s;
    }
    .filter-tabs .nav-link.active {
        background: #5D4E37;
        border-color: #5D4E37;
        color: #fff;
    }
    .filter-tabs .nav-link .count {
        background: rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 1px 7px;
        font-size: 11px;
        margin-left: 5px;
    }
    .filter-tabs .nav-link.active .count { background: rgba(200,169,81,0.4); }
    .result-card {
        background: #fff;
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 12px;
        border: 1px solid #eee;
        transition: all .2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .result-card:hover {
        border-color: #C8A951;
        box-shadow: 0 4px 15px rgba(200,169,81,0.12);
        transform: translateY(-1px);
    }
    .result-type-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .result-type-icon.mutation { background: #fdf6e3; color: #C8A951; }
    .result-type-icon.parcelle { background: #eee8d5; color: #5D4E37; }
    .result-type-icon.proprietaire { background: #fff3e0; color: #e67e22; }
    .result-type-icon.projet { background: #f3e5f5; color: #8e44ad; }
    .result-meta { font-size: 12px; color: #888; }
    .result-meta span { margin-right: 15px; }
    .filters-panel {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #eee;
    }
    .section-title {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #5D4E37;
        font-weight: 700;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #C8A951;
    }
    .mutation-timeline { position: relative; padding-left: 20px; }
    .mutation-timeline::before {
        content: '';
        position: absolute;
        left: 6px; top: 0; bottom: 0;
        width: 2px;
        background: #e0d5b8;
    }
    .timeline-item { position: relative; padding-bottom: 8px; font-size: 12px; }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -18px; top: 5px;
        width: 10px; height: 10px;
        border-radius: 50%;
        border: 2px solid #C8A951;
        background: #fff;
    }
    .timeline-item.refusee::before { border-color: #dc3545; }
    .timeline-item.annulee::before { border-color: #6c757d; }
    .prop-box-ancien { background: #fdf6e3; }
    .prop-box-nouveau { background: #edf7ed; }
    .prop-box { padding: 8px 10px; border-radius: 8px; }
    .guide-card { border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-radius: 12px; }
    .guide-card .card-body { padding: 20px; }
    .guide-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
</style>
@endpush

@section('content')
<!-- Barre de recherche -->
<div class="row justify-content-center mb-4">
    <div class="col-md-10">
        <form method="GET" action="{{ route('recherche') }}" id="searchForm">
            <div class="position-relative">
                <i class="bi bi-search search-page-icon"></i>
                <input type="text" name="q" class="form-control search-page-input" value="{{ $q }}"
                    placeholder="Rechercher par lot, nom, CNI, NIN, téléphone, projet, commune, N° notification..."
                    autofocus>
            </div>

            <!-- Onglets par type -->
            <div class="d-flex align-items-center mt-3 flex-wrap gap-2">
                <div class="filter-tabs d-flex flex-wrap">
                    @php
                        $counts = [
                            'tout' => ($totalResultats ?? 0),
                            'mutation' => ($resultats['mutations'] ?? collect())->count(),
                            'parcelle' => ($resultats['parcelles'] ?? collect())->count(),
                            'proprietaire' => ($resultats['proprietaires'] ?? collect())->count(),
                            'projet' => ($resultats['projets'] ?? collect())->count(),
                        ];
                    @endphp
                    @foreach(['tout' => 'Tout', 'mutation' => 'Mutations', 'parcelle' => 'Parcelles', 'proprietaire' => 'Propriétaires', 'projet' => 'Projets'] as $key => $label)
                        <a href="{{ route('recherche', array_merge(request()->except('type'), ['type' => $key])) }}"
                           class="nav-link {{ ($type ?? 'tout') === $key ? 'active' : '' }}">
                            {{ $label }}
                            @if($q && $counts[$key] > 0)
                                <span class="count">{{ $counts[$key] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" data-bs-toggle="collapse" data-bs-target="#filtresAvances">
                    <i class="bi bi-funnel"></i> Filtres avancés
                </button>
            </div>

            <!-- Filtres avancés -->
            <div class="collapse {{ request()->hasAny(['statut', 'projet_id', 'commune_id', 'date_debut', 'date_fin']) ? 'show' : '' }}" id="filtresAvances">
                <div class="filters-panel mt-3">
                    <input type="hidden" name="type" value="{{ $type ?? 'tout' }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Statut</label>
                            <select name="statut" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <option value="validee" {{ request('statut') == 'validee' ? 'selected' : '' }}>Validée</option>
                                <option value="refusee" {{ request('statut') == 'refusee' ? 'selected' : '' }}>Refusée</option>
                                <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="annulee" {{ request('statut') == 'annulee' ? 'selected' : '' }}>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Projet</label>
                            <select name="projet_id" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                @foreach($projets as $projet)
                                    <option value="{{ $projet->id }}" {{ request('projet_id') == $projet->id ? 'selected' : '' }}>
                                        {{ $projet->nom }} - {{ $projet->commune->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Commune</label>
                            <select name="commune_id" class="form-select form-select-sm">
                                <option value="">Toutes</option>
                                @foreach($communes as $commune)
                                    <option value="{{ $commune->id }}" {{ request('commune_id') == $commune->id ? 'selected' : '' }}>
                                        {{ $commune->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Date début</label>
                            <input type="date" name="date_debut" class="form-control form-control-sm" value="{{ request('date_debut') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Date fin</label>
                            <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ request('date_fin') }}">
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm" style="background:#C8A951; color:#fff;"><i class="bi bi-search"></i> Rechercher</button>
                        <a href="{{ route('recherche') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if($q || request()->hasAny(['statut', 'projet_id', 'commune_id', 'date_debut']))
    <div class="mb-3">
        <span class="text-muted">
            <strong>{{ $totalResultats ?? 0 }}</strong> résultat(s) trouvé(s)
            @if($q) pour « <strong>{{ $q }}</strong> » @endif
        </span>
    </div>

    <!-- MUTATIONS -->
    @if(($resultats['mutations'] ?? collect())->count() > 0)
    <div class="mb-4">
        <div class="section-title"><i class="bi bi-arrow-left-right me-1"></i> Mutations ({{ $resultats['mutations']->count() }})</div>
        @foreach($resultats['mutations'] as $mutation)
        <div class="result-card">
            <div class="d-flex align-items-start">
                <div class="result-type-icon mutation me-3"><i class="bi bi-arrow-left-right"></i></div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">
                                <a href="{{ route('mutations.show', $mutation) }}" class="text-decoration-none" style="color:#5D4E37;">
                                    Mutation N°{{ $mutation->numero_notification ?? 'N/A' }}
                                    - Lot <strong>{{ $mutation->parcelle->numero_lot }}</strong>
                                </a>
                            </h6>
                            <div class="result-meta">
                                <span><i class="bi bi-building"></i> {{ $mutation->parcelle->projet->nom }}</span>
                                <span><i class="bi bi-pin-map"></i> {{ $mutation->parcelle->projet->commune->nom }}</span>
                                <span><i class="bi bi-calendar"></i> {{ $mutation->date_mutation?->format('d/m/Y') }}</span>
                                @if($mutation->validateur)
                                    <span><i class="bi bi-person-check"></i> {{ $mutation->validateur->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-{{ $mutation->statut }}">{{ ucfirst($mutation->statut) }}</span>
                            @if($mutation->statut === 'validee')
                                <a href="{{ route('mutations.apercu', $mutation) }}" class="btn btn-outline-secondary btn-sm" title="Voir le document"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('mutations.telecharger', $mutation) }}" class="btn btn-outline-danger btn-sm" title="PDF"><i class="bi bi-file-pdf"></i></a>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-2 g-2">
                        <div class="col-md-5">
                            <div class="prop-box prop-box-ancien">
                                <small class="text-muted d-block fw-bold">Ancien propriétaire</small>
                                @if($mutation->ancienProprietaire)
                                    <span class="fw-bold">{{ $mutation->ancienProprietaire->nom_complet }}</span><br>
                                    <small>CNI: {{ $mutation->ancienProprietaire->cni_passport ?? 'N/A' }}
                                    @if($mutation->ancienProprietaire->telephone) | Tel: {{ $mutation->ancienProprietaire->telephone }} @endif</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <i class="bi bi-arrow-right fs-5" style="color:#C8A951;"></i>
                        </div>
                        <div class="col-md-5">
                            <div class="prop-box prop-box-nouveau">
                                <small class="text-muted d-block fw-bold">Nouveau propriétaire</small>
                                @if($mutation->nouveauProprietaire)
                                    <span class="fw-bold">{{ $mutation->nouveauProprietaire->nom_complet }}</span><br>
                                    <small>CNI: {{ $mutation->nouveauProprietaire->cni_passport ?? 'N/A' }}
                                    @if($mutation->nouveauProprietaire->telephone) | Tel: {{ $mutation->nouveauProprietaire->telephone }} @endif</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($mutation->motif_refus)
                        <div class="mt-2 p-2 rounded" style="background:#ffebee;">
                            <small class="text-danger"><strong>Motif :</strong> {{ $mutation->motif_refus }}</small>
                        </div>
                    @endif
                    @if($mutation->ref_lettre)
                        <div class="mt-1"><small class="text-muted">Réf. : {{ $mutation->ref_lettre }}</small></div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- PARCELLES -->
    @if(($resultats['parcelles'] ?? collect())->count() > 0)
    <div class="mb-4">
        <div class="section-title"><i class="bi bi-geo-alt-fill me-1"></i> Parcelles ({{ $resultats['parcelles']->count() }})</div>
        @foreach($resultats['parcelles'] as $parcelle)
        <div class="result-card">
            <div class="d-flex align-items-start">
                <div class="result-type-icon parcelle me-3"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">
                        <a href="{{ route('parcelles.show', $parcelle) }}" class="text-decoration-none" style="color:#5D4E37;">
                            Lot <strong>{{ $parcelle->numero_lot }}</strong> - {{ $parcelle->projet->nom }}, {{ $parcelle->projet->commune->nom }}
                        </a>
                    </h6>
                    <div class="result-meta">
                        @if($parcelle->superficie) <span><i class="bi bi-rulers"></i> {{ $parcelle->superficie }} m²</span> @endif
                        @if($parcelle->usage) <span><i class="bi bi-house"></i> {{ ucfirst($parcelle->usage) }}</span> @endif
                        @if($parcelle->date_attribution) <span><i class="bi bi-calendar"></i> {{ $parcelle->date_attribution->format('d/m/Y') }}</span> @endif
                        <span><i class="bi bi-arrow-left-right"></i> {{ $parcelle->mutations->count() }} mutation(s)</span>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-5">
                            <div class="prop-box" style="background:#fdf6e3;">
                                <small class="text-muted d-block fw-bold">Propriétaire actuel</small>
                                @if($parcelle->proprietaire)
                                    <span class="fw-bold">{{ $parcelle->proprietaire->nom_complet }}</span><br>
                                    <small>CNI: {{ $parcelle->proprietaire->cni_passport ?? 'N/A' }}
                                    @if($parcelle->proprietaire->telephone) | Tel: {{ $parcelle->proprietaire->telephone }} @endif</small>
                                @else
                                    <span class="text-muted">Aucun</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-7">
                            @if($parcelle->mutations->count() > 0)
                                <small class="text-muted fw-bold d-block mb-1">Historique</small>
                                <div class="mutation-timeline">
                                    @foreach($parcelle->mutations->sortByDesc('date_mutation')->take(5) as $m)
                                        <div class="timeline-item {{ $m->statut }}">
                                            <strong>{{ $m->date_mutation?->format('d/m/Y') }}</strong>
                                            - {{ $m->nouveauProprietaire?->nom_complet ?? '-' }}
                                            <span class="badge badge-{{ $m->statut }}" style="font-size:9px;">{{ $m->statut }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- PROPRIÉTAIRES -->
    @if(($resultats['proprietaires'] ?? collect())->count() > 0)
    <div class="mb-4">
        <div class="section-title"><i class="bi bi-person-fill me-1"></i> Propriétaires ({{ $resultats['proprietaires']->count() }})</div>
        @foreach($resultats['proprietaires'] as $prop)
        <div class="result-card">
            <div class="d-flex align-items-start">
                <div class="result-type-icon proprietaire me-3"><i class="bi bi-person-fill"></i></div>
                <div class="flex-grow-1">
                    <h6 class="mb-1" style="color:#5D4E37;">{{ $prop->nom_complet }}</h6>
                    <div class="result-meta mb-2">
                        <span><i class="bi bi-credit-card"></i> CNI: {{ $prop->cni_passport ?? 'N/A' }}</span>
                        <span><i class="bi bi-fingerprint"></i> NIN: {{ $prop->nin ?? 'N/A' }}</span>
                        @if($prop->ninea) <span><i class="bi bi-briefcase"></i> NINEA: {{ $prop->ninea }}</span> @endif
                        <span><i class="bi bi-telephone"></i> {{ $prop->telephone ?? 'N/A' }}</span>
                    </div>
                    @if($prop->parcelles->count() > 0)
                        <div>
                            <small class="text-muted fw-bold">Parcelles :</small>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($prop->parcelles as $p)
                                    <a href="{{ route('parcelles.show', $p) }}" class="badge text-decoration-none" style="background:#5D4E37; font-size:11px;">
                                        Lot {{ $p->numero_lot }} - {{ $p->projet->nom }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- PROJETS -->
    @if(($resultats['projets'] ?? collect())->count() > 0)
    <div class="mb-4">
        <div class="section-title"><i class="bi bi-building me-1"></i> Projets ({{ $resultats['projets']->count() }})</div>
        @foreach($resultats['projets'] as $projet)
        <div class="result-card">
            <div class="d-flex align-items-start">
                <div class="result-type-icon projet me-3"><i class="bi bi-building"></i></div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">
                        <a href="{{ route('parcelles.index', ['projet_id' => $projet->id]) }}" class="text-decoration-none" style="color:#5D4E37;">
                            {{ $projet->nom }}
                        </a>
                    </h6>
                    <div class="result-meta">
                        <span><i class="bi bi-pin-map"></i> {{ $projet->commune->nom }}</span>
                        @if($projet->type_lotissement) <span><i class="bi bi-tag"></i> {{ $projet->type_lotissement }}</span> @endif
                        <span><i class="bi bi-geo-alt"></i> {{ $projet->parcelles_count }} parcelle(s)</span>
                    </div>
                    @if($projet->description)
                        <div class="mt-1"><small class="text-muted">{{ Str::limit($projet->description, 150) }}</small></div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if(($totalResultats ?? 0) === 0)
    <div class="text-center py-5">
        <i class="bi bi-search" style="font-size:48px; color:#C8A951; opacity:0.5;"></i>
        <h5 class="mt-3 text-muted">Aucun résultat trouvé</h5>
        <p class="text-muted">Essayez avec d'autres termes ou ajustez les filtres.</p>
    </div>
    @endif

@else
    <!-- Guide de recherche -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center py-4">
                <img src="{{ asset('images/dgid-logo.png') }}" style="width:120px; opacity:0.4;" alt="">
                <h5 class="mt-3" style="color:#5D4E37;">Recherche globale</h5>
                <p class="text-muted">Tapez pour rechercher dans toutes les données du système</p>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="card guide-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="guide-icon me-2" style="background:#fdf6e3; color:#C8A951;"><i class="bi bi-lightbulb"></i></div>
                                <h6 class="mb-0">Que chercher ?</h6>
                            </div>
                            <ul class="list-unstyled small text-muted mb-0">
                                <li class="mb-2"><i class="bi bi-geo-alt" style="color:#C8A951;"></i> <strong>Numéro de lot</strong> : 3900, CR-100...</li>
                                <li class="mb-2"><i class="bi bi-person" style="color:#C8A951;"></i> <strong>Nom / Prénom</strong> : SY, NDIAYE, Abdoul...</li>
                                <li class="mb-2"><i class="bi bi-credit-card" style="color:#C8A951;"></i> <strong>CNI / Passeport</strong> : numéro d'identité</li>
                                <li class="mb-2"><i class="bi bi-fingerprint" style="color:#C8A951;"></i> <strong>NIN / NINEA</strong> : identification nationale</li>
                                <li class="mb-2"><i class="bi bi-telephone" style="color:#C8A951;"></i> <strong>Téléphone</strong> : 77 561...</li>
                                <li class="mb-2"><i class="bi bi-building" style="color:#C8A951;"></i> <strong>Projet / Commune</strong> : RAVIN, Mont-Rolland...</li>
                                <li class="mb-2"><i class="bi bi-hash" style="color:#C8A951;"></i> <strong>N° Notification</strong> : 0000001</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card guide-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="guide-icon me-2" style="background:#eee8d5; color:#5D4E37;"><i class="bi bi-keyboard"></i></div>
                                <h6 class="mb-0">Raccourcis</h6>
                            </div>
                            <ul class="list-unstyled small text-muted mb-0">
                                <li class="mb-2"><kbd>Ctrl</kbd>+<kbd>K</kbd> Focus recherche rapide</li>
                                <li class="mb-2"><kbd>&uarr;</kbd> <kbd>&darr;</kbd> Naviguer les suggestions</li>
                                <li class="mb-2"><kbd>Entrée</kbd> Ouvrir le résultat</li>
                                <li class="mb-2"><kbd>Echap</kbd> Fermer les suggestions</li>
                            </ul>
                            <hr>
                            <div class="d-flex align-items-center mb-2">
                                <div class="guide-icon me-2" style="background:#fdf6e3; color:#C8A951;"><i class="bi bi-funnel"></i></div>
                                <h6 class="mb-0">Filtres avancés</h6>
                            </div>
                            <p class="small text-muted mb-0">
                                Combinez statut, projet, commune et période pour affiner vos résultats.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
