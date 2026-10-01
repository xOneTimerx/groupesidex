<?php
/**
 * Gabarit « = En-tête (Centrée) » (ex-Oxygen 1634), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* En-tête */ ?><section id="section-2-337" class="ct-section c-bg-light"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-3-337" class="ct-div-block c-owl-m c-center c-center-self c-full-width c-max-width-640"><h1 id="code_block-5-337" class="ct-code-block c-tagline"><?php echo get_entete_field("entete_sous_titre"); ?></h1><h2 id="code_block-6-337" class="ct-code-block c-h3 c-heading-dark"><?php echo get_entete_field("entete_titre"); ?></h2><?php if (sx_compare(sx_fn('get_field', 'entete_contenu'), 'is_not_blank', '')) : ?><div id="code_block-7-337" class="ct-code-block"><?php echo get_entete_field("entete_contenu"); ?></div><?php endif; ?><?php $field = get_field("lien_champ_acf"); ?>

<a href="<?php the_field($field . "_btn_lien", "option"); ?>" class="c-btn-main c-btn-m c-transition"><?php the_field($field . "_btn_texte", "option"); ?></a></div></div></section>
