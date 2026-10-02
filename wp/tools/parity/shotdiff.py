#!/usr/bin/env python3
"""Compare deux dossiers de captures refshots.js : shotdiff.py <dossier-a> <dossier-b> (hauteurs + bandes de 40 px différentes)."""
import os, sys
from PIL import Image, ImageChops, ImageStat
a_dir, b_dir = sys.argv[1], sys.argv[2]
for f in sorted(x for x in os.listdir(a_dir) if x.endswith('.png')):
    if not os.path.exists(f'{b_dir}/{f}'):
        print(f'{f:30} MANQUANT'); continue
    A, B = Image.open(f'{a_dir}/{f}').convert('RGB'), Image.open(f'{b_dir}/{f}').convert('RGB')
    w, h, band = min(A.width, B.width), min(A.height, B.height), 40
    bad = [y for y in range(0, h - band, band) if sum(ImageStat.Stat(ImageChops.difference(A.crop((0, y, w, y + band)), B.crop((0, y, w, y + band)))).mean) / 3 > 6]
    print(f'{f[:-4]:30} {A.height:6} / {B.height:6}  écart {B.height - A.height:+6}  bandes≠ {len(bad):4}/{h // band}  1re {bad[0] if bad else "-"}')
