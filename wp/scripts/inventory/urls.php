<?php
/**
 * Inventaire des URL publiques FR/EN (wp eval-file urls.php > urls.json). Lecture seule.
 * [{url, key, kind, lang, id}] — key = clé de routage future du gel (post-<ID>, term-<ID>, archive-<type>-<lang>, 404-<lang>).
 */
global $wpdb, $sitepress;
$out = [];
$types = ['page', 'post', 'etapes', 'faq', 'merci', 'projets'];
$in = "'" . implode("','", $types) . "'";
// get_posts reste filtré par WPML malgré suppress_filters : SQL direct.
$rows = $wpdb->get_results("SELECT p.ID, p.post_type, t.language_code lang FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = CONCAT('post_', p.post_type)
	WHERE p.post_status = 'publish' AND p.post_type IN ($in) ORDER BY p.post_type, p.ID");
foreach ($rows as $r) {
	$lang = $r->lang ?: 'fr';
	$sitepress->switch_lang($lang);
	$robots = (array) get_post_meta($r->ID, 'rank_math_robots', true);
	$out[] = ['url' => get_permalink($r->ID), 'key' => 'post-' . $r->ID, 'kind' => $r->post_type, 'lang' => $lang, 'id' => (int) $r->ID,
		'noindex' => in_array('noindex', $robots, true)];
}
foreach (['fr', 'en'] as $lang) {
	$sitepress->switch_lang($lang);
	foreach (['category', 'post_tag', 'etapes'] as $tax) {
		foreach (get_terms(['taxonomy' => $tax, 'hide_empty' => true]) as $term) {
			$out[] = ['url' => get_term_link($term), 'key' => 'term-' . $term->term_id, 'kind' => "tax:$tax", 'lang' => $lang, 'id' => $term->term_id];
		}
	}
	foreach (['projets', 'post'] as $type) {
		$link = $type === 'post' ? get_permalink((int) apply_filters('wpml_object_id', (int) get_option('page_for_posts'), 'page', true, $lang)) : get_post_type_archive_link($type);
		if ($link) {
			$out[] = ['url' => $link, 'key' => "archive-$type-$lang", 'kind' => "archive:$type", 'lang' => $lang, 'id' => 0];
		}
	}
	foreach (get_users(['has_published_posts' => ['post'], 'fields' => ['ID']]) as $u) {
		$out[] = ['url' => get_author_posts_url($u->ID), 'key' => "author-{$u->ID}-$lang", 'kind' => 'author', 'lang' => $lang, 'id' => (int) $u->ID];
	}
	$prefix = $lang === 'en' ? '/en' : '';
	$out[] = ['url' => home_url($prefix . '/?s=cedre'), 'key' => "search-$lang", 'kind' => 'search', 'lang' => $lang, 'id' => 0];
	$out[] = ['url' => home_url($prefix . '/sx-capture-404-x9z/'), 'key' => "404-$lang", 'kind' => '404', 'lang' => $lang, 'id' => 0];
}
echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
