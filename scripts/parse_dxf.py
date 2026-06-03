#!/usr/bin/env python3
"""
Parse un fichier DXF (AutoCAD) et extrait les parcelles en GeoJSON.
Usage: python3 parse_dxf.py <fichier.dxf> <output.json> [--lat=14.95] [--lng=-16.82] [--scale=0.00001]

Les fichiers DXF utilisent des coordonnées locales (mètres).
On les convertit en coordonnées GPS avec un point d'origine (lat/lng) et une échelle.
"""

import sys
import json
import os

try:
    import ezdxf
except ImportError:
    print(json.dumps({"error": "ezdxf non installé. Exécutez: pip3 install ezdxf"}))
    sys.exit(1)


def parse_args():
    args = {
        'input': None,
        'output': None,
        'lat': 14.95,
        'lng': -16.82,
        'scale': 0.00001,  # ~1.1m par unité au Sénégal
    }
    positional = []
    for arg in sys.argv[1:]:
        if arg.startswith('--lat='):
            args['lat'] = float(arg.split('=')[1])
        elif arg.startswith('--lng='):
            args['lng'] = float(arg.split('=')[1])
        elif arg.startswith('--scale='):
            args['scale'] = float(arg.split('=')[1])
        else:
            positional.append(arg)

    if len(positional) < 2:
        print(f"Usage: {sys.argv[0]} <fichier.dxf> <output.json> [--lat=14.95] [--lng=-16.82] [--scale=0.00001]")
        sys.exit(1)

    args['input'] = positional[0]
    args['output'] = positional[1]
    return args


def local_to_gps(x, y, origin_lat, origin_lng, scale):
    """Convertir coordonnées locales (mètres) en GPS (lat/lng)."""
    lat = origin_lat + (y * scale)
    lng = origin_lng + (x * scale)
    return [lng, lat]  # GeoJSON = [longitude, latitude]


def extract_parcelles(dxf_path, origin_lat, origin_lng, scale):
    """Extraire les entités du DXF et les convertir en features GeoJSON."""
    doc = ezdxf.readfile(dxf_path)
    msp = doc.modelspace()

    features = []
    parcelle_id = 0

    # Extraire les entités géométriques
    for entity in msp:
        coords = []
        lot_number = None
        layer = entity.dxf.layer if hasattr(entity.dxf, 'layer') else ''

        if entity.dxftype() == 'LWPOLYLINE':
            # Polyligne légère (le plus courant pour les parcelles)
            points = list(entity.get_points(format='xy'))
            coords = [local_to_gps(x, y, origin_lat, origin_lng, scale) for x, y in points]
            # Fermer le polygone si nécessaire
            if coords and coords[0] != coords[-1]:
                coords.append(coords[0])

        elif entity.dxftype() == 'POLYLINE':
            points = [(v.dxf.location.x, v.dxf.location.y) for v in entity.vertices]
            coords = [local_to_gps(x, y, origin_lat, origin_lng, scale) for x, y in points]
            if coords and coords[0] != coords[-1]:
                coords.append(coords[0])

        elif entity.dxftype() == 'LINE':
            x1, y1 = entity.dxf.start.x, entity.dxf.start.y
            x2, y2 = entity.dxf.end.x, entity.dxf.end.y
            coords = [
                local_to_gps(x1, y1, origin_lat, origin_lng, scale),
                local_to_gps(x2, y2, origin_lat, origin_lng, scale),
            ]

        elif entity.dxftype() == 'CIRCLE':
            cx, cy = entity.dxf.center.x, entity.dxf.center.y
            r = entity.dxf.radius
            import math
            coords = []
            for i in range(32):
                angle = (2 * math.pi * i) / 32
                x = cx + r * math.cos(angle)
                y = cy + r * math.sin(angle)
                coords.append(local_to_gps(x, y, origin_lat, origin_lng, scale))
            coords.append(coords[0])

        elif entity.dxftype() in ('TEXT', 'MTEXT'):
            # Textes = potentiellement des numéros de lot
            text = entity.dxf.text if entity.dxftype() == 'TEXT' else entity.text
            x, y = entity.dxf.insert.x, entity.dxf.insert.y
            gps = local_to_gps(x, y, origin_lat, origin_lng, scale)

            features.append({
                "type": "Feature",
                "geometry": {
                    "type": "Point",
                    "coordinates": gps,
                },
                "properties": {
                    "type": "label",
                    "text": text.strip(),
                    "layer": layer,
                }
            })
            continue

        if len(coords) >= 3:
            parcelle_id += 1

            # Calculer le centroïde
            lngs = [c[0] for c in coords[:-1]]
            lats = [c[1] for c in coords[:-1]]
            centroid = [sum(lngs) / len(lngs), sum(lats) / len(lats)]

            features.append({
                "type": "Feature",
                "geometry": {
                    "type": "Polygon",
                    "coordinates": [coords],
                },
                "properties": {
                    "type": "parcelle",
                    "id": parcelle_id,
                    "layer": layer,
                    "lot": None,  # sera matché après
                    "centroid": centroid,
                }
            })
        elif len(coords) == 2:
            features.append({
                "type": "Feature",
                "geometry": {
                    "type": "LineString",
                    "coordinates": coords,
                },
                "properties": {
                    "type": "line",
                    "layer": layer,
                }
            })

    # Essayer de matcher les labels (textes) aux polygones
    labels = [f for f in features if f['properties'].get('type') == 'label']
    polygons = [f for f in features if f['properties'].get('type') == 'parcelle']

    for label in labels:
        lpt = label['geometry']['coordinates']
        text = label['properties']['text']

        # Trouver le polygone le plus proche
        best_dist = float('inf')
        best_poly = None
        for poly in polygons:
            centroid = poly['properties'].get('centroid', [0, 0])
            dist = ((lpt[0] - centroid[0]) ** 2 + (lpt[1] - centroid[1]) ** 2) ** 0.5
            if dist < best_dist:
                best_dist = dist
                best_poly = poly

        if best_poly and best_poly['properties']['lot'] is None:
            # Vérifier si le texte ressemble à un numéro de lot
            clean = text.replace(' ', '').replace('-', '')
            if clean.isdigit() or text.startswith('AT') or text.startswith('CR') or text.startswith('ZS') or text.startswith('PA'):
                best_poly['properties']['lot'] = text.strip()

    geojson = {
        "type": "FeatureCollection",
        "features": features,
        "metadata": {
            "origin_lat": origin_lat,
            "origin_lng": origin_lng,
            "scale": scale,
            "total_parcelles": len(polygons),
            "total_labels": len(labels),
        }
    }

    return geojson


def main():
    args = parse_args()

    if not os.path.exists(args['input']):
        print(json.dumps({"error": f"Fichier introuvable: {args['input']}"}))
        sys.exit(1)

    try:
        geojson = extract_parcelles(
            args['input'],
            args['lat'],
            args['lng'],
            args['scale']
        )

        with open(args['output'], 'w', encoding='utf-8') as f:
            json.dump(geojson, f, ensure_ascii=False, indent=2)

        print(json.dumps({
            "success": True,
            "parcelles": geojson['metadata']['total_parcelles'],
            "labels": geojson['metadata']['total_labels'],
            "output": args['output'],
        }))

    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)


if __name__ == '__main__':
    main()
