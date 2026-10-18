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

/* Heebo auto-hébergée (police variable 100–900, sous-ensembles latin et latin étendu de Google Fonts, licence OFL :
   assets/fonts/OFL.txt) : plus de feuille ni de connexion à fonts.googleapis.com / fonts.gstatic.com avant le texte,
   et plus d'adresse IP des visiteurs envoyée à Google. Le latin (accents français compris) est préchargé ;
   le latin étendu ne se télécharge que si la page en contient un caractère. display=swap : texte affiché tout de suite. */
add_action('wp_head', static function () {
	if (!sx_theme_owns_request()) {
		return;
	}
	$font = static fn(string $file) => esc_url(sx_asset_url('fonts/' . $file) . '?ver=' . sx_asset_version('fonts/' . $file));
	echo '<link rel="preload" href="' . $font('heebo-latin.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo "<style id=\"sidex-heebo\">\n"
		. "@font-face{font-family:'Heebo';font-style:normal;font-weight:100 900;font-display:swap;src:url(" . $font('heebo-latin-ext.woff2') . ") format('woff2');"
		. 'unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C4,U+2113,U+2C60-2C7F,U+A720-A7FF}' . "\n"
		. "@font-face{font-family:'Heebo';font-style:normal;font-weight:100 900;font-display:swap;src:url(" . $font('heebo-latin.woff2') . ") format('woff2');"
		. 'unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}' . "\n"
		. "</style>\n";
}, 2);

/* reCAPTCHA de Fluent Forms (≈ 380 Ko de Google, sur chaque page : le formulaire de soumission est dans le panneau
   du gabarit 69) : chargé seulement quand un formulaire qui en a un approche de l'écran (panneau ouvert, formulaire
   de page) ou reçoit le doigt, le clic ou le focus. Le widget est alors dessiné par Fluent Forms (reinitExtras), comme
   à l'initialisation. Code : assets/js/recaptcha-lazy.js, imprimé en ligne (pas de requête de plus). */
add_filter('script_loader_tag', static function (string $tag, string $handle, string $src) {
	if ($handle !== 'google-recaptcha' || !sx_theme_owns_request() || is_admin()) {
		return $tag;
	}
	$src = add_query_arg('onload', 'sxRecaptchaReady', html_entity_decode($src));
	$js = (string) file_get_contents(SIDEX_DIR . '/assets/js/recaptcha-lazy.js');
	$lazy = '<script id="google-recaptcha-js" data-src="' . esc_attr($src) . '">' . "\n" . $js . '</script>';
	// Seule la balise du fichier est remplacée (scripts en ligne « before/after » d'une extension conservés).
	$out = preg_replace_callback('#<script\b[^>]*\bid=["\']google-recaptcha-js["\'][^>]*></script>#', static fn() => $lazy, $tag, 1, $done);
	return $done && is_string($out) ? $out : $tag;
}, 10, 3);

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
