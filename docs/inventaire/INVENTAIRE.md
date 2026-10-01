# Inventaire Oxygen — groupesidex.com (staging, 25 sept. 2026)

Sources brutes dans ce dossier (`inventory.json`, `templates.json`, `urls.json`, `url-map.json`, `page-terms.json`,
`sample.json`, `refshots.json`) et, hors dépôt, dans `~/sidex/oxygen-debuild/oxygen-source-2026-09-25/` (JSON + shortcodes des 35 gabarits, code des
scripts Advanced Scripts `as-<id>.txt`, `universal.css`). Scripts rejouables : `wp/scripts/inventory/`.

## 1. Gabarits (35)

Aucune page n'a de contenu Oxygen propre : **tout passe par des `ct_template`**, appliqués par règle
(terme `types_page` pour les pages, type de contenu pour le reste). Parent commun : **69 « - Header / Footer »**.

| URL | Gabarit | Extensions Oxygen utilisées |
|---:|---|---|
| 423 | 1584 Projets - Single | oxy_gallery, reusables 149 + 1819 |
| 69 | 75 Articles - Single | TdM, temps de lecture, date de modif., reusable 186 |
| 37 | 1615 Types - Single (CPT `etapes`) | reusables 149 + 1621 |
| 10 | 77 Articles - Archive (+ /blogue/, catégories) | facettes WPGB, reusable 186 |
| 8 | 78 Pages - Autres (politiques, termes) | — |
| 6 | 73 - Page Merci | — |
| 6 | 1654 Page - À propos | carrousel OxyExtras, oxy_gallery |
| 4 | 1688 Page - Accueil | GSAP (héros), carrousel, reusables 149 + 1621 |
| 4 | 1593 Page - Revêtements | oxy_tabs, reusables 149/1621/1624 |
| 4 | 1761 Page - Professionnels | carrousel, accordéons |
| 4 | 54620 FAQ - Single | — |
| 4 | 54802 Auteurs - Single | — |
| 2 chacun | 1726 Nous joindre, 1640 Documents techniques (lightbox), 1648 FAQ, 120 Carrières, 51496 Soumission, 54732 Développement durable, 53501 Concours, 1582 Projets - Archive (facettes WPGB, superbox, toggle), 88 Recherche, 74 404 | |
| 1 | 54834 !Page - Revêtement - GEO (+ 4 brouillons) | superbox, onglets dynamiques |
| 1 | 55016 Page - Prix | 273 éléments, 10 accordéons |
| 0 publié | 55123 !Page - Ville (12 brouillons) | superbox |
| — | 191 Formulaires - Single (terme 28, aucune page publiée) | |

Parties réutilisables : 149 = CTA Principal, 186 = Articles - Card, 337 = En-tête, 1634 = En-tête (Centrée),
1621 = Bloc - Inspirations (grille WPGB 2), 1624 = Bloc - FAQ, 1819 = Breadcrumbs, **53300 BK-old (inutilisée, à ignorer)**.

Éléments à remplacer (sur 36 types) : 431 div, 361 code blocks (PHP encodé base64 dans les shortcodes),
130 textes, 104 sections, 58 icônes, 57 images, 34 listes dynamiques (répéteurs ACF), 21 accordéons Pro (OxyExtras),
9 formulaires Fluent (élément `oxy-fluent-form` : le style du formulaire vit dans l'élément), 9 facettes WPGB,
6 toggles, 4 carrousels (OxyExtras, Splide), menu Pro + off-canvas + menu coulissant (en-tête), TdM, temps de lecture.

Cas particuliers :
- `/blogue/` et `/en/blog/` : page des articles → gabarit 77 (règle `blog_posts`), pas par terme.
- `/concours/` : gabarit 53501 sans règle d'application visible (appliqué autrement : à vérifier au portage).
- `/soumission/` (page du gabarit 51496) **redirige en 301 vers /contact/** : le gabarit Soumission n'est visible
  que par le formulaire de l'en-tête. À clarifier avec Vincent.
- Les 12 archives de la taxonomie `etapes` (`/etapes/couleur/`…) redirigent vers l'accueil : rien à porter.
- Les listes de billets n'ont **aucun H1** (6 URL) : correctif SEO à prévoir (décision d2).

## 2. URL publiques : 615

423 réalisations, 69 billets, 44 pages, 37 fiches Types, 16 archives et taxonomies, 6 merci, 4 FAQ, 4 auteurs,
recherche et 404 par langue. FR/EN via WPML (`/en/`). 14 URL en `noindex` Rank Math. Carte complète :
`url-map.json` (URL → gabarit, validée sur un échantillon de 69 URL capturées). Le staging répond en ~11 s par
page sans cache : **le gel complet (phase 3) doit tourner avec une concurrence faible (3) ou le cache Kinsta actif**.

## 3. Advanced Scripts — ce qui disparaît avec lui

Actifs, à reprendre ailleurs avant la bascule :

| Script | Rôle | Destination proposée |
|---|---|---|
| 139 Snippets (44 Ko) | shortcodes `[year]` `[politiques]` `[termes]` `[oxygen-template]`, fonctions `acf_link_field`, `get_entete_field`, `get_copyright_text`, alt d'images, ACF JSON dans `wp-content/acf-json`, désactivation flux RSS/emojis/jQuery Migrate, tailles d'images, redirection de la recherche vers /recherche/, redirection « Avis Google », classe de langue sur body | mu-plugin `sidex-core` (tout sauf les conditions Oxygen) |
| 67 + 68 + 141 Projets - Tableau | tableau des revêtements d'un projet + CSS + bouton « soumission » qui préremplit le formulaire | thème (gabarit projet) |
| 86 Revêtements - Étapes | `output_types()` pour les pages Revêtements/Types | thème |
| 76 WP Gridbuilder | galerie de remplacement pour la grille 2 | mu-plugin |
| 244 + 245 | liste des postes dans le formulaire Candidature (Fluent 5 et 8) | mu-plugin |
| 264, 273, 275 | métas de taxonomies, `foundingDate` JSON-LD, titre des pages auteur (Rank Math) | mu-plugin |
| 137, 146, 266 | petites fonctions de gabarit | thème |
| 30 + 269 | bouton CTA collant au défilement | thème (JS) |
| 80-85 | GSAP + ScrollTrigger + SplitText + TextPlugin, révélation des titres | thème (JS vanilla, décision d2) |
| 143/144/248 | GLightbox des couleurs | thème |
| 138 | correctifs CSS des accordéons | disparaît avec les accordéons |
| 262 Fix WP Rocket | fausse interaction après 500 ms | à revoir avec WP Rocket |
| **11 Scripts [HEADER]** | **ClickRank.ai + Searchable Analytics** (`data-domain=groupesidex.com`) | **GTM** (décision d8). Coupé sur le staging le 25 sept. |

Inactifs (ignorés) : 7, 8, 9, 10, 12, 13, 15-18, 23, 25, 27, 246.

## 4. Contenu, champs, menus, formulaires

- **ACF** : 27 groupes, définis en JSON dans `wp-content/acf-json` (23 fichiers ; point de chargement posé par
  Snippets 139 — à reprendre dans le mu-plugin sinon les groupes disparaissent de l'admin). 3 pages d'options
  (`configuration`, `contenu`, `liens`). Groupes par terme `types_page` (Accueil 26 champs, GEO 54, Ville 59…).
  Le code des gabarits lit 216 noms de champs (liste par gabarit dans `templates.json`).
- **Menus** (FR, traduits par WPML) : Header - Menu principal (6, 19 éléments), Header - Menu secondaire (73),
  Menu - Mobile (140, 22), Footer - Liens principaux (69), secondaires (70), supplémentaires (5). Aucun
  emplacement de thème (appelés par ID dans le gabarit 69).
- **Fluent Forms** : 10 formulaires publiés FR/EN (2/9 Contact, 3/7 Soumission, 5/8 Candidature, 10/11 Incident de
  confidentialité, 13/14 Concours). 50 soumissions sur 30 jours sur le live → **ne jamais écraser la base du live**.
- **WP Grid Builder** : une seule grille, 2 « Inspirations » (élément Oxygen `wpgb-grid-18-1621` dans le bloc 1621), 8 facettes (types, essence, modèle, fini, finition,
  recherche, « en voir davantage »). CSS par grille dans `uploads/wpgb/grids/`.
- **TablePress** : 4 tableaux (articles « pourquoi le bois au Québec »).
- **mu-plugins** à garder : sidex-markdown-negotiation, sidex-faq-schema, sidex-author-meta, sidex-en-image-alt,
  sidex-phone-outage-popup, tm-lead-source, tm-security-headers, tm-oxygen-cache-purge (à retirer avec Oxygen).

## 5. Jetons de design (styles calculés, 4 gabarits × 390/1440)

- **Couleurs** : texte `#232122` (Heading/Text Dark), texte secondaire `#7e7e7e`, accent orange `#ec8626`,
  brun `#71645c` (Accent 2), fonds `#f8f8f8`, `#efefef`, `#f7f5f1`, `#eaf1e2` (vert pâle GES), sombre `#232122`/`#1a1a1a`,
  blanc 80/50/45 % sur fond sombre, bordures `#dedede` et `rgba(112,112,112,.2)`, fil d'Ariane `#a8a8a8`.
- **Polices** : texte **Arial** (pas de police web), titres **Heebo** (Google Fonts, 9 graisses chargées alors que
  ~3 servent → à auto-héberger en woff2, 400/500/600).
- **Échelle** : corps 18/28,8 ; petits 16, 14, 13, 12 ; titres fluides 41,44 → 26,6 px (1440 → 390) et 21,28 → 16,4 ;
  surtitres en capitales espacées 1,4 px. H1 global 42, H2 36, H3 32, H4 20.
- **Mise en page** : largeur max 1600, sections 125 px haut/bas et 20 px côtés, colonnes 20 px, contenus 960/700/640.
  Points de rupture Oxygen : **991, 767, 479** (+ 1600, 1399).
- **Rayons** : 3 px (boutons), 50 % (icônes rondes), 100 px (pastilles).
