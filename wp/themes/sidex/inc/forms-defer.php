<?php
/**
 * Fluent Forms : reCAPTCHA et bibliothèques de champs chargés à la demande.
 *
 * Fluent Forms enfile sur chaque page qui contient un formulaire (le formulaire de soumission est dans le panneau du
 * gabarit 69, donc partout) : l'API reCAPTCHA de Google (≈ 1,3 s de JavaScript sur mobile), flatpickr (date),
 * jquery.mask (masques) et jQuery File Upload (fichiers). On les retire de la file juste avant l'impression des scripts
 * du pied de page et assets/js/forms-defer.js (imprimé en ligne) les charge quand un formulaire approche de l'écran
 * (≈ 400 px) ou reçoit le focus, le doigt ou le clic. Chaque bibliothèque est ensuite branchée par le chemin de
 * Fluent Forms lui-même :
 *  - reCAPTCHA v2 (render=explicit) : onload → fluentFormApp(form).reinitExtras() dessine le widget ;
 *  - flatpickr : événement « reInitExtras » sur le formulaire, écouté par le script en ligne de chaque champ date ;
 *  - jQuery File Upload : événement « fluentform_reset », qui relance l'initialisation des champs fichier ;
 *  - jquery.mask : même réglage que initMask() de form-submission.js (aucun champ masqué sur le site au 18 oct. 2026).
 * Un script n'est pas différé s'il porte du code en ligne ou si un autre script en file en dépend.
 */

defined('ABSPATH') || exit;

/** Scripts différés => groupe chargé ensemble (l'ordre du groupe est l'ordre d'exécution). */
const SX_FORMS_DEFER = [
	'google-recaptcha' => 'recaptcha',
	'flatpickr' => 'flatpickr',
	'jquery-mask' => 'mask',
	'fluentform-uploader-jquery-ui-widget' => 'upload',
	'fluentform-uploader-iframe-transport' => 'upload',
	'fluentform-uploader' => 'upload',
];

/** Groupes retirés de la file pour cette page : groupe => [URL, …]. */
function sx_forms_deferred(?array $set = null): array
{
	static $groups = [];
	if ($set !== null) {
		$groups = $set;
	}
	return $groups;
}

// Avant _wp_footer_scripts (priorité 10) : les scripts de Fluent Forms sont enfilés pendant le rendu du formulaire.
add_action('wp_print_footer_scripts', static function () {
	if (!sx_theme_owns_request()) {
		return;
	}
	$wp = wp_scripts();
	$queued = static fn(string $h) => in_array($h, $wp->queue, true) && !in_array($h, $wp->done, true);
	$candidates = array_filter(array_keys(SX_FORMS_DEFER), $queued);
	if (!$candidates) {
		return;
	}

	// Dépendances de tout ce qui reste en file (un script retiré mais requis serait réimprimé par WordPress).
	$needed = [];
	$walk = static function (string $h) use (&$walk, &$needed, $wp) {
		foreach ($wp->registered[$h]->deps ?? [] as $dep) {
			if (!isset($needed[$dep])) {
				$needed[$dep] = true;
				$walk($dep);
			}
		}
	};
	foreach ($wp->queue as $h) {
		if (!isset(SX_FORMS_DEFER[$h])) {
			$walk($h);
		}
	}

	$groups = [];
	foreach ($candidates as $h) {
		$dep = $wp->registered[$h] ?? null;
		$inline = $dep && ($wp->get_data($h, 'data') || $wp->get_data($h, 'before') || $wp->get_data($h, 'after'));
		if (!$dep || !$dep->src || $inline || isset($needed[$h])) {
			continue;
		}
		$src = html_entity_decode($dep->src);
		if ($dep->ver) {
			$src = add_query_arg('ver', $dep->ver, $src);
		}
		if ($h === 'google-recaptcha') {
			// Seule la version v2 (widget dessiné par Fluent Forms) est prise en charge ; la v3 garde le chargement normal.
			if (strpos($src, 'render=explicit') === false) {
				continue;
			}
			$src = add_query_arg('onload', 'sxFormsRecaptchaReady', $src);
		}
		$groups[SX_FORMS_DEFER[$h]][] = $src;
		wp_dequeue_script($h);
	}
	// Envoi de fichiers : les trois scripts vont ensemble ; si l'un reste en file, aucun n'est différé.
	$upload = array_keys(SX_FORMS_DEFER, 'upload', true);
	if (isset($groups['upload']) && array_filter($upload, $queued)) {
		unset($groups['upload']);
		foreach ($upload as $h) {
			if (isset($wp->registered[$h])) {
				wp_enqueue_script($h);
			}
		}
	}
	sx_forms_deferred($groups);
}, 1);

// Après _wp_footer_scripts : Fluent Forms (form-submission.js) est déjà chargé.
add_action('wp_print_footer_scripts', static function () {
	$groups = sx_forms_deferred();
	if (!$groups) {
		return;
	}
	$js = (string) file_get_contents(SIDEX_DIR . '/assets/js/forms-defer.js');
	echo '<script id="sidex-forms-defer">' . "\n"
		. 'window.sxFormsDefer = ' . wp_json_encode($groups, JSON_UNESCAPED_SLASHES) . ";\n"
		. $js . "</script>\n";
}, 11);
