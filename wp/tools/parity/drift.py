#!/usr/bin/env python3
"""Dérive des gabarits Oxygen du live depuis la conversion (lecture seule, HTTP public seulement).

Le thème sidex a été généré à partir des gabarits Oxygen relevés le 25 sept. 2026 ($SIDEX_SRC). Si quelqu'un a
modifié un gabarit dans Oxygen sur le live depuis, le thème ne le reflète pas. Oxygen régénère alors
uploads/oxygen/css/<id>.css : on relève, pour chaque URL des sitemaps du live, les feuilles Oxygen chargées, puis on
compare chacune à la copie source. Signale aussi les gabarits que le thème ne connaît pas.

  drift.py [base] [--sample N]     (base : https://www.groupesidex.com ; code 1 si dérive)
"""
import os, re, sys, concurrent.futures as cf, urllib.request, urllib.error

args = [a for a in sys.argv[1:] if not a.startswith("--")]
base = (args[0] if args else "https://www.groupesidex.com").rstrip("/")
sample = int(sys.argv[sys.argv.index("--sample") + 1]) if "--sample" in sys.argv else 0
SRC = os.environ.get("SIDEX_SRC", os.path.expanduser("~/sidex/oxygen-debuild/source"))
THEME = os.path.join(os.path.dirname(__file__), "../../themes/sidex")
UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36"
HOSTS = re.compile(r"(https?:)?//(stg-groupesidexcom-staging\.kinsta\.cloud|groupesidexcom\.kinsta\.cloud|(www\.)?groupesidex\.com)")


def get(u):
    try:
        with urllib.request.urlopen(urllib.request.Request(u, headers={"User-Agent": UA}), timeout=120) as r:
            return r.status, r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, ""
    except Exception:
        return 0, ""


def urls():
    sms = []
    for lang in ("", "/en"):
        sms += re.findall(r"<loc>([^<]+)", get(base + lang + "/sitemap_index.xml")[1])
    out = set()
    for sm in sorted(set(sms)):
        locs = [l for l in re.findall(r"<loc>([^<]+)", get(sm)[1]) if not re.search(r"\.(jpe?g|png|webp|gif|svg|kml)$", l)]
        out |= set(locs[::sample] if sample and "projets" in sm else locs)
    return sorted(out)


def sheets(u):
    _, h = get(u)
    return u, dict(re.findall(r"uploads/oxygen/css/(\w+)\.css\?cache=(\d+)", h))


def norm(css):
    return re.sub(r"\s+", " ", HOSTS.sub("", css)).strip()


pages = urls()
print(f"{len(pages)} URL lues sur {base}")
used, saved = {}, {}
with cf.ThreadPoolExecutor(6) as ex:
    for u, ids in ex.map(sheets, pages):
        for i, stamp in ids.items():
            used.setdefault(i, []).append(u)
            saved[i] = max(saved.get(i, 0), int(stamp))

drift = 0
for i in sorted(used, key=lambda x: (not x.isdigit(), int(x) if x.isdigit() else 0, x)):
    where = f"{len(used[i])} page(s), ex. {used[i][0].replace(base, '') or '/'}"
    src = os.path.join(SRC, "oxygen/css", i + ".css")
    if i.isdigit() and not os.path.isfile(os.path.join(THEME, "parts/oxy", i + ".php")):
        print(f"✗ gabarit {i} inconnu du thème — {where}")
        drift += 1
        continue
    if not os.path.isfile(src):
        print(f"? {i}.css absent des sources ({SRC}) — {where}")
        continue
    st, live = get(f"{base}/wp-content/uploads/oxygen/css/{i}.css")
    if st != 200:
        print(f"? {i}.css : HTTP {st}")
    elif norm(live) != norm(open(src, encoding="utf-8", errors="replace").read()):
        print(f"✗ {i}.css modifié sur le live depuis la conversion — {where}")
        drift += 1
    elif saved[i] > os.path.getmtime(src):
        # Oxygen régénère le CSS à chaque enregistrement : CSS identique mais gabarit réenregistré → structure ou texte à revoir.
        print(f"✗ {i} réenregistré dans Oxygen après la conversion (CSS identique) — {where}")
        drift += 1
    else:
        print(f"✓ {i} inchangé ({len(used[i])} page(s))")
print("aucune dérive" if not drift else f"{drift} dérive(s) : reconvertir ces gabarits avant la mise en ligne")
sys.exit(1 if drift else 0)
