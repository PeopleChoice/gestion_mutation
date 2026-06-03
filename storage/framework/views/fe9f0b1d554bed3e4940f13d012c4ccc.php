<?php $__env->startSection('title', 'Templates de documents'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .template-card { border: 1px solid #e5e7eb; border-radius: 12px; transition: all .2s; }
    .template-card:hover { border-color: #C8A951; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .template-card.actif { border-left: 4px solid #16a34a; }
    .template-card.inactif { opacity: 0.65; }
    .type-chip { font-family: 'Courier New', monospace; font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #f1f5f9; color: #5D4E37; font-weight: 600; }
    .placeholder-group { background: #f9fafb; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
    .placeholder-group h6 { color: #5D4E37; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
    .placeholder-chip { display: inline-block; background: #fff; border: 1px solid #d1d5db; border-radius: 4px; padding: 2px 8px; margin: 2px; font-family: 'Courier New', monospace; font-size: 11px; cursor: pointer; transition: all .15s; }
    .placeholder-chip:hover { background: #fef6ec; border-color: #C8A951; }
    .placeholder-chip code { color: #5D4E37; }
    .filter-tabs { display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 8px; gap: 2px; }
    .filter-tabs button, .filter-tabs a { border: none; background: transparent; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; text-decoration: none; }
    .filter-tabs button.active, .filter-tabs a.active { background: #fff; color: #5D4E37; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-file-earmark-text"></i> Templates de documents</h4>
        <p class="text-muted mb-0 small">Modèles HTML utilisés pour générer les PDF de notification</p>
    </div>
    <a href="<?php echo e(route('templates.create')); ?>" class="btn fw-bold" style="background:#C8A951; color:#fff; border-radius:10px;">
        <i class="bi bi-plus-lg"></i> Nouveau template
    </a>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>


<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="filter-tabs">
        <a href="<?php echo e(route('templates.index')); ?>" class="<?php echo e(!request('type') ? 'active' : ''); ?>">Tous (<?php echo e(\App\Models\DocumentTemplate::count()); ?>)</a>
        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('templates.index', ['type' => $t])); ?>" class="<?php echo e(request('type') === $t ? 'active' : ''); ?>"><?php echo e($t); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <form method="GET" action="<?php echo e(route('templates.index')); ?>" class="d-flex gap-2">
        <?php if(request('type')): ?><input type="hidden" name="type" value="<?php echo e(request('type')); ?>"><?php endif; ?>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher nom, bureau..." value="<?php echo e(request('search')); ?>" style="border-radius:8px; min-width:240px;">
        <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:8px;"><i class="bi bi-search"></i></button>
    </form>
</div>


<?php if($templates->isEmpty()): ?>
    <div class="text-center py-5">
        <i class="bi bi-file-earmark-x" style="font-size:48px; color:#cbd5e1;"></i>
        <p class="text-muted mt-2">Aucun template trouvé.</p>
        <a href="<?php echo e(route('templates.create')); ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg"></i> Créer le premier template
        </a>
    </div>
<?php else: ?>
<div class="row g-3 mb-4">
    <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-md-6">
        <div class="template-card <?php echo e($template->actif ? 'actif' : 'inactif'); ?> p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="flex-fill">
                    <h6 class="mb-1" style="color:#5D4E37;">
                        <?php echo e($template->nom); ?>

                        <?php if($template->actif): ?>
                            <span class="badge bg-success ms-1" style="font-size:9px;">ACTIF</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-1" style="font-size:9px;">INACTIF</span>
                        <?php endif; ?>
                    </h6>
                    <div class="d-flex gap-2 align-items-center small">
                        <span class="type-chip"><?php echo e($template->type); ?></span>
                        <?php if($template->actif && ($usageParType[$template->type] ?? 0) > 0): ?>
                            <span class="text-muted"><i class="bi bi-file-earmark-pdf"></i> <?php echo e($usageParType[$template->type]); ?> document(s) générés</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    
                    <form method="POST" action="<?php echo e(route('templates.toggle', $template)); ?>" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-sm <?php echo e($template->actif ? 'btn-outline-success' : 'btn-outline-secondary'); ?>"
                                title="<?php echo e($template->actif ? 'Désactiver' : 'Activer'); ?>">
                            <i class="bi bi-<?php echo e($template->actif ? 'toggle-on' : 'toggle-off'); ?>"></i>
                        </button>
                    </form>
                    
                    <a href="<?php echo e(route('templates.preview', $template)); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Aperçu">
                        <i class="bi bi-eye"></i>
                    </a>
                    
                    <a href="<?php echo e(route('templates.edit', $template)); ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                        <i class="bi bi-pencil"></i>
                    </a>
                    
                    <form method="POST" action="<?php echo e(route('templates.dupliquer', $template)); ?>" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Dupliquer">
                            <i class="bi bi-files"></i>
                        </button>
                    </form>
                    
                    <form method="POST" action="<?php echo e(route('templates.destroy', $template)); ?>" class="d-inline" onsubmit="return confirm('Supprimer définitivement ?');">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="small text-muted">
                <?php if($template->centre_fiscal): ?><i class="bi bi-building"></i> <?php echo e($template->centre_fiscal); ?><br><?php endif; ?>
                <?php if($template->bureau): ?><i class="bi bi-bookmark"></i> <?php echo e($template->bureau); ?><br><?php endif; ?>
                <small>Modifié le <?php echo e($template->updated_at->format('d/m/Y H:i')); ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>


<div class="card" style="border-radius:12px; border:none; box-shadow:0 2px 12px rgba(0,0,0,0.04);">
    <div class="card-header bg-white">
        <h6 class="mb-0" style="color:#5D4E37;">
            <i class="bi bi-braces"></i> Variables disponibles dans les templates
            <small class="text-muted ms-2">Cliquez pour copier</small>
        </h6>
    </div>
    <div class="card-body">
        <?php $__currentLoopData = \App\Models\DocumentTemplatePlaceholders::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="placeholder-group">
            <h6><?php echo e($category); ?></h6>
            <div>
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $ph = '{{' . $item['key'] . '}}'; ?>
                    <span class="placeholder-chip" title="<?php echo e($item['description']); ?>"
                          onclick="copyPlaceholder('<?php echo e($ph); ?>', this)">
                        <code><?php echo e($ph); ?></code>
                        <?php if(!empty($item['label'])): ?> <span class="text-muted ms-1">— <?php echo e($item['label']); ?></span><?php endif; ?>
                    </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function copyPlaceholder(text, el) {
    navigator.clipboard.writeText(text).then(() => {
        const original = el.innerHTML;
        el.innerHTML = '<i class="bi bi-check-lg text-success"></i> Copié !';
        setTimeout(() => el.innerHTML = original, 1200);
    });
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/CheikhDiop/projetWeb/gestion-mutations/resources/views/templates/index.blade.php ENDPATH**/ ?>