<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de document - Impôts & Domaines</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .verify-card { background: #fff; border-radius: 15px; padding: 40px; max-width: 550px; width: 100%; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .verify-header { text-align: center; margin-bottom: 25px; }
        .verify-header h5 { color: #0f6e63; font-weight: 700; }
        .status-icon { font-size: 60px; text-align: center; margin-bottom: 15px; }
        .status-icon.ok { color: #20D5C0; }
        .status-icon.ko { color: #dc3545; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #888; }
        .info-value { font-weight: 600; text-align: right; }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="verify-header">
            <h5>DIRECTION GÉNÉRALE DES IMPÔTS ET DES DOMAINES</h5>
            <small class="text-muted">Vérification d'authenticité de document</small>
            <hr>
        </div>

        @if($authentique)
            <div class="status-icon ok">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h4 class="text-center" style="color: #20D5C0;">Document authentique</h4>
            <p class="text-center text-muted mb-4">Ce document a été vérifié et correspond à une mutation enregistrée dans le système.</p>

            <div class="bg-light rounded p-3 mb-3">
                <div class="info-row">
                    <span class="info-label">N° Notification</span>
                    <span class="info-value">{{ $mutation->numero_notification }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de mutation</span>
                    <span class="info-value">{{ $mutation->date_mutation?->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lot</span>
                    <span class="info-value">{{ $mutation->parcelle->numero_lot }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Projet</span>
                    <span class="info-value">{{ $mutation->parcelle->projet->nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Commune</span>
                    <span class="info-value">{{ $mutation->parcelle->projet->commune->nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Propriétaire</span>
                    <span class="info-value">
                        @if($isAuth ?? false)
                            {{ $mutation->nouveauProprietaire?->nom_complet }}
                        @else
                            @php
                                $prop = $mutation->nouveauProprietaire;
                                $civ = $prop?->civilite ? $prop->civilite . ' ' : '';
                                $initiale = $prop?->prenom ? mb_substr($prop->prenom, 0, 1) . '.' : '';
                                $nom = $prop?->nom ?? '';
                            @endphp
                            {{ trim($civ . $initiale . ' ' . $nom) ?: '—' }}
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="info-value">
                        @if($mutation->statut === 'validee')
                            <span class="badge" style="background:#20D5C0;">Validée</span>
                        @elseif($mutation->statut === 'annulee')
                            <span class="badge bg-danger">Annulée</span>
                        @endif
                    </span>
                </div>
                @if($isAuth ?? false)
                <div class="info-row">
                    <span class="info-label">Validé par</span>
                    <span class="info-value">{{ $mutation->validateur?->name }}</span>
                </div>
                @endif
            </div>

            @if($mutation->statut === 'annulee')
                <div class="alert alert-danger text-center">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Attention :</strong> Cette mutation a été annulée. Le document n'est plus valide.
                </div>
            @endif

        @else
            <div class="status-icon ko">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h4 class="text-center text-danger">Document non authentique</h4>
            <p class="text-center text-muted mb-4">Ce code de vérification ne correspond à aucun document enregistré dans le système. Le document pourrait être falsifié.</p>

            <div class="alert alert-danger text-center">
                <i class="bi bi-shield-exclamation"></i>
                Veuillez contacter le Bureau des Domaines pour vérification.
            </div>
        @endif

        <div class="text-center mt-3">
            <small class="text-muted">Code : {{ Str::limit($hash, 16, '...') }}</small>
        </div>
    </div>
</body>
</html>
