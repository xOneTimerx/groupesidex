# groupesidex.com — sortie d'Oxygen

Thème WordPress classique (ACF + Tailwind v4) qui remplace Oxygen Builder sur groupesidex.com.

- Staging Kinsta : https://stg-groupesidexcom-staging.kinsta.cloud (`ssh kinsta-groupesidex-staging`, noindex, courriels bloqués).
- Méthode : thème classique → gel des URL non portées → désactivation de la famille Oxygen → re-port gabarit par gabarit
  (skill wisdom `builder-freeze-to-classic-theme`, référence Lorendo).
- Plan de match : https://claude.ai/artifact/YKzAtgxzYKgm4RdSSRgMvQ

## Arborescence

- `wp/themes/sidex/` — le thème (à venir).
- `wp/mu-plugins/` — mu-plugins versionnés. `zz-dev-mail-killswitch.php` = **staging seulement**, ne jamais déployer sur le live.
- `wp/scripts/` — déploiement staging, gel, bascule.
