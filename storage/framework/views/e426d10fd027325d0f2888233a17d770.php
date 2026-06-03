<?php $__env->startSection('title', $mutation->type_libelle . ' - Notification N°' . $mutation->numero_notification); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .apercu-container {
        display: flex;
        gap: 20px;
        height: calc(100vh - 140px);
    }
    .apercu-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .apercu-main {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .apercu-toolbar {
        background: #fff;
        border-radius: 10px 10px 0 0;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e0e0e0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .apercu-iframe-wrapper {
        flex: 1;
        background: #525659;
        border-radius: 0 0 10px 10px;
        padding: 0;
        overflow: hidden;
    }
    .apercu-iframe-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    .info-item {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .info-item:last-child { border-bottom: none; }
    .info-label { color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-weight: 600; color: #333; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="apercu-container">
    <!-- Sidebar infos -->
    <div class="apercu-sidebar">
        <div class="card mb-3">
            <div class="card-header <?php echo e($mutation->est_attribution ? '' : 'bg-success bg-opacity-10'); ?>" style="<?php echo e($mutation->est_attribution ? 'background:#dbeafe;' : ''); ?>">
                <h6 class="mb-0 <?php echo e($mutation->est_attribution ? '' : 'text-success'); ?>" style="<?php echo e($mutation->est_attribution ? 'color:#1e40af;' : ''); ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    Notification d'<?php echo e(strtolower($mutation->type_libelle)); ?>

                </h6>
            </div>
            <div class="card-body py-2">
                <div class="info-item">
                    <div class="info-label">N° Notification</div>
                    <div class="info-value"><?php echo e($mutation->numero_notification); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date</div>
                    <div class="info-value"><?php echo e($mutation->date_mutation?->format('d/m/Y')); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Lot</div>
                    <div class="info-value"><?php echo e($mutation->parcelle->numero_lot); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Projet</div>
                    <div class="info-value"><?php echo e($mutation->parcelle->projet->nom); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Commune</div>
                    <div class="info-value"><?php echo e($mutation->parcelle->projet->commune->nom); ?></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person-check"></i> Nouveau attributaire</h6>
            </div>
            <div class="card-body py-2">
                <?php if($mutation->nouveauProprietaire): ?>
                <div class="info-item">
                    <div class="info-label">Prénom</div>
                    <div class="info-value"><?php echo e($mutation->nouveauProprietaire->prenom ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nom</div>
                    <div class="info-value"><?php echo e($mutation->nouveauProprietaire->nom ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?php echo e($mutation->nouveauProprietaire->telephone ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pièce</div>
                    <div class="info-value"><?php echo e($mutation->piece_formatee ?: 'N/A'); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if(!$mutation->est_attribution): ?>
        <div class="card mb-3">
            <div class="card-header" style="background:#eff6ff;">
                <h6 class="mb-0" style="color:#3b82f6;"><i class="bi bi-person-lines-fill"></i> Demandeur</h6>
            </div>
            <div class="card-body py-2">
                <div class="info-item">
                    <div class="info-label">Prénom</div>
                    <div class="info-value"><?php echo e($mutation->demandeur_prenom ?: '-'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nom</div>
                    <div class="info-value"><?php echo e($mutation->demandeur_nom ?: '-'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?php echo e($mutation->demandeur_telephone ?: '-'); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-grid gap-2">
            <a href="<?php echo e(route('mutations.show', $mutation)); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour à l'<?php echo e(strtolower($mutation->type_libelle)); ?>

            </a>
        </div>
    </div>

    <!-- Zone principale : toolbar + iframe PDF -->
    <div class="apercu-main">
        <div class="apercu-toolbar">
            <div>
                <span class="fw-bold">Notification d'<?php echo e(strtolower($mutation->type_libelle)); ?></span>
                <span class="text-muted ms-2">N°<?php echo e($mutation->numero_notification); ?></span>
                <span class="badge ms-2" style="background:<?php echo e($mutation->est_attribution ? '#3b82f6' : '#d97706'); ?>; color:#fff;">
                    <?php echo e(strtoupper($mutation->type_libelle)); ?>

                </span>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="imprimerPdf()">
                    <i class="bi bi-printer"></i> Imprimer
                </button>
                <a href="<?php echo e(route('mutations.telecharger', $mutation)); ?>" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-download"></i> Télécharger PDF
                </a>
                <button class="btn btn-outline-secondary btn-sm" onclick="ouvrirOnglet()">
                    <i class="bi bi-box-arrow-up-right"></i> Nouvel onglet
                </button>
            </div>
        </div>
        <div class="apercu-iframe-wrapper">
            <iframe id="pdfFrame" src="<?php echo e(route('mutations.pdf', $mutation)); ?>"></iframe>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function imprimerPdf() {
    const iframe = document.getElementById('pdfFrame');
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
}

function ouvrirOnglet() {
    window.open('<?php echo e(route('mutations.pdf', $mutation)); ?>', '_blank');
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/CheikhDiop/projetWeb/gestion-mutations/resources/views/mutations/apercu.blade.php ENDPATH**/ ?>