# Accueil mobile : JavaScript hors du chemin du LCP (GRP-10)

## Ce que fait le thème
- `inc/forms-defer.php` + `assets/js/forms-defer.js` : Fluent Forms (`fluentform-advanced.js`, `form-submission.js`) et
  CleanTalk (`apbct-public-bundle.min.js`, `ct-bot-detector-wrapper.js`) se chargent quand un formulaire approche de
  l'écran (≈ 400 px) ou reçoit focus / toucher / clic. Les données en ligne (`fluentFormVars`, `ctPublic`…) restent
  imprimées tout de suite. Un envoi déclenché avant la fin du chargement est retenu, puis rejoué une fois les deux
  groupes chargés : `form.requestSubmit()`, sinon clic sur le bouton d'envoi (jamais `form.submit()`, qui contourne
  l'écouteur « submit » de Fluent Forms).
- `inc/js-defer.php` : GSAP, TextPlugin et `o-1688.js` (diaporama + compteur) partent après l'événement `load`
  (`requestIdleCallback`). Les polyfills de WP Grid Builder (`polyfills.js`, ≈ 18 Ko : navigateurs d'avant 2017) ne sont
  plus chargés ; la poignée reste, son code « before » lance l'initialisation des grilles.
- `assets/js/title-reveal.js` : le héros de l'accueil (titres fixes et 1re diapositive) n'est pas animé.

## WP Rocket : ce que `delay_js` laisse passer (à ne pas changer en prod sans accord)
- Staging (relevé 19 oct. 2026, `wp option get wp_rocket_settings`) : `delay_js` = 0, `defer_all_js` = 1,
  `minify_js` = 1, `remove_unused_css` = 0. Rien n'est retardé : les scripts sont seulement en `defer`
  (`data-rocket-defer`). La liste `delay_js_exclusions` (jQuery, AOS, `heroSlider`, `recaptcha`…) n'agit donc pas.
- Live (sondé en HTTP seulement, réglage non lu) : aucun `type="rocketlazyloadscript"` ni script de relecture dans le
  HTML, et les scripts du thème n'ont pas d'attribut `defer` : `delay_js` et `defer_all_js` y sont inactifs.
  Si on active « Retarder l'exécution du JS » en prod, exclure `sxFormsDefer`, `sidex-after-lcp`, `heroSlider` et
  `gsap` (scripts en ligne du thème) et vérifier le formulaire et le diaporama.

## Mesures (Lighthouse mobile local, 3 passages, staging, www-équivalent sans redirection)
| | Perf | FCP | LCP |
|---|---|---|---|
| Avant (d4b2a64) | 0,79–0,86 | 1,8–1,9 s | 3,6–4,4 s |
| JS différé seulement | 0,90–0,91 | 1,2 s | 3,0–3,2 s |
| + préchargement WP Rocket de l'original retiré | 0,96–0,98 | 1,2 s | 2,0–2,3 s |
| + feuilles de plugins du bas de page non bloquantes (5 passages) | 0,95–0,98 | 1,2 s | 1,8–1,9 s, CLS 0 |

Cause du dernier gain : WP Rocket ajoute `<link rel="preload" data-rocket-preload as="image">` vers le `src` de la 1re
diapositive (l'original de 1921 px, ≈ 200 Ko) sans `imagesrcset`, alors que l'`<img>` choisit un candidat de ≈ 40 Ko sur
mobile : deux téléchargements, le gros en concurrence avec le LCP. `sx_html_rocket_preload` (rocket_buffer) retire ce
préchargement quand le HTML contient l'`<img data-sx-lcp srcset>` (déjà en `fetchpriority=high`, vu par l'analyseur).

Feuilles non bloquantes (`sx_html_defer_form_css`, accueil seulement) : CleanTalk, Fluent Forms, flatpickr, WP Grid Builder et
WPML (≈ 35 Ko) passent en `media="print"` puis « all » au chargement, avec `<noscript>`. Rocket n'applique ses traitements
qu'aux URL sans paramètre : mesurer l'URL nue, pas `/?x=1`.

Limite : le TTFB du staging (1,5–2,8 s, page jamais servie par le cache WP Rocket ni Kinsta) s'ajoute au LCP sous
émulation brute (CDP, 150 ms, 1,6 Mbps, CPU 4x : LCP − TTFB ≈ 1,7–2,0 s). Un passage au LCP > 2,5 s sur staging vient de
ce TTFB ; sur le live, la page est servie en cache (TTFB ≈ 0,35 s).

## Envoi de formulaire (staging, Playwright, 21 oct. 2026)
- Parcours réel (ouverture du panneau « soumission » → chargement différé Fluent Forms + CleanTalk + reCAPTCHA → formulaire en
  3 étapes → widget → envoi), CleanTalk actif, clé reCAPTCHA de test Google, e-mail `approved@cleantalk.org` : POST `admin-ajax`
  200 puis `/merci/soumission/`, sur la branche comme sur main (d4b2a64) et avant la PR #3 (8029eb8).
- Widget reCAPTCHA non résolu (vraies clés, navigateur sans tête) : 422 « reCaptcha verification failed » identique sur les
  trois états : ce n'est pas une régression du chargement différé.
- CleanTalk refuse (422) les envois remplis d'un coup par script (« envois trop fréquents ») et les e-mails @example.com
  (« Email seems to be spammer ») : comportement de CleanTalk, identique sur main. Pour tester, taper les champs et utiliser
  `approved@cleantalk.org`.

## Échec de chargement de CleanTalk ou de Fluent Forms (réseau, bloqueur de contenu)
`forms-defer.js` suit l'état de chaque groupe (`pending` / `ok` / `failed`). Un envoi retenu n'est rejoué que si les deux
sont `ok`. Si l'un a échoué : aucun envoi, message dans le formulaire (FR/EN) et relance du chargement au clic suivant ;
après deux échecs (bloqueur durable), l'envoi passe tel quel afin de ne pas perdre de demandes : la vérification CleanTalk
côté serveur (PHP) s'applique toujours, seuls les champs JavaScript de CleanTalk manquent. Test staging (Chromium, requêtes
`cleantalk-spam-protect/*.js` et `fd.cleantalk.org` bloquées) : envois 1 et 2 → 0 POST + message ; envoi 3 → POST ; sans
blocage → POST puis `/merci/soumission/`.
