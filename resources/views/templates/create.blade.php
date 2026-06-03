@extends('layouts.app')
@section('title', 'Nouveau template')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.css">
<style>
    .placeholder-tag {
        display: inline-block; background: #E6FAF8; color: #0f6e63; border: 1px solid #20D5C0;
        border-radius: 4px; padding: 1px 6px; font-size: 11px; font-family: monospace;
        cursor: pointer; margin: 2px; transition: all .2s;
    }
    .placeholder-tag:hover { background: #20D5C0; color: #fff; }
    .CodeMirror { border: 1px solid #dee2e6; border-radius: 6px; font-size: 12px; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between mb-3">
    <p class="text-muted mb-0">Le format par défaut est pré-rempli. Modifiez selon vos besoins.</p>
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnChargerDefaut">
        <i class="bi bi-magic"></i> Recharger le format par défaut
    </button>
</div>

<form action="{{ route('templates.store') }}" method="POST" id="templateForm">
    @csrf

    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-gear"></i> Paramètres</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" id="inputNom" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Type <span class="text-danger">*</span></label>
                    <input type="text" name="type" id="inputType" class="form-control" value="notification_attribution" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Centre fiscal</label>
                    <input type="text" name="centre_fiscal" id="inputCentre" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Bureau</label>
                    <input type="text" name="bureau" id="inputBureau" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <!-- Variables (catalogue centralisé, groupé) -->
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-braces"></i> Variables (cliquer pour insérer dans l'éditeur actif)</h6></div>
        <div class="card-body py-2">
            <input type="text" id="phSearch" class="form-control form-control-sm mb-2" placeholder="🔍 Filtrer une variable...">
            @foreach(\App\Models\DocumentTemplatePlaceholders::all() as $category => $items)
            <div class="ph-group mb-2">
                <div class="text-muted small fw-bold mb-1" style="text-transform:uppercase; letter-spacing:.5px; font-size:11px;">{{ $category }}</div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach($items as $item)
                        @php $ph = '{{' . $item['key'] . '}}'; @endphp
                        <span class="placeholder-tag ph-item"
                              data-search="{{ strtolower($item['key'] . ' ' . $item['label']) }}"
                              title="{{ $item['description'] }}"
                              onclick="insererPlaceholder('{{ $ph }}')">
                            {{ $item['label'] ?: $item['key'] }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- En-tête -->
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-card-heading"></i> En-tête (styles + image + QR)</h6></div>
        <div class="card-body">
            <textarea name="entete_html" id="enteteEditor" required></textarea>
        </div>
    </div>

    <!-- Corps -->
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-body-text"></i> Corps du document</h6></div>
        <div class="card-body">
            <textarea name="corps_html" id="corpsEditor" required></textarea>
        </div>
    </div>

    <!-- Pied -->
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-card-text"></i> Pied de page</h6></div>
        <div class="card-body">
            <textarea name="pied_html" id="piedEditor"></textarea>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn" style="background:#20D5C0; color:#fff;"><i class="bi bi-check-lg"></i> Créer le template</button>
        <a href="{{ route('templates.index') }}" class="btn btn-secondary">Annuler</a>
    </div>
</form>

<script id="defautData" type="application/json">{!! json_encode($defaut) !!}</script>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/htmlmixed/htmlmixed.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const defaut = JSON.parse(document.getElementById('defautData').textContent);

    // Filtre rapide placeholders
    const phSearch = document.getElementById('phSearch');
    if (phSearch) {
        phSearch.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.ph-item').forEach(el => {
                const match = !q || (el.dataset.search || '').includes(q);
                el.style.display = match ? '' : 'none';
            });
            document.querySelectorAll('.ph-group').forEach(g => {
                const visibles = g.querySelectorAll('.ph-item:not([style*="display: none"])').length;
                g.style.display = visibles > 0 ? '' : 'none';
            });
        });
    }

    // CodeMirror
    const cmOptions = { mode: 'htmlmixed', lineNumbers: true, lineWrapping: true, indentUnit: 4 };

    const enteteEditor = CodeMirror.fromTextArea(document.getElementById('enteteEditor'), cmOptions);
    enteteEditor.setSize(null, 200);

    const corpsEditor = CodeMirror.fromTextArea(document.getElementById('corpsEditor'), cmOptions);
    corpsEditor.setSize(null, 300);

    const piedEditor = CodeMirror.fromTextArea(document.getElementById('piedEditor'), cmOptions);
    piedEditor.setSize(null, 150);

    let activeEditor = corpsEditor;
    corpsEditor.on('focus', () => { activeEditor = corpsEditor; });
    piedEditor.on('focus', () => { activeEditor = piedEditor; });
    enteteEditor.on('focus', () => { activeEditor = enteteEditor; });

    function insererPlaceholder(tag) {
        activeEditor.replaceRange(tag, activeEditor.getCursor());
        activeEditor.focus();
    }
    window.insererPlaceholder = insererPlaceholder;

    // Sync avant soumission
    document.getElementById('templateForm').addEventListener('submit', function() {
        enteteEditor.save();
        corpsEditor.save();
        piedEditor.save();
    });

    // Charger par défaut
    function chargerDefaut() {
        document.getElementById('inputNom').value = defaut.nom;
        document.getElementById('inputType').value = defaut.type;
        document.getElementById('inputCentre').value = defaut.centre_fiscal;
        document.getElementById('inputBureau').value = defaut.bureau;
        enteteEditor.setValue(defaut.entete_html);
        corpsEditor.setValue(defaut.corps_html);
        piedEditor.setValue(defaut.pied_html);
    }

    document.getElementById('btnChargerDefaut').addEventListener('click', function() {
        showConfirm('Recharger le format par défaut ?<br><small class="text-muted">Le contenu actuel sera remplacé.</small>', () => chargerDefaut(), { label: 'Recharger', type: 'warning', icon: 'bi-arrow-counterclockwise', iconColor: '#d97706' });
    });

    // Charger par défaut à l'ouverture
    chargerDefaut();
});
</script>
@endpush
@endsection
