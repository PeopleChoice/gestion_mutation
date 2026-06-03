@extends('layouts.app')
@section('title', 'Import - ' . $import->nom_fichier)

@push('styles')
<style>
    .progress-bar-mutations { height: 8px; border-radius: 4px; }
    .ligne-row.selected { background: #e8f5e9 !important; }
    .ligne-row.traitee { opacity: 0.55; }
    .ligne-row.vierge { background: #fff8e1; }
    .ligne-row.no-match { background: #fafafa; }
    .actions-globales { position: sticky; top: 0; z-index: 10; background: #fff; border-bottom: 2px solid #e0e0e0; }
    .compteur-box { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .compteur-box.attente { background: #fff3e0; color: #e65100; }
    .compteur-box.validee { background: #e8f5e9; color: #2e7d32; }
    .compteur-box.refusee { background: #ffebee; color: #c62828; }
    .compteur-box.no-match { background: #f5f5f5; color: #757575; }
    .selection-info { display: none; background: #e3f2fd; padding: 10px 16px; border-radius: 8px; font-size: 13px; }
    .selection-info.show { display: flex; }

    /* Tableau amélioré */
    .lignes-table thead th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; padding: 10px 12px; }
    .lignes-table td { vertical-align: middle; padding: 12px; font-size: 13px; }
    .lignes-table tbody tr { border-bottom: 1px solid #f1f5f9; }
    .lignes-table tbody tr:hover:not(.traitee) { background: #f8fafc; }
    .lot-badge { display: inline-block; background: #5D4E37; color: #fff; font-weight: 700; font-size: 13px; padding: 4px 10px; border-radius: 6px; min-width: 60px; text-align: center; }
    .person-chip { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 3px; }
    .chip-nouveau { background: #dcfce7; color: #166534; }
    .chip-demandeur { background: #dbeafe; color: #1e40af; }
    .chip-ancien { background: #fef3c7; color: #92400e; }
    .chip-vierge { background: #fee2e2; color: #991b1b; }
    .person-name { font-weight: 700; color: #1e293b; font-size: 13px; line-height: 1.2; }
    .person-meta { font-size: 11px; color: #64748b; line-height: 1.3; margin-top: 2px; }
    .piece-formatee { font-family: 'Courier New', monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 11px; color: #334155; display: inline-block; }
    .filter-tabs { display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 8px; gap: 2px; }
    .filter-tabs button { border: none; background: transparent; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; }
    .filter-tabs button.active { background: #fff; color: #5D4E37; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    .filter-tabs button:hover { color: #5D4E37; }
</style>
@endpush

@section('content')
@php
    $enAttente = $import->lignes->where('statut', 'en_attente')->where('matched', true);
    $validees = $import->lignes->where('statut', 'validee');
    $refusees = $import->lignes->where('statut', 'refusee');
    $nonMatchees = $import->lignes->where('matched', false);
    $totalTraitees = $validees->count() + $refusees->count();
    $totalTraitable = $import->lignes->where('matched', true)->count();
    $pctTraite = $totalTraitable > 0 ? round(($totalTraitees / $totalTraitable) * 100) : 0;
@endphp

<!-- En-tête import -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h6 class="mb-1">{{ $import->nom_fichier }}</h6>
                <span class="text-muted">{{ $import->projet->nom }} - {{ $import->projet->commune->nom }}</span><br>
                <small class="text-muted">Importé par {{ $import->importeur->name }} le {{ $import->created_at->format('d/m/Y H:i') }}</small>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2 flex-wrap">
                    <span class="compteur-box attente" id="cptAttente">
                        <i class="bi bi-hourglass-split"></i> <span>{{ $enAttente->count() }}</span> en attente
                    </span>
                    <span class="compteur-box validee" id="cptValidee">
                        <i class="bi bi-check-circle"></i> <span>{{ $validees->count() }}</span> validée(s)
                    </span>
                    <span class="compteur-box refusee" id="cptRefusee">
                        <i class="bi bi-x-circle"></i> <span>{{ $refusees->count() }}</span> refusée(s)
                    </span>
                    @if($nonMatchees->count() > 0)
                    <span class="compteur-box no-match">
                        <i class="bi bi-question-circle"></i> {{ $nonMatchees->count() }} sans correspondance
                    </span>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <small class="fw-bold">Progression :</small>
                    <small class="text-muted">{{ $totalTraitees }}/{{ $totalTraitable }}</small>
                    <small class="fw-bold text-success">{{ $pctTraite }}%</small>
                </div>
                <div class="progress progress-bar-mutations">
                    <div class="progress-bar bg-success" id="progressBar" style="width: {{ $pctTraite }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barre d'actions globales -->
<div class="card mb-3 actions-globales">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label small fw-bold" for="selectAll">Tout sélectionner</label>
                </div>

                <!-- Info sélection -->
                <div class="selection-info align-items-center gap-2" id="selectionInfo">
                    <i class="bi bi-check2-square text-primary"></i>
                    <span><strong id="selectionCount">0</strong> ligne(s) sélectionnée(s)</span>
                    <button class="btn btn-success btn-sm ms-2" id="btnValiderSelection" title="Valider la sélection">
                        <i class="bi bi-check-lg"></i> Valider la sélection
                    </button>
                    <button class="btn btn-danger btn-sm" id="btnRefuserSelection" title="Refuser la sélection">
                        <i class="bi bi-x-lg"></i> Refuser la sélection
                    </button>
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                @php $nbValidees = $validees->count(); @endphp
                @if($nbValidees > 0)
                    <a href="{{ route('imports.pdfs-fusionnes', $import) }}" target="_blank"
                       class="btn btn-sm" style="background:#dc2626; color:#fff;"
                       title="Visualiser/Imprimer tous les documents validés (PDF fusionné)">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Imprimer tous les PDF ({{ $nbValidees }})
                    </a>
                @endif

                @if($enAttente->count() > 0)
                <button class="btn btn-success btn-sm" id="btnValiderTout">
                    <i class="bi bi-check2-all"></i> Valider tout ({{ $enAttente->count() }})
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnRefuserTout">
                    <i class="bi bi-x-lg"></i> Refuser tout ({{ $enAttente->count() }})
                </button>
                @else
                <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Tout traité</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Compteur N° notification -->
@if($enAttente->count() > 0)
<div class="card mb-3" style="border:2px dashed #d97706; border-radius:10px; background:#fffbeb;">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <strong style="color:#92400e;"><i class="bi bi-hash"></i> N° notification de départ</strong>
                <small class="text-muted d-block">Saisissez le premier numéro à utiliser. Il s'incrémentera automatiquement à chaque validation.</small>
            </div>
            <input type="text" id="numNotifDepart" class="form-control" style="max-width:200px; font-family:'Courier New',monospace; font-size:16px; font-weight:bold; border-color:#d97706;" placeholder="Ex: 0000001" value="{{ str_pad((string) (\App\Models\Mutation::max('id') + 1), 7, '0', STR_PAD_LEFT) }}">
            <button type="button" class="btn btn-sm" id="btnSetNumNotif" style="background:#d97706; color:#fff;">
                <i class="bi bi-check-lg"></i> Définir
            </button>
            <span class="ms-auto small text-muted">
                Prochain N° :
                <strong id="prochainNotif" style="font-family:'Courier New',monospace; color:#92400e; font-size:14px;">— (cliquez sur Définir)</strong>
            </span>
        </div>
    </div>
</div>
@endif

<!-- Filtres rapides -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="filter-tabs">
        <button type="button" class="active" data-filter="tous">Tous</button>
        <button type="button" data-filter="mutable">Mutables</button>
        <button type="button" data-filter="vierge">Vierges</button>
        <button type="button" data-filter="no-match">Inexistants</button>
        <button type="button" data-filter="traitee">Traités</button>
    </div>
    <div>
        <input type="text" id="searchLigne" class="form-control form-control-sm" placeholder="Rechercher lot, nom..." style="width:240px; border-radius:8px;">
    </div>
</div>

<!-- Tableau des lignes -->
<div class="card" style="border:none; box-shadow:0 2px 12px rgba(0,0,0,0.04); border-radius:12px;">
    <div class="card-body p-0">
        <table class="table mb-0 lignes-table" id="lignesTable">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th style="width:50px;">N°</th>
                    <th style="width:80px;">Lot</th>
                    <th>Précédent propriétaire</th>
                    <th>Nouveau attributaire</th>
                    <th>Demandeur</th>
                    <th style="width:120px;">Statut</th>
                    <th style="width:110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($import->lignes->sortBy('numero_ordre') as $ligne)
                @php
                    $isVierge = $ligne->matched && !$ligne->parcelle?->proprietaire_id;
                    $isMutable = $ligne->matched && $ligne->parcelle?->proprietaire_id && $ligne->statut === 'en_attente';
                    $rowClass = '';
                    if ($ligne->statut !== 'en_attente') $rowClass = 'traitee';
                    elseif (!$ligne->matched) $rowClass = 'no-match';
                    elseif ($isVierge) $rowClass = 'vierge';

                    $filterValue = 'tous';
                    if ($ligne->statut !== 'en_attente') $filterValue = 'traitee';
                    elseif (!$ligne->matched) $filterValue = 'no-match';
                    elseif ($isVierge) $filterValue = 'vierge';
                    elseif ($isMutable) $filterValue = 'mutable';

                    $searchText = strtolower(($ligne->numero_lot ?? '') . ' ' . ($ligne->prenom ?? '') . ' ' . ($ligne->nom ?? '') . ' ' . ($ligne->demandeur_prenom ?? '') . ' ' . ($ligne->demandeur_nom ?? '') . ' ' . ($ligne->cni_passport ?? ''));
                @endphp
                <tr id="ligne-{{ $ligne->id }}"
                    class="ligne-row {{ $rowClass }}"
                    data-id="{{ $ligne->id }}"
                    data-matched="{{ $ligne->matched ? '1' : '0' }}"
                    data-vierge="{{ $isVierge ? '1' : '0' }}"
                    data-statut="{{ $ligne->statut }}"
                    data-filter="{{ $filterValue }}"
                    data-search="{{ $searchText }}">

                    <td>
                        @if($isMutable)
                            <input type="checkbox" class="form-check-input cb-ligne" value="{{ $ligne->id }}">
                        @endif
                    </td>
                    <td><span class="text-muted">{{ $ligne->numero_ordre }}</span></td>
                    <td><span class="lot-badge">{{ $ligne->numero_lot }}</span></td>

                    {{-- Précédent (depuis la base) --}}
                    <td>
                        @if(!$ligne->matched)
                            <span class="text-muted small fst-italic">Lot inexistant dans le projet</span>
                        @elseif($ligne->parcelle?->proprietaire)
                            <span class="person-chip chip-ancien">Précédent</span>
                            <div class="person-name">{{ $ligne->parcelle->proprietaire->nom_complet }}</div>
                            <div class="person-meta">
                                @if($ligne->parcelle->proprietaire->cni_passport)
                                    <span class="piece-formatee">{{ $ligne->parcelle->proprietaire->type_piece ?? '' }}{{ $ligne->parcelle->proprietaire->cni_passport ? ' n° ' . $ligne->parcelle->proprietaire->cni_passport : '' }}</span>
                                @endif
                                @if($ligne->parcelle->proprietaire->telephone)
                                    <br>📞 {{ $ligne->parcelle->proprietaire->telephone }}
                                @endif
                            </div>
                        @else
                            <span class="person-chip chip-vierge"><i class="bi bi-exclamation-triangle"></i> Parcelle vierge</span>
                            <div class="person-meta fst-italic">Aucun propriétaire — non mutable</div>
                        @endif
                    </td>

                    {{-- Nouveau attributaire (depuis l'Excel) --}}
                    <td>
                        @if($ligne->prenom || $ligne->nom)
                            <span class="person-chip chip-nouveau">Nouveau</span>
                            <div class="person-name">{{ trim(($ligne->civilite ?? '') . ' ' . ($ligne->prenom ?? '') . ' ' . ($ligne->nom ?? '')) }}</div>
                            <div class="person-meta">
                                @if($ligne->cni_passport)
                                    <span class="piece-formatee">{{ $ligne->type_piece }}{{ $ligne->code_paye ? '_' . $ligne->code_paye : '' }} n° {{ $ligne->cni_passport }}</span>
                                @endif
                                @if($ligne->telephone)
                                    <br>📞 {{ $ligne->telephone }}
                                @endif
                                @if($ligne->ninea)
                                    <br>NINEA: {{ $ligne->ninea }}
                                @endif
                            </div>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>

                    {{-- Demandeur --}}
                    <td>
                        @if($ligne->demandeur_prenom || $ligne->demandeur_nom)
                            <span class="person-chip chip-demandeur">Demandeur</span>
                            <div class="person-name">{{ trim(($ligne->demandeur_prenom ?? '') . ' ' . ($ligne->demandeur_nom ?? '')) }}</div>
                            @if($ligne->demandeur_telephone)
                                <div class="person-meta">📞 {{ $ligne->demandeur_telephone }}</div>
                            @endif
                        @else
                            <span class="text-muted small fst-italic">Non renseigné</span>
                        @endif
                    </td>

                    {{-- Statut --}}
                    <td>
                        <span class="statut-badge" id="statut-{{ $ligne->id }}">
                            @if($ligne->statut === 'validee')
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Validée</span>
                            @elseif($ligne->statut === 'refusee')
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Refusée</span>
                            @elseif(!$ligne->matched)
                                <span class="badge bg-warning text-dark"><i class="bi bi-question-circle"></i> Inexistant</span>
                            @elseif($isVierge)
                                <span class="badge" style="background:#fbbf24; color:#78350f;"><i class="bi bi-exclamation-triangle"></i> Vierge</span>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-hourglass"></i> En attente</span>
                            @endif
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td id="actions-{{ $ligne->id }}">
                        @if($isMutable)
                            <button class="btn btn-sm btn-success btn-valider"
                                data-ligne-id="{{ $ligne->id }}"
                                data-lot="{{ $ligne->numero_lot }}"
                                data-civilite="{{ $ligne->civilite }}"
                                data-prenom="{{ $ligne->prenom }}"
                                data-nom="{{ $ligne->nom }}"
                                data-type-piece="{{ $ligne->type_piece }}"
                                data-code-paye="{{ $ligne->code_paye }}"
                                data-cni="{{ $ligne->cni_passport }}"
                                data-ninea="{{ $ligne->ninea }}"
                                data-telephone="{{ $ligne->telephone }}"
                                data-demandeur-prenom="{{ $ligne->demandeur_prenom }}"
                                data-demandeur-nom="{{ $ligne->demandeur_nom }}"
                                data-demandeur-telephone="{{ $ligne->demandeur_telephone }}"
                                data-ref="{{ $ligne->ref_lettre }}"
                                title="Valider cette mutation">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-refuser"
                                data-ligne-id="{{ $ligne->id }}"
                                data-lot="{{ $ligne->numero_lot }}"
                                title="Refuser cette mutation">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @elseif($ligne->statut === 'en_attente' && $isVierge && ($ligne->prenom || $ligne->nom || $ligne->cni_passport))
                            <button class="btn btn-sm btn-info text-white btn-attribuer"
                                data-ligne-id="{{ $ligne->id }}"
                                data-lot="{{ $ligne->numero_lot }}"
                                data-civilite="{{ $ligne->civilite }}"
                                data-prenom="{{ $ligne->prenom }}"
                                data-nom="{{ $ligne->nom }}"
                                data-type-piece="{{ $ligne->type_piece }}"
                                data-code-paye="{{ $ligne->code_paye }}"
                                data-cni="{{ $ligne->cni_passport }}"
                                data-ninea="{{ $ligne->ninea }}"
                                data-telephone="{{ $ligne->telephone }}"
                                title="Attribuer cette parcelle vierge">
                                <i class="bi bi-person-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-refuser-attr"
                                data-ligne-id="{{ $ligne->id }}"
                                data-lot="{{ $ligne->numero_lot }}"
                                title="Refuser">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @elseif($ligne->statut === 'en_attente' && $isVierge)
                            <span class="text-muted small fst-italic" title="Aucune info attributaire dans le fichier"><i class="bi bi-lock"></i> non mutable</span>
                        @elseif($ligne->statut === 'en_attente' && !$ligne->matched)
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- ========== MODAL : Validation unitaire ========== -->
<div class="modal fade" id="modalValider" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle"></i> Valider la mutation - Lot <span id="modal-lot"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i> Nouveau <strong>attributaire</strong> (bénéficiaire) + <strong>demandeur</strong> (souvent = précédent propriétaire).
                </div>
                <form id="formValider">
                    <input type="hidden" id="val-ligne-id">

                    <div class="row g-3 mb-3 p-2" style="background:#fffbeb; border:2px dashed #d97706; border-radius:8px;">
                        <div class="col-12">
                            <label class="form-label fw-bold mb-1" style="color:#92400e;">
                                <i class="bi bi-hash"></i> N° de notification <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="val-num-notif" class="form-control" required placeholder="Ex: 0000001"
                                style="font-family:'Courier New',monospace; font-size:16px; font-weight:bold;">
                            <small class="text-muted">Numéro à imprimer sur le PDF de notification.</small>
                        </div>
                    </div>

                    <h6 class="text-success fw-bold mt-1 mb-2"><i class="bi bi-person-check"></i> Nouveau attributaire</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Civilité <span class="text-danger">*</span></label>
                            <select id="val-civilite" class="form-select" required>
                                <option value="Monsieur">Monsieur</option>
                                <option value="Madame">Madame</option>
                                <option value="Société">Société</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" id="val-prenom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" id="val-nom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type pièce <span class="text-danger">*</span></label>
                            <select id="val-type-piece" class="form-select" required>
                                <option value="CNI">CNI</option>
                                <option value="Passeport">Passeport</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Code payé</label>
                            <input type="text" id="val-code-paye" class="form-control" placeholder="Ex: SN">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">N° pièce <span class="text-danger">*</span></label>
                            <input type="text" id="val-cni" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="text" id="val-telephone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NINEA (Société)</label>
                            <input type="text" id="val-ninea" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Adresse</label>
                            <input type="text" id="val-adresse" class="form-control">
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mt-3 mb-2"><i class="bi bi-person-lines-fill"></i> Demandeur</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Prénom demandeur</label>
                            <input type="text" id="val-demandeur-prenom" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NOM demandeur</label>
                            <input type="text" id="val-demandeur-nom" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tél demandeur</label>
                            <input type="text" id="val-demandeur-telephone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Réf. Lettre</label>
                            <input type="text" id="val-ref" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnConfirmValider">
                    <i class="bi bi-check-lg"></i> Confirmer la mutation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== MODAL : Attribution d'une parcelle vierge ========== -->
<div class="modal fade" id="modalAttribuer" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Attribuer la parcelle vierge — Lot <span id="modal-lot-attr"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small py-2">
                    <i class="bi bi-info-circle"></i>
                    Cette parcelle n'a pas de propriétaire actuel. Vous allez l'attribuer pour la première fois — pas de mutation.
                </div>
                <input type="hidden" id="attr-ligne-id">
                <div class="row g-3 mb-3 p-2" style="background:#fffbeb; border:2px dashed #d97706; border-radius:8px;">
                    <div class="col-12">
                        <label class="form-label fw-bold mb-1" style="color:#92400e;">
                            <i class="bi bi-hash"></i> N° de notification <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="attr-num-notif" class="form-control" required placeholder="Ex: 0000001"
                            style="font-family:'Courier New',monospace; font-size:16px; font-weight:bold;">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Civilité <span class="text-danger">*</span></label>
                        <select id="attr-civilite" class="form-select" required>
                            <option value="Monsieur">Monsieur</option>
                            <option value="Madame">Madame</option>
                            <option value="Société">Société</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="attr-prenom" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nom</label>
                        <input type="text" id="attr-nom" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type pièce <span class="text-danger">*</span></label>
                        <select id="attr-type-piece" class="form-select" required>
                            <option value="CNI">CNI</option>
                            <option value="Passeport">Passeport</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code payé</label>
                        <input type="text" id="attr-code-paye" class="form-control" placeholder="Ex: SN">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">N° pièce <span class="text-danger">*</span></label>
                        <input type="text" id="attr-cni" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="text" id="attr-telephone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NINEA</label>
                        <input type="text" id="attr-ninea" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-info text-white" id="btnConfirmAttribuer">
                    <i class="bi bi-check-lg"></i> Confirmer l'attribution
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== MODAL : Refus unitaire ========== -->
<div class="modal fade" id="modalRefuser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> Refuser la mutation - Lot <span id="modal-lot-refus"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ref-ligne-id">
                <div class="mb-3">
                    <label class="form-label">Motif du refus <span class="text-danger">*</span></label>
                    <textarea id="ref-motif" class="form-control" rows="3" required placeholder="Indiquez le motif du refus..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="btnConfirmRefuser">
                    <i class="bi bi-x-lg"></i> Confirmer le refus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== MODAL : Validation globale ========== -->
<div class="modal fade" id="modalValiderTout" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check2-all"></i> Valider toutes les mutations</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Attention !</strong> Vous allez valider <strong id="countValiderTout">{{ $enAttente->count() }}</strong> mutation(s) en une seule fois.
                    Les données du fichier Excel seront utilisées pour créer les nouveaux propriétaires.
                </div>
                <div class="mb-3">
                    <label class="form-label">Civilité par défaut</label>
                    <select id="vt-civilite" class="form-select">
                        <option value="Monsieur">Monsieur</option>
                        <option value="Madame">Madame</option>
                        <option value="Société">Société</option>
                    </select>
                    <small class="text-muted">Sera utilisée uniquement si la civilité n'est pas renseignée dans le fichier.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Réf. Lettre globale (optionnel)</label>
                    <input type="text" id="vt-ref" class="form-control" placeholder="ex: LM-2026-0001">
                </div>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> Chaque mutation recevra un numéro de notification unique automatiquement.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnConfirmValiderTout">
                    <i class="bi bi-check2-all"></i> Confirmer - Valider tout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== MODAL : Refus global ========== -->
<div class="modal fade" id="modalRefuserTout" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> Refuser toutes les mutations</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Attention !</strong> Vous allez refuser <strong id="countRefuserTout">{{ $enAttente->count() }}</strong> mutation(s) en une seule fois.
                    Cette action est irréversible sans intervention de l'administrateur.
                </div>
                <div class="mb-3">
                    <label class="form-label">Motif du refus global <span class="text-danger">*</span></label>
                    <textarea id="rt-motif" class="form-control" rows="3" required placeholder="Indiquez le motif applicable à toutes les mutations..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="btnConfirmRefuserTout">
                    <i class="bi bi-x-circle"></i> Confirmer - Refuser tout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== MODAL : Refus de sélection ========== -->
<div class="modal fade" id="modalRefuserSelection" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> Refuser la sélection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Vous allez refuser <strong id="countRefuserSel">0</strong> mutation(s) sélectionnée(s).
                </div>
                <div class="mb-3">
                    <label class="form-label">Motif du refus <span class="text-danger">*</span></label>
                    <textarea id="rs-motif" class="form-control" rows="3" required placeholder="Motif du refus..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="btnConfirmRefuserSelection">
                    <i class="bi bi-x-circle"></i> Confirmer le refus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== MODAL : Confirmation validation sélection ========== -->
<div class="modal fade" id="modalValiderSelection" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check2-all"></i> Valider la sélection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Vous allez valider <strong id="countValiderSel">0</strong> mutation(s) sélectionnée(s).
                    Les données du fichier Excel seront utilisées pour créer les nouveaux propriétaires.
                </div>
                <p class="text-muted small">Chaque mutation recevra un numéro de notification unique automatiquement.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnConfirmValiderSelection">
                    <i class="bi bi-check2-all"></i> Confirmer la validation
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const importId = {{ $import->id }};

    // =====================
    // COMPTEUR N° NOTIFICATION
    // =====================
    let counterNotif = null;
    const inputDepart = document.getElementById('numNotifDepart');
    const prochainEl = document.getElementById('prochainNotif');
    const btnSet = document.getElementById('btnSetNumNotif');

    function padNum(n) { return String(n).padStart(7, '0'); }
    function refreshProchain() {
        if (prochainEl) prochainEl.textContent = counterNotif !== null ? padNum(counterNotif) : '— (cliquez sur Définir)';
    }
    if (btnSet) {
        btnSet.addEventListener('click', function() {
            const v = inputDepart.value.trim();
            const n = parseInt(v, 10);
            if (isNaN(n) || n < 0) { showToast('Numéro invalide.', 'warning'); return; }
            counterNotif = n;
            refreshProchain();
            inputDepart.disabled = true;
            this.innerHTML = '<i class="bi bi-pencil"></i> Modifier';
            this.onclick = function() {
                inputDepart.disabled = false;
                this.innerHTML = '<i class="bi bi-check-lg"></i> Définir';
                this.onclick = null;
                counterNotif = null;
                refreshProchain();
            };
        });
    }
    function consumeNotif() {
        if (counterNotif === null) return null;
        const n = counterNotif;
        counterNotif++;
        refreshProchain();
        return padNum(n);
    }

    // =====================
    // FILTRES + RECHERCHE
    // =====================
    const filterBtns = document.querySelectorAll('.filter-tabs button');
    const searchInput = document.getElementById('searchLigne');
    let currentFilter = 'tous';

    function applyFilters() {
        const search = (searchInput?.value || '').toLowerCase().trim();
        document.querySelectorAll('.ligne-row').forEach(row => {
            const matchFilter = currentFilter === 'tous' || row.dataset.filter === currentFilter;
            const matchSearch = !search || (row.dataset.search || '').includes(search);
            row.style.display = matchFilter && matchSearch ? '' : 'none';
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });
    if (searchInput) searchInput.addEventListener('input', applyFilters);

    // =====================
    // SÉLECTION / CHECKBOXES
    // =====================
    const selectAll = document.getElementById('selectAll');
    const selectionInfo = document.getElementById('selectionInfo');
    const selectionCount = document.getElementById('selectionCount');

    function getCheckboxes() {
        return document.querySelectorAll('.cb-ligne');
    }

    function getSelected() {
        return [...document.querySelectorAll('.cb-ligne:checked')].map(cb => cb.value);
    }

    function updateSelectionUI() {
        const selected = getSelected();
        const count = selected.length;
        selectionCount.textContent = count;
        selectionInfo.classList.toggle('show', count > 0);

        // Highlight rows
        document.querySelectorAll('.ligne-row').forEach(row => {
            const cb = row.querySelector('.cb-ligne');
            if (cb) row.classList.toggle('selected', cb.checked);
        });
    }

    selectAll.addEventListener('change', function() {
        getCheckboxes().forEach(cb => { cb.checked = this.checked; });
        updateSelectionUI();
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('cb-ligne')) updateSelectionUI();
    });

    // =====================
    // HELPERS
    // =====================
    function marquerLigneTraitee(ligneId, statut, mutationId, numNotif) {
        const row = document.getElementById('ligne-' + ligneId);
        if (!row) return;

        row.classList.add('traitee');
        row.classList.remove('selected');
        row.dataset.statut = statut;

        // Supprimer checkbox
        const cb = row.querySelector('.cb-ligne');
        if (cb) cb.remove();

        // Statut
        const statutEl = document.getElementById('statut-' + ligneId);
        if (statut === 'validee') {
            statutEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Validée</span>';
        } else {
            statutEl.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Refusée</span>';
        }

        // Actions
        const actionsEl = document.getElementById('actions-' + ligneId);
        if (statut === 'validee' && mutationId) {
            actionsEl.innerHTML = `<a href="/mutations/${mutationId}/apercu" class="btn btn-sm btn-outline-primary" title="Visualiser"><i class="bi bi-eye"></i> Voir</a>
                <a href="/mutations/${mutationId}/telecharger" class="btn btn-sm btn-outline-danger" title="Télécharger"><i class="bi bi-download"></i></a>`;
        } else {
            actionsEl.innerHTML = '<span class="text-danger small">Refusée</span>';
        }
    }

    function updateCompteurs(validees, refusees) {
        const cptAttenteEl = document.querySelector('#cptAttente span');
        const cptValideeEl = document.querySelector('#cptValidee span');
        const cptRefuseeEl = document.querySelector('#cptRefusee span');
        const curAttente = parseInt(cptAttenteEl.textContent);
        const curValidee = parseInt(cptValideeEl.textContent);
        const curRefusee = parseInt(cptRefuseeEl.textContent);

        cptAttenteEl.textContent = curAttente - validees - refusees;
        cptValideeEl.textContent = curValidee + validees;
        cptRefuseeEl.textContent = curRefusee + refusees;

        // Progress
        const totalTraitable = {{ $totalTraitable }};
        const newTraitees = curValidee + validees + curRefusee + refusees;
        const pct = totalTraitable > 0 ? Math.round((newTraitees / totalTraitable) * 100) : 0;
        document.getElementById('progressBar').style.width = pct + '%';

        // Masquer boutons globaux si tout est traité
        const restant = curAttente - validees - refusees;
        if (restant <= 0) {
            document.getElementById('btnValiderTout')?.remove();
            document.getElementById('btnRefuserTout')?.remove();
        } else {
            const btnVT = document.getElementById('btnValiderTout');
            const btnRT = document.getElementById('btnRefuserTout');
            if (btnVT) btnVT.innerHTML = `<i class="bi bi-check2-all"></i> Valider tout (${restant})`;
            if (btnRT) btnRT.innerHTML = `<i class="bi bi-x-lg"></i> Refuser tout (${restant})`;
            if (document.getElementById('countValiderTout')) document.getElementById('countValiderTout').textContent = restant;
            if (document.getElementById('countRefuserTout')) document.getElementById('countRefuserTout').textContent = restant;
        }

        updateSelectionUI();
    }

    // =====================
    // VALIDATION UNITAIRE
    // =====================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-valider');
        if (!btn) return;
        document.getElementById('val-ligne-id').value = btn.dataset.ligneId;
        document.getElementById('modal-lot').textContent = btn.dataset.lot;
        document.getElementById('val-civilite').value = btn.dataset.civilite || 'Monsieur';
        document.getElementById('val-prenom').value = btn.dataset.prenom || '';
        document.getElementById('val-nom').value = btn.dataset.nom || '';
        document.getElementById('val-type-piece').value = btn.dataset.typePiece || 'CNI';
        document.getElementById('val-code-paye').value = btn.dataset.codePaye || '';
        document.getElementById('val-cni').value = btn.dataset.cni || '';
        document.getElementById('val-ninea').value = btn.dataset.ninea || '';
        document.getElementById('val-telephone').value = btn.dataset.telephone || '';
        document.getElementById('val-adresse').value = '';
        document.getElementById('val-demandeur-prenom').value = btn.dataset.demandeurPrenom || '';
        document.getElementById('val-demandeur-nom').value = btn.dataset.demandeurNom || '';
        document.getElementById('val-demandeur-telephone').value = btn.dataset.demandeurTelephone || '';
        document.getElementById('val-ref').value = btn.dataset.ref || '';
        // Pré-remplir le N° de notification avec la valeur du compteur (sans consommer)
        const notifInput = document.getElementById('val-num-notif');
        if (notifInput) {
            notifInput.value = counterNotif !== null ? padNum(counterNotif) : '';
            notifInput.style.background = counterNotif !== null ? '#fef3c7' : '#fff';
        }
        new bootstrap.Modal(document.getElementById('modalValider')).show();
    });

    document.getElementById('btnConfirmValider').addEventListener('click', function() {
        const ligneId = document.getElementById('val-ligne-id').value;
        const prenom = document.getElementById('val-prenom').value.trim();
        const nom = document.getElementById('val-nom').value.trim();
        const cni = document.getElementById('val-cni').value.trim();
        const numNotif = document.getElementById('val-num-notif').value.trim();

        if (!prenom || !nom || !cni) {
            showToast('Veuillez remplir les champs obligatoires (Prénom, Nom, CNI/Passeport).', 'warning');
            return;
        }
        if (!numNotif) {
            showToast('N° de notification est obligatoire.', 'warning');
            document.getElementById('val-num-notif').focus();
            return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Traitement...';

        fetch(`/mutations/valider/${ligneId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                civilite: document.getElementById('val-civilite').value,
                prenom, nom, cni_passport: cni,
                type_piece: document.getElementById('val-type-piece').value,
                ninea: document.getElementById('val-ninea').value,
                telephone: document.getElementById('val-telephone').value,
                adresse: document.getElementById('val-adresse').value,
                code_paye: document.getElementById('val-code-paye').value,
                demandeur_prenom: document.getElementById('val-demandeur-prenom').value,
                demandeur_nom: document.getElementById('val-demandeur-nom').value,
                demandeur_telephone: document.getElementById('val-demandeur-telephone').value,
                ref_lettre: document.getElementById('val-ref').value,
                numero_notification: numNotif,
            })
        })
        .then(r => r.json())
        .then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-lg"></i> Confirmer la mutation';
            if (res.success) {
                marquerLigneTraitee(ligneId, 'validee', res.mutation_id, res.numero_notification);
                updateCompteurs(1, 0);
                if (counterNotif !== null) consumeNotif();
                bootstrap.Modal.getInstance(document.getElementById('modalValider')).hide();
            } else {
                showToast(res.message || 'Erreur.', 'danger');
            }
        })
        .catch(() => { this.disabled = false; this.innerHTML = '<i class="bi bi-check-lg"></i> Confirmer la mutation'; showToast('Erreur de connexion.', 'danger'); });
    });

    // =====================
    // ATTRIBUTION (parcelle vierge avec données attributaire)
    // =====================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-attribuer');
        if (!btn) return;
        document.getElementById('attr-ligne-id').value = btn.dataset.ligneId;
        document.getElementById('modal-lot-attr').textContent = btn.dataset.lot;
        document.getElementById('attr-civilite').value = btn.dataset.civilite || 'Monsieur';
        document.getElementById('attr-prenom').value = btn.dataset.prenom || '';
        document.getElementById('attr-nom').value = btn.dataset.nom || '';
        document.getElementById('attr-type-piece').value = btn.dataset.typePiece || 'CNI';
        document.getElementById('attr-code-paye').value = btn.dataset.codePaye || '';
        document.getElementById('attr-cni').value = btn.dataset.cni || '';
        document.getElementById('attr-ninea').value = btn.dataset.ninea || '';
        document.getElementById('attr-telephone').value = btn.dataset.telephone || '';
        const notifInput = document.getElementById('attr-num-notif');
        if (notifInput) {
            notifInput.value = counterNotif !== null ? padNum(counterNotif) : '';
            notifInput.style.background = counterNotif !== null ? '#fef3c7' : '#fff';
        }
        new bootstrap.Modal(document.getElementById('modalAttribuer')).show();
    });

    document.getElementById('btnConfirmAttribuer').addEventListener('click', function() {
        const ligneId = document.getElementById('attr-ligne-id').value;
        const prenom = document.getElementById('attr-prenom').value.trim();
        const cni = document.getElementById('attr-cni').value.trim();
        const numNotif = document.getElementById('attr-num-notif').value.trim();
        if (!prenom || !cni) { showToast('Prénom et N° pièce sont obligatoires.', 'warning'); return; }
        if (!numNotif) { showToast('N° de notification est obligatoire.', 'warning'); document.getElementById('attr-num-notif').focus(); return; }
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Traitement...';
        fetch(`/attributions/valider/${ligneId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                civilite: document.getElementById('attr-civilite').value,
                prenom: prenom,
                nom: document.getElementById('attr-nom').value,
                cni_passport: cni,
                type_piece: document.getElementById('attr-type-piece').value,
                code_paye: document.getElementById('attr-code-paye').value,
                ninea: document.getElementById('attr-ninea').value,
                telephone: document.getElementById('attr-telephone').value,
                numero_notification: numNotif,
            })
        })
        .then(r => r.json())
        .then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-lg"></i> Confirmer l\'attribution';
            if (res.success) {
                marquerLigneTraitee(ligneId, 'validee', res.mutation_id, res.numero_notification);
                updateCompteurs(1, 0);
                if (counterNotif !== null) consumeNotif();
                bootstrap.Modal.getInstance(document.getElementById('modalAttribuer')).hide();
            } else showToast(res.message || 'Erreur.', 'danger');
        })
        .catch(() => { this.disabled = false; this.innerHTML = '<i class="bi bi-check-lg"></i> Confirmer l\'attribution'; showToast('Erreur de connexion.', 'danger'); });
    });

    // Refus pour attribution → endpoint dédié
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-refuser-attr');
        if (!btn) return;
        document.getElementById('ref-ligne-id').value = btn.dataset.ligneId;
        document.getElementById('modal-lot-refus').textContent = btn.dataset.lot;
        document.getElementById('ref-motif').value = '';
        document.getElementById('ref-ligne-id').dataset.endpoint = 'attribution';
        new bootstrap.Modal(document.getElementById('modalRefuser')).show();
    });

    // =====================
    // REFUS UNITAIRE
    // =====================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-refuser');
        if (!btn) return;
        document.getElementById('ref-ligne-id').value = btn.dataset.ligneId;
        document.getElementById('modal-lot-refus').textContent = btn.dataset.lot;
        document.getElementById('ref-motif').value = '';
        document.getElementById('ref-ligne-id').dataset.endpoint = 'mutation';
        new bootstrap.Modal(document.getElementById('modalRefuser')).show();
    });

    document.getElementById('btnConfirmRefuser').addEventListener('click', function() {
        const ligneIdEl = document.getElementById('ref-ligne-id');
        const ligneId = ligneIdEl.value;
        const endpoint = ligneIdEl.dataset.endpoint === 'attribution' ? 'attributions' : 'mutations';
        const motif = document.getElementById('ref-motif').value.trim();
        if (!motif) { showToast('Veuillez indiquer le motif du refus.', 'warning'); return; }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Traitement...';

        fetch(`/${endpoint}/refuser/${ligneId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ motif_refus: motif })
        })
        .then(r => r.json())
        .then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-x-lg"></i> Confirmer le refus';
            if (res.success) {
                marquerLigneTraitee(ligneId, 'refusee');
                updateCompteurs(0, 1);
                bootstrap.Modal.getInstance(document.getElementById('modalRefuser')).hide();
            } else {
                showToast(res.message || 'Erreur.', 'danger');
            }
        })
        .catch(() => { this.disabled = false; this.innerHTML = '<i class="bi bi-x-lg"></i> Confirmer le refus'; showToast('Erreur de connexion.', 'danger'); });
    });

    // =====================
    // VALIDATION GLOBALE (tout)
    // =====================
    document.getElementById('btnValiderTout')?.addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('modalValiderTout')).show();
    });

    document.getElementById('btnConfirmValiderTout').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Validation en cours...';

        fetch(`/mutations/valider-tout/${importId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                civilite: document.getElementById('vt-civilite').value,
                ref_lettre_globale: document.getElementById('vt-ref').value,
                num_notif_depart: counterNotif !== null ? padNum(counterNotif) : null,
            })
        })
        .then(r => r.json())
        .then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check2-all"></i> Confirmer - Valider tout';
            if (res.success) {
                res.resultats.forEach(r => marquerLigneTraitee(r.ligne_id, 'validee', r.mutation_id, r.numero_notification));
                updateCompteurs(res.resultats.length, 0);
                // Avancer le compteur du nombre de lignes validées
                if (counterNotif !== null && res.resultats.length > 0) {
                    counterNotif += res.resultats.length;
                    refreshProchain();
                }
                bootstrap.Modal.getInstance(document.getElementById('modalValiderTout')).hide();
            } else {
                showToast(res.message || 'Erreur.', 'danger');
            }
        })
        .catch(() => { this.disabled = false; this.innerHTML = '<i class="bi bi-check2-all"></i> Confirmer - Valider tout'; showToast('Erreur de connexion.', 'danger'); });
    });

    // =====================
    // REFUS GLOBAL (tout)
    // =====================
    document.getElementById('btnRefuserTout')?.addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('modalRefuserTout')).show();
    });

    document.getElementById('btnConfirmRefuserTout').addEventListener('click', function() {
        const motif = document.getElementById('rt-motif').value.trim();
        if (!motif) { showToast('Veuillez indiquer le motif du refus.', 'warning'); return; }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Refus en cours...';

        fetch(`/mutations/refuser-tout/${importId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ motif_refus: motif })
        })
        .then(r => r.json())
        .then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-x-circle"></i> Confirmer - Refuser tout';
            if (res.success) {
                document.querySelectorAll('.ligne-row[data-statut="en_attente"][data-matched="1"]').forEach(row => {
                    marquerLigneTraitee(row.dataset.id, 'refusee');
                });
                updateCompteurs(0, res.count);
                bootstrap.Modal.getInstance(document.getElementById('modalRefuserTout')).hide();
            } else {
                showToast(res.message || 'Erreur.', 'danger');
            }
        })
        .catch(() => { this.disabled = false; this.innerHTML = '<i class="bi bi-x-circle"></i> Confirmer - Refuser tout'; showToast('Erreur de connexion.', 'danger'); });
    });

    // =====================
    // VALIDATION SÉLECTION
    // =====================
    document.getElementById('btnValiderSelection').addEventListener('click', function() {
        const sel = getSelected();
        if (sel.length === 0) return;
        document.getElementById('countValiderSel').textContent = sel.length;
        new bootstrap.Modal(document.getElementById('modalValiderSelection')).show();
    });

    document.getElementById('btnConfirmValiderSelection').addEventListener('click', function() {
        const sel = getSelected();
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Validation...';

        fetch(`/mutations/valider-selection/${importId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                ligne_ids: sel,
                num_notif_depart: counterNotif !== null ? padNum(counterNotif) : null,
            })
        })
        .then(r => r.json())
        .then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check2-all"></i> Confirmer la validation';
            if (res.success) {
                res.resultats.forEach(r => marquerLigneTraitee(r.ligne_id, 'validee', r.mutation_id, r.numero_notification));
                updateCompteurs(res.resultats.length, 0);
                if (counterNotif !== null && res.resultats.length > 0) {
                    counterNotif += res.resultats.length;
                    refreshProchain();
                }
                bootstrap.Modal.getInstance(document.getElementById('modalValiderSelection')).hide();
                selectAll.checked = false;
            } else {
                showToast(res.message || 'Erreur.', 'danger');
            }
        })
        .catch(() => { this.disabled = false; this.innerHTML = '<i class="bi bi-check2-all"></i> Confirmer la validation'; showToast('Erreur de connexion.', 'danger'); });
    });

    // =====================
    // REFUS SÉLECTION
    // =====================
    document.getElementById('btnRefuserSelection').addEventListener('click', function() {
        const sel = getSelected();
        if (sel.length === 0) return;
        document.getElementById('countRefuserSel').textContent = sel.length;
        document.getElementById('rs-motif').value = '';
        new bootstrap.Modal(document.getElementById('modalRefuserSelection')).show();
    });

    document.getElementById('btnConfirmRefuserSelection').addEventListener('click', function() {
        const sel = getSelected();
        const motif = document.getElementById('rs-motif').value.trim();
        if (!motif) { showToast('Veuillez indiquer le motif du refus.', 'warning'); return; }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Refus...';

        fetch(`/mutations/refuser-selection/${importId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ ligne_ids: sel, motif_refus: motif })
        })
        .then(r => r.json())
        .then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-x-circle"></i> Confirmer le refus';
            if (res.success) {
                sel.forEach(id => marquerLigneTraitee(id, 'refusee'));
                updateCompteurs(0, res.count);
                bootstrap.Modal.getInstance(document.getElementById('modalRefuserSelection')).hide();
                selectAll.checked = false;
            } else {
                showToast(res.message || 'Erreur.', 'danger');
            }
        })
        .catch(() => { this.disabled = false; this.innerHTML = '<i class="bi bi-x-circle"></i> Confirmer le refus'; showToast('Erreur de connexion.', 'danger'); });
    });
});
</script>
@endpush
@endsection
