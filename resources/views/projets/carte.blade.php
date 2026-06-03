@extends('layouts.app')
@section('title', 'Carte des projets')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #map {
        height: calc(100vh - 200px);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .lot-label { background: #5D4E37 !important; color: #fff !important; border: none !important; font-size: 10px !important; padding: 2px 6px !important; border-radius: 3px !important; box-shadow: none !important; }
    .lot-label::before { border: none !important; }
    .projet-popup h6 { color: #5D4E37; margin-bottom: 5px; }
    .projet-popup .info { font-size: 12px; color: #666; margin-bottom: 3px; }
    .projet-popup .badge-count { background: #C8A951; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <span class="text-muted">{{ $projets->count() }} projet(s) géolocalisé(s)</span>
    </div>
    <a href="{{ route('projets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list"></i> Liste</a>
</div>

@if($projets->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-map" style="font-size:48px; color:#C8A951; opacity:0.5;"></i>
            <h5 class="mt-3 text-muted">Aucun projet géolocalisé</h5>
            <p class="text-muted">Ajoutez des coordonnées GPS à vos projets pour les voir sur la carte.</p>
            <a href="{{ route('projets.index') }}" class="btn btn-sm" style="background:#C8A951; color:#fff;">Gérer les projets</a>
        </div>
    </div>
@else
    <div id="map"></div>
@endif

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
@if($projets->isNotEmpty())
document.addEventListener('DOMContentLoaded', function() {
    // Centrer sur le Sénégal par défaut
    const map = L.map('map').setView([14.6928, -17.4467], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Icône personnalisée DGID
    const dgidIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background:#C8A951; width:30px; height:30px; border-radius:50%; border:3px solid #5D4E37; display:flex; align-items:center; justify-content:center;"><i class="bi bi-building" style="color:#fff; font-size:14px;"></i></div>',
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        popupAnchor: [0, -18],
    });

    const allLayers = [];

    @foreach($projets as $projet)
    (function() {
        // Marqueur du projet
        const marker = L.marker([{{ $projet->latitude }}, {{ $projet->longitude }}], { icon: dgidIcon }).addTo(map);
        marker.bindPopup(`
            <div class="projet-popup">
                <h6><i class="bi bi-building"></i> {{ $projet->nom }}</h6>
                <div class="info"><i class="bi bi-pin-map"></i> {{ $projet->commune->nom }}</div>
                @if($projet->type_lotissement)
                <div class="info"><i class="bi bi-tag"></i> {{ $projet->type_lotissement }}</div>
                @endif
                <div class="info"><i class="bi bi-geo-alt"></i> <span class="badge-count">{{ $projet->parcelles_count }} parcelle(s)</span></div>
                <div style="margin-top:8px;">
                    <a href="{{ route('projets.show', $projet) }}" class="btn btn-sm" style="background:#C8A951; color:#fff; font-size:11px;">Voir le projet</a>
                </div>
            </div>
        `);
        allLayers.push(marker);

        // Afficher les polygones des parcelles si GeoJSON existe
        @if($projet->geojson)
        const geojson = {!! json_encode($projet->geojson) !!};
        if (geojson && geojson.features) {
            const geoLayer = L.geoJSON(geojson, {
                style: function(feature) {
                    if (feature.properties.type === 'parcelle') {
                        return { color: '#C8A951', weight: 2, fillColor: '#C8A951', fillOpacity: 0.15 };
                    }
                    return { color: '#5D4E37', weight: 1, dashArray: '3' };
                },
                filter: function(feature) {
                    return feature.geometry.type === 'Polygon' || feature.geometry.type === 'LineString';
                },
                onEachFeature: function(feature, layer) {
                    if (feature.properties.type === 'parcelle' && feature.properties.lot) {
                        layer.bindPopup(`
                            <div class="projet-popup">
                                <h6>Lot ${feature.properties.lot}</h6>
                                <div class="info">{{ $projet->nom }} - {{ $projet->commune->nom }}</div>
                                <div style="margin-top:5px;">
                                    <a href="/parcelles?projet_id={{ $projet->id }}&search=${feature.properties.lot}" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">Voir la parcelle</a>
                                </div>
                            </div>
                        `);
                        layer.bindTooltip('Lot ' + feature.properties.lot, { permanent: false, direction: 'center', className: 'lot-label' });
                    }
                }
            }).addTo(map);
            allLayers.push(geoLayer);
        }
        @endif
    })();
    @endforeach

    // Ajuster le zoom
    if (allLayers.length > 0) {
        const group = L.featureGroup(allLayers);
        map.fitBounds(group.getBounds().pad(0.2));
    }
});
@endif
</script>
@endpush
@endsection
