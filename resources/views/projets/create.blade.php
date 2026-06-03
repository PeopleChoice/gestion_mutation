@extends('layouts.app')
@section('title', 'Nouveau projet')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-building"></i> Créer un projet / lotissement</h6></div>
            <div class="card-body">
                <form action="{{ route('projets.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <h6 class="text-muted mb-3"><i class="bi bi-info-circle"></i> Informations du projet</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nom du projet <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" value="{{ old('nom') }}" required placeholder="ex: RAVIN">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Commune <span class="text-danger">*</span></label>
                            <select name="commune_id" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                @foreach($communes as $commune)
                                    <option value="{{ $commune->id }}" {{ old('commune_id') == $commune->id ? 'selected' : '' }}>{{ $commune->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type de lotissement</label>
                            <select name="type_lotissement" class="form-select">
                                <option value="">--</option>
                                <option value="Régularisation" {{ old('type_lotissement') == 'Régularisation' ? 'selected' : '' }}>Régularisation</option>
                                <option value="Extension" {{ old('type_lotissement') == 'Extension' ? 'selected' : '' }}>Extension</option>
                                <option value="Nouveau" {{ old('type_lotissement') == 'Nouveau' ? 'selected' : '' }}>Nouveau</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <!-- Coordonnées GPS -->
                    <h6 class="text-muted mt-4 mb-3"><i class="bi bi-geo-alt"></i> Coordonnées GPS (optionnel)</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" name="latitude" class="form-control" step="0.0000001" value="{{ old('latitude') }}" placeholder="ex: 14.9500">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" name="longitude" class="form-control" step="0.0000001" value="{{ old('longitude') }}" placeholder="ex: -16.8200">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="getLocation()">
                                <i class="bi bi-crosshair"></i> Ma position
                            </button>
                        </div>
                    </div>

                    <!-- Import initial -->
                    <h6 class="text-muted mt-4 mb-3"><i class="bi bi-file-earmark-excel"></i> Import initial des parcelles (optionnel)</h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Fichier Excel des parcelles</label>
                            <input type="file" name="fichier_initial" class="form-control" accept=".xls,.xlsx">
                            <small class="text-muted">
                                Format attendu : LOT, Civilité, Prénom, NOM, Type pièce, Code pays, N° pièce, NINEA, TEL, etc.
                                Les parcelles sans attributaire seront créées sans propriétaire.
                            </small>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-info mb-0 mt-4" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i>
                                Le fichier peut contenir des lots avec ou sans propriétaire. Seule la colonne <strong>LOT</strong> est obligatoire.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn" style="background:#C8A951; color:#fff;">
                            <i class="bi bi-check-lg"></i> Créer le projet
                        </button>
                        <a href="{{ route('projets.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.querySelector('input[name="latitude"]').value = pos.coords.latitude.toFixed(7);
            document.querySelector('input[name="longitude"]').value = pos.coords.longitude.toFixed(7);
        }, function() { showToast('Impossible de récupérer la position.', 'warning'); });
    } else {
        showToast('Géolocalisation non supportée.', 'warning');
    }
}
</script>
@endpush
@endsection
