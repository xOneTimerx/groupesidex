<?php
/**
 * Gabarit « - 404 » (ex-Oxygen 74), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* 404 */ ?><section id="section-3-74" class="ct-section 1"><div class="ct-section-inner-wrap"><img id="image-5-74" alt="icon-sidex-black" src="/wp-content/uploads/icon-sidex-black.svg" class="ct-image c-margin-bottom-l"/><h1 id="headline-6-74" class="ct-headline c-h1 c-heading-dark c-margin-bottom-m"><span id="span-9-74" class="ct-span"><?php echo sx_kses(sx_fn('get_field', '404_titre', 'option')); ?></span></h1><div id="text_block-7-74" class="ct-text-block c-text-dark c-margin-bottom-m"><span id="span-11-74" class="ct-span"><?php echo sx_kses(sx_fn('get_field', '404_texte', 'option')); ?></span></div><a id="link_button-8-74" class="ct-link-button c-btn-main c-btn-m c-transition" href="<?php echo esc_attr(sx_fn('home_url')); ?>" target="_self" role="button"><span id="span-22-74" class="ct-span"><?php echo sx_kses(sx_fn('acf_link_field', '404_bouton', 'title', 'true')); ?></span></a></div></section>
