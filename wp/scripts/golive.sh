#!/usr/bin/env bash
# Mise en ligne du thème « sidex » sur groupesidex.com (retrait d'Oxygen) — SANS toucher à la base.
#
#   bash wp/scripts/golive.sh check      # relevé du live (lecture seule) : ~/sidex/golive/avant-<date>.json
#   bash wp/scripts/golive.sh deploy     # sauvegardes → thème + mu-plugin → bascule → purge → vérification
#   bash wp/scripts/golive.sh rollback   # retour à Oxygen (thème no-theme + 7 extensions réactivées) → purge
#
# deploy et rollback demandent de taper « live » (lire /dev/tty : lancer dans un vrai terminal WSL, pas par « ! »).
# Le thème et sidex-core.php sont copiés AVANT la bascule : inertes tant qu'Oxygen et Advanced Scripts sont actifs
# (no-theme reste le thème actif, sidex-core ne s'exécute pas tant qu'Advanced Scripts est actif).
set -euo pipefail
cd "$(dirname "$0")/../.."

# TARGET=staging : répétition générale sur le staging (sans confirmation).
TARGET=${TARGET:-live}
case "$TARGET" in
	live) HOST=kinsta-groupesidex; SITE=https://www.groupesidex.com; ENV_ID=b3076da4-c4c0-42cb-a74b-5ab5f60679c4 ;;
	staging) HOST=kinsta-groupesidex-staging; SITE=https://stg-groupesidexcom-staging.kinsta.cloud; ENV_ID=453a81bc-ec51-4927-84e0-47da8037f77c ;;
	*) echo "TARGET inconnu : $TARGET"; exit 1 ;;
esac
ROOT=/www/groupesidexcom_665/public
PLUGINS="oxygen oxyextras oxy-ninja oxy-toolbox erropix-hydrogen-pack erropix-advanced-scripts wp-grid-builder-oxygen"
MU="wp/mu-plugins/sidex-core.php wp/mu-plugins/sidex-html-sitemap-wpml.php"
OUT=~/sidex/golive/$TARGET; mkdir -p "$OUT"
STAMP=$(date +%Y%m%d-%H%M%S)
SNAP=wp/tools/parity/snap.py

remote() { ssh "$HOST" "cd $ROOT && $*"; }
confirm() {
	[ "$TARGET" = staging ] && return 0
	read -r -p "PRODUCTION $SITE — « $1 ». Taper « live » pour confirmer : " ok </dev/tty
	[ "$ok" = live ] || { echo "Annulé."; exit 1; }
}
purge() {
	remote "wp eval 'rocket_clean_domain(); if (function_exists(\"rocket_clean_minify\")) rocket_clean_minify(); echo \"WP Rocket vidé\n\";' && wp kinsta cache purge --all"
}
kinsta_backup() {
	local key r op i code
	key=$(doppler secrets get KINSTA_API_KEY --plain -p tactikmedia-assistants -c artemis-vincent)
	r=$(curl -s -X POST -H "Authorization: Bearer $key" -H "Content-Type: application/json" \
		-d "{\"tag\":\"avant-theme-sidex-$STAMP\"}" "https://api.kinsta.com/v2/sites/environments/$ENV_ID/manual-backups")
	op=$(jq -r '.operation_id // empty' <<< "$r")
	[ -n "$op" ] || { echo "✗ sauvegarde Kinsta refusée : $(jq -r '.message // .' <<< "$r" | head -c 200)"; return 1; }
	for i in $(seq 1 40); do
		sleep 15
		code=$(curl -s -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $key" "https://api.kinsta.com/v2/operations/$op")
		case "$code" in 200) echo "✓ sauvegarde Kinsta faite ($op)"; return 0 ;; 202) ;; *) echo "✗ sauvegarde Kinsta : code $code"; return 1 ;; esac
	done
	echo "✗ sauvegarde Kinsta : délai dépassé ($op)"; return 1
}

case "${1:-}" in
	check)
		python3 "$SNAP" "$SITE" "$OUT/avant-$STAMP.json"
		ln -sf "$OUT/avant-$STAMP.json" "$OUT/avant.json"
		echo "✓ relevé de référence : $OUT/avant.json"
		;;

	deploy)
		[ -f "$OUT/avant.json" ] || { echo "Lancer d'abord : $0 check"; exit 1; }
		confirm "mise en ligne du thème sidex, désactivation d'Oxygen"
		echo "== état actuel"
		state=$(remote "wp option get stylesheet && wp plugin is-active oxygen && echo oxygen-actif")
		echo "$state"
		grep -q '^no-theme$' <<< "$state" && grep -q oxygen-actif <<< "$state" || { echo "✗ état inattendu (déjà basculé ?) : arrêt"; exit 1; }

		echo "== sauvegardes"
		kinsta_backup
		remote "mkdir -p ~/private && wp db export ~/private/sidex-pre-theme-$STAMP.sql --quiet && gzip -f ~/private/sidex-pre-theme-$STAMP.sql && ls -la ~/private/sidex-pre-theme-$STAMP.sql.gz"

		echo "== fichiers (inertes avant la bascule)"
		bash tools/build-css.sh >/dev/null
		for f in $(find wp/themes/sidex wp/mu-plugins -name '*.php'); do php -l "$f" >/dev/null; done
		rsync -az --delete --exclude src/ wp/themes/sidex/ "$HOST:$ROOT/wp-content/themes/sidex/"
		rsync -az $MU "$HOST:$ROOT/wp-content/mu-plugins/"

		echo "== bascule"
		remote "wp plugin deactivate $PLUGINS && wp theme activate sidex && wp rewrite flush"
		purge

		echo "== vérification (relevé après, comparé au relevé avant)"
		sleep 10
		if python3 "$SNAP" "$SITE" "$OUT/apres-$STAMP.json" "$OUT/avant.json"; then
			echo "✓ mise en ligne vérifiée. Retour arrière possible : $0 rollback"
		else
			echo
			[ "$TARGET" = staging ] && exit 1
			read -r -p "Écarts ci-dessus. Revenir à Oxygen maintenant ? (o/N) " rb </dev/tty
			[ "$rb" = o ] && exec "$0" rollback-confirmed
		fi
		;;

	rollback|rollback-confirmed)
		[ "$1" = rollback-confirmed ] || confirm "retour à Oxygen"
		remote "wp theme activate no-theme && wp plugin activate $PLUGINS && wp rewrite flush"
		purge
		sleep 5
		python3 "$SNAP" "$SITE" "$OUT/rollback-$STAMP.json" "$OUT/avant.json" oxygen | tail -3 || true
		echo "✓ Oxygen rétabli (le thème sidex reste en place, inactif)"
		;;

	*) sed -n 2,10p "$0"; exit 1 ;;
esac
