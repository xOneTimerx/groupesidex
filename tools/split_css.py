#!/usr/bin/env python3
"""Découpe app.css et site.css par gabarit : assets/css/t/{app,site}-<gabarit>.css.

Une règle est gardée si au moins un de ses sélecteurs peut viser la page : toutes ses classes et tous ses id
figurent dans l'ensemble du gabarit, soit
  - les classes et id relevés dans le HTML du site pour ce gabarit (tools/out/css-usage.json, tools/css_usage.py) ;
  - les mots des gabarits PHP qu'il inclut, avec l'en-tête/pied 69 (branches non rendues comprises) ;
  - les chaînes des scripts du thème (classes ajoutées par JavaScript : menus, onglets, AOS, Flickity…) ;
  - les préfixes d'état et d'extension de SAFE_PREFIXES.
Les sélecteurs sans classe ni id (éléments, attributs), @font-face, @keyframes et les arguments de :not()/:is()…
sont toujours gardés. Un gabarit sans relevé garde les feuilles complètes (inc/assets.php se rabat sur elles).
Ordre et contenu des règles gardées inchangés : la cascade reste celle d'Oxygen.

    python3 tools/split_css.py            (après bash tools/build-css.sh ou une modification de src/theme.css)
"""
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
THEME = ROOT / 'wp/themes/sidex'
USAGE = ROOT / 'tools/out/css-usage.json'
OUT = THEME / 'assets/css/t'
SHEETS = ['app', 'site']
# Classes posées par JavaScript, WordPress ou les extensions après le chargement (ou selon l'utilisateur).
SAFE_PREFIXES = ('is-', 'has-', 'active', 'open', 'show', 'hide', 'hidden', 'visible', 'focus', 'current', 'selected',
                 'aos', 'oxy-', 'ct-', 'oxygen-', 'mm-', 'mm_', 'flickity', 'pswp', 'glightbox', 'gslide', 'gclose', 'gnext', 'gprev',
                 'fancybox', 'ff', 'frm', 'fluent', 'wpgb', 'wpml', 'admin-bar', 'logged-in', 'lineChild', 'lineParent',
                 'swiper', 'splide', 'skeletabs', 'tocbot', 'toc-', 'inert', 'lazy', 'rocket', 'loaded', 'sticky', 'scrolled',
                 'menu-', 'sub-menu', 'page-', 'single', 'home', 'error404', 'search', 'wp-', 'aligncenter', 'alignleft', 'alignright')

TOKEN = re.compile(r'([.#])((?:\\.|[\w-])+)')
FUNC = re.compile(r':(?:not|is|where|has|nth-[a-z-]+|lang|dir)\(')


def unescape(name: str) -> str:
    return re.sub(r'\\(.)', r'\1', name)


def strip_functions(sel: str) -> str:
    """Retire les arguments de :not(), :is()… (gardés sans condition)."""
    out, i = [], 0
    while (m := FUNC.search(sel, i)):
        out.append(sel[i:m.end() - 1])
        depth, j = 1, m.end()
        while j < len(sel) and depth:
            depth += {'(': 1, ')': -1}.get(sel[j], 0)
            j += 1
        i = j
    out.append(sel[i:])
    return ''.join(out)


def split_selectors(text: str) -> list[str]:
    parts, depth, cur = [], 0, ''
    for ch in text:
        depth += {'(': 1, '[': 1, ')': -1, ']': -1}.get(ch, 0)
        if ch == ',' and depth == 0:
            parts.append(cur)
            cur = ''
        else:
            cur += ch
    parts.append(cur)
    return [p.strip() for p in parts if p.strip()]


def parse(css: str, i: int = 0, nested: bool = False) -> tuple[list, int]:
    """Blocs : ('rule', prélude, corps) | ('at', prélude, [enfants] | corps brut) | ('raw', texte)."""
    items, n = [], len(css)
    while i < n:
        while i < n and css[i] in ' \t\r\n':
            i += 1
        if i >= n:
            break
        if css.startswith('/*', i):
            j = css.find('*/', i + 2)
            i = n if j < 0 else j + 2
            continue
        if css[i] == '}':
            if nested:
                return items, i + 1
            i += 1
            continue
        j, quote, depth = i, None, 0
        while j < n:
            ch = css[j]
            if not quote and css.startswith('/*', j):
                j = css.find('*/', j + 2) % (n + 1) + 2
                continue
            if quote:
                if ch == '\\':
                    j += 1
                elif ch == quote:
                    quote = None
            elif ch in '"\'':
                quote = ch
            elif ch in '([':
                depth += 1
            elif ch in ')]':
                depth -= 1
            elif depth == 0 and ch in '{;':
                break
            j += 1
        prelude = css[i:j].strip()
        if j >= n or css[j] == ';':
            items.append(('raw', prelude + ';'))
            i = j + 1
            continue
        if prelude.startswith('@') and re.match(r'@(media|supports|layer|container|document)\b', prelude):
            children, i = parse(css, j + 1, True)
            items.append(('at', prelude, children))
            continue
        k, depth, quote = j + 1, 1, None
        while k < n and depth:
            ch = css[k]
            if not quote and css.startswith('/*', k):
                k = css.find('*/', k + 2) % (n + 1) + 2
                continue
            if quote:
                if ch == '\\':
                    k += 1
                elif ch == quote:
                    quote = None
            elif ch in '"\'':
                quote = ch
            elif ch == '{':
                depth += 1
            elif ch == '}':
                depth -= 1
            k += 1
        body = css[j + 1:k - 1]
        items.append(('at' if prelude.startswith('@') else 'rule', prelude, body))
        i = k
    return items, i


def keep_selector(sel: str, classes: set, ids: set) -> bool:
    for kind, name in TOKEN.findall(strip_functions(sel)):
        name = unescape(name)
        if kind == '.' and name not in classes and not name.startswith(SAFE_PREFIXES):
            return False
        if kind == '#' and name not in ids:
            return False
    return True


def emit(items: list, classes: set, ids: set) -> str:
    out = []
    for item in items:
        if item[0] == 'raw':
            out.append(item[1])
        elif item[0] == 'at':
            if isinstance(item[2], list):
                inner = emit(item[2], classes, ids)
                if inner:
                    out.append(item[1] + '{' + inner + '}')
            else:
                out.append(item[1] + '{' + item[2] + '}')
        else:
            kept = [s for s in split_selectors(item[1]) if keep_selector(s, classes, ids)]
            if kept:
                out.append(','.join(kept) + '{' + item[2].strip() + '}')
    return '\n'.join(out)


def words(text: str) -> set:
    return set(re.findall(r'[A-Za-z_][\w-]*', text))


def template_parts(tpl: int, seen: set) -> list[int]:
    if tpl in seen:
        return []
    seen.add(tpl)
    f = THEME / f'parts/oxy/{tpl}.php'
    ids = [tpl]
    if f.is_file():
        for part in re.findall(r'sx_oxy_part\((\d+)\)', f.read_text()):
            ids += template_parts(int(part), seen)
    return ids


def main() -> None:
    usage = json.loads(USAGE.read_text())['templates']
    js = set()
    for f in list((THEME / 'assets/js').glob('*.js')) + list((THEME / 'assets/vendor').glob('*.js')):
        for s in re.findall(r'"((?:\\.|[^"\\\n])*)"|\'((?:\\.|[^\'\\\n])*)\'|`([^`]*)`', f.read_text(errors='replace')):
            js |= words(s[0] or s[1] or s[2])
    php = words(''.join(f.read_text() for f in (THEME / 'inc').glob('*.php')))
    sheets = {name: parse((THEME / f'assets/{name}.css').read_text())[0] for name in SHEETS}
    OUT.mkdir(exist_ok=True)
    for old in OUT.glob('*.css'):
        old.unlink()
    for tpl, data in usage.items():
        parts = set()
        for part in template_parts(69, set()) + template_parts(int(tpl), set()):
            f = THEME / f'parts/oxy/{part}.php'
            if f.is_file():
                parts |= words(f.read_text())
        classes = set(data['classes']) | parts | js | php
        ids = set(data['ids']) | parts | php
        for name in SHEETS:
            src = (THEME / f'assets/{name}.css').read_text()
            css = f'/* {name}.css réduit au gabarit {tpl} par tools/split_css.py ({data["pages"]} pages relevées). */\n' \
                  + emit(sheets[name], classes, ids) + '\n'
            (OUT / f'{name}-{tpl}.css').write_text(css)
            print(f'{name}-{tpl}.css {len(css) // 1024} Ko (complet {len(src) // 1024} Ko)')


if __name__ == '__main__':
    main()
