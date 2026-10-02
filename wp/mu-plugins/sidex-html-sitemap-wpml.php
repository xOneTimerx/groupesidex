<?php
/**
 * Plugin Name: Sidex — plan du site HTML par langue
 * Description: Le plan du site HTML de Rank Math (/html-sitemap/, /en/html-sitemap/) interroge wp_posts en SQL direct,
 *              sans le filtre de langue de WPML : la page FR listait aussi chaque traduction anglaise (titre anglais,
 *              lien vers la page FR). On restreint la requête à la langue courante via icl_translations.
 * Author: Tactik Média
 */

defined('ABSPATH') || exit;

add_filter('rank_math/html_sitemap/get_posts/join', static function ($join, $post_type) {
	global $wpdb;
	$lang = apply_filters('wpml_current_language', null);
	if (!$lang || !is_string($post_type) || !apply_filters('wpml_is_translated_post_type', false, $post_type)) {
		return $join;
	}
	return $join . $wpdb->prepare(
		" INNER JOIN {$wpdb->prefix}icl_translations AS sx_t ON ( sx_t.element_id = p.ID AND sx_t.element_type = %s AND sx_t.language_code = %s ) ",
		'post_' . $post_type,
		$lang
	);
}, 10, 2);
