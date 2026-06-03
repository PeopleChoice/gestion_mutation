@extends('layouts.app')
@section('title', 'Modifier projet - ' . $projet->nom)

@section('content')
<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-pencil"></i> Modifier le projet</h6></div>
            <div class="card-body">
                <form action="{{ route('projets.update', $projet) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" value="{{ $projet->nom }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Commune</label>
                            <select name="commune_id" class="form-select" required>
                                @foreach($communes as $commune)
                                    <option value="{{ $commune->id }}" {{ $projet->commune_id == $commune->id ? 'selected' : '' }}>{{ $commune->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="type_lotissement" class="form-select">
                                <option value="">--</option>
                                <option value="Régularisation" {{ $projet->type_lotissement == 'Régularisation' ? 'selected' : '' }}>Régularisation</option>
                                <option value="Extension" {{ $projet->type_lotissement == 'Extension' ? 'selected' : '' }}>Extension</option>
                                <option value="Nouveau" {{ $projet->type_lotissement == 'Nouveau' ? 'selected' : '' }}>Nouveau</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" name="latitude" class="form-control" step="0.0000001" value="{{ $projet->latitude }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" name="longitude" class="form-control" step="0.0000001" value="{{ $projet->longitude }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ $projet->description }}</textarea>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn" style="background:#C8A951; color:#fff;">Mettre à jour</button>
                        <a href="{{ route('projets.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import de parcelles supplémentaires -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header" style="background:#fdf6e3;">
                <h6 class="mb-0"><i class="bi bi-file-earmark-excel"></i> Importer des parcelles</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Ajouter des parcelles au projet via un fichier Excel. Les doublons seront ignorés.</p>
                <form action="{{ route('projets.importer-parcelles', $projet) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="fichier" class="form-control form-control-sm" accept=".xls,.xlsx" required>
                    </div>
                    <button type="submit" class="btn btn-sm" style="background:#C8A951; color:#fff;">
                        <i class="bi bi-upload"></i> Importer
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Statistiques</h6></div>
            <div class="card-body">
                @php
                    $nbParcelles = $projet->parcelles()->count();
                    $nbAvecProp = $projet->parcelles()->whereNotNull('proprietaire_id')->count();
                    $nbSansProp = $nbParcelles - $nbAvecProp;
                    $nbAvecGeo = $projet->parcelles()->whereNotNull('geometrie')->count();
                @endphp
                <p class="mb-1"><strong>{{ $nbParcelles }}</strong> parcelle(s)</p>
                <p class="mb-1 text-success"><i class="bi bi-check-circle"></i> {{ $nbAvecProp }} avec propriétaire</p>
                <p class="mb-1 text-warning"><i class="bi bi-exclamation-circle"></i> {{ $nbSansProp }} sans attributaire</p>
                <p class="mb-0 text-primary"><i class="bi bi-map"></i> {{ $nbAvecGeo }} avec géométrie</p>
            </div>
        </div>
    </div>
</div>
@endsection
