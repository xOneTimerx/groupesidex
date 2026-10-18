#!/usr/bin/env bash
# Assemble le CSS, vérifie le PHP et déploie le thème « sidex » + les mu-plugins du dépôt sur le STAGING seulement.
#   bash wp/scripts/theme-deploy-staging.sh
set -euo pipefail
cd "$(dirname "$0")/../.."
HOST=kinsta-groupesidex-staging
ROOT=/www/groupesidexcom_665/public

bash tools/build-css.sh >/dev/null
for f in $(find wp/themes/sidex wp/mu-plugins -name '*.php'); do php -l "$f" >/dev/null; done

rsync -az --delete --exclude src/ wp/themes/sidex/ "$HOST:$ROOT/wp-content/themes/sidex/"
rsync -az wp/mu-plugins/sidex-core.php wp/mu-plugins/sidex-oxygen-bridge.php wp/mu-plugins/sidex-html-sitemap-wpml.php "$HOST:$ROOT/wp-content/mu-plugins/"
# WP Rocket est actif sur le staging depuis le 2 oct. 2026 : vider aussi son cache (sinon l'ancien HTML reste servi).
ssh "$HOST" "cd $ROOT && (wp eval 'if (function_exists(\"rocket_clean_domain\")) rocket_clean_domain();' >/dev/null 2>&1; wp kinsta cache purge --all >/dev/null 2>&1) || true"
echo "✓ thème déployé sur https://stg-groupesidexcom-staging.kinsta.cloud/ (ancien rendu : ?oxygen=1)"
