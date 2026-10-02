#!/usr/bin/env python3
"""Compare chaque URL du staging (thème) avec la même URL sur le live (Oxygen) : statut, redirection,
title, description, robots, canonique, h1/h2, hreflang, types JSON-LD, texte visible, images, liens internes,
formulaires, traceurs. Univers d'URL = url-map.json ∪ sitemaps du live.
Usage : compare-live.py <dossier-sortie> [--threads N] [--limit N]"""
import json, re, sys, difflib, hashlib, concurrent.futures as cf, urllib.request, urllib.error
from pathlib import Path
from bs4 import BeautifulSoup
ROOT = Path(__file__).resolve().parents[3]
OUT = Path(sys.argv[1]); OUT.mkdir(parents=True, exist_ok=True); (OUT / "html").mkdir(exist_ok=True)
threads = int(sys.argv[sys.argv.index("--threads") + 1]) if "--threads" in sys.argv else 3
limit = int(sys.argv[sys.argv.index("--limit") + 1]) if "--limit" in sys.argv else 0
LIVE, STG = "https://www.groupesidex.com", "https://stg-groupesidexcom-staging.kinsta.cloud"
UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36"
HOSTS = re.compile(r"https?://(?:www\.)?(?:groupesidex\.com|stg-groupesidexcom-staging\.kinsta\.cloud)")

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None
opener = urllib.request.build_opener(NoRedirect)

def get(u, tries=2):
    for i in range(tries):
        try:
            r = opener.open(urllib.request.Request(u, headers={"User-Agent": UA}), timeout=120)
            return r.status, r.headers.get("Location"), r.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            return e.code, e.headers.get("Location"), e.read().decode("utf-8", "replace")
        except Exception as e:
            err = str(e)
    return 0, None, err

def paths():
    ps = {HOSTS.sub("", u["url"]) or "/" for u in json.loads((ROOT / "docs/inventaire/url-map.json").read_text())}
    st, _, idx = get(LIVE + "/sitemap_index.xml")
    for sm in re.findall(r"<loc>([^<]+)", idx):
        _, _, x = get(sm)
        ps |= {HOSTS.sub("", l) or "/" for l in re.findall(r"<loc>([^<]+)", x) if not re.search(r"\.(jpe?g|png|webp|gif|svg)$", l)}
    return sorted(p for p in ps if "?" not in p or p.startswith("/?s="))

TRACKERS = {"gtm": r"googletagmanager\.com/gtm|GTM-", "gtag": r"gtag/js", "crisp": r"crisp\.chat", "clarity": r"clarity\.ms",
            "facebook": r"connect\.facebook\.net|fbq\(", "pixelyoursite": r"pixelyoursite|pys-", "clickrank": r"clickrank", "searchable": r"searchable",
            "cleantalk": r"cleantalk", "recaptcha": r"recaptcha", "wp-rocket": r"wp-rocket|rocket-", "oxygen-assets": r"uploads/oxygen|plugins/oxygen"}

def extract(h):
    norm = HOSTS.sub("", h)
    s = BeautifulSoup(norm, "lxml")
    meta = lambda n, a="name": (s.find("meta", {a: n}) or {}).get("content", "")
    ld = []
    for sc in s.find_all("script", type="application/ld+json"):
        try:
            d = json.loads(sc.string or "{}")
            for n in (d.get("@graph", [d]) if isinstance(d, dict) else d):
                t = n.get("@type") if isinstance(n, dict) else None
                ld += t if isinstance(t, list) else [t] if t else []
        except Exception: ld.append("JSON-INVALIDE")
    track = sorted(k for k, rx in TRACKERS.items() if re.search(rx, h, re.I))
    forms = sorted(set(re.findall(r'data-form_id="(\d+)"', h)))
    for t in s(["script", "style", "noscript", "template", "svg"]): t.decompose()
    body = s.body or s
    imgs = sorted({re.sub(r"-\d+x\d+(?=\.)|-scaled(?=\.)|\.webp$", "", (i.get("data-lazy-src") or i.get("data-src") or (i.get("src") if not (i.get("src") or "").startswith("data:") else "") or "").split("?")[0].split("/")[-1]) for i in body.find_all("img")} - {""})
    links = sorted({a["href"].split("#")[0] for a in body.find_all("a", href=True) if a["href"].startswith("/") and not a["href"].startswith("//")})
    text = re.sub(r"\s+", " ", body.get_text(" ")).strip()
    canon = (s.find("link", rel="canonical") or {}).get("href", "")
    return {"title": (s.title.string or "").strip() if s.title else "", "desc": meta("description"), "robots": meta("robots"),
            "canonical": canon, "og_title": meta("og:title", "property"), "og_image": meta("og:image", "property").split("/")[-1],
            "h1": [x.get_text(" ", strip=True) for x in s.find_all("h1")], "h2": [x.get_text(" ", strip=True) for x in s.find_all("h2")],
            "hreflang": sorted(f'{l.get("hreflang")}={l.get("href")}' for l in s.find_all("link", rel="alternate", hreflang=True)),
            "ld": sorted(set(ld)), "trackers": track, "forms": forms, "imgs": imgs, "links": links, "text": text}

def one(p):

    a = get(LIVE + p); b = get(STG + p)
    r = {"path": p, "live": a[0], "stg": b[0], "live_loc": HOSTS.sub("", a[1] or ""), "stg_loc": HOSTS.sub("", b[1] or "")}
    diffs = []
    if a[0] != b[0]: diffs.append(f"statut live {a[0]} / staging {b[0]}")
    if a[0] in (301, 302) and r["live_loc"] != r["stg_loc"]: diffs.append(f"redirection live → {r['live_loc']} / staging → {r['stg_loc']}")
    if a[0] == 200 and b[0] == 200:
        key = hashlib.md5(p.encode()).hexdigest()[:10]
        (OUT / "html" / f"{key}-live.html").write_text(a[2]); (OUT / "html" / f"{key}-stg.html").write_text(b[2]); r["key"] = key
        x, y = extract(a[2]), extract(b[2])
        if re.search(r"(Fatal error|Warning|Notice|Deprecated)</b>:", b[2]): diffs.append("erreur PHP visible sur le staging")
        for k in ("title", "desc", "og_title", "og_image", "h1", "h2", "ld", "forms"):
            if x[k] != y[k]: diffs.append(f"{k} : live {x[k]!r} / staging {y[k]!r}"[:400])
        hl = lambda v: [re.sub(r"https?://[^/]+", "", z) for z in v]
        if hl(x["hreflang"]) != hl(y["hreflang"]): diffs.append(f"hreflang : live {x['hreflang']} / staging {y['hreflang']}"[:400])
        miss_img, extra_img = sorted(set(x["imgs"]) - set(y["imgs"])), sorted(set(y["imgs"]) - set(x["imgs"]))
        if miss_img or extra_img: diffs.append(f"images absentes du staging {miss_img[:8]} / en trop {extra_img[:8]}")
        miss_l, extra_l = sorted(set(x["links"]) - set(y["links"])), sorted(set(y["links"]) - set(x["links"]))
        if miss_l or extra_l: diffs.append(f"liens absents du staging {miss_l[:8]} / en trop {extra_l[:8]}")
        wa, wb = x["text"].split(), y["text"].split()
        sm = difflib.SequenceMatcher(None, wa, wb, autojunk=False)
        ratio = sm.ratio(); r["text_ratio"] = round(ratio, 4)
        if ratio < 0.999:
            ops = [(t, " ".join(wa[i1:i2])[:160], " ".join(wb[j1:j2])[:160]) for t, i1, i2, j1, j2 in sm.get_opcodes() if t != "equal"]
            diffs.append(f"texte {ratio:.3f} : " + " | ".join(f"{t}: «{l}» → «{s}»" for t, l, s in ops[:5]))
        r["live_trackers"], r["stg_trackers"] = x["trackers"], y["trackers"]
        r["stg_robots"], r["live_robots"] = y["robots"], x["robots"]
    r["diffs"] = diffs
    print(f"{len(diffs):2} {a[0]} {b[0]} {p}", flush=True)
    return r

ps = paths()
if limit: ps = ps[:limit]
print(f"{len(ps)} chemins", flush=True)
with cf.ThreadPoolExecutor(threads) as ex:
    res = list(ex.map(one, ps))
(OUT / "compare.json").write_text(json.dumps(res, ensure_ascii=False, indent=1))
print(f"FINI : {sum(1 for r in res if not r['diffs'])}/{len(res)} identiques")
