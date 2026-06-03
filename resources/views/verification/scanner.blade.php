@extends('layouts.app')
@section('title', 'Scanner QR Code')

@push('styles')
<style>
    .scanner-container { max-width: 700px; margin: 0 auto; }
    .video-wrapper {
        position: relative;
        background: #000;
        border-radius: 15px;
        overflow: hidden;
        aspect-ratio: 1;
        max-height: 400px;
    }
    .video-wrapper video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .scan-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    .scan-frame {
        width: 200px;
        height: 200px;
        border: 3px solid #20D5C0;
        border-radius: 15px;
        box-shadow: 0 0 0 9999px rgba(0,0,0,0.4);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { border-color: #20D5C0; }
        50% { border-color: #fff; }
    }
    .scan-line {
        position: absolute;
        width: 180px;
        height: 2px;
        background: #20D5C0;
        animation: scanMove 2s ease-in-out infinite;
    }
    @keyframes scanMove {
        0%, 100% { transform: translateY(-80px); }
        50% { transform: translateY(80px); }
    }
    .result-panel {
        background: #fff;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: none;
    }
    .result-panel.show { display: block; }
    .result-authentique { border-left: 4px solid #20D5C0; }
    .result-faux { border-left: 4px solid #dc3545; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .info-cell { padding: 10px; background: #f8f9fa; border-radius: 8px; }
    .info-cell .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-cell .value { font-size: 14px; font-weight: 600; color: #333; margin-top: 2px; }
    .camera-off {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 300px;
        color: #999;
    }
    .camera-off i { font-size: 48px; margin-bottom: 10px; }
    .tab-scan .nav-link {
        border-radius: 20px;
        padding: 8px 20px;
        font-size: 13px;
        font-weight: 600;
    }
    .tab-scan .nav-link.active { background: #20D5C0; border-color: #20D5C0; color: #fff; }
</style>
@endpush

@section('content')
<div class="scanner-container">
    <!-- Onglets Scanner / Saisie manuelle -->
    <ul class="nav nav-pills tab-scan justify-content-center mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="pill" href="#tabCamera">
                <i class="bi bi-camera-video"></i> Scanner avec caméra
            </a>
        </li>
        <li class="nav-item ms-2">
            <a class="nav-link" data-bs-toggle="pill" href="#tabManuel">
                <i class="bi bi-keyboard"></i> Saisie manuelle
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Onglet Caméra -->
        <div class="tab-pane fade show active" id="tabCamera">
            <div class="card mb-3">
                <div class="card-body p-0">
                    <div class="video-wrapper" id="videoWrapper">
                        <div class="camera-off" id="cameraOff">
                            <i class="bi bi-camera-video-off"></i>
                            <p>Caméra non démarrée</p>
                            <button class="btn btn-sm" style="background:#20D5C0; color:#fff;" id="btnStartCamera">
                                <i class="bi bi-play-fill"></i> Démarrer la caméra
                            </button>
                        </div>
                        <video id="videoElement" autoplay playsinline style="display:none;"></video>
                        <canvas id="canvasElement" style="display:none;"></canvas>
                        <div class="scan-overlay" id="scanOverlay" style="display:none;">
                            <div class="scan-frame">
                                <div class="scan-line"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-2 mb-3">
                <button class="btn btn-outline-danger btn-sm" id="btnStopCamera" style="display:none;">
                    <i class="bi bi-stop-fill"></i> Arrêter
                </button>
                <select class="form-select form-select-sm" id="cameraSelect" style="width:200px; display:none;">
                </select>
            </div>

            <div class="text-center mb-3">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> Placez le QR code du document devant la caméra
                </small>
            </div>
        </div>

        <!-- Onglet Saisie manuelle -->
        <div class="tab-pane fade" id="tabManuel">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-keyboard"></i> Saisir le code de vérification</h6>
                    <p class="text-muted small">Entrez le code hash qui se trouve sous le QR code du document.</p>
                    <div class="input-group">
                        <input type="text" id="inputCode" class="form-control" placeholder="Ex: b8f80da864df3775..." maxlength="64">
                        <button class="btn" style="background:#20D5C0; color:#fff;" id="btnVerifierManuel">
                            <i class="bi bi-search"></i> Vérifier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Résultat -->
    <div class="result-panel" id="resultPanel">
        <!-- Rempli par JS -->
    </div>

    <!-- Historique des scans de la session -->
    <div class="card mt-3" id="historiqueCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Historique des vérifications</h6>
            <button class="btn btn-sm btn-outline-secondary" id="btnClearHistorique">Effacer</button>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr><th>Heure</th><th>N° Notif.</th><th>Lot</th><th>Propriétaire</th><th>Résultat</th></tr>
                </thead>
                <tbody id="historiqueBody"></tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const video = document.getElementById('videoElement');
    const canvas = document.getElementById('canvasElement');
    const ctx = canvas.getContext('2d');
    const resultPanel = document.getElementById('resultPanel');
    const historiqueBody = document.getElementById('historiqueBody');
    const historiqueCard = document.getElementById('historiqueCard');

    let stream = null;
    let scanning = false;
    let lastScanned = '';

    // =====================
    // CAMÉRA
    // =====================
    document.getElementById('btnStartCamera').addEventListener('click', startCamera);
    document.getElementById('btnStopCamera').addEventListener('click', stopCamera);

    async function startCamera() {
        try {
            // Lister les caméras
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter(d => d.kind === 'videoinput');
            const select = document.getElementById('cameraSelect');

            if (cameras.length > 1) {
                select.innerHTML = '';
                cameras.forEach((cam, i) => {
                    const opt = document.createElement('option');
                    opt.value = cam.deviceId;
                    opt.text = cam.label || `Caméra ${i + 1}`;
                    select.appendChild(opt);
                });
                select.style.display = 'inline-block';
                select.addEventListener('change', () => switchCamera(select.value));
            }

            // Préférer la caméra arrière sur mobile
            const constraints = {
                video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 640 } }
            };

            stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('cameraOff').style.display = 'none';
            document.getElementById('scanOverlay').style.display = 'flex';
            document.getElementById('btnStopCamera').style.display = 'inline-block';

            scanning = true;
            scanFrame();
        } catch (err) {
            showToast('Impossible d\'accéder à la caméra. Vérifiez les permissions.', 'danger');
        }
    }

    async function switchCamera(deviceId) {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
        }
        stream = await navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: deviceId }, width: { ideal: 640 }, height: { ideal: 640 } }
        });
        video.srcObject = stream;
    }

    function stopCamera() {
        scanning = false;
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.style.display = 'none';
        document.getElementById('cameraOff').style.display = 'flex';
        document.getElementById('scanOverlay').style.display = 'none';
        document.getElementById('btnStopCamera').style.display = 'none';
        document.getElementById('cameraSelect').style.display = 'none';
    }

    function scanFrame() {
        if (!scanning) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert',
            });

            if (code && code.data && code.data !== lastScanned) {
                lastScanned = code.data;

                // Arrêter la caméra immédiatement
                stopCamera();

                // Extraire le hash de l'URL
                let hash = code.data;
                const match = hash.match(/verification\/([a-f0-9]{64})/);
                if (match) {
                    hash = match[1];
                }

                verifierCode(hash);
            }
        }

        requestAnimationFrame(scanFrame);
    }

    // =====================
    // VÉRIFICATION MANUELLE
    // =====================
    document.getElementById('btnVerifierManuel').addEventListener('click', function() {
        const code = document.getElementById('inputCode').value.trim();
        if (!code) { showToast('Veuillez saisir un code.', 'warning'); return; }
        verifierCode(code);
    });

    document.getElementById('inputCode').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') document.getElementById('btnVerifierManuel').click();
    });

    // =====================
    // VÉRIFICATION API
    // =====================
    function verifierCode(code) {
        resultPanel.className = 'result-panel show';
        resultPanel.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Vérification en cours...</p></div>';

        fetch('/verification/manuel', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ code: code })
        })
        .then(r => r.json())
        .then(data => {
            if (data.authentique) {
                const m = data.mutation;
                const isAnnulee = m.statut === 'annulee';

                resultPanel.className = 'result-panel show ' + (isAnnulee ? 'result-faux' : 'result-authentique');
                resultPanel.innerHTML = `
                    <div class="text-center mb-3">
                        <i class="bi ${isAnnulee ? 'bi-exclamation-triangle-fill text-warning' : 'bi-check-circle-fill'}" style="font-size:48px; ${isAnnulee ? 'color:#fd7e14;' : 'color:#20D5C0;'}"></i>
                        <h5 class="mt-2" style="color: ${isAnnulee ? '#fd7e14' : '#20D5C0'};">
                            ${isAnnulee ? 'Document annulé' : 'Document authentique'}
                        </h5>
                        ${isAnnulee ? '<p class="text-danger small">Cette mutation a été annulée. Le document n\'est plus valide.</p>' : '<p class="text-muted small">Ce document est vérifié et enregistré dans le système.</p>'}
                    </div>
                    <div class="info-grid">
                        <div class="info-cell">
                            <div class="label">N° Notification</div>
                            <div class="value">${m.numero_notification}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Date mutation</div>
                            <div class="value">${m.date_mutation}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Numéro de lot</div>
                            <div class="value">${m.lot}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Projet</div>
                            <div class="value">${m.projet}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Commune</div>
                            <div class="value">${m.commune}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Statut</div>
                            <div class="value"><span class="badge" style="background:${isAnnulee ? '#dc3545' : '#20D5C0'};">${m.statut}</span></div>
                        </div>
                        <div class="info-cell" style="grid-column: span 2;">
                            <div class="label">Nouveau propriétaire</div>
                            <div class="value">${m.nouveau_proprietaire || 'N/A'}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">CNI / Passeport</div>
                            <div class="value">${m.cni || 'N/A'}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Téléphone</div>
                            <div class="value">${m.telephone || 'N/A'}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Ancien propriétaire</div>
                            <div class="value">${m.ancien_proprietaire || 'N/A'}</div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Validé par</div>
                            <div class="value">${m.validateur || 'N/A'}</div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="${m.url}" class="btn btn-sm" style="background:#20D5C0; color:#fff;">
                            <i class="bi bi-eye"></i> Voir la mutation
                        </a>
                        <a href="/mutations/${m.id}/apercu" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-file-pdf"></i> Voir le document
                        </a>
                    </div>
                `;

                ajouterHistorique(m, true);
            } else {
                resultPanel.className = 'result-panel show result-faux';
                resultPanel.innerHTML = `
                    <div class="text-center mb-3">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size:48px;"></i>
                        <h5 class="mt-2 text-danger">Document non authentique</h5>
                        <p class="text-muted small">Ce code de vérification ne correspond à aucun document enregistré. Le document pourrait être falsifié.</p>
                    </div>
                    <div class="alert alert-danger text-center mb-0">
                        <i class="bi bi-shield-exclamation"></i> Veuillez contacter le Bureau des Domaines pour vérification.
                    </div>
                `;

                ajouterHistorique(null, false);
            }
        })
        .catch(() => {
            resultPanel.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-wifi-off"></i> Erreur de connexion. Veuillez réessayer.</div>';
        });
    }

    // =====================
    // HISTORIQUE
    // =====================
    function ajouterHistorique(mutation, authentique) {
        historiqueCard.style.display = 'block';
        const now = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const tr = document.createElement('tr');
        if (authentique && mutation) {
            tr.innerHTML = `
                <td>${now}</td>
                <td>${mutation.numero_notification}</td>
                <td>${mutation.lot}</td>
                <td>${mutation.nouveau_proprietaire || '-'}</td>
                <td><span class="badge" style="background:#20D5C0;">Authentique</span></td>
            `;
        } else {
            tr.innerHTML = `
                <td>${now}</td>
                <td colspan="3" class="text-muted">Code invalide</td>
                <td><span class="badge bg-danger">Non authentique</span></td>
            `;
        }
        historiqueBody.prepend(tr);
    }

    document.getElementById('btnClearHistorique').addEventListener('click', function() {
        historiqueBody.innerHTML = '';
        historiqueCard.style.display = 'none';
    });
});
</script>
@endpush
@endsection
