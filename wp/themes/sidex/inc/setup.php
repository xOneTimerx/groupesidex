<?php

defined('ABSPATH') || exit;

add_action('after_setup_theme', static function () {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
});

/**
 * Menu WordPress par ID (les IDs FR des éléments Oxygen ; WPML fournit l'équivalent anglais),
 * avec les mêmes classes qu'Oxygen/OxyExtras : conteneur « menu-<slug>-container », liste « menu-<slug> ».
 */
function sx_menu(int $id, string $ul_class, bool $container = true): void
{
	$menu = (int) apply_filters('wpml_object_id', $id, 'nav_menu', true);
	wp_nav_menu([
		'menu' => $menu,
		'menu_class' => $ul_class,
		'container' => $container ? 'div' : false,
		'fallback_cb' => false,
		'depth' => 0,
	]);
}
