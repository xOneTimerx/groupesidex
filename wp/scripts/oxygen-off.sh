#!/usr/bin/env bash
# Désactive (ou réactive) la famille Oxygen sur le STAGING. Réversible ; les dossiers des extensions restent en place.
#   bash wp/scripts/oxygen-off.sh          → sauvegarde SQL puis désactivation
#   bash wp/scripts/oxygen-off.sh rollback → réactivation
set -euo pipefail
HOST=kinsta-groupesidex-staging
ROOT=/www/groupesidexcom_665/public
PLUGINS="oxygen oxyextras oxy-ninja oxy-toolbox erropix-hydrogen-pack erropix-advanced-scripts wp-grid-builder-oxygen"

if [ "${1:-}" = "rollback" ]; then
  ssh "$HOST" "cd $ROOT && wp plugin activate $PLUGINS && wp kinsta cache purge --all >/dev/null 2>&1 || true"
  echo "✓ famille Oxygen réactivée sur le staging"; exit 0
fi
STAMP=$(date +%Y%m%d-%H%M)
ssh "$HOST" "cd $ROOT && mkdir -p ~/private && wp db export ~/private/sidex-pre-oxygen-off-$STAMP.sql --quiet && gzip -f ~/private/sidex-pre-oxygen-off-$STAMP.sql && ls -la ~/private/sidex-pre-oxygen-off-$STAMP.sql.gz"
ssh "$HOST" "cd $ROOT && wp plugin deactivate $PLUGINS && wp plugin list --status=active --field=name | tr '\n' ' ' && wp kinsta cache purge --all >/dev/null 2>&1 || true"
echo; echo "✓ famille Oxygen désactivée sur le staging (sauvegarde ~/private/sidex-pre-oxygen-off-$STAMP.sql.gz ; retour : $0 rollback)"
