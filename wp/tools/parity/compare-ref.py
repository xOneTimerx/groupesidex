#!/usr/bin/env python3
"""Compare les relevés (refshots.js) du thème à ceux des captures Oxygen de référence.
Usage : compare-ref.py <dossier référence> <dossier thème> [motif]
Par page × largeur : écart de hauteur, textes absents d'un côté, textes déplacés de plus de 3 px
(après recalage vertical sur le premier texte commun), images absentes. Le compteur GES est ignoré."""
import json, re, sys
from pathlib import Path
from collections import Counter

ref, thm = Path(sys.argv[1]), Path(sys.argv[2])
pat = re.compile(sys.argv[3] if len(sys.argv) > 3 else ".")
IGNORE = re.compile(r"^\d[\d\s,.]*$|tonnes de GES")  # compteur GES (change à chaque seconde)

def rows(p):
    d = json.loads(p.read_text())
    return d["height"], [r for r in d["rows"] if not IGNORE.search(r["text"])]

total = []
for a in sorted(ref.glob("*.json")):
    if not pat.search(a.stem):
        continue
    b = thm / a.name
    if not b.exists():
        print(f"{a.stem:28} ABSENT côté thème"); continue
    ha, ra = rows(a); hb, rb = rows(b)
    ka = Counter(r["text"] for r in ra); kb = Counter(r["text"] for r in rb)
    only_a = [t for t in ka if t not in kb]; only_b = [t for t in kb if t not in ka]
    # appariement par texte, dans l'ordre
    pos_b = {}
    for r in rb:
        pos_b.setdefault(r["text"], []).append(r)
    moved = []
    for r in ra:
        lst = pos_b.get(r["text"])
        if not lst:
            continue
        s = lst.pop(0)
        dx, dy = s["x"] - r["x"], s["y"] - r["y"]
        if abs(dx) > 3 or abs(dy) > 3 or abs(s["w"] - r["w"]) > 3:
            moved.append((r["text"][:40], dx, dy, s["w"] - r["w"]))
    ok = ha == hb and not only_a and not only_b and not moved
    total.append(ok)
    print(f"{a.stem:28} {'OK ' if ok else '…  '} h {ha}→{hb} ({hb-ha:+d})  absents {len(only_a)}  nouveaux {len(only_b)}  déplacés {len(moved)}")
    if not ok and "-v" in sys.argv:
        for t in only_a[:6]: print("      − ", t[:70])
        for t in only_b[:6]: print("      + ", t[:70])
        for m in moved[:8]: print("      ↕ ", m)
print(f"\n{sum(total)}/{len(total)} identiques")
