<?php
/**
 * Plugin Name: Sidex — pont Oxygen / thème classique
 * Description: Oxygen détourne template_directory/stylesheet_directory vers son propre dossier, ce qui empêche
 *              tout thème de charger son functions.php. On retire ce détournement pour que le thème « sidex »
 *              rende les pages pendant la coexistence ; ?oxygen=1 affiche l'ancien rendu Oxygen pour comparer.
 *              À supprimer avec Oxygen.
 */

defined('ABSPATH') || exit;

add_action('plugins_loaded', static function () {
	if (!function_exists('ct_disable_theme_load')) {
		return;
	}
	remove_filter('template_directory', 'ct_disable_theme_load', 1);
	remove_filter('stylesheet_directory', 'ct_disable_theme_load', 1);
	remove_filter('template', 'ct_oxygen_template_name');
	remove_filter('stylesheet', 'ct_oxygen_template_name');
}, 1);
