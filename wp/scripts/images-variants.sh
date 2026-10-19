#!/usr/bin/env bash
# Variantes légères des images lourdes, À CÔTÉ des originaux (jamais à leur place) : le thème (inc/html.php,
# sx_html_variants) les sert seulement si leur fichier existe. Ni les originaux ni la base ne sont touchés ; rien
# n'est jamais supprimé ni écrasé (une variante déjà là est gardée telle quelle) : relancer le script est sans effet.
#
#   bash wp/scripts/images-variants.sh                      # staging, SIMULATION : liste, poids avant/après estimés
#   bash wp/scripts/images-variants.sh --apply              # staging, écrit les variantes puis vide le cache
#   TARGET=live bash wp/scripts/images-variants.sh          # live, simulation (aucune écriture dans uploads)
#   TARGET=live bash wp/scripts/images-variants.sh --apply  # live : demande de taper « live » (vrai terminal WSL)
#
# Fichiers visés (relevé wp/tools/images/scan.py sur le site cible) : JPEG/PNG du premier écran des pages et articles
# (sitemaps Rank Math) et des archives, dont le plus gros candidat dépasse MIN_KB, avec tous les candidats de leur
# srcset ; tous les GIF des mêmes pages.
#   <image>.jpg|png → <image>.jpg|png.webp  cwebp sur le serveur, qualité QUALITY (65, -sharp_yuv, profil ICC gardé),
#                                           gardée seulement si au moins MIN_GAIN % plus légère ; JPEG pivotés
#                                           (EXIF Orientation) ignorés, cwebp ne lit pas cette balise.
#   <anim>.gif      → <anim>.gif.mp4 (H.264, sans son), <anim>.gif.webm (VP9), <anim>.gif.poster.webp (1re image)
#                     ffmpeg en local (absent des serveurs Kinsta), copie par rsync --ignore-existing.
#                     ffmpeg : celui du PATH, sinon FFMPEG=…, sinon imageio-ffmpeg
#                     (pip install --target ~/.cache/sidex-images/py imageio-ffmpeg).
# ONLY=webp ou ONLY=gif : une seule des deux parties. Journal : ~/sidex/images-variants-<cible>-<date>.log (+ relevé .tsv).
set -euo pipefail
cd "$(dirname "$0")/../.."

APPLY=0
[ "${1:-}" = --apply ] && APPLY=1
TARGET=${TARGET:-staging}
case "$TARGET" in
	live) HOST=kinsta-groupesidex; SITE=https://www.groupesidex.com ;;
	staging) HOST=kinsta-groupesidex-staging; SITE=https://stg-groupesidexcom-staging.kinsta.cloud ;;
	*) echo "TARGET inconnu : $TARGET"; exit 1 ;;
esac
QUALITY=${QUALITY:-65}
MIN_GAIN=${MIN_GAIN:-10}
MIN_KB=${MIN_KB:-100}
CRF_MP4=${CRF_MP4:-27}
CRF_WEBM=${CRF_WEBM:-35}
ONLY=${ONLY:-}
PAGES=${PAGES:-"/ /en/ /projets/ /en/projects/ /blogue/ /en/blog/"}
ROOT=/www/groupesidexcom_665/public
UP=$ROOT/wp-content/uploads
STAMP=$(date +%Y%m%d-%H%M%S)
LOGDIR=${LOGDIR:-$HOME/sidex}
LOG=$LOGDIR/images-variants-$TARGET-$STAMP.log
TSV=$LOGDIR/images-variants-$TARGET-$STAMP.tsv
mkdir -p "$LOGDIR"
exec > >(tee -a "$LOG") 2>&1
WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
mode=$([ $APPLY = 1 ] && echo écriture || echo simulation)
echo "== $TARGET ($SITE) — $mode — $(date -u '+%F %T UTC') — journal $LOG"

if [ "$TARGET" = live ] && [ $APPLY = 1 ]; then
	read -r -p "PRODUCTION $SITE — écrire les variantes à côté des originaux. Taper « live » pour confirmer : " ok </dev/tty
	[ "$ok" = live ] || { echo "Annulé."; exit 1; }
fi

ko() { awk -v b="$1" 'BEGIN { printf "%.0f Ko", b / 1024 }'; }

echo "== relevé des pages ($SITE, sitemaps page et post + $PAGES)"
# shellcheck disable=SC2086
python3 wp/tools/images/scan.py "$SITE" --sitemaps page,post --min-kb "$MIN_KB" $PAGES > "$TSV"
cut -f1,3 "$TSV" | sort -u > "$WORK/files"
grep -P '^(img|bg)\t' "$WORK/files" | cut -f2 > "$WORK/images" || true
grep -P '^gif\t' "$WORK/files" | cut -f2 > "$WORK/gifs" || true
echo "  $(cut -f4 "$TSV" | sort -u | wc -l) pages, $(wc -l < "$WORK/images") JPEG/PNG, $(wc -l < "$WORK/gifs") GIF (relevé : $TSV)"

[ "$ONLY" = gif ] && : > "$WORK/images"
[ "$ONLY" = webp ] && : > "$WORK/gifs"
echo "== JPEG/PNG → WebP (cwebp sur $HOST, qualité $QUALITY, gain minimal $MIN_GAIN %)"
LIST=/tmp/sidex-images-variants-$STAMP.txt
ssh "$HOST" "cat > $LIST" < "$WORK/images"
# Sortie : état ⇥ octets de l'original ⇥ octets de la variante ⇥ fichier. Encodage dans un dossier temporaire ;
# en écriture, la variante est déposée à côté de l'original par mv -n (jamais d'écrasement).
ssh "$HOST" "nice -n 15 bash -s -- $APPLY $QUALITY $MIN_GAIN $UP $LIST" <<'REMOTE' | tee "$WORK/webp.tsv"
set -u
apply=$1 q=$2 gain=$3 up=$4 list=$5
command -v cwebp >/dev/null || { echo "erreur	0	0	cwebp absent du serveur"; exit 0; }
tmp=$(mktemp -d)
cd "$up" || exit 1
while IFS= read -r f; do
	[ -n "$f" ] || continue
	case "$f" in /*|*..*) echo "refusé	0	0	$f"; continue ;; esac
	[ -f "$f" ] || { echo "absent	0	0	$f"; continue; }
	a=$(stat -c %s "$f")
	if [ -e "$f.webp" ]; then echo "existe	$a	$(stat -c %s "$f.webp")	$f"; continue; fi
	case "$f" in *.jpg|*.jpeg|*.JPG|*.JPEG)
		o=$(identify -format '%[orientation]' "$f[0]" 2>/dev/null || true)
		case "$o" in ''|Undefined|TopLeft) ;; *) echo "pivoté	$a	0	$f"; continue ;; esac ;;
	esac
	if ! cwebp -quiet -q "$q" -m 6 -sharp_yuv -metadata icc "$f" -o "$tmp/v.webp" 2>/dev/null; then echo "erreur	$a	0	$f"; continue; fi
	b=$(stat -c %s "$tmp/v.webp")
	if [ $((b * 100)) -gt $((a * (100 - gain))) ]; then echo "gain-faible	$a	$b	$f"; continue; fi
	if [ "$apply" = 1 ]; then
		chmod 644 "$tmp/v.webp"
		mv -n "$tmp/v.webp" "$f.webp"
		rm -f "$tmp/v.webp"
		echo "écrit	$a	$b	$f"
	else
		echo "à-écrire	$a	$b	$f"
	fi
done < "$list"
rm -rf "$tmp" "$list"
REMOTE

echo "== GIF → vidéo (ffmpeg local, H.264 crf $CRF_MP4 + VP9 crf $CRF_WEBM + aperçu WebP)"
: > "$WORK/gif.tsv"
if [ -s "$WORK/gifs" ]; then
	FFMPEG=${FFMPEG:-$(command -v ffmpeg || PYTHONPATH=$HOME/.cache/sidex-images/py python3 -c 'import imageio_ffmpeg as m; print(m.get_ffmpeg_exe())' 2>/dev/null || true)}
	enc=$([ -n "$FFMPEG" ] && "$FFMPEG" -hide_banner -encoders 2>/dev/null || true)
	grep -q libx264 <<< "$enc" && grep -q libvpx-vp9 <<< "$enc" \
		|| { echo "✗ ffmpeg avec libx264 et libvpx-vp9 introuvable : pip install --target ~/.cache/sidex-images/py imageio-ffmpeg"; exit 1; }
	mkdir -p "$WORK/gif"
	while IFS= read -r g; do
		case "$g" in /*|*..*) echo "refusé	0	0	$g" | tee -a "$WORK/gif.tsv"; continue ;; esac
		have=$(ssh -n "$HOST" "cd $UP && for s in mp4 webm poster.webp; do [ -e '$g'.\$s ] && echo \$s; done; true" | paste -sd ' ')
		if [ "$have" = "mp4 webm poster.webp" ]; then echo "existe	0	0	$g" | tee -a "$WORK/gif.tsv"; continue; fi
		d=$WORK/gif/$(dirname "$g"); mkdir -p "$d"; src=$WORK/gif/$g
		rsync -a "$HOST:$UP/$g" "$src"
		"$FFMPEG" -v error -y -i "$src" -an -movflags +faststart -pix_fmt yuv420p -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' \
			-c:v libx264 -preset veryslow -profile:v high -crf "$CRF_MP4" "$src.mp4"
		"$FFMPEG" -v error -y -i "$src" -an -pix_fmt yuv420p -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' \
			-c:v libvpx-vp9 -b:v 0 -crf "$CRF_WEBM" -row-mt 1 "$src.webm"
		"$FFMPEG" -v error -y -i "$src" -frames:v 1 -c:v libwebp -quality 80 "$src.poster.webp"
		a=$(stat -c %s "$src"); m=$(stat -c %s "$src.mp4"); w=$(stat -c %s "$src.webm")
		if [ "$m" -ge "$a" ]; then echo "gain-faible	$a	$m	$g" | tee -a "$WORK/gif.tsv"; continue; fi
		state=à-écrire
		if [ $APPLY = 1 ]; then
			chmod 644 "$src".*
			rsync -a --ignore-existing "$src.mp4" "$src.webm" "$src.poster.webp" "$HOST:$UP/$(dirname "$g")/"
			state=écrit
		fi
		echo "$state	$a	$m	$g	(webm $(ko "$w"), aperçu $(ko "$(stat -c %s "$src.poster.webp")"))" | tee -a "$WORK/gif.tsv"
	done < "$WORK/gifs"
fi

echo "== bilan ($mode)"
cat "$WORK/webp.tsv" "$WORK/gif.tsv" | awk -F'\t' '
	{ n[$1]++ }
	$1 == "écrit" || $1 == "à-écrire" || $1 == "existe" { a += $2; b += $3; k++ }
	END {
		for (s in n) printf "  %-12s %d\n", s, n[s]
		if (k) printf "  variantes servies (%d) : %.0f Ko → %.0f Ko (−%.0f %%) ; GIF comptés en MP4, le WebM est plus léger encore\n", k, a / 1024, b / 1024, 100 * (a - b) / a
	}'
if [ $APPLY = 1 ] && grep -q '^écrit' "$WORK/webp.tsv" "$WORK/gif.tsv"; then
	# Le HTML mis en cache (WP Rocket, Kinsta) ne connaît pas encore les nouvelles variantes.
	ssh "$HOST" "cd $ROOT && wp eval 'if (function_exists(\"rocket_clean_domain\")) rocket_clean_domain();' >/dev/null 2>&1; wp kinsta cache purge --all >/dev/null 2>&1; true"
	echo "  ✓ cache vidé (WP Rocket, Kinsta)"
fi
echo "journal : $LOG — relevé : $TSV"
