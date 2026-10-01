#!/usr/bin/env python3
"""Fiche de mesures par élément, sur plusieurs largeurs, depuis les relevés survey.js.

Usage : python3 specs.py <motif-id-regex> survey-1440.json survey-768.json ...

Pour chaque élément dont l'id correspond au motif (premier de chaque id répété
« -1 »), affiche sa boîte et ses styles non triviaux à chaque largeur, en ne
répétant une valeur que lorsqu'elle change d'une largeur à l'autre. Sert à
écrire un gabarit à partir de valeurs mesurées plutôt que lues dans le CSS.
"""

import json
import re
import sys

TRIVIAL = {
    "rgb(0, 0, 0)", "none", "normal", "0px", "auto", "visible", "static", "rgba(0, 0, 0, 0)",
    "0px none rgb(0, 0, 0)", "fill", "row", "block", "left", "start", "stretch", "flex-start",
}
SKIP = {"font-family", "object-fit", "background-image"}


def load(path):
    data = json.load(open(path))
    width = re.search(r"(\d+)\.json$", path).group(1)
    rows = {}
    for row in data["rows"]:
        rid = row.get("id") or ""
        rid = re.sub(r"-1$", "", rid) if re.search(r"-\d+-\d+-1$", rid) else rid
        rows.setdefault(rid, row)
    return width, rows


def main():
    pattern = re.compile(sys.argv[1])
    surveys = [load(p) for p in sys.argv[2:]]
    ids = []
    for _, rows in surveys:
        for rid in rows:
            if rid and pattern.search(rid) and rid not in ids:
                ids.append(rid)
    for rid in ids:
        print(f"## {rid}")
        previous = {}
        for width, rows in surveys:
            row = rows.get(rid)
            if not row:
                print(f"   {width}: absent")
                continue
            styles = {k: v for k, v in row["s"].items() if k not in SKIP and v not in TRIVIAL}
            changed = {k: v for k, v in styles.items() if previous.get(k) != v}
            gone = [k for k in previous if k not in styles]
            box = f"[{row['x']},{row['y']} {row['w']}x{row['h']}]"
            text = (row.get("text") or "")[:40]
            line = f"   {width}: {box}"
            if changed:
                line += " " + "; ".join(f"{k}: {v}" for k, v in changed.items())
            if gone:
                line += " | retirés: " + ",".join(gone)
            if text and not previous:
                line += f"  « {text} »"
            print(line)
            previous = styles


if __name__ == "__main__":
    main()
