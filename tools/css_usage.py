#!/usr/bin/env python3
"""Relevé des classes et id réellement présents dans le HTML du site, par gabarit du thème (lecture seule).

Lit le sitemap Rank Math, les versions anglaises (hreflang en-ca) et quelques URL hors sitemap (archives, 404),
puis regroupe les classes et id de chaque page selon la classe « sx-t-<gabarit> » que le thème pose sur <body>.
Sortie : tools/out/css-usage.json, lu par tools/split_css.py. À relancer après un ajout de contenu qui utilise de
nouvelles classes (ou d'un gabarit), puis `python3 tools/split_css.py`.

    python3 tools/css_usage.py [https://www.groupesidex.com]
"""
import gzip
import json
import re
import sys
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'https://www.groupesidex.com').rstrip('/')
OUT = Path(__file__).resolve().parent / 'out' / 'css-usage.json'
UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 sidex-css-usage'
EXTRA = ['/', '/en/', '/projets/', '/en/projects/', '/blogue/', '/en/blog/', '/page-introuvable-sidex-css/', '/en/page-not-found-sidex-css/',
         '/recherche/?s=bois', '/en/search/?s=wood', '/?s=bois']


def get(url: str) -> tuple[int, str]:
    req = urllib.request.Request(url, headers={'User-Agent': UA, 'Accept-Encoding': 'gzip'})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            body, status = r.read(), r.status
            enc = r.headers.get('Content-Encoding')
    except urllib.error.HTTPError as e:
        body, status, enc = e.read(), e.code, e.headers.get('Content-Encoding')
    if enc == 'gzip':
        body = gzip.decompress(body)
    return status, body.decode('utf-8', 'replace')


def sitemap_urls() -> list[str]:
    _, index = get(BASE + '/sitemap_index.xml')
    urls = []
    for sm in re.findall(r'<loc>([^<]+)</loc>', index):
        _, xml = get(sm)
        urls += [u for u in re.findall(r'<loc>([^<]+)</loc>', xml) if not u.endswith(('.kml', '.xml'))]
    return urls


def scan(url: str) -> dict | None:
    status, html = get(url)
    body = re.search(r'<body\b[^>]*class="([^"]*)"', html)
    tpl = re.search(r'\bsx-t-(\d+)\b', body.group(1)) if body else None
    if not tpl:
        return None
    classes, ids = set(), set()
    for attr in re.findall(r'\sclass\s*=\s*(?:"([^"]*)"|\'([^\']*)\')', html):
        classes.update((attr[0] or attr[1]).split())
    for attr in re.findall(r'\sid\s*=\s*(?:"([^"]*)"|\'([^\']*)\')', html):
        ids.add(attr[0] or attr[1])
    en = re.search(r'hreflang="en-ca" href="([^"]+)"', html)
    return {'url': url, 'status': status, 'tpl': tpl.group(1), 'classes': classes, 'ids': ids,
            'en': en.group(1) if en else None}


def main() -> None:
    urls = list(dict.fromkeys(sitemap_urls() + [BASE + p for p in EXTRA]))
    print(f'{len(urls)} URL', file=sys.stderr)
    with ThreadPoolExecutor(3) as pool:
        pages = [p for p in pool.map(scan, urls) if p]
        en = sorted({p['en'] for p in pages if p['en']} - set(urls))
        print(f'+ {len(en)} URL anglaises', file=sys.stderr)
        pages += [p for p in pool.map(scan, en) if p]
    usage: dict[str, dict] = {}
    for p in pages:
        t = usage.setdefault(p['tpl'], {'pages': 0, 'classes': set(), 'ids': set()})
        t['pages'] += 1
        t['classes'] |= p['classes']
        t['ids'] |= p['ids']
    OUT.parent.mkdir(exist_ok=True)
    OUT.write_text(json.dumps({'source': BASE, 'pages': len(pages), 'templates': {
        k: {'pages': v['pages'], 'classes': sorted(v['classes']), 'ids': sorted(v['ids'])} for k, v in sorted(usage.items(), key=lambda i: int(i[0]))
    }}, ensure_ascii=False, indent=0) + '\n')
    for k, v in sorted(usage.items(), key=lambda i: int(i[0])):
        print(f'gabarit {k}: {v["pages"]} pages, {len(v["classes"])} classes', file=sys.stderr)


if __name__ == '__main__':
    main()
