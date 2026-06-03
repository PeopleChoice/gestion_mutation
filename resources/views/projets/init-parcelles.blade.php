@extends('layouts.app')
@section('title', 'Initialiser les parcelles - ' . $projet->nom)

@push('styles')
<style>
    .verification-panel { display: none; margin-top: 15px; }
    .verification-panel.show { display: block; }
    .verif-item { padding: 6px 10px; border-radius: 6px; margin-bottom: 4px; font-size: 13px; display: flex; align-items: flex-start; gap: 8px; }
    .verif-item.erreur { background: #ffebee; color: #c62828; }
    .verif-item.avertissement { background: #fff8e1; color: #e65100; }
    .verif-item.info { background: #e3f2fd; color: #1565c0; }
    .verif-item.ok { background: #e8f5e9; color: #2e7d32; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 10px; }
    .stat-mini { text-align: center; padding: 10px; border-radius: 8px; background: #f8f9fa; }
    .stat-mini .number { font-size: 20px; font-weight: 700; }
    .stat-mini .label { font-size: 11px; color: #888; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-file-earmark-arrow-up"></i> Initialiser les parcelles</h4>
        <p class="text-muted mb-0 small">{{ $projet->nom }} — {{ $projet->commune->nom }}</p>
    </div>
    <a href="{{ route('projets.show', $projet) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour au projet</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">
    <!-- Colonne gauche : Import -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-upload"></i> Importer un fichier d'attribution</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('projets.importer-parcelles', $projet) }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Fichier Excel <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" id="fichierInput" class="form-control" accept=".xls,.xlsx" required>
                        <small class="text-muted">
                            Format : LOT (obligatoire) + Civilité, Prénom, NOM, Type pièce, Code payé, N° pièce, NINEA, Téléphone, Adresse, Date attribution, Observation.
                        </small>
                    </div>

                    <!-- Panneau de vérification -->
                    <div class="verification-panel" id="verifPanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="bi bi-shield-check"></i> Vérification du fichier</h6>
                            <span id="verifStatus"></span>
                        </div>

                        <div id="verifLoading" style="display:none;" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm" style="color:#3b82f6;"></div>
                            <span class="ms-2 text-muted">Analyse du fichier en cours...</span>
                        </div>

                        <div id="verifResultats"></div>

                        <div id="verifStats" style="display:none;">
                            <div class="stats-grid">
                                <div class="stat-mini">
                                    <div class="number" id="statTotal" style="color:#3b82f6;">0</div>
                                    <div class="label">Lots</div>
                                </div>
                                <div class="stat-mini">
                                    <div class="number text-success" id="statAvecProp">0</div>
                                    <div class="label">Avec attribution</div>
                                </div>
                                <div class="stat-mini">
                                    <div class="number" id="statSansProp" style="color:#fd7e14;">0</div>
                                    <div class="label">Vierges</div>
                                </div>
                                <div class="stat-mini">
                                    <div class="number text-secondary" id="statExist">0</div>
                                    <div class="label">Déjà existants</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary" id="btnVerifier" style="display:none;">
                            <i class="bi bi-shield-check"></i> Vérifier le fichier
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnImporter" disabled>
                            <i class="bi bi-upload"></i> Importer
                        </button>
                        <a href="{{ route('projets.show', $projet) }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Colonne droite : Template -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header" style="background:#eff6ff;">
                <h6 class="mb-0"><i class="bi bi-file-earmark-arrow-down"></i> Télécharger un template</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Template Excel pré-rempli avec les lots existants du projet (vide si projet sans parcelles).</p>
                <a href="{{ route('projets.init-parcelles.template', $projet) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download"></i> Télécharger template
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Mode d'emploi</h6>
            </div>
            <div class="card-body small">
                <ol class="ps-3 mb-0">
                    <li>Téléchargez le template Excel</li>
                    <li>Remplissez la colonne <strong>LOT</strong> avec les numéros de parcelles</li>
                    <li><strong>Optionnel</strong> : remplissez les infos du nouveau propriétaire pour attribuer directement la parcelle. Sinon laissez vide → la parcelle sera vierge et pourra être attribuée plus tard depuis le projet.</li>
                    <li>Uploadez le fichier ici → la vérification s'exécute automatiquement</li>
                    <li>Cliquez sur <strong>Importer</strong> pour créer les parcelles</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const fichierInput = document.getElementById('fichierInput');
    const btnVerifier = document.getElementById('btnVerifier');
    const btnImporter = document.getElementById('btnImporter');
    const verifPanel = document.getElementById('verifPanel');

    fichierInput.addEventListener('change', function() {
        if (fichierInput.files.length > 0) {
            btnVerifier.style.display = 'inline-block';
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

        fetch('{{ route("projets.init-parcelles.verifier", $projet) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('verifLoading').style.display = 'none';
            let html = '';
            data.erreurs.forEach(msg => html += `<div class="verif-item erreur"><i class="bi bi-x-circle-fill"></i> ${msg}</div>`);
            data.avertissements.forEach(msg => html += `<div class="verif-item avertissement"><i class="bi bi-exclamation-triangle-fill"></i> ${msg}</div>`);
            data.infos.forEach(msg => html += `<div class="verif-item info"><i class="bi bi-info-circle-fill"></i> ${msg}</div>`);

            if (data.valid) {
                html += `<div class="verif-item ok"><i class="bi bi-check-circle-fill"></i> <strong>Fichier valide</strong> — prêt pour l'import.</div>`;
                document.getElementById('verifStatus').innerHTML = '<span class="badge bg-success">Valide</span>';
                btnImporter.disabled = false;
            } else {
                html += `<div class="verif-item erreur"><i class="bi bi-x-circle-fill"></i> <strong>Fichier invalide</strong> — corrigez les erreurs.</div>`;
                document.getElementById('verifStatus').innerHTML = '<span class="badge bg-danger">Invalide</span>';
                btnImporter.disabled = true;
            }

            document.getElementById('verifResultats').innerHTML = html;

            if (data.stats) {
                document.getElementById('statTotal').textContent = data.stats.total;
                document.getElementById('statAvecProp').textContent = data.stats.avec_proprio;
                document.getElementById('statSansProp').textContent = data.stats.sans_proprio;
                document.getElementById('statExist').textContent = data.stats.existants;
                document.getElementById('verifStats').style.display = 'block';
            }
        })
        .catch(() => {
            document.getElementById('verifLoading').style.display = 'none';
            document.getElementById('verifResultats').innerHTML = '<div class="verif-item erreur"><i class="bi bi-wifi-off"></i> Erreur de connexion.</div>';
        });
    }
});
</script>
@endpush
@endsection
