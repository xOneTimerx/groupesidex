<?php
/**
 * Test local de wp/themes/sidex/inc/html.php (sans WordPress) : php wp/tools/tests/html-filter-test.php
 * Vérifie que le filtre agit sur une page normale, et que le HTML d'origine ressort intact quand ses étapes
 * échouent (erreur PCRE forcée, exception) : jamais de page blanche. Code 1 en cas d'échec.
 */
define('ABSPATH', __DIR__ . '/');
function add_action(...$args): void {}
function add_filter(...$args): void {}
require __DIR__ . '/../../themes/sidex/inc/html.php';

$fails = 0;
function check(string $name, bool $ok): void
{
	global $fails;
	echo ($ok ? '✓ ' : '✗ ') . $name . "\n";
	$fails += $ok ? 0 : 1;
}

$ctx = ['home' => 'https://www.groupesidex.com/', 'site' => 'Groupe Sidex', 'lang' => 'fr',
	'uploads' => ['baseurl' => 'https://www.groupesidex.com/wp-content/uploads', 'basedir' => __DIR__ . '/absent']];
$page = '<!doctype html><html><head><meta charset="UTF-8"><link rel="alternate" type="text/markdown" href="/?format=md" /></head><body>'
	. '<a id="logo" href="https://www.groupesidex.com/"><img alt="" src="/wp-content/uploads/logo.svg"></a>'
	. '<div id="fancy_icon-96-69" class="ct-fancy-icon multi-trigger"><svg id="svg-fancy_icon-96-69"><use xlink:href="#Lineariconsicon-menu"></use></svg></div>'
	. '<main id="main"><section style="background-image:url(https://www.groupesidex.com/wp-content/uploads/hero.webp)">Hero</section>'
	. str_repeat('<section><p>' . str_repeat('Revêtement de bois véritable. ', 40) . '</p><img src="/wp-content/uploads/projet.webp"></section>', 200)
	. '</main></body></html>';

// 1. Page normale : chaque étape agit, aucune erreur.
$out = sx_html_filter($page, $ctx, $errors);
check('page normale : aucune étape en échec', $errors === []);
check('page normale : préchargement du hero', str_contains($out, 'rel="preload" as="image"'));
check('page normale : fond du hero exclu du chargement différé de WP Rocket', str_contains($out, '<section data-no-lazy="1" style="background-image:url(https://www.groupesidex.com/wp-content/uploads/hero.webp)">'));
check('page normale : nom accessible du logo', str_contains($out, 'aria-label="Groupe Sidex — accueil"'));
check('page normale : déclencheur clavier', str_contains($out, 'role="button" tabindex="0" aria-label="Menu"'));
check('page normale : jumeau Markdown absolu', str_contains($out, 'href="https://www.groupesidex.com/?format=md"'));

// 1b. Diaporama du premier écran : seule la première image de fond part tout de suite, les suivantes sont rendues par script.
$slides = '<!doctype html><html><head><meta charset="UTF-8"></head><body><main id="main"><section>'
	. '<div class="slide__inner" style="background-image:linear-gradient(rgba(10,10,10,0.69), rgba(10,10,10,0.69)), url(https://www.groupesidex.com/wp-content/uploads/a.webp);background-size:auto,  cover;"></div>'
	. '<div class="slide__inner" style="background-image:linear-gradient(rgba(10,10,10,0.69), rgba(10,10,10,0.69)), url(https://www.groupesidex.com/wp-content/uploads/b.webp);background-size:auto,  cover;"></div>'
	. '</section><section style="background-image:url(https://www.groupesidex.com/wp-content/uploads/c.webp)"></section></main></body></html>';
$out = sx_html_filter($slides, $ctx, $errors);
check('diaporama : aucune étape en échec', $errors === []);
check('diaporama : 1re diapositive préchargée et gardée en fond', str_contains($out, 'rel="preload" as="image" href="https://www.groupesidex.com/wp-content/uploads/a.webp"')
	&& str_contains($out, 'style="background-image:linear-gradient(rgba(10,10,10,0.69), rgba(10,10,10,0.69)), url(https://www.groupesidex.com/wp-content/uploads/a.webp)'));
check('diaporama : 2e diapositive différée, reste du style conservé', str_contains($out,
	'style="background-size:auto,  cover" data-sx-bg="linear-gradient(rgba(10,10,10,0.69), rgba(10,10,10,0.69)), url(https://www.groupesidex.com/wp-content/uploads/b.webp)"'));
check('diaporama : sections suivantes intactes, script avant </body>', str_contains($out, '<section style="background-image:url(https://www.groupesidex.com/wp-content/uploads/c.webp)">')
	&& preg_match('#<script id="sidex-hero-bg">.*</script>\s*</body>#s', $out) === 1);
check('une seule image de fond : rien de différé', !str_contains(sx_html_filter($page, $ctx), 'data-sx-bg'));

// 1c. srcset des téléversements (WordPress fournit les tailles) ; SVG, srcset existant et URL inconnue intacts.
$sets = $ctx + ['srcset' => static fn(string $url) => str_ends_with($url, 'projet.webp')
	? 'https://www.groupesidex.com/wp-content/uploads/projet-768x768.webp 768w, https://www.groupesidex.com/wp-content/uploads/projet.webp 1200w' : null];
$imgs = '<!doctype html><html><head><meta charset="UTF-8"></head><body><main id="main"><section style="background-image:url(https://www.groupesidex.com/wp-content/uploads/hero.webp)">Hero</section><section>'
	. '<img src="/wp-content/uploads/projet.webp" width="1200" height="1200"><img src="/wp-content/uploads/autre.webp" width="10" height="10">'
	. '<img src="/wp-content/uploads/projet.webp" srcset="x.webp 1w" width="1" height="1"><img src="/wp-content/uploads/logo.svg">'
	. '</section></main></body></html>';
$out = sx_html_filter($imgs, $sets, $errors);
check('srcset : ajouté avec sizes="auto, 100vw"', str_contains($out, 'srcset="https://www.groupesidex.com/wp-content/uploads/projet-768x768.webp 768w, https://www.groupesidex.com/wp-content/uploads/projet.webp 1200w" sizes="auto, 100vw"'));
check('srcset : un seul ajout (URL inconnue, srcset existant, SVG intacts)', substr_count($out, 'srcset=') === 2 && str_contains($out, 'srcset="x.webp 1w" width="1"'));
// Image du premier écran (candidat LCP, chargée tout de suite) : pas de srcset ni de sizes="auto".
$eager = '<!doctype html><html><head><meta charset="UTF-8"></head><body><main id="main"><section><img src="/wp-content/uploads/projet.webp" width="1200" height="1200"></section></main></body></html>';
$out = sx_html_filter($eager, $sets, $errors);
check('srcset : image du premier écran laissée telle quelle (fetchpriority, sans sizes=auto)', str_contains($out, 'fetchpriority="high"') && !str_contains($out, 'srcset=') && !str_contains($out, 'sizes='));

// 1d. Feuille TablePress : retirée sans tableau, gardée avec.
$tp = '<!doctype html><html><head><meta charset="UTF-8"><link rel=\'stylesheet\' id=\'tablepress-default-css\' href=\'/wp-content/tablepress-combined.min.css?ver=12\' media=\'all\' />'
	. "\n</head><body><main id=\"main\">%s</main></body></html>";
check('TablePress : feuille retirée sans tableau', !str_contains(sx_html_filter(sprintf($tp, '<p>Texte</p>'), $ctx), 'tablepress-default-css'));
check('TablePress : feuille gardée avec un tableau', str_contains(sx_html_filter(sprintf($tp, '<table id="tablepress-1" class="tablepress tablepress-id-1"></table>'), $ctx), 'tablepress-default-css'));

// 1e. AOS : script retiré sans élément data-aos, gardé avec (data-aos-duration du menu ne compte pas).
$aos = "<!doctype html><html><head><meta charset=\"UTF-8\"></head><body><main id=\"main\">%s</main>\n<script id=\"sidex-aos-js\" src=\"/wp-content/themes/sidex/assets/vendor/aos.js?ver=1\"></script>\n</body></html>";
check('AOS : retiré sans data-aos', !str_contains(sx_html_filter(sprintf($aos, '<div class="oxy-pro-menu-container" data-aos-duration="400"></div>'), $ctx), 'sidex-aos-js'));
check('AOS : gardé avec data-aos', str_contains(sx_html_filter(sprintf($aos, '<img data-aos="fade" src="/x.webp">'), $ctx), 'sidex-aos-js'));
check('AOS : gardé avec une ligne verticale', str_contains(sx_html_filter(sprintf($aos, '<div class="ct-div-block vertical-line"></div>'), $ctx), 'sidex-aos-js'));

// 1f. Variantes légères (wp/scripts/images-variants.sh) : servies seulement si leur fichier existe.
$up = sys_get_temp_dir() . '/sx-variants-' . getmypid();
@mkdir($up);
foreach (['hero.jpg.webp', 'lazy.jpg.webp', 'carte.png.webp', 'carte-768x768.png.webp', 'anim.gif.mp4', 'anim.gif.webm', 'anim.gif.poster.webp', 'diapo2.jpg.webp'] as $f) {
	file_put_contents("$up/$f", 'x');
}
$vctx = ['uploads' => ['baseurl' => 'https://www.groupesidex.com/wp-content/uploads', 'basedir' => $up]] + $ctx;
$U = 'https://www.groupesidex.com/wp-content/uploads';
$var = '<!doctype html><html><head><meta charset="UTF-8"></head><body><main id="main">'
	. '<section><div class="slide" style="background-image:linear-gradient(red, red), url(' . $U . '/hero.jpg);background-size:cover"></div>'
	. '<div class="slide" style="background-image:url(' . $U . '/diapo2.jpg)"></div></section>'
	. '<section><img id="image-1" class="ct-image" src="' . $U . '/carte.png" srcset="' . $U . '/carte-768x768.png 768w, ' . $U . '/carte-480x480.png 480w" sizes="(max-width: 768px) 100vw, 768px" width="1024" height="1024" alt="Carte">'
	. '<img src="' . $U . '/sans-variante.jpg" width="10" height="10" alt="">'
	. '<div style="--aspect-ratio:1"><img src="' . $U . '/lazy.jpg" width="10" height="10" alt=""></div>'
	. '<div style="background-image:url(' . $U . '/lazy.jpg)"></div>'
	. '<div><img id="image-9-1621" alt="animation projets" src="' . $U . '/anim.gif" class="ct-image" width="400" height="400" loading="lazy"></div>'
	. '<img src="' . $U . '/autre.gif" width="400" height="400" alt="x"></section></main></body></html>';
$out = sx_html_filter($var, $vctx, $errors);
check('variantes : aucune étape en échec', $errors === []);
check('variantes : préchargement LCP vers le WebP', str_contains($out, '<link rel="preload" as="image" href="' . $U . '/hero.jpg.webp" type="image/webp" fetchpriority="high">'));
check('variantes : fond du héros en image-set après l\'original', str_contains($out,
	'style="background-image:linear-gradient(red, red), url(' . $U . '/hero.jpg);background-size:cover;background-image:linear-gradient(red, red), image-set(url(' . $U . "/hero.jpg.webp) type('image/webp'), url(" . $U . "/hero.jpg) type('image/jpeg'))\""));
check('variantes : fond différé du diaporama avec data-sx-bg-webp', str_contains($out, 'data-sx-bg="url(' . $U . '/diapo2.jpg)" data-sx-bg-webp="image-set(url(' . $U . '/diapo2.jpg.webp)'));
check('variantes : <picture> avec srcset WebP (candidat sans variante gardé)', str_contains($out,
	'<picture style="display:contents"><source type="image/webp" srcset="' . $U . '/carte-768x768.png.webp 768w, ' . $U . '/carte-480x480.png 480w" sizes="(max-width: 768px) 100vw, 768px"><img id="image-1"'));
check('variantes : image sans variante intacte', substr_count($out, '<picture') === 1 && !str_contains($out, 'sans-variante.jpg.webp'));
check('variantes : pas d\'enveloppe sous un sélecteur « > img »', str_contains($out, '<div style="--aspect-ratio:1"><img src="' . $U . '/lazy.jpg"'));
check('variantes : GIF → vidéo (id, classes, dimensions, nom accessible)', str_contains($out,
	'<video id="image-9-1621" class="ct-image" width="400" height="400" style="height:auto" role="img" aria-label="animation projets" autoplay muted loop playsinline disablepictureinpicture preload="none" data-sx-video data-poster="' . $U . '/anim.gif.poster.webp">'
	. '<source data-src="' . $U . '/anim.gif.webm" type="video/webm"><source data-src="' . $U . '/anim.gif.mp4" type="video/mp4"></video>'));
check('variantes : GIF sans vidéo intact, script des vidéos avant </body>', str_contains($out, 'src="' . $U . '/autre.gif"')
	&& preg_match('#<script id="sidex-gif-video">.*</script>\s*</body>#s', $out) === 1);
check('variantes : aucune variante → étape sans effet', sx_html_filter($var, $ctx) === sx_html_filter($var, ['uploads' => ['baseurl' => $U, 'basedir' => "$up/absent"]] + $ctx));
// Fond déclaré dans une feuille du thème (ex. héros de /carrieres/, o-120.css).
@mkdir("$up/theme/assets/css", 0777, true);
file_put_contents("$up/theme/assets/css/o-120.css", '@font-face{font-family:x;src:url(f.woff2)}#section-2-120{background-image:url(/wp-content/uploads/hero.jpg);background-attachment:fixed}'
	. '@media (max-width:767px){#section-2-120{background:#000 url("../img/mobile.png") center/cover}}.x{color:red}.y{background:url(data:image/svg+xml;utf8,<svg/>)}');
file_put_contents("$up/theme/assets/css/o-75.css", '#a{background-image:url(/wp-content/uploads/sans-variante.jpg)}');
$tctx = $vctx + ['theme' => ['url' => 'https://www.groupesidex.com/wp-content/themes/sidex', 'dir' => "$up/theme"]];
$css = "<!doctype html><html><head><meta charset=\"UTF-8\"><link rel='stylesheet' id='o-120-css' href='https://www.groupesidex.com/wp-content/themes/sidex/assets/css/o-120.css?ver=1' media='all' />\n"
	. "<link rel='stylesheet' id='o-75-css' href='https://www.groupesidex.com/wp-content/themes/sidex/assets/css/o-75.css?ver=1' media='all' /></head><body><main id=\"main\"><section id=\"section-2-120\"></section></main></body></html>";
$out = sx_html_filter($css, $tctx, $errors);
check('fond CSS : aucune étape en échec', $errors === []);
check('fond CSS : feuille servie en ligne à sa place, déclaration suivie de image-set() dans la même règle', str_contains($out,
	'<style id="o-120-css" data-sx-css="https://www.groupesidex.com/wp-content/themes/sidex/assets/css/o-120.css?ver=1">@font-face{font-family:x;src:url(/wp-content/themes/sidex/assets/css/f.woff2)}'
	. "#section-2-120{background-image:url(/wp-content/uploads/hero.jpg);background-image:image-set(url(/wp-content/uploads/hero.jpg.webp) type('image/webp'), url(/wp-content/uploads/hero.jpg) type('image/jpeg'));background-attachment:fixed}"
	. '@media (max-width:767px){#section-2-120{background:#000 url("/wp-content/themes/sidex/assets/css/../img/mobile.png") center/cover}}'));
check('fond CSS : feuille sans variante gardée en <link>, URI data: intacte', str_contains($out, "<link rel='stylesheet' id='o-75-css'") && str_contains($out, '.y{background:url(data:image/svg+xml;utf8,<svg/>)}'));
check('fond CSS : rien quand WP Rocket gère le CSS (live)', !str_contains(sx_html_filter($css, ['rocket_css' => true] + $tctx), 'data-sx-css'));
check('fond CSS : raccourci background et !important', sx_css_rewrite('a{background:#fff url(/wp-content/uploads/hero.jpg) no-repeat!important}', $variant = static fn($u, $x) => sx_html_variant_url($u, $x, $vctx))
	=== "a{background:#fff url(/wp-content/uploads/hero.jpg) no-repeat!important;background-image:image-set(url(/wp-content/uploads/hero.jpg.webp) type('image/webp'), url(/wp-content/uploads/hero.jpg) type('image/jpeg'))!important}");
// Live (WP Rocket : Remove Unused CSS + fonds CSS différés), extrait réel de /carrieres/ ramené à hero.jpg.
$live = '<!doctype html><html><head><style id="wpr-usedcss">#section-2-120{background-image:var(--wpr-bg-3263);background-attachment:fixed}.z{background-image:url(/wp-content/uploads/hero.jpg)}</style></head><body>'
	. "<style id=\"wpr-lazyload-bg-exclusion\">#section-2-120{--wpr-bg-3263: url('https://www.groupesidex.com/wp-content/uploads/hero.jpg');}</style>"
	. '<script>const rocket_pairs = [{"selector":".q","style":".q{--wpr-bg-77aa: url(\'https:\\/\\/www.groupesidex.com\\/wp-content\\/uploads\\/lazy.jpg\');}","hash":"77aa"},'
	. '{"selector":".r","style":".r{--wpr-bg-88bb: url(\'https:\\/\\/www.groupesidex.com\\/wp-content\\/uploads\\/sans-variante.jpg\');}","hash":"88bb"}];</script></body></html>';
$out = sx_html_rocket_css($live, $vctx);
check('WP Rocket : variable du fond du premier écran vers le WebP', str_contains($out, "#section-2-120{--wpr-bg-3263: url('https://www.groupesidex.com/wp-content/uploads/hero.jpg.webp');}"));
check('WP Rocket : paire différée (JSON échappé) vers le WebP, sans variante intacte', str_contains($out, ".q{--wpr-bg-77aa: url('https:\\/\\/www.groupesidex.com\\/wp-content\\/uploads\\/lazy.jpg.webp');}")
	&& str_contains($out, 'sans-variante.jpg\');'));
check('WP Rocket : CSS utilisé réécrit en place', str_contains($out, ".z{background-image:url(/wp-content/uploads/hero.jpg);background-image:image-set(url(/wp-content/uploads/hero.jpg.webp)"));
check('WP Rocket : feuille déjà servie en ligne par le thème non réécrite deux fois', sx_html_rocket_css($t = sx_html_filter($css, $tctx), $vctx) === $t);
array_map('unlink', glob("$up/theme/assets/css/*"));
rmdir("$up/theme/assets/css"); rmdir("$up/theme/assets"); rmdir("$up/theme");
$rocket = '<link rel="preload" data-rocket-preload as="image" href="' . $U . '/hero.jpg" fetchpriority="high"><link rel="preload" data-rocket-preload as="image" href="' . $U . '/autre.jpg">';
check('WP Rocket : son préchargement LCP passe au WebP s\'il existe, sinon intact', sx_html_rocket_preload($rocket, $vctx)
	=== '<link rel="preload" data-rocket-preload as="image" href="' . $U . '/hero.jpg.webp" type="image/webp" fetchpriority="high"><link rel="preload" data-rocket-preload as="image" href="' . $U . '/autre.jpg">');
array_map('unlink', glob("$up/*"));
rmdir($up);

// 2. Erreur PCRE forcée (limite de backtracking minuscule, JIT coupé) sur une page lourde.
function with_tiny_pcre_limit(callable $fn)
{
	$jit = ini_get('pcre.jit');
	$limit = ini_get('pcre.backtrack_limit');
	ini_set('pcre.jit', '0');
	ini_set('pcre.backtrack_limit', '10');
	try {
		return $fn();
	} finally {
		ini_set('pcre.backtrack_limit', $limit);
		ini_set('pcre.jit', $jit);
	}
}
// 2a. Seule l'étape « liens » a du travail sur cette page : elle échoue, le HTML d'origine ressort octet pour octet.
$links_only = '<!doctype html><html><head><meta charset="UTF-8"></head><body>'
	. '<a id="logo" href="https://www.groupesidex.com/"><img alt="" src="/wp-content/uploads/logo.svg"></a>'
	. '<main id="main">' . str_repeat('<p>' . str_repeat('Revêtement de bois véritable. ', 40) . '</p>', 200) . '</main></body></html>';
$out = with_tiny_pcre_limit(static fn() => sx_html_filter($links_only, $ctx, $GLOBALS['errors']));
check('erreur PCRE : étape « liens » signalée en échec', in_array('links', $errors, true));
check('erreur PCRE : chaîne renvoyée, HTML d\'origine intact', is_string($out) && $out === $links_only);
// 2b. Page complète : l'étape en échec est ignorée, les autres s'appliquent, jamais de page vide.
$out = with_tiny_pcre_limit(static fn() => sx_html_filter($page, $ctx, $GLOBALS['errors']));
check('erreur PCRE : page complète renvoyée, sans les retouches de l\'étape en échec',
	is_string($out) && strlen($out) >= strlen($page) && $errors !== [] && !str_contains($out, 'aria-label="Groupe Sidex — accueil"'));

// 3. Exception dans une étape (contexte invalide : TypeError) : les autres étapes passent, rien ne casse.
$bad = ['uploads' => 'invalide'] + $ctx;
$out = sx_html_filter($page, $bad, $errors);
check('exception : étape « images » ignorée', in_array('images', $errors, true) && !str_contains($out, 'rel="preload"'));
check('exception : les autres étapes s\'appliquent', str_contains($out, 'aria-label="Groupe Sidex — accueil"'));

// 4. Ce qui n'est pas une page HTML ressort tel quel.
check('réponse non HTML inchangée', sx_html_filter('{"ok":true}', $ctx) === '{"ok":true}');

echo $fails ? "$fails échec(s)\n" : "tous les tests passent\n";
exit($fails ? 1 : 0);
