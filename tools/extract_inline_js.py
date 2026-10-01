#!/usr/bin/env python3
"""Extrait des pages de référence les scripts en ligne qu'Oxygen/OxyExtras et Advanced Scripts imprimaient,
pour les rejouer depuis le thème une fois ces extensions désactivées. Écrit assets/js/oxygen-inline.js et
assets/js/advanced-scripts.js. Conversion unique : les fichiers sont ensuite entretenus à la main."""
import os, re
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent
REF = Path(os.environ.get("SIDEX_REF", Path.home() / "sidex/oxygen-debuild/raw-2026-09-25"))
OUT = ROOT / "wp/themes/sidex/assets/js"
OXYGEN = [  # (nom, motif du début du script) — un seul exemplaire chacun, sauf « multi »
    ("Menu Pro (comportements génériques)", r"^\s*function oxygen_init_pro_menu\(\)"),
    ("Menu Pro — ouverture des sous-menus au clic", r"^\s*jQuery\('#-pro-menu-"),
    ("En-tête collant", r"^\s*jQuery\(document\)\.ready\(function\(\) \{\s*var selector = \"#_header-"),
    ("Menu de navigation (hamburger)", r"^\s*jQuery\(document\)\.ready\(function\(\) \{\s*jQuery\('body'\)\.on\('click', '\.oxy-menu-toggle'"),
    ("AOS des lignes verticales", r"^\s*jQuery\('\.vertical-line'\)\.attr"),
    ("Menu coulissant (OxyExtras)", r"^\s*document\.addEventListener\(\s*\"DOMContentLoaded\", \(\) => \{\s*document\.querySelectorAll\('\.oxy-horizontal-slide-menu_inner"),
    ("Défilement vers les ancres", r"^\s*jQuery\(document\)\.on\('click','a\[href\*=\"#\"\]'"),
    ("Accordéon Pro (OxyExtras)", r"^\s*jQuery\(document\)\.ready\(oxygen_init_accordion\)"),
    ("Onglets Oxygen", r"^\s*function oxygenVSBInitTabs\(element\)"),
    ("Toggle Oxygen", r"^\s*jQuery\(document\)\.ready\(function\(\) \{\s*let event = new Event\('oxygenVSBInitToggleJs'\)"),
    ("Onglets dynamiques (OxyExtras)", r"^\s*jQuery\(document\)\.ready\(oxygen_dynamic_tabs\)"),
    ("Galeries Oxygen (PhotoSwipe)", r"^\s*document\.addEventListener\(\"oxygenVSBInitGalleryJs_gallery-"),
]
MULTI = {"Menu Pro — ouverture des sous-menus au clic", "Galeries Oxygen (PhotoSwipe)"}
AS = [  # id d'Advanced Scripts → titre
    ("script-642c80bf1ba4c-js", "30 Initialisation de la page"),
    ("script-69d8003667fc3-js", "269 Sticky CTA on scroll"),
    ("script-6458fb1d57f13-js", "82 [GSAP] SplitText 3.9.1"),
    ("script-645bb8fb7cea8-js", "84 Titles reveal"),
    ("script-6478fe488532b-js", "141 Projets - Tableau (préremplissage de la soumission)"),
    ("script-649b36364499a-js", "144 Glightbox - JS"),
    ("script-64b594372eac8-js", "245 Populer champ Poste Recherché"),
]
pages = [p.read_text(errors="replace") for p in sorted(REF.glob("*.html"))]
scripts = [(a, b) for h in pages for a, b in re.findall(r"<script(?![^>]*\bsrc=)([^>]*)>(.*?)</script>", h, re.S)]

parts, seen = [], set()
for name, pat in OXYGEN:
    found = [b for _, b in scripts if re.search(pat, b)]
    uniq = list(dict.fromkeys(b.strip() for b in found))
    if not uniq:
        print("ABSENT :", name); continue
    for b in (uniq if name in MULTI else uniq[:1]):
        parts.append(f"/* ── {name} ── */\n{b}\n")
    print(f"{name}: {len(uniq) if name in MULTI else 1}")
OUT.mkdir(parents=True, exist_ok=True)
head = "/* Scripts en ligne qu'Oxygen et OxyExtras imprimaient (extraits des pages de référence du 25 sept. 2026\n   par tools/extract_inline_js.py). Rejoués par le thème ; jQuery requis. */\n\n"
(OUT / "oxygen-inline.js").write_text(head + "\n".join(parts))

parts = []
for sid, title in AS:
    body = next((b for a, b in scripts if f"id='{sid}'" in a), None)
    if body is None:
        print("ABSENT AS :", title); continue
    parts.append(f"/* ── Advanced Scripts {title} ── */\n{body.strip()}\n")
head = "/* Scripts front d'Advanced Scripts (extension désactivée), repris tels quels. jQuery + GSAP requis. */\n\n"
(OUT / "advanced-scripts.js").write_text(head + "\n".join(parts))
print("ok")
