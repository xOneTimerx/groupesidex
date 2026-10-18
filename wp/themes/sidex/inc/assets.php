<?php
/**
 * Feuilles et scripts du thème, dans l'ordre où Oxygen les imprimait :
 * app.css (base Oxygen + extensions) → CSS des gabarits (o-<id>.css, priorité 30) → site.css (universal.css).
 * Les extensions conservées (Fluent Forms, WP Grid Builder, CleanTalk, WPML, TablePress) enfilent les leurs.
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', static function () {
	if (!sx_theme_owns_request()) {
		return;
	}
	// display=swap : le texte s'affiche tout de suite dans la police de repli (premier affichage plus rapide sur mobile).
	wp_enqueue_style('sidex-heebo', 'https://fonts.googleapis.com/css?family=Heebo:100,200,300,400,500,600,700,800,900&display=swap', [], null);
	wp_enqueue_style('sidex', sx_asset_url('app.css'), [], sx_asset_version('app.css'));
	add_action('wp_enqueue_scripts', static function () {
		wp_enqueue_style('sidex-site', sx_asset_url('site.css'), ['sidex'], sx_asset_version('site.css'));
	}, 40);

	$js = static function (string $handle, string $file, array $deps = []) {
		wp_enqueue_script($handle, sx_asset_url($file), $deps, sx_asset_version($file), ['in_footer' => true]);
	};
	// Splide et TextPlugin (GSAP) retirés : aucun élément ni script du site ne les utilisait (audit du 16 oct. 2026).
	$js('sidex-aos', 'vendor/aos.js');
	$js('sidex-external-links', 'vendor/oxy-toolbox-external-links.js', ['jquery']);
	$js('sidex-gsap', 'vendor/gsap.min.js');
	$js('sidex-gsap-scrolltrigger', 'vendor/ScrollTrigger.min.js', ['sidex-gsap']);
	$js('sidex', 'js/oxygen-inline.js', ['jquery', 'sidex-aos']);
	$js('sidex-advanced-scripts', 'js/advanced-scripts.js', ['jquery', 'sidex-gsap', 'sidex-gsap-scrolltrigger', 'sidex']);
	$js('sidex-a11y', 'js/a11y.js');
}, 20);

// Préconnexion à Google Fonts (la feuille Heebo est demandée dès <head>).
add_filter('wp_resource_hints', static function (array $urls, string $relation) {
	if ($relation === 'preconnect' && sx_theme_owns_request()) {
		$urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin'];
	}
	return $urls;
}, 10, 2);

/** Bibliothèque d'un élément d'extension (OxyExtras, galerie Oxygen), chargée seulement par les gabarits qui l'utilisent. */
function sx_lib(string $lib): void
{
	$js = static function (string $handle, string $file, array $deps = ['jquery']) {
		wp_enqueue_script($handle, sx_asset_url($file), $deps, sx_asset_version($file), ['in_footer' => true]);
	};
	switch ($lib) {
		case 'flickity':
			$js('sidex-flickity', 'vendor/flickity.pkgd.min.js', []);
			$js('sidex-flickity-init', 'vendor/flickity-init-4.js', ['jquery', 'sidex-flickity']);
			break;
		case 'offcanvas':
			$js('sidex-inert', 'vendor/inert.js', []);
			$js('sidex-offcanvas', 'vendor/offcanvas-init.js', ['jquery', 'sidex-inert']);
			break;
		case 'mmenu':
			$js('sidex-mmenu', 'vendor/mmenu.js', []);
			break;
		case 'photoswipe':
			wp_enqueue_style('sidex-photoswipe', sx_asset_url('vendor/photoswipe.css'), [], sx_asset_version('vendor/photoswipe.css'));
			wp_enqueue_style('sidex-photoswipe-skin', sx_asset_url('vendor/photoswipe-default-skin/default-skin.css'), ['sidex-photoswipe'], sx_asset_version('vendor/photoswipe-default-skin/default-skin.css'));
			$js('sidex-photoswipe', 'vendor/jquery.photoswipe-global.js');
			break;
		case 'tocbot':
			$js('sidex-tocbot', 'vendor/tocbot.min.js', []);
			$js('sidex-tocbot-init', 'vendor/tocbot-init.js', ['jquery', 'sidex-tocbot']);
			break;
		case 'fancybox':
			$js('sidex-fancybox', 'vendor/fancybox.min.js');
			$js('sidex-fancybox-init', 'vendor/fancybox-init-4.js', ['jquery', 'sidex-fancybox']);
			wp_localize_script('sidex-fancybox-init', 'localize_extras_plugin', ['oxygen_directory' => content_url('uploads/oxygen/css/')]);
			break;
		case 'skeletabs':
			$js('sidex-skeletabs', 'vendor/skeletabs.js');
			break;
		case 'glightbox':
			// Advanced Scripts 143/144/248 : nuanciers des revêtements (classe glightbox-couleurs du contenu ACF).
			wp_enqueue_style('sidex-glightbox', sx_asset_url('vendor/glightbox.min.css'), [], sx_asset_version('vendor/glightbox.min.css'));
			$js('sidex-glightbox', 'vendor/glightbox.js', []);
			break;
		case 'extras-wpgb':
			$js('sidex-extras-wpgb', 'vendor/gridbuildersupport.js');
			break;
	}
}
