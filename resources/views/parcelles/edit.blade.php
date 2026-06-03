@extends('layouts.app')
@section('title', 'Modifier parcelle - Lot ' . $parcelle->numero_lot)

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('parcelles.update', $parcelle) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Numéro de lot</label>
                            <input type="text" name="numero_lot" class="form-control" value="{{ $parcelle->numero_lot }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Projet</label>
                            <select name="projet_id" class="form-select" required>
                                @foreach($projets as $projet)
                                    <option value="{{ $projet->id }}" {{ $parcelle->projet_id == $projet->id ? 'selected' : '' }}>
                                        {{ $projet->nom }} - {{ $projet->commune->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Superficie</label>
                            <input type="number" name="superficie" class="form-control" step="0.01" value="{{ $parcelle->superficie }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Usage</label>
                            <select name="usage" class="form-select">
                                <option value="">--</option>
                                <option value="habitation" {{ $parcelle->usage === 'habitation' ? 'selected' : '' }}>Habitation</option>
                                <option value="commercial" {{ $parcelle->usage === 'commercial' ? 'selected' : '' }}>Commercial</option>
                                <option value="mixte" {{ $parcelle->usage === 'mixte' ? 'selected' : '' }}>Mixte</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-success">Mettre à jour</button>
                        <a href="{{ route('parcelles.show', $parcelle) }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
