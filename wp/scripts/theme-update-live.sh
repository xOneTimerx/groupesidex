#!/usr/bin/env bash
# Mise à jour du thème « sidex » et des mu-plugins du dépôt sur groupesidex.com (fichiers seulement, jamais la base).
# Le live tourne déjà sur le thème sidex (Oxygen désactivé) : golive.sh sert à la bascule, ce script aux mises à jour.
#
#   bash wp/scripts/theme-update-live.sh                    # live : demande de taper « live » (lire /dev/tty :
#                                                           # lancer dans un vrai terminal WSL, pas par « ! »)
#   TARGET=staging bash wp/scripts/theme-update-live.sh     # répétition sur le staging, sans confirmation
#   TARGET=staging FORCE_FAIL=1 bash wp/scripts/…           # répétition du retour arrière (contrôle de fumée en échec)
#   TARGET=staging FORCE_FAIL=rsync bash wp/scripts/…       # idem, comparaison des fichiers déployés impossible
#   TARGET=staging FORCE_FAIL=edge EDGE_WAIT=40 bash …      # avertissement « cache public encore ancien » (sans retour arrière)
#
# Étapes : vérifications → contrôle de fumée « avant » → sauvegarde tgz dans ~/private → rsync → purge → contrôle de
# fumée → retour arrière automatique vers la sauvegarde si un contrôle échoue (ou si une étape plante après la copie)
# → attente non bloquante du cache public (edge Kinsta/Cloudflare).
# Paquet déployé = le commit courant (git archive HEAD), pas l'arbre de travail.
#
# Les contrôles de fumée interrogent l'ORIGINE : paramètre unique ?sxsmoke=… + Cache-Control: no-cache (le 19 oct. 2026,
# le cache edge du live a servi l'ancien HTML juste après la purge et déclenché un retour arrière à tort).
# Journal complet : ~/sidex/theme-update-<cible>-<date>.log en local (LOGDIR) et ~/private/sidex-theme-update-<date>.log
# sur le serveur. SIGHUP ignoré : fermer le terminal n'interrompt ni la copie ni un retour arrière.
set -eEuo pipefail
cd "$(dirname "$0")/../.."

TARGET=${TARGET:-live}
case "$TARGET" in
	live) HOST=kinsta-groupesidex; SITE=https://www.groupesidex.com ;;
	staging) HOST=kinsta-groupesidex-staging; SITE=https://stg-groupesidexcom-staging.kinsta.cloud ;;
	*) echo "TARGET inconnu : $TARGET"; exit 1 ;;
esac
FORCE_FAIL=${FORCE_FAIL:-0}
[ "$FORCE_FAIL" != 0 ] && [ "$TARGET" != staging ] && { echo "FORCE_FAIL réservé au staging"; exit 1; }
STAMP=$(date +%Y%m%d-%H%M%S)
LOG=${LOGDIR:-$HOME/sidex}/theme-update-$TARGET-$STAMP.log
REMOTE_LOG="~/private/sidex-theme-update-$STAMP.log"
mkdir -p "$(dirname "$LOG")"
trap '' HUP
exec > >(tee --output-error=warn -a "$LOG") 2>&1
echo "journal : $LOG (copie sur $HOST : $REMOTE_LOG) — $(date -u '+%F %T UTC')"
ROOT=/www/groupesidexcom_665/public
WPC=$ROOT/wp-content
# mu-plugins reportés sur le live (comme golive.sh). Exclus : sidex-oxygen-bridge.php (coexistence avec Oxygen,
# sans objet sur le live) et zz-dev-mail-killswitch.php (staging seulement : bloque tous les courriels).
MU="sidex-core.php sidex-html-sitemap-wpml.php"
BACKUP="~/private/sidex-theme-update-$STAMP.tgz"
UA="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140 Safari/537.36"
# Un gabarit par ligne, FR et EN. Le formulaire Fluent (panneau de soumission) est sur toutes les pages.
URLS="/ /en/ /contact/ /en/contact/ /prix/ /en/quote/ /revetement-exterieur-bois/ /en/exterior-wood-siding/
/revetement-exterieur-bois-saint-sauveur/ /blogue/ /en/blog/ /cedre-blanc-ou-cedre-rouge/ /opaque/
/projets/ /projets/projet-27742/ /en/projets/project-27742/"
# Dernier commit du paquet (thème + mu-plugins), indépendant des commits qui ne touchent que les scripts.
VER=$(git log -1 --format=%h -- wp/themes/sidex wp/mu-plugins)
# git archive date chaque fichier à l'heure du commit HEAD et rsync -a la conserve : les ressources du thème sortent en
# ?ver=<cette heure> (sx_asset_version = filemtime). C'est le marqueur de la nouvelle version dans le HTML public.
MARK=$(git log -1 --format=%ct HEAD)
[ "$FORCE_FAIL" = edge ] && MARK=0000000000
EDGE_WAIT=${EDGE_WAIT:-180}
EDGE_URLS="/ /en/ /prix/"
PKG=$(mktemp -d)

remote() { ssh "$HOST" "cd $ROOT && $*"; }
# Copie du journal sur le serveur (à chaque étape et à la sortie) : le diagnostic survit au terminal.
save_log() { ssh -o ConnectTimeout=10 "$HOST" "mkdir -p ~/private && cat > $REMOTE_LOG" < "$LOG" 2>/dev/null || true; }
step() { save_log; echo "== $*"; }
finish() { rm -rf "$PKG"; sleep 1; save_log; }
trap finish EXIT
confirm() {
	[ "$TARGET" = staging ] && return 0
	read -r -p "PRODUCTION $SITE — « $1 ». Taper « live » pour confirmer : " ok </dev/tty
	[ "$ok" = live ] || { echo "Annulé."; exit 1; }
}
# Kinsta : --all = cache de page + edge + CDN + objet. WP Rocket est actif : le vider aussi.
# Cloudflare : aucun moyen de purge dans le dépôt (pas de zone ni de jeton) ; la couche edge de Kinsta est couverte par --all.
purge() {
	remote "wp eval 'if (function_exists(\"rocket_clean_domain\")) { rocket_clean_domain(); rocket_clean_minify(); echo \"WP Rocket vidé\n\"; }' && wp kinsta cache purge --all"
}
# OPcache revalide les fichiers PHP toutes les 2 s (opcache.revalidate_freq) : attendre avant de purger, sinon le
# préchargement de WP Rocket remplit le cache avec l'ancien code.
opcache_wait() { sleep 5; }

# Réponse servie par un cache (Kinsta ou Cloudflare) au lieu de l'origine.
CACHED='^(cf-cache-status: *(HIT|STALE|UPDATING|REVALIDATED)|x-kinsta-cache: *HIT|ki-cache-type: *Edge)'

# Contrôle de fumée GET seulement, à l'ORIGINE : 200 sans redirection, rendu du thème sidex, aucune erreur fatale, GTM,
# Fluent Forms (+ TextPlugin sur l'accueil). Paramètre inconnu unique = x-kinsta-cache BYPASS, cf-cache-status DYNAMIC.
smoke() {
	local u code h bad=0 f hf n=0 try q cache
	f=$(mktemp); hf=$(mktemp)
	for u in $URLS; do
		n=$((n + 1))
		for try in 1 2 3; do
			q="?sxsmoke=$STAMP-$1-$n-$try"
			code=$(curl -s -o "$f" -D "$hf" -w '%{http_code}' -A "$UA" -H 'Cache-Control: no-cache' --max-time 90 "$SITE$u$q") || code=000
			cache=$(tr -d '\r' < "$hf" | grep -iE "$CACHED" | paste -sd ' ' || true)
			[ -z "$cache" ] && break
		done
		h=()
		[ -z "$cache" ] || h+=("servi par un cache malgré $q : $cache")
		[ "$code" = 200 ] || h+=("HTTP $code")
		grep -q '<body[^>]*wp-theme-sidex' "$f" || h+=("classe wp-theme-sidex absente")
		grep -qiE 'Fatal error|Parse error|critical error on this website|erreur critique' "$f" && h+=("erreur fatale")
		[ "$GTM" = 0 ] || grep -q "googletagmanager.com/ns.html?id=$GTM_ID" "$f" || h+=("GTM absent")
		grep -q 'frm-fluent-form' "$f" || h+=("formulaire Fluent absent")
		# Compteur du héros (gabarit 1688) : TextPlugin de GSAP, déjà perdu une fois (PR #1, rétabli par PR #4).
		[ "$u" != / ] || grep -qE "id=['\"]sidex-gsap-text-js['\"]" "$f" || h+=("sidex-gsap-text-js absent")
		if [ "$FORCE_FAIL" = 1 ] && [ "$1" = apres ] && [ "$u" = / ]; then h+=("échec forcé (FORCE_FAIL=1)"); fi
		if [ ${#h[@]} = 0 ]; then echo "  ✓ $u"; else echo "  ✗ $u — $(IFS=,; echo "${h[*]}" | sed 's/,/ | /g')"; bad=1; fi
	done
	rm -f "$f" "$hf"
	return $bad
}

# Versions (?ver=) des ressources du thème appelées directement dans le HTML, une par ligne, sans doublon. Les copies
# minifiées de WP Rocket (wp-content/cache/min/…) portent la version de WP Rocket, pas celle du thème : ignorées.
theme_vers() {
	grep -oE "[^\"'( ]*wp-content/themes/sidex/[^\"' )]*\?ver=[0-9]+" "$1" | grep -v '/cache/' | sed -E 's/.*\?ver=//' | sort -u || true
}

# Cache public (edge Kinsta/Cloudflare) : les pages SANS paramètre doivent servir la nouvelle version, c.-à-d. TOUTES les
# ressources du thème en ?ver=$MARK (une seule version ancienne = HTML ancien ou mélangé). Non bloquant : un cache
# encore ancien ne justifie pas un retour arrière.
edge_wait() {
	local u f hf pending left end=$((SECONDS + EDGE_WAIT)) cache v
	f=$(mktemp); hf=$(mktemp)
	curl -s -o "$f" -A "$UA" -H 'Cache-Control: no-cache' --max-time 90 "$SITE/?sxsmoke=$STAMP-marque" || true
	v=$(theme_vers "$f" | paste -sd ' ')
	if [ "$v" != "$MARK" ] && [ "$FORCE_FAIL" != edge ]; then
		echo "  (à l'origine, versions du thème « ${v:-aucune} », attendu « $MARK » seulement : cache public non vérifiable, contrôler à la main)"
		rm -f "$f" "$hf"; return 0
	fi
	pending=$EDGE_URLS
	while :; do
		left=""
		for u in $pending; do
			curl -s -o "$f" -D "$hf" -A "$UA" --max-time 60 "$SITE$u" || true
			cache=$(tr -d '\r' < "$hf" | grep -iE '^(cf-cache-status|x-kinsta-cache|ki-cache-type|age):' | cut -d: -f2- | paste -sd '/' | tr -d ' ')
			v=$(theme_vers "$f" | paste -sd ' ')
			if [ "$v" = "$MARK" ]; then
				echo "  ✓ $u nouvelle version publique ($cache)"
			else
				echo "  … $u versions du thème « ${v:-aucune} » ($cache)"
				left="$left $u"
			fi
		done
		pending=$left
		[ -z "$pending" ] && { rm -f "$f" "$hf"; return 0; }
		[ $SECONDS -ge $end ] && break
		echo "  … ancienne version encore servie :$pending — nouvel essai dans 20 s"
		sleep 20
	done
	rm -f "$f" "$hf"
	echo "⚠ après ${EDGE_WAIT} s, le cache public sert encore l'ANCIEN HTML :$pending"
	echo "  L'origine est vérifiée (contrôle de fumée réussi) : pas de retour arrière."
	echo "  Purger : MyKinsta > Sites > groupesidex > Cache > « Clear edge cache » (ou relancer : ssh $HOST 'cd $ROOT && wp kinsta cache purge --all'),"
	echo "  puis vérifier : curl -sI $SITE/ | grep -iE 'cf-cache-status|ki-cache-type|x-kinsta-cache'"
}

restore() {
	trap - ERR
	step "RETOUR ARRIÈRE vers $BACKUP"
	# Le thème est remplacé en entier ; chaque mu-plugin reprend sa version sauvegardée, ou est retiré s'il n'existait pas.
	remote "set -e; t=\$(mktemp -d); tar -C \$t -xzf $BACKUP; rsync -a --delete \$t/themes/sidex/ $WPC/themes/sidex/;
		for m in $MU; do if [ -f \$t/mu-plugins/\$m ]; then cp -p \$t/mu-plugins/\$m $WPC/mu-plugins/\$m; else rm -f $WPC/mu-plugins/\$m; fi; done;
		rm -rf \$t; echo 'fichiers restaurés'"
	opcache_wait
	purge
	sleep 5
	step "contrôle de fumée après retour arrière"
	smoke restaure && echo "✓ version précédente rétablie et vérifiée" || echo "✗ des contrôles échouent encore après le retour arrière : intervenir"
	exit 1
}

step "vérifications ($TARGET)"
[ -z "$(git status --porcelain -- wp/themes/sidex wp/mu-plugins)" ] \
	|| { echo "✗ thème ou mu-plugins modifiés hors commit (git status) : arrêt"; exit 1; }
git archive HEAD wp/themes/sidex $(printf 'wp/mu-plugins/%s ' $MU) | tar -x -C "$PKG"
rm -rf "$PKG/wp/themes/sidex/src"
[ -f "$PKG/wp/themes/sidex/style.css" ] || { echo "✗ paquet incomplet (style.css absent)"; exit 1; }
n=0; while IFS= read -r f; do php -l "$f" >/dev/null || { echo "✗ erreur PHP : ${f#"$PKG"/}"; exit 1; }; n=$((n + 1)); done \
	< <(find "$PKG" -name '*.php')
echo "  ✓ paquet $VER $(git log -1 --format=%s "$VER") — $n fichiers PHP valides (branche $(git rev-parse --abbrev-ref HEAD))"
state=$(remote "wp option get home && wp option get stylesheet && (wp plugin is-active duracelltomi-google-tag-manager && echo gtm-actif || true)")
home=$(sed -n 1p <<< "$state"); theme=$(sed -n 2p <<< "$state")
[ "${home%/}" = "$SITE" ] || { echo "✗ home du site = $home, attendu $SITE : arrêt"; exit 1; }
[ "$theme" = sidex ] || { echo "✗ thème actif = $theme, attendu sidex (bascule : golive.sh) : arrêt"; exit 1; }
echo "  ✓ cible $home, thème actif sidex"
# GTM (extension GTM4WP) : exigé sur le live ; sur le staging, seulement si l'extension y est active.
GTM_ID=GTM-NT9CHCD
if [ "$TARGET" = live ] || grep -q gtm-actif <<< "$state"; then GTM=1; else GTM=0; echo "  (GTM non contrôlé : GTM4WP inactive sur le staging)"; fi

step "contrôle de fumée avant mise à jour"
# Un échec ici peut être ce que la mise à jour corrige ; mais le retour arrière ramènerait cet état déjà en échec.
if ! smoke avant; then
	echo "⚠ le site échoue déjà à des contrôles (rien n'a été modifié) ; un retour arrière ramènerait cet état"
	if [ "$TARGET" = live ]; then
		read -r -p "Continuer quand même ? (o/N) " go </dev/tty
		[ "$go" = o ] || { echo "Annulé."; exit 1; }
	fi
fi

confirm "mise à jour du thème sidex et des mu-plugins ($VER)"

step "sauvegarde"
remote "set -e; mkdir -p ~/private; cd $WPC; m=''; for f in $MU; do [ -f mu-plugins/\$f ] && m=\"\$m mu-plugins/\$f\"; done;
	tar -czf $BACKUP themes/sidex \$m; test -s $BACKUP; tar -tzf $BACKUP | grep -q '^themes/sidex/style.css$';
	echo \"  ✓ $BACKUP (\$(stat -c %s $BACKUP) octets, \$(tar -tzf $BACKUP | wc -l) entrées)\""

# À partir d'ici, toute erreur ramène la version sauvegardée.
trap restore ERR
step "copie"
rsync -az --delete --stats "$PKG/wp/themes/sidex/" "$HOST:$WPC/themes/sidex/" | grep -E 'files transferred|deleted' | sed 's/^/  /'
rsync -az $(printf "$PKG/wp/mu-plugins/%s " $MU) "$HOST:$WPC/mu-plugins/"
echo "  ✓ thème et mu-plugins ($MU) copiés"

step "purge"
opcache_wait
purge

# Le serveur doit porter exactement le paquet (sommes de contrôle) : sur le staging partagé, un autre déploiement
# peut avoir écrasé le thème entre-temps ; le contrôle de fumée ne vaudrait alors rien. Comparaison impossible ou
# écart = contrôle échoué : retour arrière, sur le staging comme sur le live.
step "vérification des fichiers déployés"
sleep 5
cmp_host=$HOST
if [ "$FORCE_FAIL" = rsync ]; then cmp_host=$HOST-introuvable; fi
cmp_theme=$(rsync -rlnc --delete -i "$PKG/wp/themes/sidex/" "$cmp_host:$WPC/themes/sidex/") \
	|| { echo "✗ comparaison du thème impossible (rsync)"; restore; }
cmp_mu=$(rsync -rlnc -i $(printf "$PKG/wp/mu-plugins/%s " $MU) "$HOST:$WPC/mu-plugins/") \
	|| { echo "✗ comparaison des mu-plugins impossible (rsync)"; restore; }
diff=$(printf '%s\n%s\n' "$cmp_theme" "$cmp_mu" | grep -v -e '^\.d' -e '^$' || true)
if [ -n "$diff" ]; then
	echo "$diff" | head -10 | sed 's/^/  /'
	echo "✗ fichiers du serveur différents du paquet (autre déploiement sur le staging partagé ?)"; restore
fi
echo "  ✓ le serveur porte exactement $VER"

step "contrôle de fumée après mise à jour"
smoke apres || restore
trap - ERR
echo "✓ thème sidex $VER en ligne sur $SITE, vérifié à l'origine (sauvegarde : $BACKUP sur $HOST)"

step "cache public (sans paramètre, jusqu'à ${EDGE_WAIT} s)"
edge_wait
echo "journal : $LOG (copie : $REMOTE_LOG sur $HOST)"
