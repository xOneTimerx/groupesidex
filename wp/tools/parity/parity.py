#!/usr/bin/env python3
"""Diff aligné par texte entre a.json (Oxygen) et b.json (thème).
   python3 parity.py <dossier> [seuil_px=3]
Pour chaque texte présent des deux côtés (dans l'ordre), signale les écarts de position,
taille ou style. Liste aussi les textes absents d'un côté."""
import json, sys
from difflib import SequenceMatcher

d = sys.argv[1]; tol = int(sys.argv[2]) if len(sys.argv) > 2 else 3
a = json.load(open(f'{d}/a.json')); b = json.load(open(f'{d}/b.json'))
ra = sorted(a['rows'], key=lambda r: (r['y'], r['x'])); rb = sorted(b['rows'], key=lambda r: (r['y'], r['x']))
import re
def norm_color(c):
    m = re.match(r'oklab\(0\.99\d* [-\d.e]+ [-\d.e]+(?: / ([\d.]+))?\)', c)
    if m: return f"rgba(255, 255, 255, {m.group(1)})" if m.group(1) else 'rgb(255, 255, 255)'
    return c
for r in ra + rb: r['c'] = norm_color(r['c'])
QUIET = '--quiet' in sys.argv
key = lambda r: r['text'].lower()
sm = SequenceMatcher(None, [key(r) for r in ra], [key(r) for r in rb], autojunk=False)
print(f"hauteur Oxygen {a['height']} / thème {b['height']} (écart {b['height'] - a['height']:+d})")
issues = 0
for op, i1, i2, j1, j2 in sm.get_opcodes():
    if op == 'equal':
        for x, y in zip(ra[i1:i2], rb[j1:j2]):
            diffs = []
            for k in ('x', 'y', 'w', 'h'):
                if abs(x[k] - y[k]) > tol: diffs.append(f"{k} {x[k]}→{y[k]}")
            for k in ('fs', 'fw', 'lh', 'c', 'ls', 'tt'):
                if x[k] != y[k]: diffs.append(f"{k} {x[k]}→{y[k]}")
            if QUIET and x['text'].startswith('IMG:'):
                diffs = [d for d in diffs if not d.startswith('c ')]
            if diffs:
                issues += 1
                print(f"  ≠ [{x['tag']}→{y['tag']}] {x['text'][:45]!r}: " + ', '.join(diffs))
    else:
        if QUIET:
            continue
        for x in ra[i1:i2]: print(f"  − Oxygen seulement [{x['tag']}] y={x['y']} {x['text'][:60]!r}")
        for y in rb[j1:j2]: print(f"  + thème seulement  [{y['tag']}] y={y['y']} {y['text'][:60]!r}")
        issues += (i2 - i1) + (j2 - j1)
print(f"{issues} écart(s)")
