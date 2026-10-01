#!/usr/bin/env python3
"""Parcourt les URL de docs/inventaire/url-map.json sur le staging et vérifie, pour le thème :
statut HTTP (comparé à celui relevé sous Oxygen quand on l'a), gabarit rendu (classe body sx-t-<id>) = gabarit
attendu, aucune erreur PHP visible, aucun « TODO oxy2php », présence des balises hreflang et canonique.
Usage : check-urls.py [motif] [--threads N]. Écrit docs/inventaire/url-check.json."""
import json, re, sys, concurrent.futures as cf, urllib.request, urllib.error
from pathlib import Path
ROOT = Path(__file__).resolve().parents[3]
urls = json.loads((ROOT / "docs/inventaire/url-map.json").read_text())
pat = re.compile(next((a for a in sys.argv[1:] if not a.startswith("--") and not a.isdigit()), "."))
threads = int(sys.argv[sys.argv.index("--threads") + 1]) if "--threads" in sys.argv else 4
UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36"

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None
opener = urllib.request.build_opener(NoRedirect)

def get(u):
    try:
        r = opener.open(urllib.request.Request(u, headers={"User-Agent": UA}), timeout=90)
        return r.status, r.headers.get("Location"), r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get("Location"), e.read().decode("utf-8", "replace")
    except Exception as e:
        return 0, None, str(e)

def one(u):
    st, loc, h = get(u["url"])
    m = re.search(r'<body[^>]*class="[^"]*\bsx-t-(\d+)', h)
    got = int(m.group(1)) if m else None
    exp = u.get("template")
    problems = []
    if st not in (200, 404) and not (st in (301, 302) and (u["kind"] in ("tax:etapes", "search") or u["url"].endswith("/soumission/"))):
        problems.append(f"statut {st} {loc or ''}")
    if st in (200, 404) and exp and got != exp:
        problems.append(f"gabarit {got} au lieu de {exp}")
    for e in re.findall(r"(Fatal error|Warning|Notice|Deprecated)</b>:[^<]{0,160}", h)[:3]:
        problems.append("PHP " + e)
    if "TODO oxy2php" in h:
        problems.append("TODO oxy2php")
    # Rank Math n'imprime pas de canonique sur une page noindex (tout le staging l'est).
    if st == 200 and 'rel="canonical"' not in h and not re.search(r'<meta name="robots" content="[^"]*noindex', h):
        problems.append("canonique absente")
    if st == 200 and u["kind"] != "search" and 'hreflang=' not in h:
        problems.append("hreflang absent")
    return {**{k: u[k] for k in ("url", "kind", "lang", "template")}, "status": st, "got": got, "problems": problems}

todo = [u for u in urls if pat.search(u["url"]) or pat.search(u["kind"])]
with cf.ThreadPoolExecutor(threads) as ex:
    res = list(ex.map(one, todo))
(ROOT / "docs/inventaire/url-check.json").write_text(json.dumps(res, ensure_ascii=False, indent=1))
bad = [r for r in res if r["problems"]]
print(f"{len(res) - len(bad)}/{len(res)} sans problème")
for r in bad[:60]:
    print(f"  {r['status']} {r['url'][43:]:60} {' | '.join(r['problems'])[:160]}")
