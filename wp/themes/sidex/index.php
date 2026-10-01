<?php
/**
 * Toutes les vues passent par l'enveloppe du site (ex-gabarit Oxygen 69 « Header / Footer »), qui rend
 * à son tour le gabarit converti propre à la requête (inc/templates.php) à la place de l'ancien
 * élément « Inner Content ».
 */
defined('ABSPATH') || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php sx_oxy(69); ?>
<?php wp_footer(); ?>
</body>
</html>
