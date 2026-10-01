#!/usr/bin/env python3
"""Premier endroit où le thème s'écarte de la référence (textes appariés, triés par y) : first-diff.py ref.json theme.json"""
import json, sys
a, b = (json.load(open(p))["rows"] for p in sys.argv[1:3])
pos = {}
for r in b:
    pos.setdefault(r["text"], []).append(r)
prev = 0
for r in sorted(a, key=lambda r: r["y"]):
    lst = pos.get(r["text"])
    if not lst:
        print(f"y={r['y']:6} ABSENT côté thème : {r['tag']} {r['text'][:60]}"); continue
    s = lst.pop(0); d = s["y"] - r["y"]
    if abs(d - prev) > 3:
        print(f"y={r['y']:6} décalage {prev:+d} → {d:+d} à : {r['tag']} {r['text'][:60]} (h {r['h']}→{s['h']})")
        prev = d
