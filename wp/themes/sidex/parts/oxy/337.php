<?php
/**
 * Gabarit « = En-tête » (ex-Oxygen 337), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* En-tête */ ?><section id="section-2-337" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-3-337" class="ct-div-block c-max-width-960 c-full-width c-margin-bottom-l"><h1 id="code_block-5-337" class="ct-code-block c-tagline c-text-light c-margin-bottom-l"><?php echo get_entete_field("entete_sous_titre"); ?></h1><h2 id="code_block-6-337" class="ct-code-block c-heading-light title-reveal c-h1 c-margin-bottom-m"><?php echo get_entete_field("entete_titre"); ?></h2><div id="code_block-7-337" class="ct-code-block c-text-light c-margin-bottom-l"><?php echo get_entete_field("entete_contenu"); ?></div><div id="code_block-10-337" class="ct-code-block"><?php $field = get_field("lien_champ_acf"); ?>

<a href="<?php the_field($field . "_btn_lien", "option"); ?>" class="c-btn-main c-btn-m c-transition"><?php the_field($field . "_btn_texte", "option"); ?></a></div></div><div id="div_block-11-337" class="ct-div-block"><img id="image-12-337" alt="bounce-logo" src="/wp-content/uploads/logo-sidex-blanc-bounce.svg" class="ct-image bounce2"/></div></div></section>
