#!/usr/bin/env python3
# Extrait les règles CSS (avec leur @media) dont le sélecteur contient un motif.
#   python3 cssq.py fichier.css motif [motif...]
import re, sys
css = re.sub(r'/\*.*?\*/', '', open(sys.argv[1]).read(), flags=re.S)
pats = sys.argv[2:]
def walk(s, ctx):
    i = 0
    while i < len(s):
        j = s.find('{', i)
        if j == -1: break
        sel = s[i:j].strip()
        depth, k = 1, j + 1
        while depth and k < len(s):
            if s[k] == '{': depth += 1
            elif s[k] == '}': depth -= 1
            k += 1
        body = s[j+1:k-1]
        if sel.startswith('@media') or sel.startswith('@supports'):
            walk(body, sel)
        elif any(p in sel for p in pats):
            print(f"{ctx+' ▸ ' if ctx else ''}{sel} {{{re.sub(r'\s+', ' ', body).strip()}}}")
        i = k
walk(css, '')
