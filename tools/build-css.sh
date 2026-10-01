#!/usr/bin/env bash
# Assemble les feuilles du thème dans l'ordre de la cascade d'Oxygen (sources hors dépôt : $SIDEX_SRC).
#   app.css  = base Oxygen + extensions (avant les CSS des gabarits)
#   site.css = universal.css d'Oxygen + correctifs (après les CSS des gabarits)
set -euo pipefail
cd "$(dirname "$0")/.."
SRC=${SIDEX_SRC:-$HOME/sidex/oxygen-debuild/source}
AS=$HOME/sidex/oxygen-debuild/oxygen-source-2026-09-25
T=wp/themes/sidex
HOSTS='(https?:)?//(stg-groupesidexcom-staging\.kinsta\.cloud|groupesidexcom\.kinsta\.cloud|(www\.)?groupesidex\.com)'
strip() { sed -E "s#${HOSTS}(/wp-content/)#\4#g"; }
{
  echo "/* Thème Sidex — base (ex-feuilles d'Oxygen, OxyNinja, Oxy Toolbox, Advanced Scripts 68). Généré par tools/build-css.sh. */"
  for f in aos.css oxygen.css core-sss.min.css splide.min.css style.css; do echo "/* ── $f ── */"; cat "$SRC/plugin-css/$f"; echo; done
  echo "/* ── Advanced Scripts 68 Projets - Tableau ── */"; cat "$AS/as-68.txt"; echo
} | strip > $T/assets/app.css
{
  echo "/* Thème Sidex — universal.css d'Oxygen + Advanced Scripts 138 + correctifs du thème. Généré par tools/build-css.sh. */"
  cat "$SRC/oxygen/css/universal.css"; echo
  echo "/* ── Advanced Scripts 138 OxyExtras - Accordéons Fix ── */"; cat "$AS/as-138.txt"; echo
  echo "/* ── Correctifs du thème (src/theme.css) ── */"; cat $T/src/theme.css
} | strip > $T/assets/site.css
grep -cE "$HOSTS" $T/assets/app.css $T/assets/site.css || true
echo "✓ app.css $(wc -c < $T/assets/app.css) o, site.css $(wc -c < $T/assets/site.css) o"
