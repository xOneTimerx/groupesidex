#!/usr/bin/env python3
"""Relevé des images lourdes servies par le site (lecture seule, requêtes GET/HEAD publiques).

Pour chaque page : images JPEG/PNG du premier écran (de <body> à la fin de la 2e <section> de <main>, comme
inc/html.php) — <img> (src, srcset, data-*), fonds en style="background-image:url(…)" et data-sx-bg / data-bg —
et tous les GIF de la page. Poids lu par HEAD (Content-Length) sur le site.

    python3 wp/tools/images/scan.py https://www.groupesidex.com / /en/ /projets/        # pages données
    python3 wp/tools/images/scan.py https://www.groupesidex.com --sitemaps page,post     # pages des sitemaps Rank Math

Sortie TSV sur stdout : type (img|bg|gif) ⇥ octets ⇥ chemin dans uploads ⇥ page. Une ligne par couple fichier/page ;
une image retenue (plus gros candidat ≥ --min-kb) vient avec tous les candidats de son srcset.
"""

import argparse
import concurrent.futures as cf
import re
import sys
import urllib.parse
import urllib.request

UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36'
UPLOADS = '/wp-content/uploads/'
ATTRS = r'(?:src|srcset|data-src|data-srcset|data-lazy-src|data-lazy-srcset|data-bg|data-sx-bg|style|poster)'


def get(url, method='GET'):
    req = urllib.request.Request(url, method=method, headers={'User-Agent': UA, 'Cache-Control': 'no-cache'})
    with urllib.request.urlopen(req, timeout=60) as r:
        return r.read().decode('utf-8', 'replace') if method == 'GET' else int(r.headers.get('Content-Length') or 0)


def sitemap_paths(site, kinds):
    index = get(site + '/sitemap_index.xml')
    out = []
    for loc in re.findall(r'<loc>([^<]+)</loc>', index):
        name = loc.rsplit('/', 1)[-1]
        if any(re.fullmatch(re.escape(k) + r'-sitemap\d*\.xml', name) for k in kinds):
            out += [urllib.parse.urlsplit(u).path for u in re.findall(r'<loc>([^<]+)</loc>', get(loc))]
    return out


def first_screen_end(html):
    """Fin du premier écran : fin de la 2e <section> de <main> (sinon fin de <main>)."""
    main = html.find('<main')
    if main < 0:
        return len(html)
    end = html.find('</main>', main)
    end = end if end >= 0 else len(html)
    pos = html.find('<section', main)
    for _ in range(2):
        if pos < 0:
            break
        pos = html.find('</section>', pos + 1)
    return pos if 0 <= pos < end else end


def upload_paths(fragment):
    """Chemins /wp-content/uploads/… cités dans les attributs d'images d'un fragment HTML (variantes ramenées à
    l'original : x.jpg.webp → x.jpg, x.gif.mp4 → x.gif, pour relire une page qui les sert déjà)."""
    out = []
    for m in re.finditer(r'\s' + ATTRS + r'=("[^"]*"|\'[^\']*\')', fragment, re.I):
        for u in re.findall(r'(?:https?://[^/"\'\s,()]+)?' + re.escape(UPLOADS) + r'[^"\'\s,()?#]+', m.group(1)):
            p = urllib.parse.unquote(urllib.parse.urlsplit(u).path)
            out.append(re.sub(r'\.(jpe?g|png|gif)\.(webp|mp4|webm|poster\.webp)$', r'.\1', p, flags=re.I))
    return out


def elements(fragment):
    """(type, chemins) par balise : bg (fond en style ou data-bg), gif (GIF ou sa vidéo), img (le reste)."""
    for m in re.finditer(r'<(?:img|source|video|[a-z][a-z0-9]*(?=[^>]*(?:background-image|data-(?:sx-)?bg=)))\b[^>]*>', fragment, re.I):
        paths = upload_paths(m.group(0))
        if not paths:
            continue
        if any(p.lower().endswith('.gif') for p in paths):
            yield 'gif', [p for p in paths if p.lower().endswith('.gif')]
        else:
            kind = 'bg' if re.search(r'background-image|data-(?:sx-)?bg=', m.group(0), re.I) else 'img'
            yield kind, [p for p in paths if re.search(r'\.(jpe?g|png)$', p, re.I)]


def scan_page(site, path):
    html = get(site + path)
    body = max(html.find('<body'), 0)
    fold = first_screen_end(html)
    groups = [(k, tuple(dict.fromkeys(ps))) for k, ps in elements(html[body:fold]) if k != 'gif' and ps]
    groups += [(k, tuple(dict.fromkeys(ps))) for k, ps in elements(html[body:]) if k == 'gif']
    return path, set(groups)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('site')
    ap.add_argument('paths', nargs='*')
    ap.add_argument('--sitemaps', default='')
    ap.add_argument('--min-kb', type=int, default=100, help='seuil JPEG/PNG (les GIF sont tous gardés)')
    a = ap.parse_args()
    site = a.site.rstrip('/')
    paths = list(dict.fromkeys(a.paths + (sitemap_paths(site, a.sitemaps.split(',')) if a.sitemaps else [])))
    found = []
    with cf.ThreadPoolExecutor(6) as ex:
        for fut in cf.as_completed([ex.submit(scan_page, site, p) for p in paths]):
            try:
                found.append(fut.result())
            except Exception as e:  # page en erreur : signalée, le relevé continue
                print(f'! {e}', file=sys.stderr)
    files = sorted({p for _, groups in found for _, ps in groups for p in ps})
    with cf.ThreadPoolExecutor(8) as ex:
        sizes = dict(zip(files, ex.map(lambda p: _size(site, p), files)))
    for page, groups in sorted(found):
        rows = set()
        for kind, ps in groups:
            # Élément retenu si son plus gros candidat dépasse le seuil : tous ses candidats (srcset) suivent.
            if kind == 'gif' or max(sizes[p] for p in ps) >= a.min_kb * 1024:
                rows |= {(kind, p) for p in ps}
        for kind, p in sorted(rows):
            print(f'{kind}\t{sizes[p]}\t{p[len(UPLOADS):]}\t{page}')


def _size(site, path):
    try:
        return get(site + urllib.parse.quote(path), 'HEAD')
    except Exception:
        return 0


if __name__ == '__main__':
    main()
