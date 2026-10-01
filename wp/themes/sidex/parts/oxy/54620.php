<?php
/**
 * Gabarit « FAQ - Single » (ex-Oxygen 54620), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* En-tête */ ?><section id="section-35-54620" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-36-54620" class="ct-div-block c-max-width-960 c-full-width c-margin-bottom-l c-center-self c-center"><span id="code_block-37-54620" class="ct-code-block c-tagline c-margin-bottom-l"><?= the_field("bloc_faq_sous_titre", "option"); ?></span><h1 id="code_block-38-54620" class="ct-code-block title-reveal c-h1 c-margin-bottom-m"><?php echo the_title(); ?></h1><span class="multi-trigger-soumission c-btn-main c-btn-m c-transition"><?php the_field("lien_soumission_btn_texte", "option"); ?></span>

</div></div></section><?php /* FAQ */ ?><section id="section-40-54620" class="ct-section"><div class="ct-section-inner-wrap"><div id="code_block-41-54620" class="ct-code-block"><?php echo the_content(); ?></div></div></section>
