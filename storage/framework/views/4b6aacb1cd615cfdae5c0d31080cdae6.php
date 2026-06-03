<?php $__env->startSection('title', 'Nouvelle mutation manuelle'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .form-section {
        background: #fff; border-radius: 14px; padding: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.04);
        margin-bottom: 16px;
    }
    .section-title {
        font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;
        color: #94a3b8; font-weight: 700; margin-bottom: 16px;
        display: flex; align-items: center; gap: 8px;
    }
    .parcelle-search-wrapper { position: relative; }
    .parcelle-search-results {
        position: absolute; top: 100%; left: 0; right: 0;
        background: #fff; border: 1px solid #dee2e6; border-top: none;
        border-radius: 0 0 10px 10px; max-height: 350px; overflow-y: auto;
        z-index: 100; box-shadow: 0 8px 20px rgba(0,0,0,0.1); display: none;
    }
    .parcelle-search-results.show { display: block; }
    .parcelle-item {
        padding: 10px 14px; cursor: pointer; transition: background .15s;
        border-bottom: 1px solid #f0f0f0; font-size: 13px;
    }
    .parcelle-item:last-child { border-bottom: none; }
    .parcelle-item:hover { background: #faf6ec; }
    .parcelle-item .lot { font-weight: 700; color: #5D4E37; }
    .parcelle-item .proj { font-size: 11px; color: #999; }
    .parcelle-item .prop { font-size: 12px; color: #555; margin-top: 2px; }

    .selected-parcelle {
        background: #f0f9ff; border: 2px solid #5D4E37; border-radius: 12px;
        padding: 14px; margin-bottom: 14px;
    }
    .selected-parcelle .label { font-size: 11px; color: #94a3b8; text-transform: uppercase; }
    .selected-parcelle .value { font-weight: 700; color: #5D4E37; font-size: 15px; }
    .selected-parcelle .small { font-size: 12px; color: #6b7280; }

    .arrow-divider {
        text-align: center; color: #C8A951; font-size: 22px; margin: 8px 0;
    }

    .mode-tabs { display: flex; gap: 8px; margin-bottom: 12px; }
    .mode-tab {
        flex: 1; padding: 10px; text-align: center; border-radius: 10px;
        border: 2px solid #e5e7eb; background: #fff; cursor: pointer;
        font-weight: 600; font-size: 13px; color: #6b7280; transition: all .15s;
    }
    .mode-tab.active { border-color: #C8A951; background: #fef6ec; color: #5D4E37; }
    .mode-tab:hover { border-color: #C8A951; }

    .prop-search-wrapper { position: relative; }
    .prop-search-results {
        position: absolute; top: 100%; left: 0; right: 0;
        background: #fff; border: 1px solid #dee2e6; border-top: none;
        border-radius: 0 0 10px 10px; max-height: 250px; overflow-y: auto;
        z-index: 100; box-shadow: 0 8px 20px rgba(0,0,0,0.1); display: none;
    }
    .prop-search-results.show { display: block; }
    .prop-item { padding: 10px 14px; cursor: pointer; transition: background .15s; border-bottom: 1px solid #f0f0f0; }
    .prop-item:hover { background: #faf6ec; }
    .prop-item .name { font-weight: 700; color: #5D4E37; font-size: 13px; }
    .prop-item .id { font-size: 11px; color: #999; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-plus-circle"></i> Nouvelle mutation manuelle</h4>
        <p class="text-muted mb-0 small">Enregistrer un changement de proprietaire sans passer par un import</p>
    </div>
    <a href="<?php echo e(route('mutations.index')); ?>" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<?php if($errors->any()): ?>
    <div class="alert alert-danger" style="border-radius:10px;">
        <ul class="mb-0 small">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;"><?php echo e(session('error')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('mutations.store')); ?>" id="mutationForm">
    <?php echo csrf_field(); ?>

    
    <div class="form-section">
        <div class="section-title"><i class="bi bi-1-circle-fill" style="color:#C8A951;"></i> Parcelle concernee</div>

        <div id="parcelleSelectedBox" style="display:<?php echo e($parcelle ? 'block' : 'none'); ?>;">
            <div class="selected-parcelle" style="background:linear-gradient(to right, #fef3c7 0%, #fff8e1 100%); border-color:#d97706;">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="label">Lot</div>
                        <div class="value" id="selectedLot"><?php echo e($parcelle?->numero_lot); ?></div>
                        <div class="small mt-1" id="selectedProj"><?php echo e($parcelle?->projet?->nom); ?></div>
                    </div>
                    <div class="flex-fill">
                        <div class="label"><i class="bi bi-person-fill" style="color:#d97706;"></i> Précédent propriétaire (sera remplacé)</div>
                        <div class="value" id="selectedProp"><?php echo e($parcelle?->proprietaire?->nom_complet ?? 'Aucun'); ?></div>
                        <div class="small" id="selectedCni">
                            <?php if($parcelle?->proprietaire?->cni_passport): ?>
                                <?php echo e($parcelle->proprietaire->type_piece); ?> n° <?php echo e($parcelle->proprietaire->cni_passport); ?>

                            <?php endif; ?>
                            <?php if($parcelle?->proprietaire?->telephone): ?> · 📞 <?php echo e($parcelle->proprietaire->telephone); ?> <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnChangeParcelle" style="border-radius:8px;"><i class="bi bi-arrow-repeat"></i> Changer</button>
                </div>
                <?php if($parcelle && !$parcelle->proprietaire): ?>
                <div class="alert alert-danger mt-2 mb-0 py-2 small" style="border-radius:8px;">
                    <i class="bi bi-exclamation-triangle"></i> Cette parcelle est <strong>vierge</strong> (sans propriétaire) — la mutation est impossible. Utilisez l'attribution depuis la page projet.
                </div>
                <?php endif; ?>
            </div>
            <input type="hidden" name="parcelle_id" id="parcelleId" value="<?php echo e($parcelle?->id); ?>">
            <input type="hidden" id="parcelleHasOwner" value="<?php echo e($parcelle?->proprietaire ? '1' : '0'); ?>">
        </div>

        <div id="parcelleSearchBox" style="display:<?php echo e($parcelle ? 'none' : 'block'); ?>;">
            <div class="row g-2 mb-2">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Filtrer par projet</label>
                    <select id="projetFilter" class="form-select" style="border-radius:10px;">
                        <option value="">— Tous les projets —</option>
                        <?php $__currentLoopData = $projets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($proj->id); ?>"><?php echo e($proj->code); ?> — <?php echo e($proj->nom); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <div class="parcelle-search-wrapper">
                        <label class="form-label small fw-semibold">Rechercher une parcelle <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="parcelleSearch" placeholder="N° de lot, proprietaire, CNI..." autocomplete="off" style="border-radius:10px;">
                        <div class="parcelle-search-results" id="parcelleResults"></div>
                    </div>
                </div>
            </div>
            <small class="text-muted"><i class="bi bi-info-circle"></i> Seules les parcelles deja attribuees peuvent faire l'objet d'une mutation.</small>
        </div>
    </div>

    <div class="arrow-divider"><i class="bi bi-arrow-down"></i></div>

    
    <div class="form-section">
        <div class="section-title"><i class="bi bi-2-circle-fill" style="color:#C8A951;"></i> Nouveau proprietaire</div>

        <div class="mode-tabs">
            <div class="mode-tab active" data-mode="new" id="tabNew"><i class="bi bi-person-plus"></i> Nouveau</div>
            <div class="mode-tab" data-mode="existing" id="tabExisting"><i class="bi bi-person-check"></i> Selectionner un existant</div>
        </div>

        
        <div id="modeNew">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Civilite <span class="text-danger">*</span></label>
                    <select name="civilite" class="form-select" style="border-radius:10px;">
                        <option value="Monsieur" <?php echo e(old('civilite') === 'Monsieur' ? 'selected' : ''); ?>>Monsieur</option>
                        <option value="Madame" <?php echo e(old('civilite') === 'Madame' ? 'selected' : ''); ?>>Madame</option>
                        <option value="Société" <?php echo e(old('civilite') === 'Société' ? 'selected' : ''); ?>>Societe</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Prenom / Raison sociale <span class="text-danger">*</span></label>
                    <input type="text" name="prenom" class="form-control" value="<?php echo e(old('prenom')); ?>" style="border-radius:10px;">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nom</label>
                    <input type="text" name="nom" class="form-control" value="<?php echo e(old('nom')); ?>" style="border-radius:10px;">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Type pièce <span class="text-danger">*</span></label>
                    <select name="type_piece" id="newTypePiece" class="form-select" style="border-radius:10px;">
                        <option value="">--</option>
                        <option value="CNI" <?php echo e(old('type_piece') === 'CNI' ? 'selected' : ''); ?>>CNI</option>
                        <option value="Passeport" <?php echo e(old('type_piece') === 'Passeport' ? 'selected' : ''); ?>>Passeport</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Code payé <span class="text-danger">*</span></label>
                    <input type="text" name="code_paye" id="newCodePaye" class="form-control" value="<?php echo e(old('code_paye')); ?>" placeholder="Ex: SN" style="border-radius:10px;">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">N° pièce <span class="text-danger">*</span></label>
                    <input type="text" name="cni_passport" id="newCniPassport" class="form-control" value="<?php echo e(old('cni_passport')); ?>" style="border-radius:10px;">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" value="<?php echo e(old('telephone')); ?>" style="border-radius:10px;">
                </div>
                <div class="col-md-6">
                    <div class="alert mb-0 py-2 px-3" style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; font-size:13px;">
                        <span class="text-muted small">Pièce formatée :</span>
                        <strong id="piecePreview" class="ms-2" style="font-family:'Courier New',monospace; color:#5D4E37;">—</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">NINEA</label>
                    <input type="text" name="ninea" class="form-control" value="<?php echo e(old('ninea')); ?>" style="border-radius:10px;">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Adresse</label>
                    <input type="text" name="adresse" class="form-control" value="<?php echo e(old('adresse')); ?>" style="border-radius:10px;">
                </div>
            </div>
        </div>

        
        <div id="modeExisting" style="display:none;">
            <div class="prop-search-wrapper">
                <label class="form-label small fw-semibold">Rechercher un proprietaire existant</label>
                <input type="text" class="form-control" id="propSearch" placeholder="Nom, prenom, CNI..." autocomplete="off" style="border-radius:10px;">
                <div class="prop-search-results" id="propResults"></div>
            </div>
            <div id="propSelectedBox" class="selected-parcelle mt-2" style="display:none; border-color:#C8A951;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="label">Selectionne</div>
                        <div class="value" id="propSelectedName"></div>
                        <div class="small" id="propSelectedInfo"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnChangeProp" style="border-radius:8px;"><i class="bi bi-x"></i></button>
                </div>
            </div>
            <input type="hidden" name="nouveau_proprietaire_id" id="propId" value="">
        </div>
    </div>

    <div class="arrow-divider"><i class="bi bi-arrow-down"></i></div>

    
    <div class="form-section">
        <div class="section-title"><i class="bi bi-3-circle-fill" style="color:#C8A951;"></i> Details de la mutation</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">N° notification</label>
                <input type="text" name="numero_notification" class="form-control" value="<?php echo e(old('numero_notification')); ?>" style="border-radius:10px;" placeholder="Ex: 0004857 (auto si vide)">
                <?php if($dernierNumero): ?>
                    <small class="text-muted">
                        Dernier utilisé : <strong style="font-family:'Courier New',monospace; color:#92400e;"><?php echo e($dernierNumero); ?></strong>
                        — prochain : <strong style="font-family:'Courier New',monospace; color:#5D4E37;"><?php echo e(str_pad((int) preg_replace('/\D/', '', $dernierNumero) + 1, 7, '0', STR_PAD_LEFT)); ?></strong>
                    </small>
                <?php else: ?>
                    <small class="text-muted">Laissez vide pour génération automatique.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Date de mutation <span class="text-danger">*</span></label>
                <input type="date" name="date_mutation" class="form-control" value="<?php echo e(old('date_mutation', now()->format('Y-m-d'))); ?>" required style="border-radius:10px;">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Ref. lettre</label>
                <input type="text" name="ref_lettre" class="form-control" value="<?php echo e(old('ref_lettre')); ?>" style="border-radius:10px;" placeholder="Ex: 2025/DGID/123">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Observation</label>
                <textarea name="observation" class="form-control" rows="2" style="border-radius:10px;" placeholder="Contexte, reference, notes..."><?php echo e(old('observation')); ?></textarea>
            </div>
        </div>

        <hr class="my-4">
        <div class="section-title"><i class="bi bi-person-lines-fill" style="color:#C8A951;"></i> Demandeur <span class="text-muted" style="font-weight:normal;">(celui qui dépose la demande — souvent = précédent proprio)</span></div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Prénom demandeur</label>
                <input type="text" name="demandeur_prenom" class="form-control" value="<?php echo e(old('demandeur_prenom')); ?>" style="border-radius:10px;">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Nom demandeur</label>
                <input type="text" name="demandeur_nom" class="form-control" value="<?php echo e(old('demandeur_nom')); ?>" style="border-radius:10px;">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Téléphone demandeur</label>
                <input type="text" name="demandeur_telephone" class="form-control" value="<?php echo e(old('demandeur_telephone')); ?>" style="border-radius:10px;">
            </div>
        </div>
    </div>

    <?php if(auth()->user()->hasRole('admin')): ?>
    <div class="form-section">
        <div class="section-title"><i class="bi bi-shield-check" style="color:#C8A951;"></i> Validation</div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="validation" id="valAttente" value="en_attente" checked>
            <label class="form-check-label" for="valAttente">
                <strong>En attente</strong> — La mutation necessitera une validation ulterieure.
            </label>
        </div>
        <div class="form-check mt-2">
            <input class="form-check-input" type="radio" name="validation" id="valValide" value="validee">
            <label class="form-check-label" for="valValide">
                <strong>Validee immediatement</strong> — Le nouveau proprietaire sera immediatement attribue, un QR code de verification sera genere.
            </label>
        </div>
    </div>
    <?php else: ?>
    <input type="hidden" name="validation" value="en_attente">
    <div class="alert alert-info small" style="border-radius:10px;">
        <i class="bi bi-info-circle"></i> La mutation sera enregistree en <strong>attente de validation</strong> par un administrateur.
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 justify-content-end">
        <a href="<?php echo e(route('mutations.index')); ?>" class="btn btn-outline-secondary" style="border-radius:10px;">Annuler</a>
        <button type="submit" class="btn fw-bold" style="background:#5D4E37; color:#fff; border-radius:10px; padding:10px 24px;" id="btnSubmit">
            <i class="bi bi-check-circle"></i> Enregistrer la mutation
        </button>
    </div>
</form>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
    // ========== RECHERCHE PARCELLE ==========
    const parcelleSearch = document.getElementById('parcelleSearch');
    const parcelleResults = document.getElementById('parcelleResults');
    const parcelleSelectedBox = document.getElementById('parcelleSelectedBox');
    const parcelleSearchBox = document.getElementById('parcelleSearchBox');
    const parcelleIdInput = document.getElementById('parcelleId');
    let parcelleSearchTimer;

    const projetFilter = document.getElementById('projetFilter');

    if (parcelleSearch) {
        parcelleSearch.addEventListener('input', function() {
            clearTimeout(parcelleSearchTimer);
            const q = this.value.trim();
            if (q.length < 1) { parcelleResults.classList.remove('show'); return; }
            parcelleSearchTimer = setTimeout(() => loadParcelles(q), 250);
        });
    }

    if (projetFilter) {
        projetFilter.addEventListener('change', function() {
            const q = parcelleSearch.value.trim();
            if (q.length >= 1) loadParcelles(q);
            else if (this.value) loadParcelles('');
            else parcelleResults.classList.remove('show');
        });
    }

    function loadParcelles(q) {
        const projetId = projetFilter ? projetFilter.value : '';
        let url = '<?php echo e(route("mutations.api.parcelles")); ?>?q=' + encodeURIComponent(q);
        if (projetId) url += '&projet_id=' + encodeURIComponent(projetId);
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    parcelleResults.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search"></i> Aucune parcelle trouvee</div>';
                } else {
                    parcelleResults.innerHTML = data.map(p => `
                        <div class="parcelle-item" data-id="${p.id}" data-lot="${escapeHtml(p.numero_lot)}" data-proj="${escapeHtml(p.projet)}" data-prop="${escapeHtml(p.proprietaire)}" data-cni="${escapeHtml(p.cni || '')}">
                            <div><span class="lot">Lot ${escapeHtml(p.numero_lot)}</span> <span class="proj">— ${escapeHtml(p.projet)}</span></div>
                            <div class="prop"><i class="bi bi-person"></i> ${escapeHtml(p.proprietaire)} ${p.cni ? '· ' + escapeHtml(p.cni) : ''}</div>
                        </div>
                    `).join('');
                    parcelleResults.querySelectorAll('.parcelle-item').forEach(el => {
                        el.addEventListener('click', function() {
                            selectParcelle({
                                id: this.dataset.id,
                                lot: this.dataset.lot,
                                proj: this.dataset.proj,
                                prop: this.dataset.prop,
                                cni: this.dataset.cni,
                            });
                        });
                    });
                }
                parcelleResults.classList.add('show');
            });
    }

    function selectParcelle(p) {
        parcelleIdInput.value = p.id;
        document.getElementById('selectedLot').textContent = p.lot;
        document.getElementById('selectedProj').textContent = p.proj;
        document.getElementById('selectedProp').textContent = p.prop;
        document.getElementById('selectedCni').textContent = p.cni;
        parcelleSelectedBox.style.display = 'block';
        parcelleSearchBox.style.display = 'none';
        parcelleResults.classList.remove('show');
        // Marqueur : on a forcément un propriétaire car l'API ne renvoie que les attribuées
        const hasOwnerInput = document.getElementById('parcelleHasOwner');
        if (hasOwnerInput) hasOwnerInput.value = '1';

        // Pré-remplir le demandeur avec le précédent (souvent identique)
        const propParts = (p.prop || '').replace(/^(Monsieur|Madame|Société)\s+/, '').trim().split(/\s+/);
        const demPrenomEl = document.querySelector('input[name="demandeur_prenom"]');
        const demNomEl = document.querySelector('input[name="demandeur_nom"]');
        if (demPrenomEl && !demPrenomEl.value && propParts[0]) demPrenomEl.value = propParts[0];
        if (demNomEl && !demNomEl.value && propParts.slice(1).length) demNomEl.value = propParts.slice(1).join(' ');
    }

    document.getElementById('btnChangeParcelle')?.addEventListener('click', function() {
        parcelleIdInput.value = '';
        parcelleSelectedBox.style.display = 'none';
        parcelleSearchBox.style.display = 'block';
        parcelleSearch.value = '';
        parcelleSearch.focus();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.parcelle-search-wrapper')) parcelleResults.classList.remove('show');
        if (!e.target.closest('.prop-search-wrapper')) document.getElementById('propResults').classList.remove('show');
    });

    // ========== TABS ==========
    const tabNew = document.getElementById('tabNew');
    const tabExisting = document.getElementById('tabExisting');
    const modeNew = document.getElementById('modeNew');
    const modeExisting = document.getElementById('modeExisting');

    tabNew.addEventListener('click', () => {
        tabNew.classList.add('active'); tabExisting.classList.remove('active');
        modeNew.style.display = 'block'; modeExisting.style.display = 'none';
        document.getElementById('propId').value = '';
        document.querySelectorAll('#modeNew [name="civilite"], #modeNew [name="prenom"]').forEach(el => el.required = true);
    });
    tabExisting.addEventListener('click', () => {
        tabExisting.classList.add('active'); tabNew.classList.remove('active');
        modeNew.style.display = 'none'; modeExisting.style.display = 'block';
        document.querySelectorAll('#modeNew [name]').forEach(el => el.required = false);
    });

    // ========== RECHERCHE PROPRIETAIRE ==========
    const propSearch = document.getElementById('propSearch');
    const propResults = document.getElementById('propResults');
    const propSelectedBox = document.getElementById('propSelectedBox');
    const propIdInput = document.getElementById('propId');
    let propTimer;

    propSearch.addEventListener('input', function() {
        clearTimeout(propTimer);
        const q = this.value.trim();
        if (q.length < 2) { propResults.classList.remove('show'); return; }
        propTimer = setTimeout(() => loadProps(q), 250);
    });

    function loadProps(q) {
        fetch('<?php echo e(route("mutations.api.proprietaires")); ?>?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    propResults.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search"></i> Aucun proprietaire</div>';
                } else {
                    propResults.innerHTML = data.map(p => `
                        <div class="prop-item" data-id="${p.id}" data-name="${escapeHtml(p.nom_complet)}" data-info="${escapeHtml([p.cni, p.telephone].filter(Boolean).join(' · '))}">
                            <div class="name"><i class="bi bi-person"></i> ${escapeHtml(p.nom_complet)}</div>
                            <div class="id">${escapeHtml([p.cni, p.telephone].filter(Boolean).join(' · ') || '-')}</div>
                        </div>
                    `).join('');
                    propResults.querySelectorAll('.prop-item').forEach(el => {
                        el.addEventListener('click', function() {
                            propIdInput.value = this.dataset.id;
                            document.getElementById('propSelectedName').textContent = this.dataset.name;
                            document.getElementById('propSelectedInfo').textContent = this.dataset.info;
                            propSelectedBox.style.display = 'block';
                            propResults.classList.remove('show');
                        });
                    });
                }
                propResults.classList.add('show');
            });
    }

    document.getElementById('btnChangeProp').addEventListener('click', () => {
        propIdInput.value = '';
        propSelectedBox.style.display = 'none';
        propSearch.value = '';
    });

    // ========== PREVIEW PIÈCE FORMATÉE ==========
    const piecePreview = document.getElementById('piecePreview');
    const newType = document.getElementById('newTypePiece');
    const newCode = document.getElementById('newCodePaye');
    const newCni = document.getElementById('newCniPassport');

    function updatePiecePreview() {
        const type = (newType?.value || '').trim();
        const code = (newCode?.value || '').trim().toUpperCase();
        const num = (newCni?.value || '').trim();
        if (!num) { piecePreview.textContent = '—'; return; }
        const prefix = /^pass/i.test(type) ? 'PP' : (type || '');
        if (!prefix) { piecePreview.textContent = code ? `${code} n° : ${num}` : `n° : ${num}`; return; }
        piecePreview.textContent = code ? `${prefix}_${code} n° : ${num}` : `${prefix} n° : ${num}`;
    }
    [newType, newCode, newCni].forEach(el => el && el.addEventListener('input', updatePiecePreview));
    [newType, newCode, newCni].forEach(el => el && el.addEventListener('change', updatePiecePreview));
    updatePiecePreview();

    // ========== SUBMIT ==========
    document.getElementById('mutationForm').addEventListener('submit', function(e) {
        if (!parcelleIdInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner une parcelle.');
            return;
        }
        // RÈGLE : on ne mute pas une parcelle vierge
        const hasOwner = document.getElementById('parcelleHasOwner')?.value === '1';
        if (!hasOwner) {
            e.preventDefault();
            alert('Cette parcelle est vierge (sans propriétaire). Une mutation suppose un changement de propriétaire — utilisez l\'attribution depuis la page projet.');
            return;
        }
        // Si mode existant actif, il faut un proprietaire selectionne
        if (tabExisting.classList.contains('active') && !propIdInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un propriétaire existant.');
            return;
        }
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('btnSubmit').innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement...';
    });

    function escapeHtml(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }
})();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/CheikhDiop/projetWeb/gestion-mutations/resources/views/mutations/create.blade.php ENDPATH**/ ?>