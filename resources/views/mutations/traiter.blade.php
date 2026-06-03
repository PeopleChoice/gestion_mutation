@extends('layouts.app')
@section('title', 'Traiter les mutations - ' . $import->projet->nom)

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <strong>Import :</strong> {{ $import->nom_fichier }}<br>
                <strong>Projet :</strong> {{ $import->projet->nom }} - {{ $import->projet->commune->nom }}
            </div>
            <div class="col-md-6 text-end">
                <span class="badge bg-success fs-6">{{ $import->lignes_matchees }} correspondances</span>
                <span class="badge bg-secondary fs-6">{{ $import->total_lignes }} lignes</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="lignesTable">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Demandeur (Excel)</th>
                    <th>Lot</th>
                    <th>Propriétaire actuel</th>
                    <th>Correspondance</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($import->lignes as $ligne)
                <tr id="ligne-{{ $ligne->id }}">
                    <td>{{ $ligne->numero_ordre }}</td>
                    <td>
                        <strong>{{ $ligne->civilite }} {{ $ligne->prenom }} {{ $ligne->nom }}</strong><br>
                        <small class="text-muted">
                            @if($ligne->telephone) Tél: {{ $ligne->telephone }} @endif
                            @if($ligne->type_piece || $ligne->cni_passport)
                                <br>{{ $ligne->type_piece }}{{ $ligne->code_paye ? '_' . $ligne->code_paye : '' }}
                                {{ $ligne->cni_passport ? 'n° ' . $ligne->cni_passport : '' }}
                            @endif
                            @if($ligne->ninea) NINEA: {{ $ligne->ninea }} @endif
                        </small>
                    </td>
                    <td><strong>{{ $ligne->numero_lot }}</strong></td>
                    <td>
                        @if($ligne->parcelle && $ligne->parcelle->proprietaire)
                            <strong>{{ $ligne->parcelle->proprietaire->nom_complet }}</strong><br>
                            <small class="text-muted">
                                CNI: {{ $ligne->parcelle->proprietaire->cni_passport ?? 'N/A' }}
                            </small>
                        @else
                            <span class="text-muted">Aucun propriétaire</span>
                        @endif
                    </td>
                    <td>
                        @if($ligne->matched)
                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> Trouvé</span>
                        @else
                            <span class="badge bg-warning">Non trouvé</span>
                        @endif
                    </td>
                    <td>
                        <span class="statut-badge" id="statut-{{ $ligne->id }}">
                            @if($ligne->statut === 'validee')
                                <span class="badge bg-success">Validée</span>
                            @elseif($ligne->statut === 'refusee')
                                <span class="badge bg-danger">Refusée</span>
                            @else
                                <span class="badge bg-secondary">En attente</span>
                            @endif
                        </span>
                    </td>
                    <td>
                        @if($ligne->statut === 'en_attente' && $ligne->matched)
                            <button class="btn btn-sm btn-success btn-valider" data-ligne-id="{{ $ligne->id }}"
                                data-lot="{{ $ligne->numero_lot }}"
                                data-civilite="{{ $ligne->civilite }}"
                                data-prenom="{{ $ligne->prenom }}"
                                data-nom="{{ $ligne->nom }}"
                                data-type-piece="{{ $ligne->type_piece }}"
                                data-code-paye="{{ $ligne->code_paye }}"
                                data-cni="{{ $ligne->cni_passport }}"
                                data-ninea="{{ $ligne->ninea }}"
                                data-telephone="{{ $ligne->telephone }}"
                                data-ref="{{ $ligne->ref_lettre }}">
                                <i class="bi bi-check-lg"></i> Valider
                            </button>
                            <button class="btn btn-sm btn-danger btn-refuser" data-ligne-id="{{ $ligne->id }}" data-lot="{{ $ligne->numero_lot }}">
                                <i class="bi bi-x-lg"></i> Refuser
                            </button>
                        @elseif($ligne->statut === 'en_attente' && !$ligne->matched)
                            <span class="text-muted small">Pas de correspondance</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Validation -->
<div class="modal fade" id="modalValider" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle"></i> Valider la mutation - Lot <span id="modal-lot"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="text-muted mb-3">Informations du nouveau propriétaire</h6>
                <form id="formValider">
                    <input type="hidden" id="val-ligne-id">
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
                            <label class="form-label">Code pays</label>
                            <input type="text" id="val-code-paye" class="form-control" placeholder="Ex: SN">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">N° pièce <span class="text-danger">*</span></label>
                            <input type="text" id="val-cni" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" id="val-telephone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NINEA (Société)</label>
                            <input type="text" id="val-ninea" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adresse</label>
                            <input type="text" id="val-adresse" class="form-control">
                        </div>
                        <div class="col-md-6">
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

<!-- Modal Refus -->
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Ouvrir modal validation
    document.querySelectorAll('.btn-valider').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('val-ligne-id').value = this.dataset.ligneId;
            document.getElementById('modal-lot').textContent = this.dataset.lot;
            document.getElementById('val-civilite').value = this.dataset.civilite || 'Monsieur';
            document.getElementById('val-prenom').value = this.dataset.prenom || '';
            document.getElementById('val-nom').value = this.dataset.nom || '';
            document.getElementById('val-type-piece').value = this.dataset.typePiece || 'CNI';
            document.getElementById('val-code-paye').value = this.dataset.codePaye || '';
            document.getElementById('val-cni').value = this.dataset.cni || '';
            document.getElementById('val-ninea').value = this.dataset.ninea || '';
            document.getElementById('val-telephone').value = this.dataset.telephone || '';
            document.getElementById('val-ref').value = this.dataset.ref || '';
            new bootstrap.Modal(document.getElementById('modalValider')).show();
        });
    });

    // Confirmer validation
    document.getElementById('btnConfirmValider').addEventListener('click', function() {
        const ligneId = document.getElementById('val-ligne-id').value;
        const cni = document.getElementById('val-cni').value.trim();
        const prenom = document.getElementById('val-prenom').value.trim();
        const nom = document.getElementById('val-nom').value.trim();

        if (!cni || !prenom || !nom) {
            showToast('Veuillez remplir les champs obligatoires (Prénom, Nom, CNI/Passeport).', 'warning');
            return;
        }

        const data = {
            civilite: document.getElementById('val-civilite').value,
            prenom: prenom,
            nom: nom,
            type_piece: document.getElementById('val-type-piece').value,
            code_paye: document.getElementById('val-code-paye').value,
            cni_passport: cni,
            ninea: document.getElementById('val-ninea').value,
            telephone: document.getElementById('val-telephone').value,
            adresse: document.getElementById('val-adresse').value,
            ref_lettre: document.getElementById('val-ref').value,
        };

        fetch(`/mutations/valider/${ligneId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('statut-' + ligneId).innerHTML = '<span class="badge bg-success">Validée</span>';
                const row = document.getElementById('ligne-' + ligneId);
                row.querySelector('td:last-child').innerHTML = `<a href="/mutations/${res.mutation_id}/pdf" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-pdf"></i> PDF</a>`;
                bootstrap.Modal.getInstance(document.getElementById('modalValider')).hide();
            } else {
                showToast(res.message || 'Erreur lors de la validation.', 'danger');
            }
        })
        .catch(() => showToast('Erreur de connexion.', 'danger'));
    });

    // Ouvrir modal refus
    document.querySelectorAll('.btn-refuser').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('ref-ligne-id').value = this.dataset.ligneId;
            document.getElementById('modal-lot-refus').textContent = this.dataset.lot;
            document.getElementById('ref-motif').value = '';
            new bootstrap.Modal(document.getElementById('modalRefuser')).show();
        });
    });

    // Confirmer refus
    document.getElementById('btnConfirmRefuser').addEventListener('click', function() {
        const ligneId = document.getElementById('ref-ligne-id').value;
        const motif = document.getElementById('ref-motif').value.trim();

        if (!motif) {
            showToast('Veuillez indiquer le motif du refus.', 'warning');
            return;
        }

        fetch(`/mutations/refuser/${ligneId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ motif_refus: motif })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('statut-' + ligneId).innerHTML = '<span class="badge bg-danger">Refusée</span>';
                const row = document.getElementById('ligne-' + ligneId);
                row.querySelector('td:last-child').innerHTML = '<span class="text-danger small">Refusée</span>';
                bootstrap.Modal.getInstance(document.getElementById('modalRefuser')).hide();
            } else {
                showToast(res.message || 'Erreur lors du refus.', 'danger');
            }
        })
        .catch(() => showToast('Erreur de connexion.', 'danger'));
    });
});
</script>
@endpush
@endsection
