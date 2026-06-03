@extends('layouts.app')
@section('title', 'Projet - ' . $projet->nom)

@push('styles')
<style>
    .parcelle-row.non-attribuee { background: #fff9f0; }
    .parcelle-row:hover { background: #fdf6e3 !important; }
    .badge-non-attribue { background: #fd7e14; color: #fff; font-size: 11px; }
    .badge-attribue { background: #C8A951; color: #fff; font-size: 11px; }
    .stat-box { text-align: center; padding: 15px; border-radius: 10px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
    .stat-box .number { font-size: 28px; font-weight: 700; }
    .stat-box .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .search-parcelle { border-radius: 20px; padding-left: 35px; border: 2px solid #e0d5b8; }
    .search-parcelle:focus { border-color: #C8A951; box-shadow: 0 0 0 3px rgba(200,169,81,0.15); }
</style>
@endpush

@section('content')
<!-- En-tête projet -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h4 class="mb-1" style="color:#5D4E37;">
                    {{ $projet->nom }}
                    @if($projet->code)
                        <span class="badge ms-2" style="background:#5D4E37; color:#fff; font-family:'Courier New',monospace; font-size:13px;">{{ $projet->code }}</span>
                    @endif
                </h4>
                <div class="text-muted">
                    <i class="bi bi-pin-map"></i> {{ $projet->commune->nom }}
                    @if($projet->type_lotissement) | <i class="bi bi-tag"></i> {{ $projet->type_lotissement }} @endif
                </div>
                @if($projet->description)
                    <p class="text-muted small mt-1 mb-0">{{ $projet->description }}</p>
                @endif
            </div>
            <div class="col-md-6 text-end">
                <a href="{{ route('projets.exporter', $projet) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Exporter Excel</a>
                <a href="{{ route('projets.edit', $projet) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Modifier</a>
                <a href="{{ route('projets.init-parcelles', $projet) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-arrow-up"></i> Initialiser parcelles
                </a>
                <a href="{{ route('imports.create') }}" class="btn btn-sm" style="background:#C8A951; color:#fff;"><i class="bi bi-upload"></i> Importer mutations</a>
            </div>
        </div>
    </div>
</div>

@if(!empty($alertes))
<div class="mb-3">
    @foreach($alertes as $alerte)
        @php
            $colors = [
                'warning' => ['bg' => '#fef3c7', 'border' => '#d97706', 'color' => '#92400e'],
                'info'    => ['bg' => '#dbeafe', 'border' => '#3b82f6', 'color' => '#1e40af'],
                'danger'  => ['bg' => '#fee2e2', 'border' => '#dc2626', 'color' => '#991b1b'],
                'primary' => ['bg' => '#e0e7ff', 'border' => '#6366f1', 'color' => '#4338ca'],
                'success' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'color' => '#166534'],
            ];
            $c = $colors[$alerte['type']] ?? $colors['info'];
        @endphp
        <div class="d-flex align-items-center justify-content-between mb-2 p-3" style="background:{{ $c['bg'] }}; border-left:4px solid {{ $c['border'] }}; border-radius:8px;">
            <div class="d-flex align-items-center gap-3">
                <i class="bi {{ $alerte['icon'] }}" style="font-size:22px; color:{{ $c['border'] }};"></i>
                <div>
                    <strong style="color:{{ $c['color'] }};">{{ $alerte['titre'] }}</strong>
                    @if(!empty($alerte['message']))
                        <div class="small" style="color:{{ $c['color'] }}; opacity:0.8;">{{ $alerte['message'] }}</div>
                    @endif
                </div>
            </div>
            @if(!empty($alerte['cta']))
                <a href="{{ $alerte['cta']['url'] }}" class="btn btn-sm" style="background:{{ $c['border'] }}; color:#fff;">
                    {{ $alerte['cta']['label'] }} <i class="bi bi-arrow-right"></i>
                </a>
            @endif
        </div>
    @endforeach
</div>
@endif

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-box">
            <div class="number" style="color:#5D4E37;">{{ $stats['total'] }}</div>
            <div class="label">Parcelles</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-box">
            <div class="number" style="color:#C8A951;">{{ $stats['attribuees'] }}</div>
            <div class="label">Attribuées</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-box">
            <div class="number" style="color:#fd7e14;">{{ $stats['non_attribuees'] }}</div>
            <div class="label">Non attribuées</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-box">
            <div class="number" style="color:#5D4E37;">{{ $stats['mutations'] }}</div>
            <div class="label">Mutations</div>
        </div>
    </div>
</div>

<!-- Barre de recherche + filtres -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative flex-grow-1">
                <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#C8A951;"></i>
                <input type="text" id="searchParcelle" class="form-control form-control-sm search-parcelle" placeholder="Rechercher par lot, nom, CNI...">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="tous">Tous ({{ $stats['total'] }})</button>
                <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="attribue">Attribués ({{ $stats['attribuees'] }})</button>
                <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="non-attribue">Non attribués ({{ $stats['non_attribuees'] }})</button>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des parcelles -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="parcellesTable">
            <thead>
                <tr>
                    <th style="width:80px;">Lot</th>
                    <th>Propriétaire</th>
                    <th>CNI / Passeport</th>
                    <th>NIN</th>
                    <th>Téléphone</th>
                    <th>Statut</th>
                    <th style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parcelles as $parcelle)
                <tr class="parcelle-row {{ $parcelle->proprietaire ? '' : 'non-attribuee' }}"
                    data-filter="{{ $parcelle->proprietaire ? 'attribue' : 'non-attribue' }}"
                    data-search="{{ strtolower($parcelle->numero_lot . ' ' . ($parcelle->proprietaire?->nom_complet ?? '') . ' ' . ($parcelle->proprietaire?->cni_passport ?? '') . ' ' . ($parcelle->proprietaire?->nin ?? '') . ' ' . ($parcelle->proprietaire?->telephone ?? '')) }}">
                    <td>
                        <strong style="color:#5D4E37; font-size:15px;">{{ $parcelle->numero_lot }}</strong>
                    </td>
                    <td>
                        @if($parcelle->proprietaire)
                            <strong>{{ $parcelle->proprietaire->nom_complet }}</strong>
                        @else
                            <span style="color:#fd7e14; font-weight:600;"><i class="bi bi-exclamation-circle"></i> Non attribué</span>
                        @endif
                    </td>
                    <td>{{ $parcelle->proprietaire?->cni_passport ?? '-' }}</td>
                    <td>{{ $parcelle->proprietaire?->nin ?? '-' }}</td>
                    <td>{{ $parcelle->proprietaire?->telephone ?? '-' }}</td>
                    <td>
                        @if($parcelle->proprietaire)
                            <span class="badge badge-attribue">Attribué</span>
                        @else
                            <span class="badge badge-non-attribue">Non attribué</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('parcelles.show', $parcelle) }}" class="btn btn-sm btn-outline-secondary" title="Voir"><i class="bi bi-eye"></i></a>
                            @if($parcelle->proprietaire)
                                <a href="{{ route('mutations.create', ['parcelle_id' => $parcelle->id]) }}" class="btn btn-sm btn-outline-primary" title="Muter">
                                    <i class="bi bi-arrow-left-right"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-attribuer" title="Forcer l'attribution (mot de passe requis)"
                                    data-parcelle-id="{{ $parcelle->id }}"
                                    data-lot="{{ $parcelle->numero_lot }}"
                                    data-has-owner="1"
                                    data-current-owner="{{ $parcelle->proprietaire->nom_complet }}">
                                    <i class="bi bi-shield-lock"></i>
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-success btn-attribuer" title="Attribuer cette parcelle"
                                    data-parcelle-id="{{ $parcelle->id }}"
                                    data-lot="{{ $parcelle->numero_lot }}"
                                    data-has-owner="0">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Attribuer / Forcer --}}
<div class="modal fade" id="modalAttribuer" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formAttribuer">
                @csrf
                <div class="modal-header" style="background:#C8A951; color:#fff;">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i>
                        <span id="modalAttribuerTitle">Attribuer la parcelle</span>
                        — Lot <span id="modalAttribuerLot"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="alertForce" class="alert alert-warning d-none">
                        <i class="bi bi-shield-exclamation"></i>
                        <strong>Attention !</strong> Cette parcelle est déjà attribuée à
                        <strong id="currentOwnerName"></strong>. Normalement, un changement de
                        propriétaire se fait via une <em>mutation</em>. Saisissez le mot de passe
                        administrateur pour forcer une nouvelle attribution directe.
                    </div>

                    <div class="row g-3 mb-3 p-2" style="background:#fffbeb; border:2px dashed #d97706; border-radius:8px;">
                        <div class="col-12">
                            <label class="form-label fw-bold mb-1" style="color:#92400e;">
                                <i class="bi bi-hash"></i> N° de notification <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="numero_notification" id="attribNumNotif" class="form-control" required placeholder="Ex: 0000001"
                                style="font-family:'Courier New',monospace; font-size:16px; font-weight:bold;">
                            <small class="text-muted">Numéro imprimé sur le PDF de notification d'attribution.</small>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted mb-2"><i class="bi bi-person"></i> Nouveau propriétaire</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">Civilité <span class="text-danger">*</span></label>
                            <select name="civilite" class="form-select" required>
                                <option value="Monsieur">Monsieur</option>
                                <option value="Madame">Madame</option>
                                <option value="Société">Société</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Nom</label>
                            <input type="text" name="nom" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Type pièce</label>
                            <select name="type_piece" class="form-select">
                                <option value="">--</option>
                                <option value="CNI">CNI</option>
                                <option value="Passeport">Passeport</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Code payé</label>
                            <input type="text" name="code_paye" class="form-control" placeholder="Ex: SN">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">N° pièce</label>
                            <input type="text" name="cni_passport" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Téléphone</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">NINEA</label>
                            <input type="text" name="ninea" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Adresse</label>
                            <input type="text" name="adresse" class="form-control">
                        </div>
                    </div>

                    <div id="forcePasswordWrapper" class="mt-3 d-none">
                        <label class="form-label fw-bold text-danger">
                            <i class="bi bi-shield-lock"></i> Mot de passe administrateur <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="force_password" class="form-control" autocomplete="off">
                        <small class="text-muted">Défini dans la variable d'environnement <code>ATTRIBUTION_FORCE_PASSWORD</code>.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn" style="background:#C8A951; color:#fff;" id="btnAttribuer">
                        <i class="bi bi-check-lg"></i> Attribuer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========== FILTRES PARCELLES ==========
    const rows = document.querySelectorAll('.parcelle-row');
    const searchInput = document.getElementById('searchParcelle');
    const filterBtns = document.querySelectorAll('.filter-btn');
    let currentFilter = 'tous';

    // Recherche
    searchInput.addEventListener('input', applyFilters);

    // Filtres
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });

    function applyFilters() {
        const search = searchInput.value.toLowerCase().trim();

        rows.forEach(row => {
            const matchFilter = currentFilter === 'tous' || row.dataset.filter === currentFilter;
            const matchSearch = !search || row.dataset.search.includes(search);
            row.style.display = matchFilter && matchSearch ? '' : 'none';
        });
    }

    // ========== MODAL ATTRIBUTION ==========
    const modalEl = document.getElementById('modalAttribuer');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const form = document.getElementById('formAttribuer');
    const alertForce = document.getElementById('alertForce');
    const forceWrap = document.getElementById('forcePasswordWrapper');
    const forceInput = forceWrap?.querySelector('input[name="force_password"]');
    const btnAttribuer = document.getElementById('btnAttribuer');
    const modalTitle = document.getElementById('modalAttribuerTitle');
    const modalLot = document.getElementById('modalAttribuerLot');
    const currentOwnerEl = document.getElementById('currentOwnerName');

    document.querySelectorAll('.btn-attribuer').forEach(btn => {
        btn.addEventListener('click', function() {
            const parcelleId = this.dataset.parcelleId;
            const hasOwner = this.dataset.hasOwner === '1';

            form.action = `/parcelles/${parcelleId}/attribuer`;
            modalLot.textContent = this.dataset.lot;
            form.reset();

            // Pré-remplir le N° de notification (suggestion auto)
            const next = '{{ str_pad((string) (\App\Models\Mutation::max("id") + 1), 7, "0", STR_PAD_LEFT) }}';
            const notifInput = document.getElementById('attribNumNotif');
            if (notifInput) notifInput.value = next;

            if (hasOwner) {
                modalTitle.textContent = "Forcer l'attribution de la parcelle";
                alertForce.classList.remove('d-none');
                forceWrap.classList.remove('d-none');
                if (forceInput) forceInput.required = true;
                currentOwnerEl.textContent = this.dataset.currentOwner || '';
                btnAttribuer.innerHTML = '<i class="bi bi-shield-lock"></i> Forcer l\'attribution';
                btnAttribuer.className = 'btn btn-danger';
            } else {
                modalTitle.textContent = 'Attribuer la parcelle';
                alertForce.classList.add('d-none');
                forceWrap.classList.add('d-none');
                if (forceInput) forceInput.required = false;
                btnAttribuer.innerHTML = '<i class="bi bi-check-lg"></i> Attribuer';
                btnAttribuer.className = 'btn';
                btnAttribuer.style.background = '#C8A951';
                btnAttribuer.style.color = '#fff';
            }

            modal.show();
        });
    });
});
</script>
@endpush
@endsection
