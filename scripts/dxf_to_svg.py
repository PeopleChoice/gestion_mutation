#!/usr/bin/env python3
"""
Convertit un fichier DXF en image PNG pour l'affichage web.
Filtre les points aberrants pour un rendu correct.
Usage: python3 dxf_to_svg.py <fichier.dxf> <output.png>
"""

import sys
import os
import json
import math

try:
    import ezdxf
    from ezdxf.addons.drawing import RenderContext, Frontend
    from ezdxf.addons.drawing.matplotlib import MatplotlibBackend
    from ezdxf import bbox
    import matplotlib.pyplot as plt
except ImportError as e:
    print(json.dumps({"error": f"Dependance manquante : {e}"}))
    sys.exit(1)


def collect_points(msp):
    """Collecter tous les points significatifs pour calculer un bbox robuste."""
    xs, ys = [], []
    for entity in msp:
        try:
            ebb = bbox.extents([entity])
            if not ebb.has_data:
                continue
            xs.append(ebb.extmin.x); xs.append(ebb.extmax.x)
            ys.append(ebb.extmin.y); ys.append(ebb.extmax.y)
        except Exception:
            continue
    return xs, ys


def percentile(sorted_list, pct):
    if not sorted_list:
        return 0
    k = (len(sorted_list) - 1) * pct
    f = math.floor(k); c = math.ceil(k)
    if f == c:
        return sorted_list[int(k)]
    return sorted_list[f] * (c - k) + sorted_list[c] * (k - f)


def main():
    if len(sys.argv) < 3:
        print(json.dumps({"error": f"Usage: {sys.argv[0]} <input.dxf> <output.png>"}))
        sys.exit(1)

    input_path = sys.argv[1]
    output_path = sys.argv[2]

    if not os.path.exists(input_path):
        print(json.dumps({"error": f"Fichier introuvable : {input_path}"}))
        sys.exit(1)

    try:
        doc = ezdxf.readfile(input_path)
    except Exception as e:
        print(json.dumps({"error": f"Lecture DXF impossible : {e}"}))
        sys.exit(1)

    try:
        msp = doc.modelspace()

        # Calcul bbox robuste via percentiles (filtre les points aberrants)
        xs, ys = collect_points(msp)
        if not xs or not ys:
            print(json.dumps({"error": "Aucune entite exploitable dans le DXF"}))
            sys.exit(1)

        xs.sort(); ys.sort()
        # Utiliser les percentiles 2-98 pour eliminer les outliers
        minx = percentile(xs, 0.02)
        maxx = percentile(xs, 0.98)
        miny = percentile(ys, 0.02)
        maxy = percentile(ys, 0.98)

        # Marge de 5%
        w = maxx - minx; h = maxy - miny
        minx -= w * 0.05; maxx += w * 0.05
        miny -= h * 0.05; maxy += h * 0.05

        width = maxx - minx
        height = maxy - miny
        aspect = width / height if height else 1

        if aspect >= 1:
            fig_w, fig_h = 28, max(8, 28 / aspect)
        else:
            fig_w, fig_h = max(8, 28 * aspect), 28

        fig = plt.figure(figsize=(fig_w, fig_h), facecolor='white')
        ax = fig.add_subplot(1, 1, 1)
        ax.set_aspect('equal')
        ax.set_axis_off()

        ctx = RenderContext(doc)
        backend = MatplotlibBackend(ax)
        frontend = Frontend(ctx, backend)
        frontend.draw_layout(msp, finalize=True)

        # Forcer le bbox apres le rendu (filtre les outliers visuels)
        ax.set_xlim(minx, maxx)
        ax.set_ylim(miny, maxy)

        fig.savefig(output_path, format='png', dpi=130, bbox_inches='tight', pad_inches=0.1, facecolor='white')
        plt.close(fig)

        nb_entities = sum(1 for _ in msp)

        print(json.dumps({
            "success": True,
            "output": output_path,
            "entities": nb_entities,
            "size_bytes": os.path.getsize(output_path),
            "bbox": {"w": round(width, 2), "h": round(height, 2)},
        }))
    except Exception as e:
        import traceback
        print(json.dumps({"error": f"Conversion echouee : {e}", "trace": traceback.format_exc()[:800]}))
        sys.exit(1)


if __name__ == '__main__':
    main()
