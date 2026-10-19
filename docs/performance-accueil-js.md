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
| Après | 0,90–0,91 | 1,2 s | 3,0–3,2 s |

Le TTFB du staging est de 1,5–2,8 s (cache Kinsta en `BYPASS`) contre ≈ 0,35–0,7 s sur le live : c'est lui qui garde le
LCP au-dessus de 2,5 s sur staging. Hors TTFB, l'image du héros s'affiche ≈ 190 ms après la réponse (avant : 180–490 ms,
et FCP 0,7 s plus tard). Avec le TTFB du live, le LCP attendu est ≈ 1 s ; à confirmer par une mesure en prod après report.

## Envoi de formulaire (staging, Playwright)
Clé reCAPTCHA de test Google et CleanTalk « forms__contact_forms_test » coupé le temps du test, réglages restaurés
ensuite : envoi sans aucune interaction préalable (scripts encore non chargés), avec et sans `requestSubmit` →
POST `admin-ajax` unique, redirection vers `/merci/soumission/`. Avec CleanTalk actif, le POST atteint le serveur et
CleanTalk répond (« envois trop fréquents » : limitation par IP après les essais répétés), donc la protection reste
appliquée sur le chemin différé.
