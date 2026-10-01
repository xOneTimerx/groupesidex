#!/usr/bin/env python3
"""Analyse les sources Oxygen rapatriées (hors dépôt : $SIDEX_OXY_SRC, défaut ~/sidex/oxygen-debuild/oxygen-source-2026-09-25) : champs ACF, fonctions PHP, shortcodes,
reusables, scripts JS/CSS par gabarit. Écrit docs/inventaire/templates.json. Lecture seule."""
import base64, json, re, sys
from pathlib import Path

root = Path(__file__).resolve().parents[3] / "docs"
import os
src = Path(os.environ.get("SIDEX_OXY_SRC", Path.home() / "sidex/oxygen-debuild/oxygen-source-2026-09-25"))
inv = json.loads((root / "inventaire" / "inventory.json").read_text())

def b64(s):
    try:
        return base64.b64decode(s).decode("utf-8", "replace")
    except Exception:
        return ""

out = []
for t in inv["templates"]:
    sc = (src / f"{t['id']}.sc").read_text(errors="replace")
    js = (src / f"{t['id']}.json").read_text(errors="replace")
    code = {"php": [], "js": [], "css": []}
    for kind, val in re.findall(r'"code-(php|js|css)":"([A-Za-z0-9+/=]{8,})"', sc):
        code[kind].append(b64(val))
    php = "\n".join(code["php"])
    fields = set(re.findall(r"arguments='([a-z0-9_]+)'", sc))
    fields |= set(re.findall(r"\bfield='([a-z0-9_]+)'", sc))
    fields |= set(re.findall(r"(?:get_field|the_field|get_sub_field|the_sub_field|have_rows|get_field_object)\(\s*['\"]([a-z0-9_]+)['\"]", php))
    funcs = sorted(set(re.findall(r"function='([a-z0-9_]+)'", sc)))
    datas = sorted(set(re.findall(r"data='([a-z0-9_]+)'", sc)))
    shortcodes = sorted(set(re.findall(r"\[(fluentform|wpgb_grid|wpgb_facet|table|tablepress|rank_math_breadcrumb|wpml_language_selector_widget|[a-z_]+_shortcode)[^\]]*\]", php + sc)))
    shortcodes_full = sorted(set(re.findall(r"\[(?:fluentform|wpgb_grid|wpgb_facet|table)[^\]]*\]", php + sc.replace("\\'", "'"))))
    reusables = sorted(set(re.findall(r'"view_id":"?(\d+)', js)))
    grids = sorted(set(re.findall(r'"(?:wpgb_grid|grid)":"?(\d+)', js)))
    libs = sorted({m for m in re.findall(r"\b(gsap|ScrollTrigger|SplitText|splide|Splide|Flickity|swiper|Swiper|fancybox|lottie|aos|AOS|jQuery)\b", "\n".join(code["js"]) + php)})
    names = t["element_types"]
    out.append({
        "id": t["id"], "title": t["title"], "parent": t["parent"], "rules": t["rules"], "elements": t["elements"],
        "element_types": names, "acf_fields": sorted(fields), "php_functions": funcs, "dynamic_data": datas,
        "shortcodes": shortcodes_full, "reusables": reusables, "wpgb": grids, "js_libs": libs,
        "code_blocks": {k: len(v) for k, v in code.items()}, "code_bytes": {k: sum(map(len, v)) for k, v in code.items()},
        "oxyextras": sorted(k for k in names if k.startswith("oxy_") or k.startswith("oxyextras")),
    })
(root / "inventaire" / "templates.json").write_text(json.dumps(out, ensure_ascii=False, indent=1))
for o in out:
    print(o["id"], o["title"][:34].ljust(34), "el", o["elements"], "acf", len(o["acf_fields"]), "code", o["code_blocks"], "sc", o["shortcodes"][:2], "reu", o["reusables"], "lib", o["js_libs"], "ext", o["oxyextras"][:4])
