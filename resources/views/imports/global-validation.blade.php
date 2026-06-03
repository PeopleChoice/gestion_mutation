@extends('layouts.app')
@section('title', 'Validation des mutations — Import global')

@push('styles')
<style>
    .stat-mini { text-align:center; padding:12px; border-radius:8px; background:#f8f9fa; }
    .stat-mini .number { font-size:24px; font-weight:700; }
    .stat-mini .label { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:0.5px; }
    .ligne-row.traitee { opacity:0.55; }
    .ligne-row.vierge { background:#fff8e1; }
    .ligne-row.no-match { background:#fafafa; }
    .lignes-table thead th { background:#f8fafc; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; font-weight:700; padding:10px 12px; }
    .lignes-table td { vertical-align:middle; padding:10px 12px; font-size:13px; }
    .lot-badge { display:inline-block; background:#5D4E37; color:#fff; font-weight:700; font-size:12px; padding:3px 8px; border-radius:5px; }
    .person-chip { display:inline-block; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:600; }
    .chip-nouveau { background:#dcfce7; color:#166534; }
    .chip-demandeur { background:#dbeafe; color:#1e40af; }
    .chip-ancien { background:#fef3c7; color:#92400e; }
    .person-name { font-weight:700; color:#1e293b; font-size:12px; }
    .person-meta { font-size:11px; color:#64748b; }
    .piece-formatee { font-family:'Courier New',monospace; background:#f1f5f9; padding:1px 5px; border-radius:3px; font-size:10px; }
    .projet-section { margin-bottom:24px; }
    .projet-header { background:linear-gradient(to right, #fef6ec, #fff); padding:12px 16px; border-radius:10px 10px 0 0; border:1px solid #e0d5b8; border-bottom:none; display:flex; justify-content:space-between; align-items:center; }
    .projet-code { background:#5D4E37; color:#fff; font-family:'Courier New',monospace; padding:3px 10px; border-radius:5px; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-check2-circle"></i> Validation des mutations</h4>
        <p class="text-muted mb-0 small">Import global — {{ count($imports) }} projet(s) à traiter</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('imports.global') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour à l'import global</a>
        @if($enAttente > 0)
            <button class="btn btn-sm btn-success" id="btnValiderTout"><i class="bi bi-check2-all"></i> Tout valider ({{ $mutables }} mutables)</button>
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('warning'))<div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="alert" style="background:#eff6ff; border-left:4px solid #3b82f6; border-radius:8px;">
    <i class="bi bi-info-circle text-primary"></i>
    <strong>Aucune mutation/attribution n'a encore été enregistrée.</strong>
    C'est ici que vous décidez <strong>oui (Valider)</strong> ou <strong>non (Refuser)</strong> pour chaque ligne.
</div>

<div class="card mb-3" style="border:2px dashed #d97706; border-radius:10px; background:#fffbeb;">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <strong style="color:#92400e;"><i class="bi bi-hash"></i> Compteur global de notifications</strong>
                <small class="text-muted d-block">Un seul compteur partagé entre <strong>tous les projets</strong> de cet import. Chaque validation (manuelle ou en masse) consomme un numéro et incrémente.</small>
            </div>
            <input type="text" id="numNotifDepart" class="form-control" style="max-width:200px; font-family:'Courier New',monospace; font-size:16px; font-weight:bold; border-color:#d97706;" placeholder="Ex: 0000001" value="{{ session('num_notif_depart', str_pad((string) (\App\Models\Mutation::max('id') + 1), 7, '0', STR_PAD_LEFT)) }}">
            <button type="button" class="btn btn-sm" id="btnSetNumNotif" style="background:#d97706; color:#fff;">
                <i class="bi bi-check-lg"></i> Définir
            </button>
            <span class="ms-auto small text-muted">Prochain N° : <strong id="prochainNotif" style="font-family:'Courier New',monospace; color:#92400e; font-size:14px;">—</strong></span>
        </div>
    </div>
</div>

<!-- Stats globales -->
<div class="row g-2 mb-3">
    <div class="col"><div class="stat-mini"><div class="number" style="color:#5D4E37;">{{ $totalLignes }}</div><div class="label">Total lignes</div></div></div>
    <div class="col"><div class="stat-mini"><div class="number text-success">{{ $mutables }}</div><div class="label">Mutables</div></div></div>
    <div class="col"><div class="stat-mini"><div class="number" style="color:#d97706;">{{ $vierges }}</div><div class="label">Vierges</div></div></div>
    <div class="col"><div class="stat-mini"><div class="number text-warning">{{ $nonMatched }}</div><div class="label">Inexistants</div></div></div>
    <div class="col"><div class="stat-mini"><div class="number text-secondary">{{ $traites }}</div><div class="label">Déjà traités</div></div></div>
</div>

@foreach($imports as $imp)
@php
    $isAttribution = $imp->type === 'attribution';
@endphp
<div class="projet-section" data-import-id="{{ $imp->id }}" data-type="{{ $imp->type }}">
    <div class="projet-header" style="{{ $isAttribution ? 'background:linear-gradient(to right, #dcfce7, #fff); border-color:#86efac;' : '' }}">
        <div>
            <span class="projet-code">{{ $imp->projet->code }}</span>
            <strong class="ms-2">{{ $imp->projet->nom }}</strong>
            <small class="text-muted ms-2">{{ $imp->projet->commune->nom }}</small>
            @if($isAttribution)
                <span class="badge ms-2" style="background:#16a34a; color:#fff;">ATTRIBUTION</span>
            @else
                <span class="badge ms-2" style="background:#d97706; color:#fff;">MUTATION</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary">{{ $imp->lignes->count() }} ligne(s)</span>
            <span class="badge bg-success">{{ $imp->lignes->where('statut','validee')->count() }} validée(s)</span>
            <span class="badge bg-danger">{{ $imp->lignes->where('statut','refusee')->count() }} refusée(s)</span>
            @if(!$isAttribution)
            <button class="btn btn-sm btn-success btn-valider-import" data-import-id="{{ $imp->id }}" title="Valider toutes les lignes mutables de ce projet">
                <i class="bi bi-check2-all"></i> Valider tout
            </button>
            @endif
        </div>
    </div>
    <div class="card mb-0" style="border-radius:0 0 10px 10px; border:1px solid #e0d5b8; border-top:none;">
        <div class="card-body p-0">
            <table class="table mb-0 lignes-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Lot</th>
                        @if($isAttribution)
                            <th>État du lot</th>
                            <th>Attributaire (à créer)</th>
                            <th style="width:110px;">Statut</th>
                            <th style="width:110px;">Actions</th>
                        @else
                            <th>Précédent propriétaire</th>
                            <th>Nouveau attributaire</th>
                            <th>Demandeur</th>
                            <th style="width:110px;">Statut</th>
                            <th style="width:110px;">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($imp->lignes->sortBy('numero_ordre') as $ligne)
                    @php
                        // Pour ATTRIBUTION : "valide" si le lot n'existe pas OU existe vierge
                        // Pour MUTATION : "valide" si le lot existe avec un proprio
                        if ($isAttribution) {
                            $lotExists = $ligne->matched;
                            $lotConflict = $lotExists && $ligne->parcelle?->proprietaire_id;  // déjà attribué
                            $isProcessable = !$lotConflict && $ligne->statut === 'en_attente';
                            $rowClass = '';
                            if ($ligne->statut !== 'en_attente') $rowClass = 'traitee';
                            elseif ($lotConflict) $rowClass = 'no-match';
                        } else {
                            $isVierge = $ligne->matched && !$ligne->parcelle?->proprietaire_id;
                            $isProcessable = $ligne->matched && $ligne->parcelle?->proprietaire_id && $ligne->statut === 'en_attente';
                            $rowClass = '';
                            if ($ligne->statut !== 'en_attente') $rowClass = 'traitee';
                            elseif (!$ligne->matched) $rowClass = 'no-match';
                            elseif ($isVierge) $rowClass = 'vierge';
                        }
                    @endphp
                    <tr id="ligne-{{ $ligne->id }}" class="ligne-row {{ $rowClass }}" data-mutable="{{ $isProcessable ? '1' : '0' }}">
                        <td><span class="lot-badge">{{ $ligne->numero_lot }}</span></td>

                        @if($isAttribution)
                            {{-- Cellule "État du lot" --}}
                            <td>
                                @if(!$lotExists)
                                    <span class="person-chip" style="background:#dbeafe; color:#1e40af;"><i class="bi bi-plus-circle"></i> Nouveau lot</span>
                                    <div class="person-meta">Sera créé puis attribué</div>
                                @elseif($lotConflict)
                                    <span class="person-chip" style="background:#fee2e2; color:#991b1b;"><i class="bi bi-exclamation-triangle"></i> Déjà attribué</span>
                                    <div class="person-meta">Owner: {{ $ligne->parcelle->proprietaire->nom_complet }} — utilisez une mutation</div>
                                @else
                                    <span class="person-chip" style="background:#fef3c7; color:#92400e;"><i class="bi bi-arrow-up-circle"></i> Vierge existant</span>
                                    <div class="person-meta">Sera attribué (lot {{ $ligne->numero_lot }} déjà créé)</div>
                                @endif
                            </td>
                            {{-- Cellule "Attributaire" --}}
                            <td>
                                @if($ligne->prenom || $ligne->nom)
                                    <span class="person-chip chip-nouveau">Attributaire</span>
                                    <div class="person-name">{{ trim(($ligne->civilite ?? '') . ' ' . ($ligne->prenom ?? '') . ' ' . ($ligne->nom ?? '')) }}</div>
                                    <div class="person-meta">
                                        @if($ligne->cni_passport)
                                            <span class="piece-formatee">{{ $ligne->type_piece }}{{ $ligne->code_paye ? '_' . $ligne->code_paye : '' }} n° {{ $ligne->cni_passport }}</span>
                                        @endif
                                        @if($ligne->telephone) · {{ $ligne->telephone }} @endif
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">—</span>
                                @endif
                            </td>
                        @else
                            {{-- MUTATION : 3 colonnes --}}
                            <td>
                                @if(!$ligne->matched)
                                    <small class="text-muted fst-italic">Lot inexistant</small>
                                @elseif($ligne->parcelle?->proprietaire)
                                    <span class="person-chip chip-ancien">Précédent</span>
                                    <div class="person-name">{{ $ligne->parcelle->proprietaire->nom_complet }}</div>
                                    <div class="person-meta">
                                        @if($ligne->parcelle->proprietaire->cni_passport)
                                            <span class="piece-formatee">{{ $ligne->parcelle->proprietaire->type_piece }} n° {{ $ligne->parcelle->proprietaire->cni_passport }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="person-chip" style="background:#fee2e2; color:#991b1b;"><i class="bi bi-exclamation-triangle"></i> Vierge</span>
                                @endif
                            </td>
                            <td>
                                @if($ligne->prenom || $ligne->nom)
                                    <span class="person-chip chip-nouveau">Nouveau</span>
                                    <div class="person-name">{{ trim(($ligne->civilite ?? '') . ' ' . ($ligne->prenom ?? '') . ' ' . ($ligne->nom ?? '')) }}</div>
                                    <div class="person-meta">
                                        @if($ligne->cni_passport)
                                            <span class="piece-formatee">{{ $ligne->type_piece }}{{ $ligne->code_paye ? '_' . $ligne->code_paye : '' }} n° {{ $ligne->cni_passport }}</span>
                                        @endif
                                        @if($ligne->telephone) · {{ $ligne->telephone }} @endif
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($ligne->demandeur_prenom || $ligne->demandeur_nom)
                                    <span class="person-chip chip-demandeur">Demandeur</span>
                                    <div class="person-name">{{ trim(($ligne->demandeur_prenom ?? '') . ' ' . ($ligne->demandeur_nom ?? '')) }}</div>
                                    @if($ligne->demandeur_telephone)<div class="person-meta">{{ $ligne->demandeur_telephone }}</div>@endif
                                @else
                                    <span class="text-muted small fst-italic">—</span>
                                @endif
                            </td>
                        @endif

                        <td id="statut-{{ $ligne->id }}">
                            @if($ligne->statut === 'validee')
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Validée</span>
                            @elseif($ligne->statut === 'refusee')
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Refusée</span>
                            @elseif($isAttribution && $lotConflict)
                                <span class="badge" style="background:#fbbf24; color:#78350f;">Conflit</span>
                            @elseif(!$isAttribution && !$ligne->matched)
                                <span class="badge bg-warning text-dark">Inexistant</span>
                            @elseif(!$isAttribution && $isVierge ?? false)
                                <span class="badge" style="background:#fbbf24; color:#78350f;">Vierge</span>
                            @else
                                <span class="badge bg-secondary">En attente</span>
                            @endif
                        </td>
                        <td id="actions-{{ $ligne->id }}">
                            @if($isProcessable)
                                <button class="btn btn-sm btn-success btn-valider-ligne"
                                    data-type="{{ $imp->type }}"
                                    data-ligne-id="{{ $ligne->id }}"
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
                                    title="Valider"
                                ><i class="bi bi-check-lg"></i></button>
                                <button class="btn btn-sm btn-danger btn-refuser-ligne" data-type="{{ $imp->type }}" data-ligne-id="{{ $ligne->id }}"
                                    data-lot="{{ $ligne->numero_lot }}" title="Refuser"
                                ><i class="bi bi-x-lg"></i></button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

{{-- Modal validation : vérification + édition des infos avant enregistrement --}}
<div class="modal fade" id="modalValider" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i>
                    Vérifier et valider — Lot <span id="modalValLot"></span>
                    <small class="ms-2 opacity-75" id="modalValProj"></small>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3 small py-2">
                    <i class="bi bi-info-circle"></i>
                    Vérifiez les informations puis cliquez <strong>Confirmer</strong> pour enregistrer la mutation et générer le QR code.
                </div>

                {{-- Précédent (lecture seule) --}}
                <div class="mb-3 p-2" style="background:#fef3c7; border-left:3px solid #d97706; border-radius:6px;">
                    <small class="text-muted">Précédent propriétaire (en base, sera remplacé)</small>
                    <div class="fw-bold" id="modalValAncien">—</div>
                </div>

                <input type="hidden" id="valLigneId">

                <div class="row g-3 mb-3 p-2" style="background:#fffbeb; border:2px dashed #d97706; border-radius:8px;">
                    <div class="col-12">
                        <label class="form-label fw-bold mb-1" style="color:#92400e;">
                            <i class="bi bi-hash"></i> N° de notification <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="valNumNotif" class="form-control" required placeholder="Ex: 0000001"
                            style="font-family:'Courier New',monospace; font-size:16px; font-weight:bold;">
                        <small class="text-muted">Ce numéro sera imprimé sur le PDF. Auto-incrémenté à chaque validation.</small>
                    </div>
                </div>

                <h6 class="text-success fw-bold mt-2 mb-2"><i class="bi bi-person-check"></i> Nouveau attributaire</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Civilité <span class="text-danger">*</span></label>
                        <select id="valCivilite" class="form-select" required>
                            <option value="Monsieur">Monsieur</option>
                            <option value="Madame">Madame</option>
                            <option value="Société">Société</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="valPrenom" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="valNom" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Type pièce <span class="text-danger">*</span></label>
                        <select id="valTypePiece" class="form-select" required>
                            <option value="CNI">CNI</option>
                            <option value="Passeport">Passeport</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Code payé</label>
                        <input type="text" id="valCodePaye" class="form-control" placeholder="Ex: SN">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">N° pièce <span class="text-danger">*</span></label>
                        <input type="text" id="valCni" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Téléphone</label>
                        <input type="text" id="valTelephone" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="alert mb-0 py-2 px-3" style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; font-size:13px;">
                            <span class="text-muted small">Pièce formatée :</span>
                            <strong id="valPiecePreview" class="ms-2" style="font-family:'Courier New',monospace; color:#5D4E37;">—</strong>
                        </div>
                    </div>
                </div>

                <h6 class="text-primary fw-bold mt-3 mb-2"><i class="bi bi-person-lines-fill"></i> Demandeur</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Prénom demandeur</label>
                        <input type="text" id="valDemPrenom" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">NOM demandeur</label>
                        <input type="text" id="valDemNom" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tél demandeur</label>
                        <input type="text" id="valDemTel" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">Réf. Lettre</label>
                        <input type="text" id="valRef" class="form-control">
                    </div>
                </div>
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

{{-- Modal refus --}}
<div class="modal fade" id="modalRefus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> Refuser — Lot <span id="modalRefuseLot"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="refLigneId">
                <label class="form-label">Motif du refus <span class="text-danger">*</span></label>
                <textarea id="refMotif" class="form-control" rows="3" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="btnConfirmRefus"><i class="bi bi-x-lg"></i> Confirmer</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ========== COMPTEUR N° NOTIFICATION ==========
    let counterNotif = null;
    const inputDepart = document.getElementById('numNotifDepart');
    const prochainEl = document.getElementById('prochainNotif');

    function padNum(n) { return String(n).padStart(7, '0'); }
    function refreshProchain() {
        prochainEl.textContent = counterNotif !== null ? padNum(counterNotif) : '—';
    }
    document.getElementById('btnSetNumNotif').addEventListener('click', function() {
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
    function consumeNotif() {
        if (counterNotif === null) return null;
        const n = counterNotif;
        counterNotif++;
        refreshProchain();
        return padNum(n);
    }

    // Auto-définir le compteur si on a une valeur saisie au préalable sur /imports-global
    @if(session('num_notif_depart'))
    document.getElementById('btnSetNumNotif').click();
    @endif

    function markValidated(ligneId) {
        const row = document.getElementById('ligne-' + ligneId);
        if (!row) return;
        row.classList.add('traitee');
        document.getElementById('statut-' + ligneId).innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Validée</span>';
        document.getElementById('actions-' + ligneId).innerHTML = '';
        row.dataset.mutable = '0';
    }
    function markRefused(ligneId) {
        const row = document.getElementById('ligne-' + ligneId);
        if (!row) return;
        row.classList.add('traitee');
        document.getElementById('statut-' + ligneId).innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Refusée</span>';
        document.getElementById('actions-' + ligneId).innerHTML = '';
        row.dataset.mutable = '0';
    }

    // ========== VALIDATION LIGNE PAR LIGNE (avec modal de vérification) ==========
    const valModal = new bootstrap.Modal(document.getElementById('modalValider'));
    const valPiecePreview = document.getElementById('valPiecePreview');

    function updateValPreview() {
        const type = document.getElementById('valTypePiece').value.trim();
        const code = document.getElementById('valCodePaye').value.trim().toUpperCase();
        const num  = document.getElementById('valCni').value.trim();
        if (!num) { valPiecePreview.textContent = '—'; return; }
        const prefix = /^pass/i.test(type) ? 'PP' : (type || '');
        if (!prefix) { valPiecePreview.textContent = code ? `${code} n° : ${num}` : `n° : ${num}`; return; }
        valPiecePreview.textContent = code ? `${prefix}_${code} n° : ${num}` : `${prefix} n° : ${num}`;
    }
    ['valTypePiece','valCodePaye','valCni'].forEach(id => {
        document.getElementById(id).addEventListener('input', updateValPreview);
        document.getElementById(id).addEventListener('change', updateValPreview);
    });

    document.querySelectorAll('.btn-valider-ligne').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const projetSection = row.closest('.projet-section');
            const projHeader = projetSection?.querySelector('.projet-header strong')?.textContent || '';
            const lotBadge = row.querySelector('.lot-badge')?.textContent || '';
            const ancienName = row.querySelector('.chip-ancien + .person-name')?.textContent || '—';

            currentValType = this.dataset.type || 'mutation';
            const isAttribution = currentValType === 'attribution';

            // Masquer la section "Précédent" + "Demandeur" pour les attributions
            document.querySelector('.modal-body > .mb-3.p-2').style.display = isAttribution ? 'none' : '';
            const demandeurHeading = document.querySelectorAll('.modal-body h6')[1];
            if (demandeurHeading) {
                demandeurHeading.style.display = isAttribution ? 'none' : '';
                demandeurHeading.nextElementSibling.style.display = isAttribution ? 'none' : '';
            }
            // Adapter le titre du modal
            document.querySelector('#modalValider .modal-header').className = 'modal-header ' + (isAttribution ? 'bg-info' : 'bg-success') + ' text-white';
            document.querySelector('#modalValider .modal-title i').className = 'bi ' + (isAttribution ? 'bi-person-plus' : 'bi-check-circle');
            document.querySelector('#modalValider .modal-title').firstChild.nextSibling.textContent = isAttribution ? ' Vérifier et attribuer — Lot ' : ' Vérifier et valider — Lot ';

            document.getElementById('valLigneId').value = this.dataset.ligneId;
            document.getElementById('modalValLot').textContent = lotBadge;
            document.getElementById('modalValProj').textContent = projHeader;
            document.getElementById('modalValAncien').textContent = ancienName;

            // Pré-remplir N° notification avec la valeur courante du compteur (sans consommer)
            const notifInput = document.getElementById('valNumNotif');
            if (counterNotif !== null) {
                notifInput.value = padNum(counterNotif);
                notifInput.style.background = '#fef3c7';
            } else {
                notifInput.value = '';
                notifInput.style.background = '#fff';
            }

            document.getElementById('valCivilite').value   = this.dataset.civilite || 'Monsieur';
            document.getElementById('valPrenom').value     = this.dataset.prenom || '';
            document.getElementById('valNom').value        = this.dataset.nom || '';
            document.getElementById('valTypePiece').value  = this.dataset.typePiece || 'CNI';
            document.getElementById('valCodePaye').value   = this.dataset.codePaye || '';
            document.getElementById('valCni').value        = this.dataset.cni || '';
            document.getElementById('valTelephone').value  = this.dataset.telephone || '';
            document.getElementById('valDemPrenom').value  = this.dataset.demandeurPrenom || '';
            document.getElementById('valDemNom').value     = this.dataset.demandeurNom || '';
            document.getElementById('valDemTel').value     = this.dataset.demandeurTelephone || '';
            document.getElementById('valRef').value        = this.dataset.ref || '';

            updateValPreview();
            valModal.show();
        });
    });

    let currentValType = 'mutation';

    document.getElementById('btnConfirmValider').addEventListener('click', function() {
        const ligneId = document.getElementById('valLigneId').value;
        const prenom  = document.getElementById('valPrenom').value.trim();
        const nom     = document.getElementById('valNom').value.trim();
        const cni     = document.getElementById('valCni').value.trim();
        const numNotif = document.getElementById('valNumNotif').value.trim();
        if (!prenom || (currentValType === 'mutation' && !nom) || !cni) {
            showToast('Prénom et N° pièce sont obligatoires.', 'warning');
            return;
        }
        if (!numNotif) {
            showToast('N° de notification est obligatoire.', 'warning');
            document.getElementById('valNumNotif').focus();
            return;
        }

        const data = {
            civilite:            document.getElementById('valCivilite').value,
            prenom: prenom, nom: nom, cni_passport: cni,
            type_piece:          document.getElementById('valTypePiece').value,
            code_paye:           document.getElementById('valCodePaye').value,
            telephone:           document.getElementById('valTelephone').value,
            ref_lettre:          document.getElementById('valRef').value,
            numero_notification: numNotif,
        };
        // Demandeur uniquement pour les mutations
        if (currentValType === 'mutation') {
            data.demandeur_prenom    = document.getElementById('valDemPrenom').value;
            data.demandeur_nom       = document.getElementById('valDemNom').value;
            data.demandeur_telephone = document.getElementById('valDemTel').value;
        }

        const url = currentValType === 'attribution'
            ? `/attributions/valider/${ligneId}`
            : `/mutations/valider/${ligneId}`;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement...';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(data)
        }).then(r => r.json()).then(res => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-lg"></i> Confirmer';
            if (res.success) {
                markValidated(ligneId);
                // Consommer le compteur (si actif) et avancer
                if (counterNotif !== null) consumeNotif();
                // Ajouter un bouton PDF dans la ligne après validation
                if (res.mutation_id) {
                    const actionsCell = document.getElementById('actions-' + ligneId);
                    actionsCell.innerHTML = `<a href="/mutations/${res.mutation_id}/apercu" target="_blank" class="btn btn-sm btn-outline-primary" title="Voir PDF"><i class="bi bi-file-pdf"></i></a>`;
                }
                valModal.hide();
            } else showToast(res.message || 'Erreur', 'danger');
        }).catch(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-lg"></i> Confirmer';
            showToast('Erreur de connexion', 'danger');
        });
    });

    // ========== REFUS LIGNE PAR LIGNE ==========
    let refModal = new bootstrap.Modal(document.getElementById('modalRefus'));
    let currentRefType = 'mutation';
    document.querySelectorAll('.btn-refuser-ligne').forEach(btn => {
        btn.addEventListener('click', function() {
            currentRefType = this.dataset.type || 'mutation';
            document.getElementById('refLigneId').value = this.dataset.ligneId;
            document.getElementById('modalRefuseLot').textContent = this.dataset.lot;
            document.getElementById('refMotif').value = '';
            refModal.show();
        });
    });
    document.getElementById('btnConfirmRefus').addEventListener('click', function() {
        const ligneId = document.getElementById('refLigneId').value;
        const motif = document.getElementById('refMotif').value.trim();
        if (!motif) { showToast('Motif requis.', 'warning'); return; }
        this.disabled = true;
        const url = currentRefType === 'attribution'
            ? `/attributions/refuser/${ligneId}`
            : `/mutations/refuser/${ligneId}`;
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ motif_refus: motif })
        }).then(r => r.json()).then(res => {
            this.disabled = false;
            if (res.success) { markRefused(ligneId); refModal.hide(); }
            else showToast(res.message, 'danger');
        });
    });

    // ========== VALIDER TOUT (par projet) ==========
    document.querySelectorAll('.btn-valider-import').forEach(btn => {
        btn.addEventListener('click', function() {
            const _btn = this;
            showConfirm('Valider toutes les lignes mutables de ce projet ?', () => {
            const importId = _btn.dataset.importId;
            _btn.disabled = true;
            _btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ...';
            fetch(`/mutations/valider-tout/${importId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    civilite: 'Monsieur',
                    num_notif_depart: counterNotif !== null ? padNum(counterNotif) : null,
                })
            }).then(r => r.json()).then(res => {
                _btn.disabled = false;
                _btn.innerHTML = '<i class="bi bi-check2-all"></i> Valider tout';
                if (res.success) {
                    (res.resultats || []).forEach(r => markValidated(r.ligne_id));
                    if (counterNotif !== null && (res.resultats || []).length > 0) {
                        counterNotif += res.resultats.length;
                        refreshProchain();
                    }
                    showToast(res.message, 'success');
                } else showToast(res.message, 'danger');
            });
            }, { label: 'Valider', type: 'success', icon: 'bi-check2-all', iconColor: '#16a34a' });
        });
    });

    // ========== VALIDER TOUT GLOBAL ==========
    const btnGlobal = document.getElementById('btnValiderTout');
    if (btnGlobal) {
        btnGlobal.addEventListener('click', function() {
            const mutables = document.querySelectorAll('.ligne-row[data-mutable="1"]').length;
            if (mutables === 0) { showToast('Aucune ligne mutable.', 'warning'); return; }
            showConfirm(`Valider les <strong>${mutables}</strong> lignes mutables de tous les projets ?`, () => {
                document.querySelectorAll('.btn-valider-import').forEach(b => b.click());
            }, { label: 'Valider tout', type: 'success', icon: 'bi-check2-all', iconColor: '#16a34a' });
        });
    }
});
</script>
@endpush
@endsection
