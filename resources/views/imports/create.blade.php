@extends('layouts.app')
@section('title', 'Importer un fichier Excel')

@push('styles')
<style>
    .verification-panel { display: none; margin-top: 15px; }
    .verification-panel.show { display: block; }
    .verif-item { padding: 6px 10px; border-radius: 6px; margin-bottom: 4px; font-size: 13px; display: flex; align-items: flex-start; gap: 8px; }
    .verif-item.erreur { background: #ffebee; color: #c62828; }
    .verif-item.avertissement { background: #fff8e1; color: #e65100; }
    .verif-item.info { background: #e3f2fd; color: #1565c0; }
    .verif-item.ok { background: #e8f5e9; color: #2e7d32; }
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
    .stat-mini { text-align: center; padding: 10px; border-radius: 8px; background: #f8f9fa; }
    .stat-mini .number { font-size: 20px; font-weight: 700; }
    .stat-mini .label { font-size: 11px; color: #888; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <!-- Colonne gauche : Import -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-upload"></i> Importer un fichier de mutation</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Projet (Lotissement) <span class="text-danger">*</span></label>
                        <select name="projet_id" id="projetSelect" class="form-select" required>
                            <option value="">-- Sélectionner un projet --</option>
                            @foreach($projets as $projet)
                                <option value="{{ $projet->id }}" {{ old('projet_id') == $projet->id ? 'selected' : '' }}>
                                    {{ $projet->nom }} - {{ $projet->commune->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fichier Excel <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" id="fichierInput" class="form-control" accept=".xls,.xlsx" required>
                        <small class="text-muted">
                            Format (2 lignes d'en-tête) : groupes <strong>LOT • NOUVEAU PROPRIÉTAIRE • DEMANDEUR • DÉTAILS MUTATION</strong>.<br>
                            Colonnes : N° O | LOT | Civilité, Prénom, NOM, Type pièce, Code payé, N° pièce, NINEA, Téléphone | Prénom demandeur, NOM demandeur, Tél demandeur | Réf. Lettre, Date, Observation.<br>
                            <i class="bi bi-info-circle"></i> Le précédent propriétaire est lu automatiquement depuis la base — uniquement les <strong>lots déjà attribués</strong> sont mutables.
                        </small>
                    </div>

                    <!-- Panneau de vérification -->
                    <div class="verification-panel" id="verifPanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="bi bi-shield-check"></i> Vérification du fichier</h6>
                            <span id="verifStatus"></span>
                        </div>

                        <div id="verifLoading" style="display:none;" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm" style="color:#20D5C0;"></div>
                            <span class="ms-2 text-muted">Analyse du fichier en cours...</span>
                        </div>

                        <div id="verifResultats"></div>

                        <div id="verifStats" style="display:none;">
                            <div class="stats-grid">
                                <div class="stat-mini">
                                    <div class="number" id="statTotal" style="color:#20D5C0;">0</div>
                                    <div class="label">Lignes</div>
                                </div>
                                <div class="stat-mini">
                                    <div class="number text-success" id="statTrouves">0</div>
                                    <div class="label">Correspondances</div>
                                </div>
                                <div class="stat-mini">
                                    <div class="number text-danger" id="statNonTrouves">0</div>
                                    <div class="label">Non trouvés</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary" id="btnVerifier" style="display:none;">
                            <i class="bi bi-shield-check"></i> Vérifier le fichier
                        </button>
                        <button type="submit" class="btn" style="background:#20D5C0; color:#fff;" id="btnImporter" disabled>
                            <i class="bi bi-upload"></i> Importer
                        </button>
                        <a href="{{ route('imports.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Colonne droite : Template + Email -->
    <div class="col-md-5">
        <!-- Télécharger template -->
        <div class="card mb-3">
            <div class="card-header" style="background:#E6FAF8;">
                <h6 class="mb-0"><i class="bi bi-file-earmark-arrow-down"></i> Télécharger un template</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Téléchargez un fichier Excel pré-rempli avec les lots du projet.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Projet</label>
                    <select class="form-select form-select-sm" id="templateProjet">
                        <option value="">-- Sélectionner --</option>
                        @foreach($projets as $projet)
                            <option value="{{ $projet->id }}">{{ $projet->nom }} - {{ $projet->commune->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="avecLots" checked>
                    <label class="form-check-label small" for="avecLots">
                        Pré-remplir avec les lots et anciens propriétaires
                    </label>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="telechargerTemplate()">
                    <i class="bi bi-download"></i> Télécharger Excel
                </button>
            </div>
        </div>

        <!-- Envoyer par email -->
        <div class="card">
            <div class="card-header" style="background:#E6FAF8;">
                <h6 class="mb-0"><i class="bi bi-envelope"></i> Envoyer par email</h6>
            </div>
            <div class="card-body">
                <form id="formEmail" method="POST" action="">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Projet</label>
                        <select class="form-select form-select-sm" id="emailProjet" required>
                            <option value="">-- Sélectionner --</option>
                            @foreach($projets as $projet)
                                <option value="{{ $projet->id }}">{{ $projet->nom }} - {{ $projet->commune->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Nom du promoteur <span class="text-danger">*</span></label>
                        <input type="text" name="nom_promoteur" class="form-control form-control-sm" required placeholder="Ex: Djibril THIOMBANE">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm" required placeholder="promoteur@email.com">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Message</label>
                        <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Instructions..."></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="avec_lots" value="1" id="emailAvecLots" checked>
                        <label class="form-check-label small" for="emailAvecLots">Inclure les lots</label>
                    </div>
                    <button type="submit" class="btn btn-sm" style="background:#20D5C0; color:#fff;" onclick="return envoyerEmail()">
                        <i class="bi bi-send"></i> Envoyer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const fichierInput = document.getElementById('fichierInput');
    const projetSelect = document.getElementById('projetSelect');
    const btnVerifier = document.getElementById('btnVerifier');
    const btnImporter = document.getElementById('btnImporter');
    const verifPanel = document.getElementById('verifPanel');

    // Afficher le bouton vérifier quand fichier + projet sont sélectionnés
    function checkReady() {
        const ready = fichierInput.files.length > 0 && projetSelect.value;
        btnVerifier.style.display = ready ? 'inline-block' : 'none';
        // Désactiver import tant que pas vérifié
        btnImporter.disabled = true;
        verifPanel.classList.remove('show');
    }

    fichierInput.addEventListener('change', function() {
        checkReady();
        // Lancer la vérification automatiquement
        if (fichierInput.files.length > 0 && projetSelect.value) {
            lancerVerification();
        }
    });

    projetSelect.addEventListener('change', function() {
        checkReady();
        if (fichierInput.files.length > 0 && projetSelect.value) {
            lancerVerification();
        }
    });

    btnVerifier.addEventListener('click', lancerVerification);

    function lancerVerification() {
        verifPanel.classList.add('show');
        document.getElementById('verifLoading').style.display = 'block';
        document.getElementById('verifResultats').innerHTML = '';
        document.getElementById('verifStats').style.display = 'none';
        document.getElementById('verifStatus').innerHTML = '';
        btnImporter.disabled = true;

        const formData = new FormData();
        formData.append('fichier', fichierInput.files[0]);
        formData.append('projet_id', projetSelect.value);

        fetch('{{ route("imports.verifier") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('verifLoading').style.display = 'none';

            let html = '';

            // Erreurs
            data.erreurs.forEach(msg => {
                html += `<div class="verif-item erreur"><i class="bi bi-x-circle-fill"></i> ${msg}</div>`;
            });

            // Avertissements
            data.avertissements.forEach(msg => {
                html += `<div class="verif-item avertissement"><i class="bi bi-exclamation-triangle-fill"></i> ${msg}</div>`;
            });

            // Infos
            data.infos.forEach(msg => {
                html += `<div class="verif-item info"><i class="bi bi-info-circle-fill"></i> ${msg}</div>`;
            });

            // Résultat global
            if (data.valid) {
                html += `<div class="verif-item ok"><i class="bi bi-check-circle-fill"></i> <strong>Fichier valide</strong> - prêt pour l'import.</div>`;
                document.getElementById('verifStatus').innerHTML = '<span class="badge" style="background:#20D5C0;">Valide</span>';
                btnImporter.disabled = false;
            } else {
                html += `<div class="verif-item erreur"><i class="bi bi-x-circle-fill"></i> <strong>Fichier invalide</strong> - corrigez les erreurs avant d'importer.</div>`;
                document.getElementById('verifStatus').innerHTML = '<span class="badge bg-danger">Invalide</span>';
                btnImporter.disabled = true;
            }

            document.getElementById('verifResultats').innerHTML = html;

            // Stats
            if (data.stats) {
                document.getElementById('statTotal').textContent = data.stats.total;
                document.getElementById('statTrouves').textContent = data.stats.trouves;
                document.getElementById('statNonTrouves').textContent = data.stats.non_trouves;
                document.getElementById('verifStats').style.display = 'block';
            }
        })
        .catch(() => {
            document.getElementById('verifLoading').style.display = 'none';
            document.getElementById('verifResultats').innerHTML = '<div class="verif-item erreur"><i class="bi bi-wifi-off"></i> Erreur de connexion.</div>';
        });
    }
});

function telechargerTemplate() {
    const projetId = document.getElementById('templateProjet').value;
    if (!projetId) { showToast('Sélectionnez un projet.', 'warning'); return; }
    const avecLots = document.getElementById('avecLots').checked ? '1' : '0';
    window.location.href = '/imports/template/' + projetId + '?avec_lots=' + avecLots;
}

function envoyerEmail() {
    const projetId = document.getElementById('emailProjet').value;
    if (!projetId) { showToast('Sélectionnez un projet.', 'warning'); return false; }
    const form = document.getElementById('formEmail');
    form.action = '/imports/envoyer-template/' + projetId;
    showConfirm('Envoyer le template par email ?', () => { form._confirmed = true; form.requestSubmit(); });
    return false;
}
</script>
@endpush
@endsection
