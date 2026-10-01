<?php
/**
 * Choix du gabarit converti d'Oxygen selon la requête. Reprend les règles d'application des gabarits
 * Oxygen (docs/inventaire/url-map.json) : pages par terme « types_page », singuliers et archives par
 * type de contenu. Le gabarit 69 (en-tête / pied) enveloppe toujours le résultat (index.php).
 */

defined('ABSPATH') || exit;

/** Terme types_page → gabarit (règles `_ct_template_taxonomies` ; 265 Concours appliqué hors règle par Oxygen). */
const SX_PAGE_TEMPLATES = [
	2 => 1648, 3 => 120, 4 => 78, 28 => 191, 29 => 1654, 31 => 1593, 32 => 1726, 71 => 1640, 72 => 1688,
	74 => 1761, 249 => 51496, 265 => 53501, 270 => 54732, 276 => 54834, 287 => 55016, 288 => 55123,
];
const SX_SINGLE_TEMPLATES = ['post' => 75, 'projets' => 1584, 'etapes' => 1615, 'etapes_cachees' => 1615, 'faq' => 54620, 'merci' => 73];
const SX_ARCHIVE_TEMPLATES = ['projets' => 1582, 'post' => 77];

function sx_request_template(): int
{
	if (is_404()) {
		return 74;
	}
	if (is_search()) {
		return 88;
	}
	if (is_author()) {
		return 54802;
	}
	if (is_home() || is_category() || is_tag() || is_date()) {
		return 77;
	}
	if (is_page()) {
		$terms = wp_get_post_terms(get_queried_object_id(), 'types_page', ['fields' => 'ids']);
		foreach (is_array($terms) ? $terms : [] as $term) {
			$term = (int) apply_filters('wpml_object_id', (int) $term, 'types_page', true, 'fr');
			if (isset(SX_PAGE_TEMPLATES[$term])) {
				return SX_PAGE_TEMPLATES[$term];
			}
		}
		return 78;
	}
	if (is_singular()) {
		return SX_SINGLE_TEMPLATES[get_post_type()] ?? 78;
	}
	if (is_post_type_archive()) {
		$type = get_query_var('post_type');
		return SX_ARCHIVE_TEMPLATES[is_array($type) ? reset($type) : $type] ?? 77;
	}
	return 77;
}

/** Parties réutilisables incluses par un gabarit (pour charger leur CSS dans <head>). */
function sx_template_parts(int $id, array &$seen = []): array
{
	if (isset($seen[$id])) {
		return [];
	}
	$seen[$id] = true;
	$ids = [$id];
	$file = SIDEX_DIR . '/parts/oxy/' . $id . '.php';
	if (is_file($file) && preg_match_all('/sx_oxy_part\((\d+)\)/', (string) file_get_contents($file), $m)) {
		foreach ($m[1] as $part) {
			$ids = array_merge($ids, sx_template_parts((int) $part, $seen));
		}
	}
	return $ids;
}

add_action('wp', static function () {
	if (is_admin() || !sx_theme_owns_request()) {
		return;
	}
	$GLOBALS['sx_template'] = sx_request_template();
	sx_oxy_preload(array_merge(sx_template_parts(69), sx_template_parts($GLOBALS['sx_template'])));
});

// « oxygen-body » : plusieurs règles d'universal.css le visent encore.
add_filter('body_class', static function ($classes) {
	if (!sx_theme_owns_request()) {
		return $classes;
	}
	return array_merge($classes, ['oxygen-body', 'sx-t-' . ($GLOBALS['sx_template'] ?? 0)]);
});

/* WP Grid Builder : les facettes (réalisations, blogue) filtrent la requête principale (grille « wpgb-content »),
   ce que faisait le pont wp-grid-builder-oxygen. Nécessite le réglage WPGB « Filter custom content ». */
add_action('pre_get_posts', static function (WP_Query $query) {
	if (is_admin() || !$query->is_main_query()) {
		return;
	}
	if ($query->is_post_type_archive('projets') || $query->is_home() || $query->is_category()) {
		$query->set('wp_grid_builder', true);
	}
}, 1);
