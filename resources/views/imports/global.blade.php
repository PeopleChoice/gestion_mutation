@extends('layouts.app')
@section('title', 'Imports globaux multi-projets')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-collection"></i> Imports globaux multi-projets</h4>
        <p class="text-muted mb-0 small">Un seul fichier pour plusieurs projets — dispatch automatique selon le <strong>Code projet</strong></p>
    </div>
    <div>
        <a href="{{ route('imports.create') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Import par projet</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('warning'))<div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

{{-- Saisie du N° de notification de départ --}}
<div class="card mb-3" style="border:2px dashed #d97706; border-radius:10px; background:#fffbeb;">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="flex-grow-1">
                <strong style="color:#92400e;"><i class="bi bi-hash"></i> N° de notification de départ (compteur global)</strong>
                <div class="small text-muted">
                    Un seul compteur pour <strong>tous les projets</strong> et <strong>tous les fichiers</strong> à valider.
                    Il s'incrémente à chaque validation (manuelle ou en masse).
                </div>
            </div>
            <input type="text" id="numNotifDepartGlobal" class="form-control"
                style="max-width:220px; font-family:'Courier New',monospace; font-size:16px; font-weight:bold; border-color:#d97706;"
                placeholder="Ex: 0000001"
                value="{{ session('num_notif_depart', str_pad((string) (\App\Models\Mutation::max('id') + 1), 7, '0', STR_PAD_LEFT)) }}">
            <button type="button" id="btnSaveNumNotifGlobal" class="btn btn-sm" style="background:#d97706; color:#fff;">
                <i class="bi bi-check-lg"></i> Mémoriser
            </button>
            <span id="confirmNumNotif" class="badge ms-1" style="background:#10b981; color:#fff; display:none;">
                <i class="bi bi-check"></i> Mémorisé
            </span>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Colonne 1 : Attribution globale --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#dcfce7; color:#166534;">
                <h6 class="mb-0"><i class="bi bi-file-earmark-arrow-up"></i> Attribution initiale (multi-projets)</h6>
                <a href="{{ route('imports.global.template.attribution') }}" class="btn btn-sm btn-outline-success" title="Télécharger le template Excel">
                    <i class="bi bi-download"></i> Template
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted small">Crée des parcelles dans plusieurs projets à partir d'un seul fichier.</p>
                <form action="{{ route('imports.global.attribution') }}" method="POST" enctype="multipart/form-data" class="form-import-global">
                    @csrf
                    <input type="hidden" name="num_notif_depart" class="num-notif-hidden">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fichier Excel <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control" accept=".xls,.xlsx" required>
                    </div>
                    <div class="mb-3">
                        <strong class="small">Colonnes requises :</strong>
                        <ul class="small mb-1">
                            <li><code>Code projet</code> (obligatoire — ex: <code>RA0001</code>, <code>LC0001</code>)</li>
                            <li><code>LOT</code> (obligatoire)</li>
                            <li>Optionnel : Civilité, Prénom, NOM, Type pièce, Code payé, N° pièce, NINEA, Téléphone, Adresse, Observation</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> Importer (attribution)</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Colonne 2 : Mutation globale --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#fef3c7; color:#92400e;">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right"></i> Mutations (multi-projets)</h6>
                <a href="{{ route('imports.global.template.mutation') }}" class="btn btn-sm" style="border:1px solid #92400e; color:#92400e; background:#fff;" title="Télécharger le template Excel">
                    <i class="bi bi-download"></i> Template
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted small">Crée un Import par projet trouvé dans le fichier. Validation lot par lot ensuite.</p>
                <form action="{{ route('imports.global.mutation') }}" method="POST" enctype="multipart/form-data" class="form-import-global">
                    @csrf
                    <input type="hidden" name="num_notif_depart" class="num-notif-hidden">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fichier Excel <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control" accept=".xls,.xlsx" required>
                    </div>
                    <div class="mb-3">
                        <strong class="small">Colonnes requises :</strong>
                        <ul class="small mb-1">
                            <li><code>Code projet</code> + <code>LOT</code></li>
                            <li>Nouveau propriétaire : Civilité, Prénom, NOM, Type pièce, Code payé, N° pièce, NINEA, Téléphone</li>
                            <li>Demandeur : Prénom demandeur, NOM demandeur, Tél demandeur</li>
                            <li>Détails : Réf. Lettre, Date, Observation</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn" style="background:#d97706; color:#fff;"><i class="bi bi-upload"></i> Importer (mutations)</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-list-ul"></i> Codes des projets disponibles</h6>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="width:120px;">Code</th>
                    <th>Projet</th>
                    <th>Commune</th>
                    <th>Parcelles</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projets as $projet)
                <tr>
                    <td><span class="badge" style="background:#5D4E37; color:#fff; font-family:'Courier New',monospace;">{{ $projet->code }}</span></td>
                    <td>{{ $projet->nom }}</td>
                    <td>{{ $projet->commune->nom ?? '-' }}</td>
                    <td>{{ $projet->parcelles()->count() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('numNotifDepartGlobal');
    const btn = document.getElementById('btnSaveNumNotifGlobal');
    const confirmBadge = document.getElementById('confirmNumNotif');
    const hiddenInputs = document.querySelectorAll('.num-notif-hidden');

    // Restaurer depuis sessionStorage si présent
    const saved = sessionStorage.getItem('num_notif_depart');
    if (saved) {
        input.value = saved;
        hiddenInputs.forEach(h => h.value = saved);
    }

    function syncHidden() {
        const v = input.value.trim();
        hiddenInputs.forEach(h => h.value = v);
    }
    syncHidden();
    input.addEventListener('input', syncHidden);

    btn.addEventListener('click', function() {
        const v = input.value.trim();
        const n = parseInt(v, 10);
        if (isNaN(n) || n < 0) { showToast('Numéro invalide.', 'warning'); return; }
        sessionStorage.setItem('num_notif_depart', v);
        syncHidden();
        confirmBadge.style.display = 'inline-block';
        setTimeout(() => confirmBadge.style.display = 'none', 2500);
    });

    // Bloquer le submit si pas de numéro
    document.querySelectorAll('.form-import-global').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!input.value.trim()) {
                e.preventDefault();
                showToast('Veuillez d\'abord saisir le N° de notification de départ.', 'warning');
                input.focus();
            }
        });
    });
});
</script>
@endpush
@endsection
