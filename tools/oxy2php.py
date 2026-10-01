#!/usr/bin/env python3
"""Convertit un gabarit Oxygen (source/oxygen/templates/<id>.json) en gabarit PHP du thème.

Usage : python3 tools/oxy2php.py <id> [<id>...]   (ou --all)

Sorties (thème wp/themes/sidex) :
  parts/oxy/<id>.php     balisage identique à celui qu'Oxygen imprimait (mêmes id/classes), liaisons
                         dynamiques traduites en PHP (get_field, boucles WP_Query / répéteurs ACF, …)
  assets/css/o-<id>.css  CSS compilé d'Oxygen pour ce gabarit + CSS des blocs de code
  assets/js/o-<id>.js    JS des blocs de code, dans l'ordre du gabarit

Conversion unique : les fichiers produits sont ensuite versionnés et retouchés à la main dans le thème ;
ils ne dépendent d'aucune extension Oxygen. Les éléments non gérés sont signalés par « TODO oxy2php ».
"""

import json
import re
import sys
from pathlib import Path

import os
ROOT = Path(__file__).resolve().parent.parent
# Sources Oxygen exportées du staging : hors dépôt (sources du site client).
SRCROOT = Path(os.environ.get("SIDEX_SRC", Path.home() / "sidex/oxygen-debuild/source"))
SRC = SRCROOT / "oxygen"
REF = Path(os.environ.get("SIDEX_REF", Path.home() / "sidex/oxygen-debuild/raw-2026-09-25"))
THEME = ROOT / "wp" / "themes" / "sidex"
SIGN = re.compile(r"\s*ct_sign_sha256='[0-9a-f]+'")
SHORTCODE = re.compile(r"\[oxygen\s+([^\]]*)\]")
ATTR = re.compile(r"(\w+)='([^']*)'")

EXPORT = json.loads((SRC / "export.json").read_text())
ICON_WIDTHS = {}


def esc(s):
    return s.replace("\\", "\\\\").replace("'", "\\'")


def php_str(s):
    return "'" + esc(s) + "'"


class Ctx:
    def __init__(self, tid):
        self.tid = tid
        self.css = []
        self.js = []
        self.todo = []
        self.icons = set()
        self.loop_depth = 0  # > 0 dans une liste dynamique (ids suffixés, data-id)
        self.repeater = []  # pile de clés de répéteur ACF


def dyn(expr, ctx, mode="html"):
    """Traduit une valeur pouvant contenir des shortcodes [oxygen …] en fragment PHP écho."""
    expr = SIGN.sub("", str(expr)).replace("\\'", "'")

    def repl(m):
        a = dict(ATTR.findall(m.group(1)))
        data = a.get("data")
        if data == "phpfunction":
            fn = a.get("function", "")
            args = [x for x in a.get("arguments", "").split(",")] if a.get("arguments") else []
            call = f"sx_fn({php_str(fn)}{''.join(', ' + php_str(x) for x in args)})"
        elif data == "permalink":
            call = "get_permalink()"
        elif data == "featured_image":
            call = "(string) get_the_post_thumbnail_url(null, 'full')"
        elif data == "acfreparray":
            call = f"sx_sub({php_str(a.get('field', ''))})"
        elif data == "custom_acf_content":
            call = f"sx_acf_content({php_str(a.get('settings_path', ''))}, {php_str(a.get('insert_type', ''))}, {php_str(a.get('separator', ''))}, {php_str(a.get('settings_page', ''))})"
        elif data == "date":
            call = f"get_the_date({php_str(a.get('format', ''))})"
        elif data == "title":
            call = "get_the_title()"
        elif data == "featured_image_id":
            call = "(string) get_post_thumbnail_id()"
        elif data == "id":
            call = "(string) get_the_ID()"
        elif data == "excerpt":
            call = "get_the_excerpt()"
        elif data == "author":
            call = ("get_author_posts_url((int) get_the_author_meta('ID'))" if a.get("link") == "author_posts_url"
                    else "get_the_author()")
        elif data == "content":
            call = "apply_filters('the_content', get_the_content())"
        else:
            ctx.todo.append(f"shortcode {m.group(0)}")
            return f"<?php /* TODO oxy2php {esc(m.group(0))} */ ?>"
        wrap = {"html": "sx_kses", "attr": "esc_attr", "url": "esc_url"}[mode]
        return f"<?php echo {wrap}({call}); ?>"

    return SHORTCODE.sub(repl, expr)


def dyn_expr(expr, ctx):
    """Valeur [oxygen …] → expression PHP (chaîne), sans écho."""
    frag = dyn(expr, ctx, "html")
    m = re.fullmatch(r"<\?php echo sx_kses\((.*)\); \?>", frag.strip(), re.S)
    return m.group(1) if m else php_str(frag)


def text(s):
    """Texte statique Oxygen (HTML permis)."""
    return s


def elem_id(sel, ctx):
    if ctx.loop_depth:
        return f' id="{sel}-<?php echo (int) $sx_i{ctx.loop_depth}; ?>"'
    return f' id="{sel}"'


def data_id(sel, ctx):
    return f' data-id="{sel}"' if ctx.loop_depth else ""


def class_attr(base, o):
    classes = [c for c in ([base] if base else []) + list(o.get("classes") or []) if c]
    return f' class="{" ".join(classes)}"' if classes else ""


def custom_attrs(orig, ctx):
    out = ""
    for a in orig.get("custom-attributes") or []:
        if a.get("name"):
            out += f' {a["name"]}="{dyn(a.get("value", ""), ctx, "attr")}"'
    return out


def aos_attrs(orig):
    if orig.get("aos-enable") != "true":
        return ""
    out = ""
    for k in ("type", "duration", "easing", "offset", "delay", "anchor-placement", "once", "mirror"):
        v = orig.get(f"aos-{k}")
        if v:
            out += f' data-aos{"" if k == "type" else "-" + k}="{v}"'
    return out


DYN_OPS = ["==", ">=", "<=", "contains", "is_blank", "is_not_blank", "!=", ">", "<", "does_not_contain"]


def conditions(orig, ctx):
    """Conditions d'affichage Oxygen → expression PHP (sémantique relue dans oxygen/includes/conditions.php
    et Advanced Scripts 18 « Conditions d'affichage »)."""
    conds = orig.get("globalconditions")
    if not conds:
        return None
    parts = []
    for c in conds:
        name = c.get("name")
        val = str(c.get("value", ""))
        op = c.get("operator", 0)
        if name == "ZZOXYVSBDYNAMIC":
            parts.append(f"sx_compare({dyn_expr(c.get('oxycode', ''), ctx)}, {php_str(DYN_OPS[int(op)])}, {php_str(val)})")
        elif name == "Champ ACF Vide?":
            parts.append(f"(bool) get_field({php_str(val)})")
        elif name == "Champ ACF Vide? (Option)":
            parts.append(f"(bool) get_field({php_str(val)}, 'option')")
        elif name == "Champ ACF Vide? (Champ dans un repeater ACF)":
            parts.append(f"(bool) get_sub_field({php_str(val)})")
        elif name == "Post Type vide?":
            # opérateurs [true, false] : true = « vide » ; false = « pas vide »
            cmp = "=== 0" if int(op) == 0 else "> 0"
            parts.append(f"(int) wp_count_posts({php_str(val)})->publish {cmp}")
        elif name == "Champ True / False":
            parts.append(("" if int(op) == 0 else "!") + f"(bool) get_field({php_str(val)})")
        elif name == "Champ True / False (Champ dans un repeater ACF)":
            parts.append(("" if int(op) == 0 else "!") + f"(bool) get_sub_field({php_str(val)})")
        elif name == "Champ True / False (Options)":
            parts.append(("" if int(op) == 0 else "!") + f"(bool) get_field({php_str(val)}, 'option')")
        elif name == "WPML":
            parts.append(f"(sx_lang() {'==' if int(op) == 0 else '!='} {php_str(val)})")
        elif name == "Pour le nième élément":
            parts.append(f"(sx_loop_index() {'===' if int(op) == 0 else '!=='} {int(val or 0)})")
        elif name == "À chaque n éléments":
            parts.append(f"({int(val or 0)} > 0 && (sx_loop_index() % {int(val or 1)} {'===' if int(op) == 0 else '!=='} 0))")
        elif not name:
            parts.append("true")  # condition sans nom : ignorée par Oxygen (élément affiché, vérifié sur À propos)
        else:
            ctx.todo.append(f"condition inconnue {json.dumps(c, ensure_ascii=False)[:200]}")
            parts.append("true")
    joiner = " || " if orig.get("conditionstype") == "or" else " && "
    return joiner.join(parts)


def render_children(node, ctx):
    return "".join(render(c, ctx) for c in node.get("children") or [])


def with_placeholders(content, node, ctx):
    """ct_content contient des <span id="ct-placeholder-N"></span> à remplacer par les enfants N."""
    content = SIGN.sub("", content or "")
    kids = {str(c.get("id")): c for c in node.get("children") or []}

    def repl(m):
        c = kids.pop(m.group(1), None)
        return render(c, ctx) if c else ""

    out = re.sub(r'<span id="ct-placeholder-(\d+)"></span>', repl, content)
    return dyn(out, ctx)


def render(node, ctx):
    name = node.get("name", "")
    o = node.get("options", {}) or {}
    orig = o.get("original", {}) or {}
    sel = o.get("selector", "")
    nick = o.get("nicename", "")
    ident = elem_id(sel, ctx) + data_id(sel, ctx)
    extra = custom_attrs(orig, ctx) + aos_attrs(orig)
    cond = conditions(orig, ctx)
    html = render_node(name, node, o, orig, sel, ident, extra, ctx)
    if nick and not re.match(r"^[\w ]+\(#\d+\)$", nick) and name in ("ct_section", "ct_div_block", "oxy_dynamic_list", "ct_reusable"):
        html = f"<?php /* {esc(nick).replace('*/', '')} */ ?>" + html
    if cond:
        html = f"<?php if ({cond}) : ?>{html}<?php endif; ?>"
    return html


def tag_of(orig, default):
    return orig.get("tag") or default


def render_node(name, node, o, orig, sel, ident, extra, ctx):
    kids = lambda: render_children(node, ctx)  # noqa: E731

    if name == "ct_section":
        tag = tag_of(orig, "section")
        video = orig.get("video_background")
        cls = class_attr("ct-section", o) if not video else class_attr("oxy-video-background ct-section", o)
        pre = ""
        if video:
            pre = ("<div class='oxy-video-container'><video autoplay loop playsinline muted>"
                   f"<source src='{dyn(video, ctx, 'url')}'></video><div class='oxy-video-overlay'></div></div>")
        return f'<{tag}{ident}{cls}{extra}>{pre}<div class="ct-section-inner-wrap">{kids()}</div></{tag}>'

    if name in ("ct_div_block", "ct_new_columns"):
        tag = tag_of(orig, "div")
        base = "ct-div-block" if name == "ct_div_block" else "ct-new-columns"
        style = bg_style(orig, ctx)
        return f"<{tag}{ident}{class_attr(base, o)}{style}{extra}>{kids()}</{tag}>"

    if name == "ct_code_block":
        tag = tag_of(orig, "div")
        if orig.get("code-css"):
            ctx.css.append(SIGN.sub("", orig["code-css"]).replace("%%ELEMENT_ID%%", sel))
        if orig.get("code-js"):
            ctx.js.append(f"/* {sel} */\n" + SIGN.sub("", orig["code-js"]).replace("%%ELEMENT_ID%%", sel))
        code = SIGN.sub("", orig.get("code-php", "") or "")
        if orig.get("unwrap") == "true":
            return code  # option « unwrap » d'Oxygen : le contenu seul, sans l'enveloppe ct-code-block
        return f"<{tag}{ident}{class_attr('ct-code-block', o)}{extra}>{code}</{tag}>"

    if name == "ct_text_block":
        tag = tag_of(orig, "div")
        return f"<{tag}{ident}{class_attr('ct-text-block', o)}{extra}>{with_placeholders(o.get('ct_content', ''), node, ctx)}</{tag}>"

    if name == "ct_headline":
        tag = tag_of(orig, "h1")
        return f"<{tag}{ident}{class_attr('ct-headline', o)}{extra}>{with_placeholders(o.get('ct_content', ''), node, ctx)}</{tag}>"

    if name == "ct_span":
        return f"<span{ident}{class_attr('ct-span', o)}{extra}>{with_placeholders(o.get('ct_content', ''), node, ctx)}</span>"

    if name in ("ct_link_text", "ct_link", "ct_link_button"):
        base = {"ct_link_text": "ct-link-text", "ct_link": "ct-link", "ct_link_button": "ct-link-button"}[name]
        href = dyn(orig.get("url", ""), ctx, "attr")
        target = orig.get("target", "_self") or "_self"
        role = ' role="button"' if name == "ct_link_button" else ""
        style = bg_style(orig, ctx) if name == "ct_link" else ""
        inner = kids() if name == "ct_link" else with_placeholders(o.get("ct_content", ""), node, ctx)
        rel = ' rel="noopener"' if target == "_blank" else ""
        return f'<a{ident}{class_attr(base, o)} href="{href}" target="{target}"{rel}{role}{style}{extra}>{inner}</a>'

    if name == "ct_image":
        return render_image(o, orig, ident, extra, ctx)

    if name == "ct_fancy_icon":
        icon = orig.get("icon-id", "")
        ctx.icons.add(icon)
        return (f"<div{ident}{class_attr('ct-fancy-icon', o)}{extra}><svg id=\"svg-{sel}\"><use xlink:href=\"#{icon}\"></use></svg></div>")

    if name == "ct_reusable":
        vid = str(orig.get("view_id") or o.get("view_id") or "")
        return f"<?php sx_oxy_part({vid}); ?>"

    if name == "ct_inner_content":
        call = "sx_page_template()" if ctx.tid == "69" else "sx_inner_content()"
        return f'<div{ident}{class_attr("ct-inner-content", o)}><?php {call}; ?></div>'

    if name == "oxy_dynamic_list":
        return render_dynamic_list(node, o, orig, sel, ident, extra, ctx)

    if name == "oxy-fluent-form":
        if orig.get("oxy-fluent-form_form_source") == "acf":
            form = f"(int) {dyn_expr(orig.get('oxy-fluent-form_acf_form_field', ''), ctx)}"
        else:
            form = str(int(orig.get("oxy-fluent-form_form_id") or 0))
        return f'<div{ident}{class_attr("oxy-fluent-form", o)}{extra}><?php sx_fluent_form({form}); ?></div>'

    if name == "oxy-carousel-builder":
        opening = ref_opening(sel, ctx) or f'<div{ident}{class_attr("oxy-carousel-builder", o)}><div class="oxy-carousel-builder_inner oxy-inner-content">'
        opening = re.sub(r' id="' + re.escape(sel) + r'(?:-\d+)?"( data-id="[^"]*")?', lambda m: ident, opening, count=1)
        if orig.get("oxy-carousel-builder_carousel_type") == "acf_gallery":
            inner = (f"<?php sx_carousel_gallery({php_str(sel)}, {php_str(orig.get('oxy-carousel-builder_acf_field_name', ''))}, "
                     f"{php_str(orig.get('oxy-carousel-builder_gallery_image_size', 'large'))}, {'$sx_i' + str(ctx.loop_depth) if ctx.loop_depth else '0'}); ?>")
            return f"{opening}{inner}</div></div>"
        return f"{opening}{kids()}</div></div>"

    if name == "oxy_gallery":
        css = ref_gallery_css(sel)
        if css:
            ctx.css.append(css)
        opts = {k: orig.get(k) for k in ("gallery_source", "acf_field", "link", "gallery_thumbnail_size", "images", "image_ids") if orig.get(k)}
        cls = " ".join(o.get("classes") or [])
        layout = "masonry" if orig.get("layout") == "masonry" or orig.get("masonry") == "true" else "grid"
        return f"<?php sx_gallery({php_str(sel)}, {php_str(cls)}, {php_str(layout)}, {php_str(json.dumps(opts, ensure_ascii=False))}); ?>"

    if name == "oxy-pro-accordion":
        return (f"<?php sx_accordion({php_str(sel)}, {php_str(' '.join(o.get('classes') or []))}, "
                f"{php_str(orig.get('oxy-pro-accordion_repeater_field', ''))}, {php_str(orig.get('oxy-pro-accordion_title_field', ''))}, "
                f"{php_str(orig.get('oxy-pro-accordion_content_field', ''))}, {php_str(orig.get('oxy-pro-accordion_toggle_icon', ''))}); ?>")

    if name == "ct_modal":
        backdrop = ref_backdrop(sel, ctx) or '<div tabindex="-1" class="oxy-modal-backdrop">'
        return f"{backdrop}<div{ident}{class_attr('ct-modal', o)}{extra}>{kids()}</div></div>"

    if name == "oxy-counter":
        opening = ref_counter(sel, ctx)
        return opening or f"<?php /* TODO oxy2php counter {sel} */ ?>"

    if name == "oxy-wpgb-facet":
        facet = orig.get("oxy-wpgb-facet_facet") or orig.get("facet") or ""
        ctx.todo.append(f"facette WPGB {sel} {facet} (grille à relier)")
        return f'<div{ident}{class_attr("oxy-wpgb-facet", o)}{extra}><?php sx_wpgb_facet({php_str(str(facet))}); ?></div>'

    if name == "oxy_header":
        open_ = ref_open_tag(sel) or f'<header{ident}{class_attr("oxy-header-wrapper oxy-header", o)}>'
        return f"{open_}{kids()}</header>"

    if name == "oxy_header_row":
        open_ = ref_open_tag(sel) or f'<div{ident}{class_attr("oxy-header-row", o)}>'
        return f'{open_}<div class="oxy-header-container">{kids()}</div></div>'

    if name in ("oxy_header_left", "oxy_header_center", "oxy_header_right"):
        open_ = ref_open_tag(sel) or f'<div{ident} class="{name.replace("_", "-")}">'
        return f"{open_}{kids()}</div>"

    if name in ("oxy-pro-menu", "oxy_nav_menu", "oxy-horizontal-slide-menu"):
        return ref_menu(name, sel, o, orig, ctx)

    if name == "oxy_superbox":
        return f'<div{ident}{class_attr("oxy-superbox", o)}{extra}><div class="oxy-superbox-wrap">{kids()}</div></div>'

    if name == "oxy_toggle":
        open_ = ref_open_tag(sel) or f'<div{ident}{class_attr("oxy-toggle", o)}>'
        return (f"{open_}<div class='oxy-expand-collapse-icon' href='#'></div>"
                f"<div class='oxy-toggle-content'>{kids()}</div></div>")

    if name in ("oxy_tabs", "oxy-table-of-contents"):
        html = ref_element(sel)  # élément vide côté serveur, rempli par le JS
        return portable(html) if html else f"<?php /* TODO oxy2php {name} {sel} */ ?>"

    if name == "oxy-dynamic-tabs":
        html = ref_element(sel) or ""
        m = re.search(r'^(.*?<div class="oxy-dynamic-tabs_inner[^>]*>)', html, re.S)
        opening = m.group(1) if m else f'<div{ident}{class_attr("oxy-dynamic-tabs", o)}><div class="oxy-dynamic-tabs_inner">'
        return (f"{opening}<?php sx_dynamic_tabs({php_str(sel)}, {php_str(orig.get('oxy-dynamic-tabs_repeater_field', ''))}, "
                f"{php_str(orig.get('oxy-dynamic-tabs_tab_field', ''))}, {php_str(orig.get('oxy-dynamic-tabs_tab_content_field', ''))}); ?></div></div>")

    if name == "oxy-lightbox":
        html = ref_element(sel) or ref_element(sel + "-1-1") or ""
        m = re.search(r'(<div class="oxy-lightbox_inner oxy-inner-content"[^>]*>)', html)
        inner = m.group(1) if m else '<div class="oxy-lightbox_inner oxy-inner-content">'
        kid = (node.get("children") or [{}])[0]
        src_expr = dyn_expr((kid.get("options", {}).get("original", {}) or {}).get("src", ""), ctx)
        inner = re.sub(r'data-src="[^"]*"', lambda _m: 'data-src="<?php echo esc_url(' + src_expr + '); ?>"', inner, count=1)
        link_id = "link" + sel.replace("-lightbox", "-lightbox", 1)
        return (f'<div{ident}{class_attr("oxy-lightbox woocommerce", o)}{extra}>'
                f'<div{elem_id("link" + sel, ctx)}{data_id("link" + sel, ctx)} class="oxy-lightbox_link ">{kids()}</div>{inner}</div></div>')

    if name == "oxy-wpgb-grid":
        grid = orig.get("oxy-wpgb-grid_grid") or orig.get("grid") or "2"
        return f'<div{ident}{class_attr("oxy-wpgb-grid", o)}{extra}><?php sx_wpgb_grid({int(grid)}); ?></div>'

    if name == "oxy-reading-time":
        before = dyn_expr(orig.get("oxy-reading-time_before", ""), ctx)
        after = php_str(orig.get('oxy-reading-time_text_after_singular', 'minute')) + ', ' + php_str(orig.get('oxy-reading-time_text_after_plural', 'minutes'))
        return f'<div{ident}{class_attr("oxy-reading-time", o)} ><?php echo sx_reading_time({before}, {after}); ?></div>'

    if name == "oxy-post-modified-date":
        fmt = orig.get("oxy-post-modified-date_date_format", "")
        return (f'<span{ident}{class_attr("oxy-post-modified-date", o)} >{orig.get("oxy-post-modified-date_date_before", "")}'
                f' <?php echo esc_html(get_the_modified_date({php_str(fmt)})); ?></span>')

    if name == "oxy-off-canvas":
        html = ref_element(sel)
        m = re.search(r'^(.*?<div id="' + re.escape(sel) + r'-inner"[^>]*>)', html or "", re.S)
        if not m:
            ctx.todo.append(f"off-canvas {sel} introuvable dans la référence")
            return f"<?php /* TODO oxy2php off-canvas {sel} */ ?>"
        return f"{portable(m.group(1))}{kids()}</div></div>"

    ctx.todo.append(f"élément {name} {sel}")
    return f"<?php /* TODO oxy2php {name} {sel} */ ?><div{ident}{class_attr(name.replace('_', '-'), o)}{extra}>{kids()}</div>"


def bg_style(orig, ctx):
    bg = orig.get("background-image")
    if not bg or not orig.get("background-imagedynamic"):
        return ""
    url = dyn(bg, ctx, "url")
    overlay = orig.get("overlay-color")
    if overlay:
        return f' style="background-image:linear-gradient({overlay}, {overlay}), url({url});background-size:auto,  {orig.get("background-size", "auto")};"'
    size = orig.get("background-size")
    return f' style="background-image:url({url});' + (f'background-size: {size};' if size else '') + '"'


def render_image(o, orig, ident, extra, ctx):
    alt = dyn(orig.get("alt", ""), ctx, "attr")
    if orig.get("image_type") == "2" and orig.get("attachment_id"):
        aid = orig["attachment_id"]
        aid_php = f"(int) {dyn_expr(aid, ctx)}" if "[oxygen" in str(aid) else str(int(aid))
        size = orig.get("attachment_size", "full")
        cls = " ".join(["ct-image"] + list(o.get("classes") or []))
        return (f"<?php echo sx_img({aid_php}, {php_str(size)}, {php_str(cls)}, "
                f"{php_str(ident.strip())}, {php_str(orig.get('alt', ''))}); ?>")
    src = dyn(orig.get("src", ""), ctx, "url")
    return f'<img{ident} alt="{alt}" src="{src}"{class_attr("ct-image", o)}{extra}/>'


def render_dynamic_list(node, o, orig, sel, ident, extra, ctx):
    ctx.loop_depth += 1
    d = ctx.loop_depth
    inner = render_children(node, ctx)
    ctx.loop_depth -= 1
    head = f'<div{ident}{class_attr("oxy-dynamic-list", o)}{extra}>'
    if orig.get("use_acf_repeater") == "true":
        key = orig.get("acf_repeater", "")
        owner = "sx_row_owner(" + php_str(key) + ")"
        return (f"{head}<?php $sx_i{d} = 0; if (have_rows({php_str(key)}, {owner})) : while (have_rows({php_str(key)}, {owner})) : the_row(); $sx_i{d}++; ?>"
                f"{inner}<?php endwhile; endif; ?></div>")
    mode = orig.get("wp_query", "default")
    if mode == "default":
        paginate = " sx_pagination();" if "paginate_size" in orig else ""
        return (f"{head}<?php $sx_i{d} = 0; while (have_posts()) : the_post(); $sx_i{d}++; ?>{inner}"
                f"<?php endwhile; ?>{'<?php sx_pagination(); ?>' if paginate else ''}</div>")
    if mode == "advanced":
        adv = []
        for row in orig.get("wp_query_advanced") or []:
            vals = [dyn_expr(v.get("value", ""), ctx) for v in row.get("values", [])]
            adv.append(f"{php_str(row.get('key', ''))} => [{', '.join(vals)}]")
        return (f"{head}<?php $sx_q{d} = sx_query_advanced([{', '.join(adv)}]); $sx_i{d} = 0; "
                f"while ($sx_q{d}->have_posts()) : $sx_q{d}->the_post(); $sx_i{d}++; ?>{inner}"
                f"<?php endwhile; wp_reset_postdata(); ?></div>")
    args = {k: v for k, v in orig.items() if k.startswith("query_") or k in ("wp_query",)}
    return (f"{head}<?php $sx_q{d} = sx_query({php_str(json.dumps(args, ensure_ascii=False))}); $sx_i{d} = 0; "
            f"while ($sx_q{d}->have_posts()) : $sx_q{d}->the_post(); $sx_i{d}++; ?>{inner}"
            f"<?php endwhile; wp_reset_postdata(); ?></div>")


REF_HTML = None


def ref_html():
    global REF_HTML
    if REF_HTML is None:
        REF_HTML = "\n".join(p.read_text(errors="replace") for p in sorted(REF.glob("*.html")))
    return REF_HTML


def ref_opening(sel, ctx):
    m = re.search(r'<div id="' + re.escape(sel) + r'(?:-\d+)?"[^>]*>\s*<div class="oxy-carousel-builder_inner[^>]*>', ref_html())
    return re.sub(r"\s+", " ", m.group(0)) if m else None


def ref_backdrop(sel, ctx):
    m = re.search(r'(<div tabindex="-1" class="oxy-modal-backdrop[^>]*>)\s*<div id="' + re.escape(sel) + '"', ref_html())
    return re.sub(r"\s+", " ", m.group(1)) if m else None


def ref_gallery_css(sel):
    """Oxygen imprime le CSS de chaque galerie en ligne (<style data-element-id>) : on le fige dans le CSS du gabarit."""
    m = re.search(r'<style data-element-id="#' + re.escape(sel) + r'"[^>]*>(.*?)</style>', ref_html(), re.S)
    return m.group(1).strip() if m else ""


def ref_counter(sel, ctx):
    m = re.search(r'<div id="' + re.escape(sel) + r'"[^>]*>.*?</span><span class="oxy-counter_suffix">[^<]*</span></div>', ref_html(), re.S)
    return m.group(0) if m else None



TAG = re.compile(r"<(/?)([a-zA-Z][\w-]*)\b[^>]*?(/?)>")
VOID = {"img", "br", "hr", "input", "meta", "link", "source", "use", "path", "circle", "rect", "line", "polygon", "wbr"}


def ref_element(sel):
    """HTML complet (équilibré) du premier élément d'id `sel` dans les pages de référence."""
    h = ref_html()
    m = re.search(r'<([a-zA-Z][\w-]*)\b[^>]*\bid="' + re.escape(sel) + r'"', h)
    if not m:
        return None
    depth, i = 0, m.start()
    for t in TAG.finditer(h, i):
        close, tag, selfclose = t.group(1), t.group(2).lower(), t.group(3)
        if tag in VOID or selfclose:
            continue
        depth += -1 if close else 1
        if depth == 0:
            return h[i:t.end()]
    return None


def ref_open_tag(sel):
    m = re.search(r'<[a-zA-Z][\w-]*\b[^>]*\bid="' + re.escape(sel) + r'"[^>]*>', ref_html())
    return re.sub(r"\s+", " ", m.group(0)) if m else None


def ref_menu(name, sel, o, orig, ctx):
    """Menu Oxygen/OxyExtras : balisage figé depuis la référence, liste <ul> rendue en direct par wp_nav_menu."""
    html = ref_element(sel)
    menu = o.get("menu_id") or orig.get("menu_id") or orig.get("oxy-horizontal-slide-menu_extras_menu_name") or ""
    if not html or not menu:
        ctx.todo.append(f"menu {name} {sel} (menu={menu!r}) introuvable")
        return f"<?php /* TODO oxy2php menu {sel} */ ?>"
    m = re.search(r'<ul id="menu-[^"]*" class="([^"]+)">', html)
    ul_class = m.group(1) if m else ""
    container = name != "oxy-horizontal-slide-menu"
    if container:
        pat = r'<div class="menu-[^"]*-container"><ul id="menu-[^"]*".*?</ul></div>'
    else:
        pat = r'<ul id="menu-[^"]*" class="oxy-horizonal-slide-menu_list">.*?</ul>(?=\s*</nav>)'
    call = f"<?php sx_menu({int(menu)}, {php_str(ul_class)}, {'true' if container else 'false'}); ?>"
    out, n = re.subn(pat, lambda _m: call, html, count=1, flags=re.S)
    if not n:
        ctx.todo.append(f"menu {sel} : liste non repérée")
    return portable(out)

HOSTS = r"(?:stg-groupesidexcom-staging\.kinsta\.cloud|groupesidexcom\.kinsta\.cloud|(?:www\.)?groupesidex\.com)"


def portable(body):
    """Aucune URL absolue du site dev : médias en chemin relatif, pages via home_url()."""
    body = re.sub(r"https?://" + HOSTS + r"(/wp-content/[^'\"\s)]*)", r"\1", body)
    return re.sub(r"https?://" + HOSTS + r"(/[^'\"\s<]*)?", lambda m: "<?php echo esc_url(home_url('" + (m.group(1) or "/") + "')); ?>", body)


def dedupe_attrs(body):
    def fix(m):
        seen, out = set(), []
        for a in re.findall(r'\s[\w:-]+(?:="[^"]*")?', m.group(2)):
            key = a.strip().split("=")[0]
            if key in seen:
                continue
            seen.add(key)
            out.append(a)
        return "<" + m.group(1) + "".join(out) + m.group(3)
    return re.sub(r'<(a|div|section|span|h\d|img)((?:\s[\w:-]+(?:="[^"]*")?)+)(\s*/?>)', fix, body)


def convert(tid):
    data = json.loads((SRC / "templates" / f"{tid}.json").read_text())
    ctx = Ctx(tid)
    body = "".join(render(c, ctx) for c in data.get("children", []))
    meta = EXPORT.get(str(tid), {})
    header = (f"<?php\n/**\n * Gabarit « {meta.get('title', tid)} » (ex-Oxygen {tid}), converti par tools/oxy2php.py le "
              f"1er oct. 2026 puis entretenu à la main.\n */\ndefined('ABSPATH') || exit;\n?>\n")
    out = THEME / "parts" / "oxy" / f"{tid}.php"
    out.parent.mkdir(parents=True, exist_ok=True)
    body = portable(body)
    body = dedupe_attrs(body)
    out.write_text(header + body + "\n")
    css_src = SRC / "css" / f"{tid}.css"
    css = css_src.read_text() if css_src.exists() else ""
    (THEME / "assets" / "css").mkdir(parents=True, exist_ok=True)
    css = css + ("\n" + "\n".join(ctx.css) if ctx.css else "")
    css = re.sub(r"(?:https?:)?//" + HOSTS + r"(?=/wp-content/)", "", css)
    (THEME / "assets" / "css" / f"o-{tid}.css").write_text(css)
    (THEME / "assets" / "js").mkdir(parents=True, exist_ok=True)
    js_file = THEME / "assets" / "js" / f"o-{tid}.js"
    if ctx.js:
        js_file.write_text("\n\n".join(ctx.js) + "\n")
    elif js_file.exists():
        js_file.unlink()
    return ctx


def main():
    ids = [p.stem for p in (SRC / "templates").glob("*.json") if p.stem.isdigit()] if "--all" in sys.argv else sys.argv[1:]
    icons = set()
    for tid in sorted(ids, key=int):
        if tid in ("53300",):
            continue  # 53300 = ancienne partie « BK-old », utilisée nulle part
        ctx = convert(tid)
        icons |= ctx.icons
        todo = sorted(set(ctx.todo))
        print(f"{tid}: {len(ctx.css)} css, {len(ctx.js)} js, {len(todo)} à revoir")
        for t in todo:
            print("   -", t[:180])
    (ROOT / "tools" / "out").mkdir(exist_ok=True)
    (ROOT / "tools" / "out" / "icons.json").write_text(json.dumps(sorted(icons)))


if __name__ == "__main__":
    main()
