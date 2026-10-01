#!/usr/bin/env python3
"""Premières bandes horizontales où deux captures diffèrent : pixdiff.py a.png b.png [bande=20] [seuil=6]
Ignore les 20 premiers px (compteur GES de l'en-tête). Écrit aussi un côte-à-côte autour du premier écart."""
import sys
from PIL import Image, ImageChops, ImageStat
a, b = Image.open(sys.argv[1]).convert("RGB"), Image.open(sys.argv[2]).convert("RGB")
band = int(sys.argv[3]) if len(sys.argv) > 3 else 20
thr = float(sys.argv[4]) if len(sys.argv) > 4 else 6
w, h = min(a.width, b.width), min(a.height, b.height)
first = None
hits = []
for y in range(20, h - band, band):
    d = ImageStat.Stat(ImageChops.difference(a.crop((0, y, w, y + band)), b.crop((0, y, w, y + band)))).mean
    if sum(d) / 3 > thr:
        hits.append((y, round(sum(d) / 3, 1)))
        first = first if first is not None else y
print(f"hauteurs {a.height} / {b.height} ; {len(hits)} bandes différentes ; premières : {hits[:12]}")
if first is not None and len(sys.argv) > 5:
    y0 = max(0, first - 300)
    c = Image.new("RGB", (w * 2 + 20, 900), "white")
    c.paste(a.crop((0, y0, w, y0 + 900)), (0, 0)); c.paste(b.crop((0, y0, w, y0 + 900)), (w + 20, 0))
    c.resize(((w * 2 + 20) // 2, 450)).save(sys.argv[5])
