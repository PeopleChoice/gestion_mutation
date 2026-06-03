<?php $__env->startSection('title', 'Modifier le document - ' . $template->nom); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/theme/monokai.min.css">
<style>
    .editor-layout { display: flex; gap: 20px; height: calc(100vh - 160px); }
    .editor-panel { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .preview-panel { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .editor-panel .card, .preview-panel .card { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .editor-panel .card-body { flex: 1; overflow-y: auto; }
    .preview-frame { flex: 1; background: #525659; border-radius: 0 0 10px 10px; overflow: hidden; }
    .preview-frame iframe { width: 100%; height: 100%; border: none; }
    .placeholder-tag {
        display: inline-block; background: #E6FAF8; color: #0f6e63; border: 1px solid #20D5C0;
        border-radius: 4px; padding: 1px 6px; font-size: 11px; font-family: monospace;
        cursor: pointer; margin: 2px; transition: all .2s;
    }
    .placeholder-tag:hover { background: #20D5C0; color: #fff; }
    .section-header {
        background: #f8f9fa; padding: 8px 12px; border-bottom: 1px solid #eee;
        font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        color: #666; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
    }
    .section-header:hover { background: #f0f0f0; }
    .section-body { padding: 12px; border-bottom: 1px solid #eee; }
    .section-body.collapsed { display: none; }
    .CodeMirror { border: 1px solid #dee2e6; border-radius: 6px; font-size: 12px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<form action="<?php echo e(route('templates.update', $template)); ?>" method="POST" id="templateForm">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

    <div class="editor-layout">
        <!-- Panneau Éditeur -->
        <div class="editor-panel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0"><i class="bi bi-pencil-square"></i> Éditeur du document</h6>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm" style="background:#20D5C0; color:#fff;">
                            <i class="bi bi-check-lg"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="rafraichirApercu()">
                            <i class="bi bi-arrow-clockwise"></i> Aperçu
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Infos générales -->
                    <div class="section-header" onclick="toggleSection('sectionInfos')">
                        <span><i class="bi bi-gear"></i> Paramètres généraux</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="section-body" id="sectionInfos">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nom</label>
                                <input type="text" name="nom" class="form-control form-control-sm" value="<?php echo e($template->nom); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Type</label>
                                <input type="text" name="type" class="form-control form-control-sm" value="<?php echo e($template->type); ?>" required
                                       title="Le type identifie l'usage du template (ex: notification_attribution)">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Centre fiscal</label>
                                <input type="text" name="centre_fiscal" class="form-control form-control-sm" value="<?php echo e($template->centre_fiscal); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Statut</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="actif" id="actifSwitch" value="1" <?php echo e($template->actif ? 'checked' : ''); ?>>
                                    <label class="form-check-label small" for="actifSwitch">
                                        <span id="actifLabel"><?php echo e($template->actif ? 'Actif' : 'Inactif'); ?></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label small fw-bold">Bureau</label>
                                <input type="text" name="bureau" class="form-control form-control-sm" value="<?php echo e($template->bureau); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Placeholders (catalogue centralisé, groupé par catégorie) -->
                    <div class="section-header" onclick="toggleSection('sectionPlaceholders')">
                        <span><i class="bi bi-braces"></i> Variables disponibles (cliquer pour insérer dans l'éditeur actif)</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="section-body" id="sectionPlaceholders">
                        <input type="text" id="phSearch" class="form-control form-control-sm mb-2" placeholder="🔍 Filtrer une variable...">
                        <?php $__currentLoopData = \App\Models\DocumentTemplatePlaceholders::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="ph-group mb-2">
                            <div class="text-muted small fw-bold mb-1" style="text-transform:uppercase; letter-spacing:.5px; font-size:11px;"><?php echo e($category); ?></div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $ph = '{{' . $item['key'] . '}}'; ?>
                                    <span class="placeholder-tag ph-item"
                                          data-key="<?php echo e($item['key']); ?>"
                                          data-search="<?php echo e(strtolower($item['key'] . ' ' . $item['label'])); ?>"
                                          title="<?php echo e($item['description']); ?>"
                                          onclick="insererPlaceholder('<?php echo e($ph); ?>')">
                                        <?php echo e($item['label'] ?: $item['key']); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <!-- En-tête HTML -->
                    <div class="section-header" onclick="toggleSection('sectionEntete')">
                        <span><i class="bi bi-card-heading"></i> En-tête (styles + image + QR)</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="section-body collapsed" id="sectionEntete">
                        <small class="text-muted d-block mb-2">Contient les styles CSS, l'image d'en-tête, le QR code. Modifier avec prudence.</small>
                        <textarea name="entete_html" id="enteteEditor"><?php echo htmlspecialchars($template->entete_html); ?></textarea>
                    </div>

                    <!-- Corps du document -->
                    <div class="section-header" onclick="toggleSection('sectionCorps')">
                        <span><i class="bi bi-body-text"></i> Corps du document (contenu principal)</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="section-body" id="sectionCorps">
                        <textarea name="corps_html" id="corpsEditor"><?php echo htmlspecialchars($template->corps_html); ?></textarea>
                    </div>

                    <!-- Pied de page -->
                    <div class="section-header" onclick="toggleSection('sectionPied')">
                        <span><i class="bi bi-card-text"></i> Pied de page (destinataire)</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="section-body" id="sectionPied">
                        <textarea name="pied_html" id="piedEditor"><?php echo htmlspecialchars($template->pied_html); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panneau Aperçu -->
        <div class="preview-panel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0"><i class="bi bi-eye"></i> Aperçu du document</h6>
                    <a href="<?php echo e(route('templates.preview', $template)); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i> Plein écran
                    </a>
                </div>
                <div class="preview-frame">
                    <iframe id="previewFrame" src="<?php echo e(route('templates.preview', $template)); ?>"></iframe>
                </div>
            </div>
        </div>
    </div>
</form>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/htmlmixed/htmlmixed.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle label "Actif/Inactif" dynamiquement
    const actifSwitch = document.getElementById('actifSwitch');
    const actifLabel = document.getElementById('actifLabel');
    if (actifSwitch && actifLabel) {
        actifSwitch.addEventListener('change', function() {
            actifLabel.textContent = this.checked ? 'Actif' : 'Inactif';
        });
    }

    // Filtre rapide sur la liste des placeholders
    const phSearch = document.getElementById('phSearch');
    if (phSearch) {
        phSearch.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.ph-item').forEach(el => {
                const match = !q || (el.dataset.search || '').includes(q);
                el.style.display = match ? '' : 'none';
            });
            // Masquer aussi les groupes vides
            document.querySelectorAll('.ph-group').forEach(g => {
                const visibles = g.querySelectorAll('.ph-item:not([style*="display: none"])').length;
                g.style.display = visibles > 0 ? '' : 'none';
            });
        });
    }

    // CodeMirror pour les 3 éditeurs
    const cmOptions = {
        mode: 'htmlmixed',
        lineNumbers: true,
        lineWrapping: true,
        theme: 'default',
        indentUnit: 4,
    };

    const enteteEditor = CodeMirror.fromTextArea(document.getElementById('enteteEditor'), { ...cmOptions, lineNumbers: true });
    enteteEditor.setSize(null, 250);

    const corpsEditor = CodeMirror.fromTextArea(document.getElementById('corpsEditor'), { ...cmOptions });
    corpsEditor.setSize(null, 300);

    const piedEditor = CodeMirror.fromTextArea(document.getElementById('piedEditor'), { ...cmOptions });
    piedEditor.setSize(null, 150);

    // Éditeur actif (pour insertion de placeholder)
    let activeEditor = corpsEditor;
    corpsEditor.on('focus', () => { activeEditor = corpsEditor; });
    piedEditor.on('focus', () => { activeEditor = piedEditor; });
    enteteEditor.on('focus', () => { activeEditor = enteteEditor; });

    function insererPlaceholder(tag) {
        const cursor = activeEditor.getCursor();
        activeEditor.replaceRange(tag, cursor);
        activeEditor.focus();
    }
    window.insererPlaceholder = insererPlaceholder;

    // Toggle sections
    window.toggleSection = function(id) {
        const el = document.getElementById(id);
        el.classList.toggle('collapsed');
        // Refresh CodeMirror quand la section s'ouvre
        setTimeout(() => {
            enteteEditor.refresh();
            corpsEditor.refresh();
            piedEditor.refresh();
        }, 100);
    };

    // Sync CodeMirror -> textarea avant soumission
    document.getElementById('templateForm').addEventListener('submit', function() {
        enteteEditor.save();
        corpsEditor.save();
        piedEditor.save();
    });

    // Rafraîchir l'aperçu
    window.rafraichirApercu = function() {
        enteteEditor.save();
        corpsEditor.save();
        piedEditor.save();

        const form = document.getElementById('templateForm');
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            redirect: 'follow'
        })
        .then(() => {
            document.getElementById('previewFrame').src = '<?php echo e(route("templates.preview", $template)); ?>?' + Date.now();
        })
        .catch(() => alert('Erreur lors de la sauvegarde.'));
    };
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/CheikhDiop/projetWeb/gestion-mutations/resources/views/templates/edit.blade.php ENDPATH**/ ?>