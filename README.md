# groupesidex.com — sortie d'Oxygen

Thème WordPress classique (ACF + Tailwind v4) qui remplace Oxygen Builder sur groupesidex.com.

- Staging Kinsta : https://stg-groupesidexcom-staging.kinsta.cloud (`ssh kinsta-groupesidex-staging`, noindex, courriels bloqués).
- Méthode : thème classique → gel des URL non portées → désactivation de la famille Oxygen → re-port gabarit par gabarit
  (skill wisdom `builder-freeze-to-classic-theme`, référence Lorendo).
- Plan de match : https://claude.ai/artifact/YKzAtgxzYKgm4RdSSRgMvQ

## Arborescence

- `wp/themes/sidex/` — le thème. `parts/oxy/*.php` : gabarits convertis par `tools/oxy2php.py`, puis retouchés à la main
  (ordre des titres, `<main>`) : ne pas les régénérer sans reporter ces retouches. `inc/html.php` : retouches du HTML final
  (images, LCP, noms accessibles), test local : `php wp/tools/tests/html-filter-test.php`. CSS : `src/theme.css` → `bash tools/build-css.sh`.
- `wp/mu-plugins/` — mu-plugins versionnés. `zz-dev-mail-killswitch.php` = **staging seulement**, ne jamais déployer sur le live.
- `wp/scripts/` — déploiement staging, gel, bascule.

## Mise en ligne (Vincent, dans un terminal WSL)

1. Déployer le thème sur le staging (`bash wp/scripts/theme-deploy-staging.sh`) et le relire.
2. `bash wp/scripts/golive.sh check` — relevé du live + dérive des gabarits Oxygen (`wp/tools/parity/drift.py`).
3. `bash wp/scripts/golive.sh deploy` dans les 3 h (relevé plus vieux refusé ; thème modifié hors commit refusé).
4. En cas d'écart : `bash wp/scripts/golive.sh rollback` (Oxygen réactivé, base intacte).

Répétition sans confirmation : `TARGET=staging bash wp/scripts/golive.sh check|deploy|rollback`.
