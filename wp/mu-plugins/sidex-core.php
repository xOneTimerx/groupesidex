<?php
/**
 * Plugin Name: Sidex — fonctions du site (ex-Advanced Scripts)
 * Description: Reprend les accroches PHP qu'Advanced Scripts portait (scripts 76, 139, 244, 264, 273, 275) et qui
 *              doivent vivre indépendamment du thème : points de chargement ACF JSON, shortcodes, redirections,
 *              filtres Fluent Forms / WP Grid Builder / Rank Math. Inactif tant qu'Advanced Scripts est actif
 *              (sinon tout s'exécuterait deux fois). Les fonctions appelées par les gabarits sont dans le thème.
 * Author: Tactik Média
 */

defined('ABSPATH') || exit;

add_action('plugins_loaded', static function () {
	if (in_array('erropix-advanced-scripts/advanced-scripts.php', (array) get_option('active_plugins', []), true)) {
		return;
	}

	/* ACF : les groupes de champs vivent dans wp-content/acf-json (sans ceci, ils disparaissent de l'admin). */
	add_filter('acf/settings/save_json', static fn() => WP_CONTENT_DIR . '/acf-json');
	add_filter('acf/settings/load_json', static function ($paths) {
		unset($paths[0]);
		$paths[] = WP_CONTENT_DIR . '/acf-json';
		return array_values(array_unique($paths));
	});

	add_filter('upload_mimes', static function ($types) {
		$types['json'] = 'text/plain';
		return $types;
	});

	add_filter('get_the_archive_title', static function ($title) {
		if (is_post_type_archive()) {
			return post_type_archive_title('', false);
		}
		if (is_tax()) {
			return single_term_title('', false);
		}
		return $title;
	});

	/* Shortcodes des pages légales et de l'année (contenu des pages 82, 85, 49376, 49378). */
	add_shortcode('year', static fn() => date('Y'));
	add_shortcode('politiques', static fn() => sidex_core_generate_text('texte_politiques'));
	add_shortcode('termes', static fn() => sidex_core_generate_text('texte_termes'));

	/* Optimisation + sécurité */
	remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
	remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
	add_action('pre_ping', static function (&$links) {
		foreach ($links as $l => $link) {
			if (0 === strpos($link, get_option('home'))) {
				unset($links[$l]);
			}
		}
	});
	foreach (['do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom', 'do_feed_rss2_comments', 'do_feed_atom_comments'] as $feed) {
		add_action($feed, static function () {
			wp_die(__('No feed available, please visit the <a href="' . esc_url(home_url('/')) . '">homepage</a>!'));
		}, 1);
	}
	remove_action('wp_head', 'feed_links_extra', 3);
	remove_action('wp_head', 'feed_links', 2);
	add_filter('rest_endpoints', static function ($endpoints) {
		unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);
		return $endpoints;
	});
	add_action('init', static function () {
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action('admin_print_styles', 'print_emoji_styles');
		remove_filter('the_content_feed', 'wp_staticize_emoji');
		remove_filter('comment_text_rss', 'wp_staticize_emoji');
		remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
		add_filter('tiny_mce_plugins', static fn($plugins) => is_array($plugins) ? array_diff($plugins, ['wpemoji']) : []);
		add_filter('wp_resource_hints', static function ($urls, $relation) {
			return 'dns-prefetch' === $relation ? array_diff($urls, [apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/')]) : $urls;
		}, 10, 2);
	});
	add_action('wp_default_scripts', static function ($scripts) {
		if (!is_admin() && !empty($scripts->registered['jquery'])) {
			$scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']);
		}
	});
	add_action('wp_footer', static fn() => wp_dequeue_script('wp-embed'));

	/* Recherche : /?s=… → /recherche/… */
	add_action('template_redirect', static function () {
		if (is_search() && !empty($_GET['s'])) {
			wp_redirect(home_url('/recherche/') . urlencode(get_query_var('s')));
			exit;
		}
	});

	/* WPML : classe de langue sur <body>. */
	add_filter('body_class', static function ($classes) {
		if (defined('ICL_LANGUAGE_CODE')) {
			$classes[] = 'wpml-' . ICL_LANGUAGE_CODE;
		}
		return $classes;
	});

	/* Tailles d'images */
	foreach ([480, 640, 720, 960, 1168, 1440, 1920] as $w) {
		add_image_size('image-' . $w, $w, 9999);
	}
	add_filter('image_size_names_choose', static function ($sizes) {
		foreach ([480, 640, 720, 960, 1168, 1440, 1920] as $w) {
			$sizes['image-' . $w] = 'image-' . $w;
		}
		return $sizes;
	});

	/* Page « Avis Google » → lien d'avis Google (options ACF). */
	add_action('wp_head', static function () {
		if (!function_exists('get_field') || !get_field('avis_page_wordpress', 'option')) {
			return;
		}
		if (is_page(get_post_field('post_name', get_field('avis_page_wordpress', 'option')))) {
			wp_redirect(get_field('avis_lien_google', 'option') ?: home_url());
			exit;
		}
	});

	/* WP Grid Builder 76 : la grille 2 « Inspirations » affiche une galerie (de la page, sinon des options). */
	add_filter('wp_grid_builder/grid/query_args', static function ($query_args, $grid_id) {
		if (2 === $grid_id) {
			$query_args['post_type'] = 'attachment';
			$query_args['post__in'] = get_field('galerie_remplacement') ?: get_field('bloc_inspirations_galerie', 'option');
			$query_args['post_status'] = 'any';
			$query_args['orderby'] = 'post__in';
			$query_args['posts_per_page'] = -1;
		}
		return $query_args;
	}, 10, 2);

	/* Fluent Forms 244 : liste des postes des formulaires Candidature (5 FR, 8 EN) = répéteur « postes » des pages Carrières. */
	add_filter('fluentform_rendering_field_data_select', static function ($data, $form) {
		if (!in_array((int) $form->id, [5, 8], true) || ($data['attributes']['name'] ?? '') !== 'type_emploi') {
			return $data;
		}
		$rows = get_field('postes', (int) $form->id === 5 ? 1656 : 49313);
		if ($rows) {
			$data['settings']['advanced_options'] = array_map(static fn($row) => ['label' => $row['titre'], 'value' => $row['titre'], 'calc_value' => ''], $rows);
		}
		return $data;
	}, 10, 2);
	add_filter('fluentform/validate_input_item_select', static fn($error, $field) => [], 10, 2);

	/* 264 : titres et descriptions SEOPress des taxonomies (repris tels quels ; SEOPress est désactivé). */
	add_filter('seopress_titles_title', static function ($html) {
		$title = is_tax() ? get_term_meta(get_queried_object()->term_id, '_seopress_titles_title', true) : '';
		return $title ?: $html;
	});
	add_filter('seopress_titles_desc', static function ($html) {
		$desc = is_tax() ? get_term_meta(get_queried_object()->term_id, '_seopress_titles_desc', true) : '';
		return $desc ?: $html;
	});

	/* 273 : foundingDate de l'organisation dans le JSON-LD Rank Math. */
	add_filter('rank_math/json_ld', static function ($data, $jsonld) {
		if (!is_array($data)) {
			return $data;
		}
		foreach ($data as $key => $node) {
			if (is_array($node) && isset($node['@type']) && in_array('Organization', (array) $node['@type'], true) && empty($node['foundingDate'])) {
				$data[$key]['foundingDate'] = '2006';
			}
		}
		return $data;
	}, 20, 2);

	/* 275 : titre des pages auteur = prénom nom | site. */
	add_filter('rank_math/frontend/title', static function ($title) {
		if (is_author() && ($author = get_queried_object()) instanceof WP_User) {
			$name = trim(get_user_meta($author->ID, 'first_name', true) . ' ' . get_user_meta($author->ID, 'last_name', true));
			$title = ($name !== '' ? $name : $author->display_name) . ' | ' . get_bloginfo('name');
		}
		return $title;
	}, 20);
}, 5);

/** Texte légal des options ACF avec ses jetons (ex-generate_text d'Advanced Scripts 139). */
function sidex_core_generate_text(string $field): string
{
	if (function_exists('generate_text')) {
		return (string) generate_text($field);
	}
	$text = (string) get_field($field, 'option');
	$text = str_replace('{{nom_compagnie}}', (string) get_field('nom_compagnie', 'option'), $text);
	$text = str_replace('{{nom_contact}}', (string) get_field('nom_contact_politiques', 'option'), $text);
	$text = str_replace('{{titre_contact}}', (string) get_field('titre_contact_politiques', 'option'), $text);
	$courriel = (string) get_field('courriel_contact_politiques', 'option');
	$text = str_replace('{{courriel_contact}}', "<a href='mailto:" . $courriel . "'>" . $courriel . '</a>', $text);
	$site = (string) get_field('site_web_compagnie_politiques', 'option');
	$text = str_replace('{{site_web_compagnie_politiques}}', "<a href='" . $site . "'>" . $site . '</a>', $text);
	$joindre = (string) get_field('lien_nous_joindre_politiques', 'option');
	return str_replace('{{lien_nous_joindre_politiques}}', "<a href='" . $joindre . "'>" . $joindre . '</a>', $text);
}
