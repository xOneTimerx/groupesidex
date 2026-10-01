# Parité Oxygen ↔ thème

Outils utilisés pour porter chaque gabarit. Dépendances : `puppeteer-core` et un Chromium
(`~/.local/bin/chromium`) — voir `~/.local/lib/shoot/` sur le poste (node_modules déjà installés).

- `run-parity.sh <url> <nom> [largeurs]` — rend la même URL avec `?oxygen=1` (Oxygen) et sans (thème),
  animations figées, puis `parity.py` aligne les textes et signale les écarts de position/police.
  Objectif : écart de hauteur 0 et aucun texte déplacé de plus de 3 px à 390/768/1024/1280/1440/1920.
- `sbs.py <dossier>` — captures côte à côte réduites (Oxygen à gauche).
- `functional.js <url>` — menus, carrousel, vidéo, en-tête collant, menu mobile, console.
- `survey.js`, `show.py`, `geo.js` — relevés de styles calculés ; `cssq.py` — règles CSS Oxygen par sélecteur.

Règle apprise : ne pas se fier aux media queries des fichiers Oxygen (la cascade en écrase plusieurs) ;
mesurer les éléments aux largeurs cibles.
