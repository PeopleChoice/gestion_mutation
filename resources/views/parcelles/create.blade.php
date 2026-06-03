@extends('layouts.app')
@section('title', 'Enregistrer une parcelle')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Nouvelle parcelle</h6></div>
            <div class="card-body">
                <form action="{{ route('parcelles.store') }}" method="POST">
                    @csrf
                    <h6 class="text-muted mb-3">Informations de la parcelle</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Numéro de lot <span class="text-danger">*</span></label>
                            <input type="text" name="numero_lot" class="form-control" value="{{ old('numero_lot') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Projet <span class="text-danger">*</span></label>
                            <select name="projet_id" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                @foreach($projets as $projet)
                                    <option value="{{ $projet->id }}" {{ old('projet_id') == $projet->id ? 'selected' : '' }}>
                                        {{ $projet->nom }} - {{ $projet->commune->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Superficie (m²)</label>
                            <input type="number" name="superficie" class="form-control" step="0.01" value="{{ old('superficie') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Usage</label>
                            <select name="usage" class="form-select">
                                <option value="">--</option>
                                <option value="habitation">Habitation</option>
                                <option value="commercial">Commercial</option>
                                <option value="mixte">Mixte</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-muted mb-3">Propriétaire</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Civilité</label>
                            <select name="civilite" class="form-select">
                                <option value="Monsieur">Monsieur</option>
                                <option value="Madame">Madame</option>
                                <option value="Société">Société</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" value="{{ old('prenom') }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" value="{{ old('nom') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CNI / Passeport</label>
                            <input type="text" name="cni_passport" class="form-control" value="{{ old('cni_passport') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NIN</label>
                            <input type="text" name="nin" class="form-control" value="{{ old('nin') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NINEA</label>
                            <input type="text" name="ninea" class="form-control" value="{{ old('ninea') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" value="{{ old('telephone') }}">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Enregistrer</button>
                        <a href="{{ route('parcelles.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
