# AGENTS.md — groupesidex

- Tout le travail se fait sur le **staging** (`kinsta-groupesidex-staging`). Rien sur le live (`kinsta-groupesidex`) sans accord explicite de Vincent.
- Ne jamais pousser la base du staging vers le live : le live reçoit des leads Fluent Forms. Mise en ligne = report ciblé.
- Contenu = champs ACF existants (mêmes noms de champs : des shortcodes Oxygen signés et les pages GEO/Ville les lisent par nom).
- Tokens Tailwind uniquement ; mesurer les styles calculés d'Oxygen, ne pas se fier à ses classes ni à ses media queries.
- Documentation et commentaires en français ; code et commits en anglais.
- Oxygen : lire `_ct_builder_json` / `_ct_builder_shortcodes` (avec le souligné).
