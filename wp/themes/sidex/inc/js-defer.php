<?php
/**
 * JavaScript hors du chemin du LCP (suite de forms-defer.php).
 *
 * 1. Diaporama de l'accueil : GSAP (≈ 70 Ko), TextPlugin (compteur du héros) et o-1688.js partaient avec le reste des
 *    scripts du pied de page, contre l'image du héros. La 1re diapositive est visible sans eux (les suivantes sont
 *    visibility:hidden en CSS, xPercent à 0 par défaut) : ils se chargent après l'événement « load », au repos du
 *    navigateur (requestIdleCallback), dans l'ordre. o-1688.js démarre tout de suite si DOMContentLoaded est passé.
 * 2. WP Grid Builder : polyfills.js (≈ 18 Ko : Array.includes/find/fill, Element.closest, etc.) ne sert qu'à des
 *    navigateurs d'avant 2017, hors cible. Le fichier est retiré, mais la poignée reste (layout.js et lightbox.js en
 *    dépendent) avec son code « before » : c'est lui qui lance l'initialisation des grilles (« wpgb.loaded »).
 */

defined('ABSPATH') || exit;

/** Scripts de l'accueil chargés après le LCP, dans l'ordre d'exécution. */
const SX_AFTER_LCP = ['sidex-gsap', 'sidex-gsap-text', 'sidex-o-1688'];

// Avant _wp_footer_scripts (priorité 10) : les scripts du gabarit sont déjà en file (sx_oxy_preload, wp_enqueue_scripts).
add_action('wp_print_footer_scripts', static function () {
	if (!sx_theme_owns_request()) {
		return;
	}
	$wp = wp_scripts();
	$urls = [];
	foreach (SX_AFTER_LCP as $h) {
		$dep = $wp->registered[$h] ?? null;
		// sidex-gsap n'est qu'une dépendance : enfilé par ses dépendants, il disparaît avec eux.
		if (!$dep || !$dep->src || in_array($h, $wp->done, true) || $h !== 'sidex-gsap' && !in_array($h, $wp->queue, true)) {
			return; // diaporama absent de cette page : rien à différer
		}
		$urls[$h] = add_query_arg('ver', $dep->ver, $dep->src);
	}
	// Un script du pied de page qui en dépendrait (hors la liste) le réimprimerait : on s'abstient.
	foreach ($wp->queue as $h) {
		if (!in_array($h, SX_AFTER_LCP, true) && array_intersect(SX_AFTER_LCP, $wp->registered[$h]->deps ?? [])) {
			return;
		}
	}
	foreach (SX_AFTER_LCP as $h) {
		wp_dequeue_script($h);
	}
	$GLOBALS['sx_after_lcp'] = array_values($urls);
}, 1);

add_action('wp_print_footer_scripts', static function () {
	if (empty($GLOBALS['sx_after_lcp'])) {
		return;
	}
	echo '<script id="sidex-after-lcp">(function(u){function go(){var i=0;(function n(){if(i>=u.length)return;var s=document.createElement("script");'
		. 's.src=u[i++];s.async=false;s.onload=s.onerror=n;document.body.appendChild(s)})()}'
		. 'function idle(){window.requestIdleCallback?requestIdleCallback(go,{timeout:2000}):setTimeout(go,200)}'
		. 'document.readyState==="complete"?idle():addEventListener("load",idle)})(' . wp_json_encode($GLOBALS['sx_after_lcp'], JSON_UNESCAPED_SLASHES) . ")</script>\n";
}, 11);

// WP Grid Builder enfile ses scripts au pied de page (wp_footer, priorité 11) ; on neutralise juste avant leur impression.
add_action('wp_print_footer_scripts', static function () {
	$wp = wp_scripts();
	if (sx_theme_owns_request() && isset($wp->registered['wpgb-polyfills'])) {
		$wp->registered['wpgb-polyfills']->src = false;
	}
}, 1);
