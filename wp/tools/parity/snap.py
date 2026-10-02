#!/usr/bin/env python3
"""Relevé rapide d'un site pour la mise en ligne : statut, title, H1, description, canonique, formulaires, rendu
(thème sidex ou Oxygen) et erreurs PHP visibles, pour chaque chemin des sitemaps (projets échantillonnés).
  snap.py <base> <sortie.json>                → relevé
  snap.py <base> <sortie.json> <avant.json> [rendu]  → + comparaison avec un relevé précédent (code 1 si écart) ;
                                                rendu attendu : sidex (défaut) ou oxygen (après un retour arrière)"""
import json, re, sys, concurrent.futures as cf, urllib.request, urllib.error

base, out = sys.argv[1].rstrip("/"), sys.argv[2]
before = json.load(open(sys.argv[3])) if len(sys.argv) > 3 else None
expect = sys.argv[4] if len(sys.argv) > 4 else "sidex"
UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36"
HOSTS = re.compile(r"https?://(?:www\.)?(?:groupesidex\.com|stg-groupesidexcom-staging\.kinsta\.cloud)")

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None
opener = urllib.request.build_opener(NoRedirect)

def get(u):
    try:
        r = opener.open(urllib.request.Request(u, headers={"User-Agent": UA}), timeout=120)
        return r.status, r.headers.get("Location") or "", r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get("Location") or "", e.read().decode("utf-8", "replace")
    except Exception as e:
        return 0, "", str(e)

def paths():
    if before:
        return sorted(before)
    ps = {"/", "/en/", "/sx-snap-404-test/", "/etapes/essence/"}
    sms = []
    for lang in ("", "/en"):
        _, _, idx = get(base + lang + "/sitemap_index.xml")
        sms += re.findall(r"<loc>([^<]+)", idx)
    for sm in sorted(set(sms)):
        _, _, x = get(sm)
        locs = [HOSTS.sub("", l) for l in re.findall(r"<loc>([^<]+)", x) if not re.search(r"\.(jpe?g|png|webp|gif|svg)$", l)]
        ps |= set(locs[::10] if "projets" in sm else locs)
    return sorted(ps)

def text(rx, h):
    m = re.search(rx, h, re.S | re.I)
    return re.sub(r"<[^>]+>|\s+", " ", m.group(1)).strip() if m else ""

def one(p):
    st, loc, h = get(base + p)
    r = {"status": st, "location": HOSTS.sub("", loc)}
    if st == 200:
        r.update({"title": text(r"<title>(.*?)</title>", h), "h1": text(r"<h1[^>]*>(.*?)</h1>", h),
                  "desc": text(r'<meta name="description" content="([^"]*)"', h), "canonical": HOSTS.sub("", text(r'<link rel="canonical" href="([^"]*)"', h)),
                  "forms": sorted(set(re.findall(r'data-form_id="(\d+)"', h))),
                  "render": "sidex" if re.search(r'<body[^>]*\bsx-t-\d+', h) else ("oxygen" if "uploads/oxygen/css" in h else "?"),
                  "php_error": bool(re.search(r"(Fatal error|Warning|Notice|Deprecated)</b>:", h))})
    return p, r

with cf.ThreadPoolExecutor(6) as ex:
    res = dict(ex.map(one, paths()))
json.dump(res, open(out, "w"), ensure_ascii=False, indent=1)
renders = {}
for r in res.values():
    renders[r.get("render", r["status"])] = renders.get(r.get("render", r["status"]), 0) + 1
print(f"{len(res)} chemins — rendus : {renders}")
if before:
    bad = 0
    for p, a in before.items():
        b = res.get(p, {})
        diffs = [f"{k} : {a.get(k)!r} → {b.get(k)!r}" for k in ("status", "location", "title", "h1", "desc", "canonical", "forms") if a.get(k) != b.get(k)]
        if b.get("php_error"): diffs.append("erreur PHP visible")
        if b.get("status") == 200 and a.get("render") in ("sidex", "oxygen") and b.get("render") != expect: diffs.append(f"rendu {b.get('render')}")
        if diffs:
            bad += 1
            print(f"  ✗ {p} — " + " | ".join(diffs)[:300])
    print(f"{len(before) - bad}/{len(before)} chemins sans écart")
    sys.exit(1 if bad else 0)
