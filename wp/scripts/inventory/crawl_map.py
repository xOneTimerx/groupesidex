#!/usr/bin/env python3
"""Usage : crawl_map.py <dossier inventaire> [dossier HTML brut]. Parcourt urls.json et relève, pour chaque URL : statut, redirection, gabarits Oxygen appliqués
(fichiers uploads/oxygen/css/<slug>-<id>.css), formulaires Fluent, grilles WPGB. Écrit url-map.json."""
import json, re, sys, concurrent.futures as cf, urllib.request, urllib.error
from pathlib import Path
here = Path(sys.argv[1] if len(sys.argv) > 1 else ".")
RAW = Path(sys.argv[2]) if len(sys.argv) > 2 else None
if RAW: RAW.mkdir(parents=True, exist_ok=True)
urls = json.loads((here / "urls.json").read_text())
tpl_ids = {t["id"] for t in json.loads((here / "templates.json").read_text())}
UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36 TactikInventory"

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None
opener = urllib.request.build_opener(NoRedirect)

def get(u):
    req = urllib.request.Request(u, headers={"User-Agent": UA})
    try:
        r = opener.open(req, timeout=60); return r.status, r.headers.get("Location"), r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get("Location"), e.read().decode("utf-8", "replace")
    except Exception as e:
        return 0, None, str(e)

def one(x):
    st, loc, html = get(x["url"])
    css = re.findall(r"/uploads/oxygen/css/(?:[a-z0-9_-]+-)?(\d+)\.css", html)
    tpls = [int(i) for i in css if int(i) in tpl_ids]
    if RAW:
        (RAW / (x["key"] + ".html")).write_text(html)
    return {**x, "status": st, "location": loc, "templates": list(dict.fromkeys(tpls)),
            "forms": sorted(set(re.findall(r'data-form_id="(\d+)"', html))),
            "wpgb": sorted(set(re.findall(r'wpgb-grid-(\d+)', html))),
            "bytes": len(html), "h1": len(re.findall(r"<h1[\s>]", html))}

with cf.ThreadPoolExecutor(3) as ex:
    res = list(ex.map(one, urls))
(here / "url-map.json").write_text(json.dumps(res, ensure_ascii=False, indent=1))
print("done", len(res))
