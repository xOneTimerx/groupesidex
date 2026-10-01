<?php
/** Terme types_page de chaque page publiée (wp eval-file page_terms.php). Lecture seule. */
global $wpdb;
$rows = $wpdb->get_results("SELECT p.ID, tt.term_id FROM {$wpdb->posts} p
	JOIN {$wpdb->term_relationships} r ON r.object_id = p.ID
	JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = r.term_taxonomy_id AND tt.taxonomy = 'types_page'
	WHERE p.post_type = 'page' AND p.post_status IN ('publish','draft')");
$out = [];
foreach ($rows as $r) { $out[$r->ID][] = (int) $r->term_id; }
$terms = [];
foreach (get_terms(['taxonomy' => 'types_page', 'hide_empty' => false]) as $t) { $terms[$t->term_id] = $t->name; }
echo wp_json_encode(['pages' => $out, 'terms' => $terms, 'drafts' => $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='page' AND post_status='draft'")]);
