<?php
/**
 * Coexistence avec Oxygen sur le staging : le thème rend toutes les pages publiques et retire alors les sorties
 * d'Oxygen, d'OxyExtras et les scripts front d'Advanced Scripts qu'il rejoue lui-même ; ?oxygen=1 laisse Oxygen
 * rendre la page (comparaison). Sans Oxygen ni Advanced Scripts actifs, ce fichier ne fait plus rien.
 * À supprimer avec Oxygen.
 */

defined('ABSPATH') || exit;

function sx_theme_owns_request(): bool
{
	static $owns = null;
	if ($owns !== null) {
		return $owns;
	}
	if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || defined('SHOW_CT_BUILDER')
		|| isset($_GET['ct_builder']) || isset($_GET['oxygen']) || is_feed() || is_embed()) {
		return $owns = false;
	}
	return $owns = true;
}

add_filter('template_include', static function ($template) {
	if (!sx_theme_owns_request()) {
		return $template;
	}
	sx_unhook_oxygen();
	return get_theme_file_path('index.php');
}, 1000);

function sx_unhook_oxygen(): void
{
	if (function_exists('ct_enqueue_scripts')) {
		remove_action('wp_enqueue_scripts', 'ct_enqueue_scripts');
		remove_action('wp_head', 'oxy_print_cached_css', 999999);
		remove_action('wp_head', 'ct_footer_styles_hook');
		remove_action('wp_head', 'oxygen_vsb_iframe_styles');
		remove_action('wp_footer', 'ct_footer_script_hook', 20);
		remove_filter('body_class', 'ct_body_class');
		if (isset($GLOBALS['oxygen_vsb_scripts'])) {
			remove_action('wp_footer', [$GLOBALS['oxygen_vsb_scripts'], 'frontend_scripts']);
			remove_action('wp_footer', [$GLOBALS['oxygen_vsb_scripts'], 'builder_scripts']);
		}
	}

	// Advanced Scripts : ses scripts JS/CSS front sont rejoués par le thème (assets/js/advanced-scripts.js,
	// assets/app.css, assets/site.css). Ses scripts PHP et HTML restent les siens.
	global $wp_filter;
	foreach (['wp_head', 'wp_footer', 'wp_enqueue_scripts', 'wp_body_open'] as $hook) {
		foreach ($wp_filter[$hook]->callbacks ?? [] as $priority => $callbacks) {
			foreach ($callbacks as $callback) {
				$object = is_array($callback['function']) ? $callback['function'][0] : null;
				if (is_object($object) && in_array(get_class($object), ['ERROPiX\\AdvancedScripts\\Processor\\JavaScript', 'ERROPiX\\AdvancedScripts\\Processor\\CSS'], true)) {
					remove_action($hook, $callback['function'], $priority);
				}
			}
		}
	}

	// Oxygen et ses extensions enfilent aussi des fichiers par handle : on les retire en bloc.
	$strip = static function () {
		$patterns = ['/plugins/oxygen/', '/plugins/oxy-ninja/', '/plugins/oxyextras/', '/plugins/oxy-toolbox/', '/plugins/wp-grid-builder-oxygen/',
			'/plugins/erropix-hydrogen-pack/', '/uploads/oxygen/', 'cdnjs.cloudflare.com/ajax/libs/gsap', 'unpkg.com/gsap', 'glightbox', '/wp-content/scripts/'];
		foreach (['wp_styles', 'wp_scripts'] as $global) {
			$registry = $GLOBALS[$global] ?? null;
			if (!$registry) {
				continue;
			}
			foreach ($registry->queue as $handle) {
				if (str_starts_with($handle, 'sidex')) {
					continue;
				}
				$src = (string) ($registry->registered[$handle]->src ?? '');
				foreach ($patterns as $pattern) {
					if (str_contains($src, $pattern) || str_starts_with($handle, 'oxygen')) {
						$global === 'wp_styles' ? wp_dequeue_style($handle) : wp_dequeue_script($handle);
						break;
					}
				}
			}
		}
	};
	add_action('wp_enqueue_scripts', $strip, 9999);
	add_action('wp_print_styles', $strip, 9999);
	add_action('wp_print_footer_scripts', $strip, 1);
}
