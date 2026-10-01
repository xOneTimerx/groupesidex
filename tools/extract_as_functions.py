#!/usr/bin/env python3
"""Recopie telles quelles les fonctions PHP d'Advanced Scripts appelées par les gabarits, chacune protégée
par function_exists (Advanced Scripts actif = ses versions gagnent). Écrit wp/themes/sidex/inc/site.php.
Conversion unique, puis fichier entretenu à la main."""
import re
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent
AS = Path.home() / "sidex/oxygen-debuild/oxygen-source-2026-09-25"
WANT = {
    139: ["acf_link_subfield", "acf_link_field", "get_field_image_alt", "get_option_field_image_alt", "get_sub_field_image_alt",
          "get_alt_from_url", "get_copyright_text", "generate_text", "get_nb_posts", "get_translated_link",
          "get_entete_field", "get_entete_link", "get_acf_param_tax"],
    67: ["output_tableau", "output_tableau_header", "output_tableau_content_row", "get_field_link", "get_value_title",
         "output_tableau_content_row_column_img", "output_tableau_content_row_column_link", "output_tableau_content_row_column"],
    86: ["output_types_new", "output_image_couleur", "output_image_etape"],
    137: ["faq_get_etape_acf_query"], 146: ["output_section_class", "output_onglet_class"], 266: ["count_acf_repeater_rows"],
}

def strip_comments(code):
    return re.sub(r"/\*.*?\*/", lambda m: "\n" * m.group(0).count("\n"), code, flags=re.S)

def extract(code, name):
    m = re.search(r"^function\s+" + name + r"\s*\(", code, re.M)
    if not m:
        raise SystemExit(f"introuvable : {name}")
    i, depth, mode = code.index("{", m.end()), 0, "php"
    j = i
    # Compte les accolades en sautant les chaînes et le HTML hors PHP (?> … <?php).
    while j < len(code):
        if mode == "php":
            if code.startswith("?>", j):
                mode = "html"; j += 2; continue
            ch = code[j]
            if ch in "'\"":
                q, j = ch, j + 1
                while code[j] != q:
                    j += 2 if code[j] == "\\" else 1
            elif ch == "{":
                depth += 1
            elif ch == "}":
                depth -= 1
                if depth == 0:
                    return code[m.start():j + 1]
        elif code.startswith("<?php", j) or code.startswith("<?=", j):
            mode = "php"
        j += 1
    raise SystemExit(f"fin introuvable : {name}")

out = ["<?php", "/**", " * Fonctions d'Advanced Scripts appelées par les gabarits convertis (scripts 67, 86, 137, 139, 146, 266),",
       " * recopiées telles quelles par tools/extract_as_functions.py le 1er oct. 2026. Protégées par function_exists :",
       " * tant qu'Advanced Scripts est actif, ses versions identiques sont celles qui s'exécutent.", " */", "",
       "defined('ABSPATH') || exit;", ""]
for sid, names in WANT.items():
    code = strip_comments((AS / f"as-{sid}.txt").read_text())
    out.append(f"/* ── Advanced Scripts {sid} ── */")
    for n in names:
        body = extract(code, n)
        out += [f"if (!function_exists('{n}')) {{", body, "}", ""]
(ROOT / "wp/themes/sidex/inc/site.php").write_text("\n".join(out) + "\n")
print("ok", sum(len(v) for v in WANT.values()), "fonctions")
