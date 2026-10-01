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
rsync -az wp/mu-plugins/sidex-core.php wp/mu-plugins/sidex-oxygen-bridge.php "$HOST:$ROOT/wp-content/mu-plugins/"
ssh "$HOST" "cd $ROOT && wp kinsta cache purge --all >/dev/null 2>&1 || true"
echo "✓ thème déployé sur https://stg-groupesidexcom-staging.kinsta.cloud/ (ancien rendu : ?oxygen=1)"
