#!/usr/bin/env python3
# Côte à côte réduit de a.png (Oxygen) et b.png (thème), découpé en tranches : python3 sbs.py <dossier> [hauteur_tranche]
import sys
from PIL import Image
d = sys.argv[1]; step = int(sys.argv[2]) if len(sys.argv) > 2 else 1800
a = Image.open(f'{d}/a.png').convert('RGB'); b = Image.open(f'{d}/b.png').convert('RGB')
w = a.width; h = max(a.height, b.height); n = 0
for y in range(0, h, step):
    tile = Image.new('RGB', (w * 2 + 20, step), 'red')
    tile.paste(a.crop((0, y, w, min(y + step, a.height))), (0, 0))
    tile.paste(b.crop((0, y, w, min(y + step, b.height))), (w + 20, 0))
    scale = min(1, 1600 / tile.width)
    tile.resize((int(tile.width * scale), int(tile.height * scale))).save(f'{d}/sbs-{n}.png'); n += 1
print(n, 'tranches')
