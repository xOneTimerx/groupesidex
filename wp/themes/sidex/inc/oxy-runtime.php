<?php
/**
 * Fonctions d'exécution des gabarits convertis d'Oxygen (parts/oxy/*.php).
 *
 * Elles reproduisent le comportement des données dynamiques d'Oxygen (phpfunction, requêtes des listes,
 * conditions, galeries, accordéon, formulaires) sans dépendre d'Oxygen. Sémantique relue dans
 * oxygen/component-framework 4.9.1 (oxygen-dynamic-shortcodes.php, dynamic-list.class.php, conditions.php).
 */

defined('ABSPATH') || exit;

/** Gabarits Oxygen convertis : id → fichiers du thème. */
function sx_oxy(int $id): void
{
	sx_oxy_assets($id);
	$file = SIDEX_DIR . '/parts/oxy/' . $id . '.php';
	if (is_file($file)) {
		include $file;
	}
}

/** Partie réutilisable (ex-« reusable part » Oxygen). */
function sx_oxy_part(int $id): void
{
	sx_oxy($id);
}

/** CSS et JS du gabarit : le CSS s'imprime en place (comme les <link> d'Oxygen), le JS part au pied de page. */
function sx_oxy_assets(int $id): void
{
	static $done = [];
	if (isset($done[$id])) {
		return;
	}
	$done[$id] = true;
	$css = 'css/o-' . $id . '.css';
	if (is_file(SIDEX_DIR . '/assets/' . $css) && filesize(SIDEX_DIR . '/assets/' . $css) > 0) {
		if (did_action('wp_print_styles') && !doing_action('wp_enqueue_scripts')) {
			printf('<link rel="stylesheet" href="%s">', esc_url(sx_asset_url($css) . '?ver=' . sx_asset_version($css)));
		} else {
			wp_enqueue_style('sidex-o-' . $id, sx_asset_url($css), ['sidex'], sx_asset_version($css));
		}
	}
	foreach (SX_OXY_LIBS[$id] ?? [] as $lib) {
		sx_lib($lib);
	}
	$js = 'js/o-' . $id . '.js';
	if (is_file(SIDEX_DIR . '/assets/' . $js)) {
		wp_enqueue_script('sidex-o-' . $id, sx_asset_url($js), ['sidex', 'sidex-advanced-scripts'], sx_asset_version($js), ['in_footer' => true]);
	}
}

/** Charge d'avance (dans <head>) le CSS des gabarits de la page, pour éviter tout flash. */
function sx_oxy_preload(array $ids): void
{
	add_action('wp_enqueue_scripts', static function () use ($ids) {
		foreach ($ids as $id) {
			sx_oxy_assets((int) $id);
		}
	}, 30);
}

/** [oxygen data='phpfunction'] : appelle la fonction avec les arguments bruts (toujours au moins ''). */
function sx_fn(string $function, string ...$args): string
{
	if (!function_exists($function)) {
		return '';
	}
	if (!$args) {
		$args = [''];
	}
	ob_start();
	$value = call_user_func_array($function, $args);
	$echoed = (string) ob_get_clean();
	if (is_array($value)) {
		$value = isset($value['url']) ? $value['url'] : implode(',', array_filter($value, 'is_scalar'));
	}
	return $echoed . (is_scalar($value) ? (string) $value : '');
}

/** Sortie telle quelle, comme Oxygen (les champs ACF de ce site contiennent du HTML voulu). */
function sx_kses($value): string
{
	return (string) $value;
}

/** [oxygen data='acfreparray' field='…'] : sous-champ de la rangée courante. */
function sx_sub(string $field): string
{
	$value = get_sub_field($field);
	if (is_array($value)) {
		if (isset($value['url'])) {
			return (string) $value['url'];
		}
		// Galerie / relation : liste d'ids (non vide = « is_not_blank » comme chez Oxygen).
		return implode(',', array_map(static fn($v) => is_array($v) ? ($v['ID'] ?? $v['id'] ?? '') : (is_object($v) ? $v->ID : $v), $value));
	}
	return is_scalar($value) ? (string) $value : '';
}

/** [oxygen data='custom_acf_content' …] */
function sx_acf_content(string $path, string $insert = '', string $separator = '', string $settings_page = ''): string
{
	$value = get_field($path, $settings_page === 'true' ? 'option' : false);
	if (is_array($value)) {
		$ids = array_map(static fn($v) => is_object($v) ? $v->ID : (is_array($v) ? ($v['ID'] ?? $v['id'] ?? '') : $v), $value);
		return implode($separator !== '' ? $separator : ',', $ids);
	}
	if (is_object($value) && isset($value->ID)) {
		return (string) $value->ID;
	}
	return (string) $value;
}

/** Condition dynamique d'Oxygen (ZZOXYVSBDYNAMIC). */
function sx_compare(string $value, string $operator, string $expected): bool
{
	switch ($operator) {
		case '==':
			return $value == $expected;
		case '!=':
			return $value != $expected;
		case '>=':
			return (float) $value >= (float) $expected;
		case '<=':
			return (float) $value <= (float) $expected;
		case '>':
			return (float) $value > (float) $expected;
		case '<':
			return (float) $value < (float) $expected;
		case 'contains':
			return str_contains($value, $expected);
		case 'does_not_contain':
			return !str_contains($value, $expected);
		case 'is_blank':
			return trim($value) === '';
		case 'is_not_blank':
			return trim($value) !== '';
	}
	return false;
}

/** Propriétaire d'un répéteur ACF (clé de champ) : page d'options, sous-champ de la rangée courante, ou post. */
function sx_row_owner(string $key)
{
	static $cache = [];
	if (!isset($cache[$key])) {
		$owner = false;
		$field = function_exists('acf_get_field') ? acf_get_field($key) : null;
		$parent = $field['parent'] ?? 0;
		while ($parent && is_string($parent) && str_starts_with($parent, 'field_')) {
			$parent = acf_get_field($parent)['parent'] ?? 0;
		}
		$group = $parent ? acf_get_field_group($parent) : null;
		foreach ((array) ($group['location'] ?? []) as $rules) {
			foreach ((array) $rules as $rule) {
				if (($rule['param'] ?? '') === 'options_page') {
					$owner = 'option';
				}
			}
		}
		$cache[$key] = $owner;
	}
	return $cache[$key];
}

/** Liste dynamique « custom » d'Oxygen → WP_Query (mêmes règles que dynamic-list.class.php). */
function sx_query(string $json): WP_Query
{
	$o = json_decode($json, true) ?: [];
	$args = ['post_type' => $o['query_post_types'] ?? 'post'];
	if (!empty($o['query_post_ids'])) {
		$args['post__in'] = explode(',', $o['query_post_ids']);
	}
	foreach (['query_taxonomies_any' => 'OR', 'query_taxonomies_all' => 'AND'] as $key => $relation) {
		if (!empty($o[$key]) && is_array($o[$key])) {
			$byTax = [];
			foreach ($o[$key] as $value) {
				[$tax, $term] = array_pad(explode(',', $value), 2, '');
				$byTax[$tax === 'tag' ? 'post_tag' : $tax][] = $term;
			}
			$args['tax_query'] = ['relation' => $relation];
			foreach ($byTax as $tax => $terms) {
				$args['tax_query'][] = ['taxonomy' => $tax, 'terms' => $terms] + ($relation === 'AND' ? ['operator' => 'AND'] : []);
			}
		}
	}
	if (!empty($o['query_authors'])) {
		$args['author__in'] = $o['query_authors'];
	}
	$args['order'] = $o['query_order'] ?? '';
	$args['orderby'] = $o['query_order_by'] ?? '';
	if (($o['query_ignore_sticky_posts'] ?? '') === 'true') {
		$args['ignore_sticky_posts'] = true;
	}
	$count = (int) ($o['query_count'] ?? 0);
	if ($count > 0) {
		$args['posts_per_page'] = $count;
		if (get_query_var('paged')) {
			$args['paged'] = get_query_var('paged');
		}
	} else {
		$args['nopaging'] = true;
	}
	return new WP_Query($args);
}

/** Liste dynamique « advanced » : [clé => [valeurs]] comme Oxy_VSB_Advanced_Query::query_args. */
function sx_query_advanced(array $rows): WP_Query
{
	$args = [];
	foreach ($rows as $key => $values) {
		$values = array_values(array_filter(array_map('strval', $values), static fn($v) => $v !== ''));
		if (in_array($key, ['post__in', 'post__not_in', 'post_type', 'post_status', 'category__in'], true)) {
			$list = [];
			foreach ($values as $v) {
				$list = array_merge($list, array_map('trim', explode(',', $v)));
			}
			$args[$key] = $list ?: [0];
		} else {
			$args[$key] = count($values) === 1 ? $values[0] : $values;
		}
	}
	return new WP_Query($args);
}

/** Pagination des listes (liste Articles, 77). */
function sx_pagination(): void
{
	$links = paginate_links(['type' => 'plain', 'prev_text' => '&laquo; Précédent', 'next_text' => 'Suivant &raquo;']);
	if ($links) {
		echo '<div class="oxy-repeater-pages-wrap"><div class="oxy-repeater-pages">' . $links . '</div></div>';
	}
}

/** Contenu de la page (ex-élément « Inner Content »). */
function sx_inner_content(): void
{
	while (have_posts()) {
		the_post();
		the_content();
	}
}

/** Formulaire Fluent Forms par id (0 = rien). */
function sx_fluent_form(int $id): void
{
	if ($id > 0) {
		echo do_shortcode('[fluentform id="' . $id . '"]');
	}
}

/** Facette WP Grid Builder (la grille visée est la liste dynamique qui porte data-wpgb). */
function sx_wpgb_facet(string $facet): void
{
	if (function_exists('wpgb_render_facet') && $facet !== '') {
		wpgb_render_facet(['id' => (int) $facet, 'grid' => 'wpgb-content']);
	}
}

/** Carrousel « galerie ACF » (ex-OxyExtras) : champ galerie du post ou de la rangée courante. */
function sx_carousel_gallery(string $id, string $field, string $size, int $row): void
{
	$images = get_sub_field($field) ?: get_field($field);
	echo '<div class="oxy-carousel-builder_gallery-images">';
	foreach ((array) $images as $i => $image) {
		$aid = is_array($image) ? (int) ($image['ID'] ?? $image['id'] ?? 0) : (int) $image;
		$src = $aid ? wp_get_attachment_image_src($aid, $size) : null;
		if (!$src) {
			continue;
		}
		$cell = $id . '-' . $i;
		printf('<div class="oxy-carousel-builder_gallery-image" id="%s" data-id="%s"><img src="%s" alt="%s"></div>',
			esc_attr($cell . ($row ? '-' . $row : '')), esc_attr($cell), esc_url($src[0]), esc_attr((string) get_post_meta($aid, '_wp_attachment_image_alt', true)));
	}
	echo '</div>';
}

/** Galerie Oxygen (grille ou maçonnerie) avec visionneuse. */
function sx_gallery(string $id, string $classes, string $layout, string $json): void
{
	$o = json_decode($json, true) ?: [];
	$images = [];
	if (($o['gallery_source'] ?? '') === 'acf' && !empty($o['acf_field'])) {
		// Sur une archive, le champ vit dans la page d'options (même repli qu'Oxygen).
		$images = get_field($o['acf_field']) ?: get_field($o['acf_field'], 'option');
		$images = is_array($images) ? $images : [];
	} elseif (!empty($o['image_ids'])) {
		$images = array_map('intval', explode(',', $o['image_ids']));
	}
	$class = trim('oxy-gallery ' . $classes . ' oxy-gallery-captions oxy-gallery-' . $layout);
	echo '<div id="' . esc_attr($id) . '" class="' . esc_attr($class) . '" data-lightbox>';
	if (!$images) {
		echo '<div class="oxygen-empty-gallery"></div>';
	}
	foreach ($images as $image) {
		$aid = is_array($image) ? (int) ($image['ID'] ?? $image['id'] ?? 0) : (int) $image;
		$src = $aid ? wp_get_attachment_image_src($aid, $o['gallery_thumbnail_size'] ?? 'full') : null;
		$full = $aid ? wp_get_attachment_image_src($aid, 'full') : null;
		if (!$src) {
			continue;
		}
		$alt = (string) get_post_meta($aid, '_wp_attachment_image_alt', true);
		$caption = (string) wp_get_attachment_caption($aid);
		printf(
			"<a href='%s' class='oxy-gallery-item'><figure class='oxy-gallery-item-contents'><img src=\"%s\" data-original-src=\"%s\" data-original-src-width=\"%d\" data-original-src-height=\"%d\" alt=\"%s\" loading=\"lazy\"><figcaption>%s</figcaption></figure></a>",
			esc_url($full[0]), esc_url($src[0]), esc_url($full[0]), (int) $full[1], (int) $full[2], esc_attr($alt), esc_html($caption)
		);
	}
	echo '</div>';
}

/** Accordéon (ex-OxyExtras Pro Accordion, source ACF) : même balisage qu'OxyExtras, premier élément ouvert. */
function sx_accordion(string $id, string $classes, string $repeater, string $title, string $content, string $icon): void
{
	if (!have_rows($repeater)) {
		return;
	}
	$base = ltrim($id, '-');
	echo '<div id="' . esc_attr($id) . '" class="oxy-pro-accordion ' . esc_attr($classes) . ' "><div class="oxy-pro-accordion_inner" data-icon="animate" data-expand="300" data-repeater="disable" data-repeater-first="false" data-acf="open" data-type="acf" data-disablesibling="false" data-accordion>';
	$i = 0;
	while (have_rows($repeater)) {
		the_row();
		$i++;
		$head = 'header-' . $base . '-' . $i;
		$body = 'body-' . $base . '-' . $i;
		echo '<div class="oxy-pro-accordion_item"' . ($i === 1 ? ' data-init="open"' : '') . '>';
		printf('<button id="%s" class="oxy-pro-accordion_header" aria-controls="%s" aria-expanded="false" type="button">', esc_attr($head), esc_attr($body));
		printf('<span class="oxy-pro-accordion_title-area"><h4 class="oxy-pro-accordion_title">%s</h4><span class="oxy-pro-accordion_subtitle"></span></span>', wp_kses_post((string) get_sub_field($title)));
		printf('<span class="oxy-pro-accordion_icon oxy-pro-accordion_icon-animate"><svg id="toggle-%s" class="oxy-pro-accordion_toggle-icon"><use xlink:href="#%s"></use></svg></span></button>', esc_attr($id), esc_attr($icon));
		printf('<div id="%s" class="oxy-pro-accordion_body" aria-labelledby="%s" role="region"><div class="oxy-pro-accordion_content">%s</div></div>', esc_attr($body), esc_attr($head), wp_kses_post((string) get_sub_field($content)));
		echo '</div>';
	}
	echo '</div></div>';
}

/** Image de la médiathèque (ex-ct_image « attachment »). */
function sx_img(int $id, string $size, string $class, string $idattr, string $alt): string
{
	$attrs = ['class' => $class, 'alt' => $alt !== '' ? $alt : (string) get_post_meta($id, '_wp_attachment_image_alt', true)];
	$html = wp_get_attachment_image($id, $size, false, $attrs);
	return $idattr !== '' ? preg_replace('/^<img /', '<img ' . $idattr . ' ', $html) : $html;
}

/* ─── Icônes : sprite des symboles Oxygen (FontAwesome, Linearicons, OxyNinja) ─── */

function sx_icon_use(string $icon): string
{
	$GLOBALS['sx_icons'][$icon] = true;
	return '<svg><use xlink:href="#' . esc_attr($icon) . '"></use></svg>';
}

add_action('wp_footer', static function () {
	$file = SIDEX_DIR . '/assets/icons/sprite.svg';
	if (is_file($file)) {
		readfile($file);
	}
}, 5);

/* ─── Ajouts Sidex ─── */

/** Gabarit propre à la requête, rendu à la place de l'« Inner Content » du gabarit 69. */
function sx_page_template(): void
{
	sx_oxy((int) ($GLOBALS['sx_template'] ?? sx_request_template()));
}

/** Rang (1, 2, …) dans la liste dynamique ou le répéteur en cours (conditions « nième élément »). */
function sx_loop_index(): int
{
	global $wp_query;
	if (function_exists('get_row_index') && get_row_index()) {
		return (int) get_row_index();
	}
	return (int) ($wp_query->current_post ?? 0) + 1;
}

/** Onglets dynamiques (ex-OxyExtras « Dynamic Tabs », source répéteur ACF) : même balisage qu'OxyExtras. */
function sx_dynamic_tabs(string $id, string $repeater, string $tab_field, string $content_field): void
{
	$rows = get_field($repeater);
	if (!is_array($rows) || !$rows) {
		return;
	}
	echo '<ul class="oxy-dynamic-tabs_tab-group">';
	foreach ($rows as $i => $row) {
		printf('<li class="oxy-dynamic-tabs_tab-item" id="%s-item"><button class="oxy-dynamic-tabs_tab"><span class="oxy-dynamic-tabs_tab-text">%s</span></button></li>',
			esc_attr($id . ($i + 1)), wp_kses_post((string) ($row[$tab_field] ?? '')));
	}
	echo '</ul><div class="oxy-dynamic-tabs_panel-group">';
	foreach ($rows as $i => $row) {
		printf('<div class="oxy-dynamic-tabs_panel" id="%s"><div class="oxy-dynamic-tabs_panel-inner">%s</div></div>',
			esc_attr($id . '-' . ($i + 1)), (string) ($row[$content_field] ?? ''));
	}
	echo '</div>';
}

/** Grille WP Grid Builder (ex-élément « WPGB Grid » du pont Oxygen). */
function sx_wpgb_grid(int $grid): void
{
	if (function_exists('wpgb_render_grid')) {
		wpgb_render_grid($grid);
	}
}

/** Temps de lecture, calcul d'OxyExtras : mots du contenu ÷ 275, arrondi vers le haut. */
function sx_reading_time(string $before, string $singular, string $plural): string
{
	$words = str_word_count(strip_tags((string) get_post_field('post_content', get_the_ID())));
	$minutes = (int) ceil($words / 275);
	return $before . ' ' . $minutes . ' ' . ($minutes === 1 ? $singular : $plural);
}
