#!/usr/bin/env python3
"""Arbre lisible d'un gabarit Oxygen (source/oxygen/templates/<id>.json).

Usage : python3 tools/oxytree.py <id> [--styles]

Une ligne par élément : type, nom, id de sélecteur, classes, texte, liaisons dynamiques
(get_field, acf_repeater, requêtes de repeater), conditions et, avec --styles, les styles
propres (original + media). Sert à lire un gabarit avant de le porter dans le thème.
"""

import json
import re
import sys
from pathlib import Path

import os
ROOT = Path(os.environ.get("SIDEX_SRC", Path.home() / "sidex/oxygen-debuild/source")) / "oxygen" / "templates"
SIGN = re.compile(r"\s*ct_sign_sha256='[0-9a-f]+'")
SKIP_KEYS = {"globalConditionsResult", "url_encoded", "srcdynamic", "custom-js", "selector"}


def clean(s):
    s = SIGN.sub("", str(s))
    s = re.sub(r"<span id=\"span-\d+-\d+\" class=\"ct-span\">(.*?)</span>", r"\1", s)
    return " ".join(s.split())


def walk(node, depth, styles, out):
    o = node.get("options", {})
    orig = o.get("original", {}) or {}
    name = node.get("name", "")
    bits = [f"{'  ' * depth}{name.replace('ct_', '').replace('oxy_', 'oxy:')}", f"#{o.get('selector', '')}"]
    if o.get("nicename") and not re.match(r"^[\w ]+\(#\d+\)$", o["nicename"]):
        bits.append(f"«{o['nicename']}»")
    if o.get("classes"):
        bits.append("." + ".".join(o["classes"]))
    tag = orig.get("tag")
    if tag:
        bits.append(f"<{tag}>")
    for key in ("url", "src", "alt", "code-php", "acf_repeater", "use_acf_repeater", "query_post_type", "wp_query",
                "query_args", "code-js", "code-css", "embed_src", "icon-id", "ct_content", "full_shortcode",
                "reusable", "view_id", "custom_query", "query_post_ids", "shortcode_tag"):
        val = orig.get(key, o.get(key))
        if val not in (None, "", [], {}):
            val = clean(val)
            if key in ("code-php", "code-js", "code-css"):
                val = val[:300]
            bits.append(f"{key}={val[:220]}")
    if o.get("ct_content"):
        bits.append(f"“{clean(o['ct_content'])[:160]}”")
    conds = orig.get("globalconditions") or o.get("conditions")
    if conds:
        bits.append(f"IF={clean(json.dumps(conds, ensure_ascii=False))[:200]}")
    out.append("  ".join(bits))
    if styles:
        st = {k: v for k, v in orig.items() if k not in SKIP_KEYS and not k.startswith(("code-", "url", "src", "alt", "tag"))}
        if st:
            out.append(f"{'  ' * depth}    · {clean(json.dumps(st, ensure_ascii=False))[:600]}")
        for m, mv in (o.get("media") or {}).items():
            out.append(f"{'  ' * depth}    @{m} {clean(json.dumps(mv.get('original', mv), ensure_ascii=False))[:400]}")
    for child in node.get("children", []) or []:
        walk(child, depth + 1, styles, out)


def main():
    tid = sys.argv[1]
    styles = "--styles" in sys.argv
    data = json.loads((ROOT / f"{tid}.json").read_text())
    out = []
    for child in data.get("children", []):
        walk(child, 0, styles, out)
    print("\n".join(out))


if __name__ == "__main__":
    main()
