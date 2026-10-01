<?php
/**
 * Inventaire lecture seule du site (gabarits Oxygen, contenus, Advanced Scripts, ACF, menus, formulaires, grilles).
 *   wp eval-file inventory.php > /tmp/sidex-inventory.json
 * N'écrit rien en base.
 */
global $wpdb;
$out = [];
$meta = static fn($id, $k) => get_post_meta($id, $k, true);

// 1. Gabarits Oxygen
$tpls = $wpdb->get_results("SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type='ct_template' AND post_status='publish' ORDER BY ID");
foreach ($tpls as $t) {
	$json = (string) $meta($t->ID, '_ct_builder_json');
	$sc = (string) $meta($t->ID, '_ct_builder_shortcodes');
	$names = [];
	$tree = json_decode($json, true);
	$walk = function ($n) use (&$walk, &$names) {
		if (!empty($n['name'])) { $names[$n['name']] = ($names[$n['name']] ?? 0) + 1; }
		foreach ($n['children'] ?? [] as $c) { $walk($c); }
	};
	if ($tree) { $walk($tree); }
	unset($names['root']);
	preg_match_all('/\[(fluentform|wpgb_grid|wpgb_facet|wpgb_template|table|tablepress)[^\]]*\]/i', $json . $sc, $scm);
	preg_match_all('/(?:get_field|the_field|get_sub_field|the_sub_field|have_rows)\(\s*[\'"]([a-z0-9_]+)[\'"]/i', base64_decode_all($json) . $json, $acf1);
	preg_match_all('/"field":"([a-z0-9_]+)"|field=\\\\?"([a-z0-9_]+)|\\\\?"acf_field\\\\?":\\\\?"([a-z0-9_]+)/i', $json . $sc, $acf2);
	$fields = array_values(array_unique(array_filter(array_merge($acf1[1], $acf2[1], $acf2[2], $acf2[3]))));
	preg_match_all('/ct_reusable[^}]*?"view_id":"?(\d+)/', $json, $reu);
	$rules = [];
	foreach (['_ct_template_type','_ct_template_order','_ct_template_front_page','_ct_template_blog_posts','_ct_template_single_all','_ct_template_post_types','_ct_template_taxonomies','_ct_use_template_taxonomies','_ct_template_archive_post_types','_ct_template_archive_among_taxonomies','_ct_template_all_archives','_ct_template_categories','_ct_template_custom_taxonomies','_ct_template_404_page','_ct_template_search_page','_ct_template_index','_ct_template_inner_content','_ct_template_include_ids','_ct_template_exclude_ids','_ct_template_post_of_parents'] as $k) {
		$v = $meta($t->ID, $k);
		if ($v !== '' && $v !== [] && $v !== 'false' && $v !== null) { $rules[substr($k, 13)] = $v; }
	}
	$out['templates'][] = [
		'id' => (int) $t->ID, 'title' => $t->post_title, 'parent' => (int) $meta($t->ID, '_ct_parent_template'),
		'rules' => $rules, 'json_bytes' => strlen($json), 'elements' => array_sum($names), 'element_types' => $names,
		'shortcodes' => array_values(array_unique($scm[0])), 'acf_fields' => $fields, 'reusables' => array_values(array_unique($reu[1])),
		'has_code_blocks' => isset($names['ct_code_block']),
	];
}
function base64_decode_all(string $json): string {
	preg_match_all('/"code-(?:php|js|css)":"([^"]*)"/', $json, $m);
	return implode("\n", array_map(static fn($s) => (string) base64_decode($s, true) ?: stripcslashes($s), $m[1]));
}

// 2. Contenus publiés par type et langue
$out['content'] = $wpdb->get_results("SELECT p.post_type, t.language_code lang, COUNT(*) n FROM {$wpdb->posts} p LEFT JOIN {$wpdb->prefix}icl_translations t ON t.element_id=p.ID AND t.element_type=CONCAT('post_',p.post_type) WHERE p.post_status='publish' AND p.post_type NOT IN ('acf-field','acf-field-group','nav_menu_item','revision','ct_template','oxy_user_library','wp_global_styles','seopress_404','acfe-dt','acfe-dop','acfe-dpt','tablepress_table','scheduled-action') GROUP BY 1,2 ORDER BY 1,2", ARRAY_A);
$out['public_post_types'] = array_values(get_post_types(['public' => true]));
$out['public_taxonomies'] = array_values(get_taxonomies(['public' => true]));

// 3. Advanced Scripts
foreach (get_terms(['taxonomy' => 'erropix_scripts', 'hide_empty' => false]) as $s) {
	$code = (string) get_term_meta($s->term_id, 'script_code', true);
	$out['advanced_scripts'][] = [
		'id' => $s->term_id, 'title' => get_term_meta($s->term_id, 'script_title', true), 'type' => get_term_meta($s->term_id, 'script_type', true),
		'location' => get_term_meta($s->term_id, 'script_location', true), 'hook' => get_term_meta($s->term_id, 'script_hook', true),
		'shortcode' => get_term_meta($s->term_id, 'script_shortcode', true), 'status' => get_term_meta($s->term_id, 'script_status', true),
		'bytes' => strlen($code),
		'tracking' => (bool) preg_match('/gtag|googletagmanager|AW-\d|fbq\(|clarity|crisp|hotjar|pixel/i', $code),
	];
}

// 4. ACF
foreach (acf_get_field_groups() as $g) {
	$out['acf_groups'][] = ['key' => $g['key'], 'title' => $g['title'], 'active' => $g['active'], 'location' => $g['location'], 'fields' => count(acf_get_fields($g['key']) ?: [])];
}
$out['acf_options_pages'] = function_exists('acf_get_options_pages') ? array_keys((array) acf_get_options_pages()) : [];

// 5. Menus
foreach (wp_get_nav_menus() as $m) {
	$lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_nav_menu' AND element_id=%d", $m->term_taxonomy_id));
	$out['menus'][] = ['id' => $m->term_id, 'name' => $m->name, 'items' => $m->count, 'lang' => $lang];
}
$out['menu_locations'] = get_nav_menu_locations();

// 6. Formulaires Fluent Forms, grilles WPGB, TablePress
$out['fluent_forms'] = $wpdb->get_results("SELECT id, title, status FROM {$wpdb->prefix}fluentform_forms ORDER BY id", ARRAY_A);
$out['fluent_entries_last30'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fluentform_submissions WHERE created_at > NOW() - INTERVAL 30 DAY");
if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wpgb_grids'")) {
	$out['wpgb_grids'] = $wpdb->get_results("SELECT id, name, type, source FROM {$wpdb->prefix}wpgb_grids ORDER BY id", ARRAY_A);
	$out['wpgb_facets'] = $wpdb->get_results("SELECT id, name, type FROM {$wpdb->prefix}wpgb_facets ORDER BY id", ARRAY_A);
}
$out['tablepress'] = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='tablepress_table' AND post_status='publish'", ARRAY_A);

// 7. Réglages globaux utiles au thème
$out['settings'] = [
	'front_page' => (int) get_option('page_on_front'), 'posts_page' => (int) get_option('page_for_posts'), 'permalink' => get_option('permalink_structure'),
	'wpml_languages' => array_keys((array) apply_filters('wpml_active_languages', null, [])),
	'oxygen_global_colors' => get_option('oxygen_vsb_global_colors'), 'oxygen_global_settings' => get_option('ct_global_settings'),
	'universal_css_url' => get_option('oxygen_vsb_universal_css_url'),
	'mu_plugins' => array_keys(get_mu_plugins()),
];
echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
