#!/usr/bin/env python3
"""Carte URL -> gabarit Oxygen à partir des règles (types_page, CPT, archives). Écrit url-map.json + sample.json. Lecture seule."""
import json, collections
from pathlib import Path
d = Path(__file__).resolve().parents[3] / "docs" / "inventaire"
urls = json.loads((d / "urls.json").read_text())
pt = json.loads((d / "page-terms.json").read_text())
tpls = json.loads((d / "templates.json").read_text())
by_term = {}
for t in tpls:
    tax = t["rules"].get("taxonomies") or {}
    if "types_page" in (tax.get("names") or []):
        for v in tax["values"]:
            by_term[int(v)] = t["id"]
KIND = {"post": 75, "projets": 1584, "etapes": 1615, "faq": 54620, "merci": 73, "archive:post": 77, "tax:category": 77,
        "archive:projets": 1582, "search": 88, "404": 74, "author": 54802, "tax:etapes": None, "tax:post_tag": 77}
title = {t["id"]: t["title"] for t in tpls}
out = []
for u in urls:
    tid = None
    if u["kind"] == "page":
        terms = pt["pages"].get(str(u["id"]), [])
        tid = next((by_term[t] for t in terms if t in by_term), None)
        u["types_page"] = [pt["terms"].get(str(t), t) for t in terms]
    else:
        tid = KIND.get(u["kind"])
    u["template"] = tid
    u["template_title"] = title.get(tid, "— aucun (repli Oxygen/404 ?)")
    out.append(u)
(d / "url-map.json").write_text(json.dumps(out, ensure_ascii=False, indent=1))
c = collections.Counter((u["template"], u["template_title"]) for u in out)
for (tid, tt), n in sorted(c.items(), key=lambda x: -x[1]):
    print(f"{n:4} {tid} {tt}")
print("pages sans gabarit :", [(u["id"], u["url"], u.get("types_page")) for u in out if u["kind"] == "page" and not u["template"]])
# échantillon : 2 URL par (gabarit, langue)
seen = collections.defaultdict(list)
for u in out:
    k = (u["template"], u["lang"])
    if len(seen[k]) < 2 and u["kind"] not in ("search",):
        seen[k].append(u)
sample = [x for v in seen.values() for x in v]
(d / "sample.json").write_text(json.dumps(sample, ensure_ascii=False, indent=1))
print("échantillon :", len(sample))
