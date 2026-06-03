<?php $__env->startSection('title', 'Demandes d\'annulation'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Lot</th>
                    <th>Projet</th>
                    <th>Propriétaire actuel</th>
                    <th>Demandé par</th>
                    <th>Motif</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $annulations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $annulation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><strong><?php echo e($annulation->mutation->parcelle->numero_lot); ?></strong></td>
                    <td><?php echo e($annulation->mutation->parcelle->projet->nom); ?></td>
                    <td><?php echo e($annulation->mutation->nouveauProprietaire?->nom_complet ?? '-'); ?></td>
                    <td><?php echo e($annulation->demandeur->name); ?></td>
                    <td><?php echo e(Str::limit($annulation->motif, 50)); ?></td>
                    <td><?php echo e($annulation->created_at->format('d/m/Y H:i')); ?></td>
                    <td>
                        <form action="<?php echo e(route('annulations.traiter', $annulation)); ?>" method="POST" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="approuver">
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approuver cette annulation ? Le terrain sera remis à l\'ancien propriétaire.')">
                                <i class="bi bi-check-lg"></i> Approuver
                            </button>
                        </form>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalRejet<?php echo e($annulation->id); ?>">
                            <i class="bi bi-x-lg"></i> Rejeter
                        </button>

                        <!-- Modal rejet -->
                        <div class="modal fade" id="modalRejet<?php echo e($annulation->id); ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="<?php echo e(route('annulations.traiter', $annulation)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="rejeter">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Rejeter l'annulation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Motif du rejet</label>
                                                <textarea name="motif_rejet" class="form-control" rows="3" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">Confirmer le rejet</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Aucune demande d'annulation en attente</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo e($annulations->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/CheikhDiop/projetWeb/gestion-mutations/resources/views/mutations/annulations.blade.php ENDPATH**/ ?>