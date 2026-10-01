#!/usr/bin/env python3
"""Bibliothèques front que chaque gabarit charge (d'après ses types d'éléments Oxygen). Écrit inc/oxy-libs.php."""
import json
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent
LIBS = {
    "oxy-carousel-builder": ["flickity"], "oxy-off-canvas": ["offcanvas"], "oxy-horizontal-slide-menu": ["mmenu"],
    "oxy_gallery": ["photoswipe"], "oxy-table-of-contents": ["tocbot"], "oxy-lightbox": ["fancybox"],
    "oxy-dynamic-tabs": ["skeletabs"], "oxy-wpgb-facet": ["extras-wpgb"], "oxy-wpgb-grid": ["extras-wpgb"],
}
out = {}
for t in json.loads((ROOT / "docs/inventaire/templates.json").read_text()):
    libs = sorted({l for k in t["element_types"] for l in LIBS.get(k, [])})
    if libs:
        out[t["id"]] = libs
lines = ["<?php", "/** Bibliothèques front par gabarit converti (généré par tools/gen_libs.py). */", "defined('ABSPATH') || exit;", "", "const SX_OXY_LIBS = ["]
lines += [f"\t{k} => [{', '.join(repr(x) for x in v)}]," for k, v in sorted(out.items())]
lines += ["];", ""]
(ROOT / "wp/themes/sidex/inc/oxy-libs.php").write_text("\n".join(lines))
print(out)
