<?php
/**
 * Retouches du HTML final (audit du 16 oct. 2026) : performance du premier affichage et accessibilité.
 *
 * Appliquées sur la page entière plutôt que dans chaque gabarit converti : elles visent aussi le contenu ACF
 * et les extensions. Aucune ne change la mise en page : attributs seulement (dimensions, chargement différé,
 * priorité, noms accessibles, rôles), plus une balise <link rel="preload"> dans <head>.
 * sx_html_filter() est pure (HTML → HTML) : testable hors WordPress (wp/tools/tests/html-filter-test.php).
 */

defined('ABSPATH') || exit;

add_action('template_redirect', static function () {
	if (!sx_theme_owns_request() || is_feed() || is_robots() || isset($_GET['format'])) {
		return;
	}
	ob_start(static function (string $html): string {
		$out = sx_html_filter($html, [
			'home' => home_url('/'),
			'site' => get_bloginfo('name'),
			'lang' => sx_lang(),
			'uploads' => wp_get_upload_dir(),
			'srcset' => 'sx_upload_srcset',
		], $errors);
		// Une étape a échoué (HTML d'origine conservé pour elle) : une seule ligne de journal par jour, pas par requête.
		if ($errors && !get_transient('sx_html_filter_error')) {
			set_transient('sx_html_filter_error', 1, DAY_IN_SECONDS);
			error_log('sidex inc/html.php : étape ignorée (' . implode(', ', $errors) . ') sur ' . ($_SERVER['REQUEST_URI'] ?? '?'));
		}
		return $out;
	});
}, 1000);

/**
 * @param array{home:string,site:string,lang:string,uploads:array{baseurl:string,basedir:string},srcset?:callable(string):?string} $ctx
 */
function sx_html_filter(string $html, array $ctx, ?array &$errors = null): string
{
	$errors = [];
	if (stripos($html, '<html') === false) {
		return $html;
	}
	$steps = [
		'images' => static fn(string $h) => sx_html_images($h, $ctx),
		'backgrounds' => static fn(string $h) => sx_html_hero_backgrounds($h),
		'variants' => static fn(string $h) => sx_html_variants($h, $ctx),
		'unused' => static fn(string $h) => sx_html_unused_assets($h),
		'links' => static fn(string $h) => sx_html_links($h, $ctx),
		'triggers' => static fn(string $h) => sx_html_triggers($h, $ctx),
		// Icônes du sprite : décoratives (le nom vient du lien ou du bouton qui les porte).
		'icons' => static fn(string $h) => preg_replace('/<svg id="svg-(?![^>]*aria-hidden)/', '<svg aria-hidden="true" focusable="false" id="svg-', $h),
		// Jumeau Markdown : URL absolue (les agents la lisent hors contexte de page).
		'markdown' => static fn(string $h) => preg_replace('#(<link\b[^>]*type="text/markdown"[^>]*\bhref=")/#',
			'$1' . preg_replace('#^(https?://[^/]+).*$#', '$1', (string) $ctx['home']) . '/', $h),
	];
	// Garde-fous : preg_* renvoie null sur erreur PCRE (limite de backtracking, pile JIT) ; une étape qui échoue ou lève
	// une exception est ignorée et le HTML de l'étape précédente est gardé. Jamais de page blanche.
	try {
		foreach ($steps as $name => $step) {
			try {
				$next = $step($html);
			} catch (Throwable $e) {
				$next = null;
			}
			if (is_string($next) && $next !== '') {
				$html = $next;
			} else {
				$errors[] = $name;
			}
		}
	} catch (Throwable $e) {
		$errors[] = 'filter';
	}
	return $html;
}

/**
 * Images : la première image du premier écran (fond de la première section ou première <img>) est préchargée en
 * priorité haute ; les <img> des téléversements sans dimensions reçoivent width/height (ratio réservé) ; les <img>
 * dimensionnées plus bas sont différées (loading="lazy") ; ces images différées, si elles n'ont pas de srcset,
 * reçoivent les tailles intermédiaires de WordPress ($ctx['srcset']) avec sizes="auto, 100vw" : le navigateur choisit
 * selon la largeur réellement affichée (le mobile ne télécharge plus l'original de 1200 à 1700 px pour une vignette).
 */
function sx_html_images(string $html, array $ctx): ?string
{
	$main = strpos($html, '<main');
	if ($main === false) {
		return $html;
	}
	$first = strpos($html, '<section', $main);
	$fold = $first === false ? $main : (strpos($html, '</section>', $first) ?: $main);
	$end = strpos($html, '</main>', $main) ?: strlen($html);
	$second = strpos($html, '</section>', $fold + 10);
	$fold2 = $second !== false && $second < $end ? $second : $end;

	// Candidat LCP : fond de la première section, sinon première <img> matricielle des deux premières sections.
	$lcp_img = null;
	$hero = $first !== false ? substr($html, $first, $fold - $first) : '';
	if (preg_match('/background-image:[^;"]*url\(([^)]+\.(?:webp|jpe?g|png|avif))\)/i', $hero, $m)) {
		$url = html_entity_decode(trim($m[1], '\'" '));
		// L'élément qui porte ce fond est exclu du chargement différé de WP Rocket (sinon le fond, élément LCP, passe
		// en data-bg et n'est appliqué qu'après son script : mesuré sur les pages GEO).
		if (preg_match('/<[a-z][a-z0-9]*\b(?=[^>]*background-image:[^;"]*url\()(?![^>]*data-no-lazy)/i', $hero, $tag, PREG_OFFSET_CAPTURE)) {
			$at = $first + $tag[0][1] + strlen($tag[0][0]);
			$html = substr($html, 0, $at) . ' data-no-lazy="1"' . substr($html, $at);
			$fold += 17;
		}
		$preload = '<link rel="preload" as="image" href="' . htmlspecialchars($url, ENT_QUOTES) . '" fetchpriority="high">';
		$with = preg_replace('/<meta charset="[^"]*"\s*\/?>/i', '$0' . "\n" . $preload, $html, 1, $done);
		if (!is_string($with)) {
			return null;
		}
		if ($done) {
			$html = $with;
			$offset = strlen($preload) + 1;
			$main += $offset;
			$fold += $offset;
		}
	} elseif (preg_match('/<img\b(?![^>]*\.svg)[^>]*>/i', substr($html, $main, $fold2 - $main), $m, PREG_OFFSET_CAPTURE)) {
		$lcp_img = $main + $m[0][1];
	}

	return preg_replace_callback('/<img\b[^>]*>/i', static function (array $m) use ($ctx, $main, $fold, $lcp_img) {
		[$tag, $pos] = $m[0];
		$add = '';
		if (!preg_match('/\swidth=/i', $tag) && !preg_match('/\ssrcset=/i', $tag) && ($size = sx_html_upload_size($tag, $ctx))) {
			$add .= ' width="' . $size[0] . '" height="' . $size[1] . '"';
		}
		if ($pos === $lcp_img) {
			if (!str_contains($tag, 'fetchpriority=')) {
				$add .= ' fetchpriority="high"';
			}
			$tag = preg_replace('/\sloading="lazy"/i', '', $tag);
			$add .= str_contains($tag, 'data-no-lazy') ? '' : ' data-no-lazy="1"';
		} elseif ($pos > $fold && preg_match('/\swidth=/i', $tag . $add) && !preg_match('/\sloading=|ignore-lazyload|data-no-lazy/i', $tag)) {
			// Seulement les images dimensionnées : une image différée sans ratio ferait bouger la page (et les révélations de titres).
			$add .= ' loading="lazy"';
		}
		if ($pos > $main && !preg_match('/\sdecoding=/i', $tag)) {
			$add .= ' decoding="async"';
		}
		// srcset seulement sur les images différées : sizes="auto" (largeur réellement affichée) n'est valable qu'avec
		// loading="lazy" ; une image du premier écran garderait sizes="100vw" et demanderait une source trop grande.
		if (isset($ctx['srcset']) && preg_match('/\sloading="lazy"/i', $tag . $add) && !preg_match('/\ssrcset=/i', $tag) && preg_match('/\ssrc="([^"]+)"/i', $tag, $src)
			&& ($set = ($ctx['srcset'])(html_entity_decode($src[1])))) {
			$add .= ' srcset="' . htmlspecialchars($set, ENT_QUOTES) . '"' . (preg_match('/\ssizes=/i', $tag) ? '' : ' sizes="auto, 100vw"');
		}
		return $add === '' ? $tag : preg_replace('/\s*\/?>$/', $add . '$0', $tag, 1);
	}, $html, -1, $count, PREG_OFFSET_CAPTURE);
}

/** Dimensions intrinsèques d'une image des téléversements (fichier local), sinon null. */
function sx_html_upload_size(string $tag, array $ctx): ?array
{
	static $cache = [];
	if (!preg_match('/\ssrc="([^"]+)"/i', $tag, $m) || preg_match('/\.svg(\?|$)/i', $m[1])) {
		return null;
	}
	$path = parse_url(html_entity_decode($m[1]), PHP_URL_PATH) ?: '';
	$base = parse_url($ctx['uploads']['baseurl'], PHP_URL_PATH) ?: '/wp-content/uploads';
	if (!str_starts_with($path, $base . '/')) {
		return null;
	}
	$file = $ctx['uploads']['basedir'] . substr(rawurldecode($path), strlen($base));
	if (!array_key_exists($file, $cache)) {
		$info = is_file($file) ? @getimagesize($file) : false;
		$cache[$file] = $info && $info[0] > 0 && $info[1] > 0 ? [(int) $info[0], (int) $info[1]] : null;
	}
	return $cache[$file];
}

/**
 * srcset d'une image des téléversements (tailles intermédiaires de WordPress), sinon null. GIF exclus : leurs tailles
 * intermédiaires ne sont pas animées.
 */
function sx_upload_srcset(string $url): ?string
{
	static $cache = [];
	if (preg_match('/\.(svg|gif)(\?|$)/i', $url)) {
		return null;
	}
	if (!array_key_exists($url, $cache)) {
		$abs = str_starts_with($url, '/') ? preg_replace('#^(https?://[^/]+).*$#', '$1', wp_get_upload_dir()['baseurl']) . $url : $url;
		$id = attachment_url_to_postid($abs);
		$meta = $id ? wp_get_attachment_metadata($id) : false;
		$set = null;
		if (is_array($meta) && !empty($meta['width']) && !empty($meta['height'])) {
			$size = [(int) $meta['width'], (int) $meta['height']];
			$set = wp_calculate_image_srcset($size, $abs, $meta, $id) ?: null;
		}
		$cache[$url] = $set;
	}
	return $cache[$url];
}

/**
 * Diaporama du premier écran (accueil : 4 diapositives en image de fond) : seule la première image se charge tout de
 * suite (c'est l'élément LCP, préchargé) ; les suivantes passent en data-sx-bg et un petit script les remet au
 * chargement de la page, au plus tard 4 s après (la 2e diapositive arrive à 7,5 s). Sur mobile, ≈ 400 Ko de moins en
 * concurrence avec l'image LCP.
 */
function sx_html_hero_backgrounds(string $html): ?string
{
	$main = strpos($html, '<main');
	$first = $main === false ? false : strpos($html, '<section', $main);
	$end = $first === false ? false : strpos($html, '</section>', $first);
	if ($end === false) {
		return $html;
	}
	$seen = 0;
	$hero = preg_replace_callback('/<([a-z][a-z0-9]*)\b([^>]*?)\sstyle="([^"]*background-image:[^";]*url\([^)]+\)[^"]*)"([^>]*)>/i',
		static function (array $m) use (&$seen) {
			if ($seen++ === 0 || str_contains($m[0], 'data-sx-bg') || !preg_match('/background-image:([^;"]*);?/i', $m[3], $bg)) {
				return $m[0];
			}
			$style = trim(str_replace($bg[0], '', $m[3]), '; ');
			return '<' . $m[1] . $m[2] . ($style !== '' ? ' style="' . $style . '"' : '') . ' data-sx-bg="' . trim($bg[1]) . '"' . $m[4] . '>';
		}, substr($html, $first, $end - $first));
	if (!is_string($hero)) {
		return null;
	}
	if ($seen < 2) {
		return $html;
	}
	$script = '<script id="sidex-hero-bg">(function(){var done=false;function show(){if(done)return;done=true;'
		. 'document.querySelectorAll("[data-sx-bg]").forEach(function(el){el.style.backgroundImage=el.getAttribute("data-sx-bg");el.removeAttribute("data-sx-bg");'
		// Variante WebP (sx_html_variants) : image-set() ignoré par un navigateur qui ne le connaît pas, l'original reste.
		. 'var w=el.getAttribute("data-sx-bg-webp");if(w){el.style.backgroundImage=w;el.removeAttribute("data-sx-bg-webp");}});}'
		. 'window.addEventListener("load",show);setTimeout(show,4000);})();</script>';
	$html = substr($html, 0, $first) . $hero . substr($html, $end);
	$body = strripos($html, '</body>');
	return $body === false ? $html . $script : substr($html, 0, $body) . $script . "\n" . substr($html, $body);
}

/**
 * Variantes légères générées À CÔTÉ des originaux par wp/scripts/images-variants.sh (les originaux et la base ne
 * changent jamais) : <fichier>.webp pour un JPEG/PNG ; <fichier>.mp4, .webm et .poster.webp pour un GIF animé.
 * Une variante n'est servie que si son fichier existe ; sinon l'original reste : rien ne casse s'il en manque une.
 * - <img> JPEG/PNG → <picture style="display:contents"><source type="image/webp" srcset="…">…</picture>. Un candidat du
 *   srcset sans variante garde son URL d'origine dans la source WebP (valide : le type ne sert qu'à choisir la source).
 *   display:contents laisse l'<img> à sa place dans la mise en page (flex, grille) ; aucune enveloppe sous un parent
 *   visé par un sélecteur enfant « > img » des feuilles d'Oxygen.
 * - fond style="background-image:url(…)" → seconde déclaration image-set(WebP, original) : un navigateur qui ne
 *   connaît pas image-set() garde la première. Fond différé du diaporama (data-sx-bg) : data-sx-bg-webp.
 * - préchargement de l'image LCP → la variante WebP, avec type="image/webp" (ignoré sans prise en charge).
 * - <img> GIF → <video autoplay muted loop playsinline> : même id, mêmes classes et dimensions (même cadrage), nom
 *   accessible repris de alt (role="img" + aria-label). Sources et image d'aperçu posées à l'approche de l'écran,
 *   comme le GIF qui était différé par WP Rocket (rien n'est chargé quand le bloc est masqué, sur mobile).
 */
function sx_html_variants(string $html, array $ctx): ?string
{
	$variant = static fn(string $url, string $suffix): ?string => sx_html_variant_url($url, $suffix, $ctx);
	$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES);
	$videos = 0;

	$html = preg_replace_callback('/<img\b[^>]*>/i', static function (array $m) use ($html, $variant, $esc, &$videos) {
		[$tag, $pos] = $m[0];
		if (!preg_match('/\ssrc="([^"]+)"/i', $tag, $src)) {
			return $tag;
		}
		$before = substr($html, max(0, $pos - 400), min(400, $pos));
		if (preg_match('/<(?:picture|source)\b[^>]*>\s*$/i', $before)
			|| preg_match('/<[a-z][^<>]*(?:--aspect-ratio|oxy-pricing-box-graphic|data-cta=)[^<>]*>\s*$/i', $before)) {
			return $tag;
		}
		$url = html_entity_decode($src[1]);

		if (preg_match('/\.gif(?:[?#]|$)/i', $url)) {
			if (!($mp4 = $variant($url, '.mp4'))) {
				return $tag;
			}
			$webm = $variant($url, '.webm');
			$poster = $variant($url, '.poster.webp');
			$alt = preg_match('/\salt="([^"]*)"/i', $tag, $a) ? $a[1] : '';
			$attrs = preg_replace(['/\s(?:src|srcset|sizes|alt|loading|decoding|fetchpriority)="[^"]*"/i', '/\s*\/?>$/'], '', substr($tag, 4));
			$videos++;
			return '<video' . $attrs . (trim($alt) !== '' ? ' role="img" aria-label="' . $alt . '"' : ' aria-hidden="true"')
				. ' autoplay muted loop playsinline disablepictureinpicture preload="none" data-sx-video'
				. ($poster ? ' data-poster="' . $esc($poster) . '"' : '') . '>'
				. ($webm ? '<source data-src="' . $esc($webm) . '" type="video/webm">' : '')
				. '<source data-src="' . $esc($mp4) . '" type="video/mp4"></video>';
		}

		if (!preg_match('/\.(?:jpe?g|png)(?:[?#]|$)/i', $url)) {
			return $tag;
		}
		$hit = false;
		$set = preg_match('/\ssrcset="([^"]+)"/i', $tag, $s) ? html_entity_decode($s[1]) : $url;
		$webp = array_map(static function (string $c) use ($variant, &$hit) {
			[$u, $d] = array_pad(preg_split('/\s+/', trim($c), 2), 2, '');
			$v = $variant($u, '.webp');
			$hit = $hit || $v !== null;
			return trim(($v ?? $u) . ' ' . $d);
		}, explode(',', $set));
		if (!$hit) {
			return $tag;
		}
		$sizes = preg_match('/\ssizes="[^"]*"/i', $tag, $z) ? $z[0] : '';
		return '<picture style="display:contents"><source type="image/webp" srcset="' . $esc(implode(', ', $webp)) . '"' . $sizes . '>' . $tag . '</picture>';
	}, $html, -1, $count, PREG_OFFSET_CAPTURE);
	if (!is_string($html)) {
		return null;
	}

	$html = preg_replace_callback('/\s(style|data-sx-bg)="([^"]*url\([^"]*)"/i', static function (array $m) use ($variant, $esc) {
		$style = strcasecmp($m[1], 'style') === 0;
		if ($style && !preg_match('/background-image:([^;]*)/i', $m[2], $bg)) {
			return $m[0];
		}
		$hit = false;
		$set = preg_replace_callback('/url\(([^)]+)\)/i', static function (array $u) use ($variant, $esc, &$hit) {
			$url = html_entity_decode(trim($u[1], '\'" '));
			if (!preg_match('/\.(jpe?g|png)(?:[?#]|$)/i', $url, $ext) || !($v = $variant($url, '.webp'))) {
				return $u[0];
			}
			$hit = true;
			return "image-set(url(" . $esc($v) . ") type('image/webp'), " . $u[0] . " type('image/" . (strtolower($ext[1]) === 'png' ? 'png' : 'jpeg') . "'))";
		}, $style ? $bg[1] : $m[2]);
		if (!$hit || !is_string($set)) {
			return $m[0];
		}
		return $style ? ' style="' . rtrim($m[2], '; ') . ';background-image:' . trim($set) . '"' : $m[0] . ' data-sx-bg-webp="' . trim($set) . '"';
	}, $html);
	if (!is_string($html)) {
		return null;
	}

	$html = preg_replace_callback('/<link rel="preload" as="image" href="([^"]+)"/i', static function (array $m) use ($variant, $esc) {
		$v = $variant(html_entity_decode($m[1]), '.webp');
		return $v ? '<link rel="preload" as="image" href="' . $esc($v) . '" type="image/webp"' : $m[0];
	}, $html, 1);
	if (!is_string($html) || $videos === 0) {
		return $html;
	}

	$script = '<script id="sidex-gif-video">(function(){var vs=document.querySelectorAll("video[data-sx-video]");'
		. 'function go(v){if(v.getAttribute("data-sx-on"))return;v.setAttribute("data-sx-on","1");var p=v.getAttribute("data-poster");if(p)v.poster=p;'
		. 'v.querySelectorAll("source[data-src]").forEach(function(s){s.src=s.getAttribute("data-src");});v.load();var r=v.play();if(r&&r.catch)r.catch(function(){});}'
		. 'if(!("IntersectionObserver" in window)){vs.forEach(go);return;}'
		. 'var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){io.unobserve(e.target);go(e.target);}});},{rootMargin:"300px"});'
		. 'vs.forEach(function(v){io.observe(v);});})();</script>';
	$body = strripos($html, '</body>');
	return $body === false ? $html . $script : substr($html, 0, $body) . $script . "\n" . substr($html, $body);
}

/** URL de la variante d'un fichier des téléversements (<url><suffixe>) si le fichier existe, sinon null. */
function sx_html_variant_url(string $url, string $suffix, array $ctx): ?string
{
	static $cache = [];
	$path = parse_url($url, PHP_URL_PATH) ?: '';
	$base = parse_url($ctx['uploads']['baseurl'], PHP_URL_PATH) ?: '/wp-content/uploads';
	if (!str_starts_with($path, $base . '/') || str_contains($path, '..')) {
		return null;
	}
	$file = $ctx['uploads']['basedir'] . substr(rawurldecode($path), strlen($base)) . $suffix;
	$cache[$file] ??= is_file($file) && filesize($file) > 0;
	return $cache[$file] ? preg_replace('/[?#].*$/', '', $url) . $suffix : null;
}

/**
 * Fichiers enfilés sur toutes les pages alors que seul un élément précis s'en sert : retirés quand la page n'a pas
 * cet élément (contenu ACF compris, d'où le contrôle sur le HTML final). TablePress : 4 articles seulement ;
 * AOS (14 Ko) : éléments data-aos ou lignes verticales (oxygen-inline.js ne l'appelle que s'il est là).
 */
function sx_html_unused_assets(string $html): ?string
{
	$body = stripos($html, '<body');
	if ($body === false) {
		return $html;
	}
	$page = substr($html, $body);
	if (stripos($page, 'tablepress') === false) {
		$html = preg_replace('#<link\b[^>]*\bid=[\'"]tablepress-default-css[\'"][^>]*>\s*#i', '', $html, 1);
	}
	if (is_string($html) && !preg_match('/\sdata-aos=|\bclass="[^"]*\bvertical-line\b/i', $page)) {
		$html = preg_replace('#<script\b[^>]*\bid=[\'"]sidex-aos-js[\'"][^>]*></script>\s*#i', '', $html, 1);
	}
	return $html;
}

/** Liens sans texte (logo à alt vide, icône seule) : nom accessible déduit de la cible. */
function sx_html_links(string $html, array $ctx): ?string
{
	$en = $ctx['lang'] === 'en';
	$home = rtrim($ctx['home'], '/');
	$social = ['facebook.com' => 'Facebook', 'instagram.com' => 'Instagram', 'linkedin.com' => 'LinkedIn', 'youtube.com' => 'YouTube',
		'pinterest.' => 'Pinterest', 'tiktok.com' => 'TikTok', 'x.com' => 'X', 'twitter.com' => 'X'];

	return preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/is', static function (array $m) use ($en, $home, $social, $ctx) {
		[$all, $attrs, $inner] = $m;
		if (preg_match('/\saria-label(ledby)?=/i', $attrs) || !preg_match('/\shref="([^"]*)"/i', $attrs, $h)) {
			return $all;
		}
		$alts = preg_match_all('/<img\b[^>]*\salt="([^"]+)"/i', $inner, $a) ? implode(' ', $a[1]) : '';
		if (trim(html_entity_decode(strip_tags($inner))) !== '' || trim($alts) !== '') {
			return $all;
		}
		$href = html_entity_decode($h[1]);
		$label = null;
		if (str_starts_with($href, 'tel:')) {
			$label = ($en ? 'Call ' : 'Appeler le ') . trim(substr($href, 4));
		} elseif (str_starts_with($href, 'mailto:')) {
			$label = ($en ? 'Email ' : 'Écrire à ') . trim(substr($href, 7));
		} elseif ($href === '' || $href === '/' || rtrim($href, '/') === $home || rtrim($href, '/') === $home . '/en') {
			$label = $ctx['site'] . ($en ? ' — home' : ' — accueil');
		} else {
			foreach ($social as $domain => $name) {
				if (preg_match('#^https?://([a-z0-9-]+\.)*' . preg_quote($domain, '#') . '#i', $href)) {
					$label = $name;
					break;
				}
			}
		}
		if ($label === null) {
			return $all;
		}
		if (str_contains($attrs, 'target="_blank"')) {
			$label .= $en ? ' (new tab)' : ' (nouvel onglet)';
		}
		return '<a aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '"' . $attrs . '>' . $inner . '</a>';
	}, $html);
}

/**
 * Déclencheurs des panneaux (menu, soumission) : des <div>/<span> cliquables (OxyExtras). On les rend
 * atteignables au clavier (role="button", tabindex) ; assets/js/a11y.js les active avec Entrée/Espace.
 * Icône seule : nom selon l'icône (menu ou fermer).
 */
function sx_html_triggers(string $html, array $ctx): ?string
{
	$en = $ctx['lang'] === 'en';
	return preg_replace_callback('/<(div|span)\b([^>]*\bclass="[^"]*\bmulti-trigger(?:-soumission)?\b[^"]*"[^>]*)>/i', static function (array $m) use ($html, $en) {
		[$tag, $name, $attrs] = $m;
		if (preg_match('/\srole=/i', $attrs)) {
			return $tag;
		}
		$add = ' role="button" tabindex="0"';
		// Icône seule (ct-fancy-icon) : le <svg> qui suit indique l'action (menu ou fermer).
		if (str_contains($attrs, 'ct-fancy-icon') && !preg_match('/\saria-label=/i', $attrs)
			&& preg_match('/^\s*<svg[^>]*>\s*<use xlink:href="#[^"]*-(menu|cross|close)"/i', substr($html, (int) strpos($html, $tag) + strlen($tag), 200), $icon)) {
			$add .= ' aria-label="' . ($icon[1] === 'menu' ? 'Menu' : ($en ? 'Close' : 'Fermer')) . '"';
		}
		return '<' . $name . $add . $attrs . '>';
	}, $html);
}
