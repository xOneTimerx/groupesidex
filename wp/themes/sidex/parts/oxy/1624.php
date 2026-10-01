<?php
/**
 * Gabarit « = Bloc - FAQ » (ex-Oxygen 1624), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* FAQ */ ?><section id="section-81-1593" class="ct-section c-columns-gap-l c-bg-light-alt"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-82-1593" class="ct-div-block c-owl-m c-max-width-960 c-center c-center-self"><div id="code_block-83-1593" class="ct-code-block c-tagline c-heading-accent-alt"><?php the_field("bloc_faq_sous_titre", "option"); ?></div><div id="code_block-84-1593" class="ct-code-block c-heading-dark c-h3 title-reveal"><?php the_field("bloc_faq_titre", "option"); ?></div></div><?php sx_accordion('-pro-accordion-6-1624', 'accordeons', 'faq', 'titre', 'contenu', 'Lineariconsicon-arrow-down', 'h5', '', 'closed'); ?></div></section>
