#!/usr/bin/env python3
"""Options complètes d'éléments d'un gabarit : python3 tools/oxynode.py <gabarit> <ct_id|motif-de-nom>..."""
import json, re, sys
from pathlib import Path
import os
data = json.loads((Path(os.environ.get("SIDEX_SRC", Path.home() / "sidex/oxygen-debuild/source")) / "oxygen/templates" / f"{sys.argv[1]}.json").read_text())
wanted = sys.argv[2:]
SIGN = re.compile(r"\s*ct_sign_sha256='[0-9a-f]+'")
def walk(n):
    o = n.get("options", {})
    if any(str(n.get("id")) == w or re.search(w, o.get("nicename", "") + " " + n.get("name", ""), re.I) for w in wanted):
        opts = {k: v for k, v in o.items() if k not in ("ct_id", "ct_parent", "ct_depth", "activeselector")}
        print(f"== {n.get('name')} #{n.get('id')}")
        print(SIGN.sub("", json.dumps(opts, ensure_ascii=False, indent=1)))
    for c in n.get("children", []) or []:
        walk(c)
for c in data.get("children", []):
    walk(c)
