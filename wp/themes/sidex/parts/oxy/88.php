<?php
/**
 * Gabarit « - Résultats de recherche » (ex-Oxygen 88), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<section id="section-2-88" class="ct-section"><div class="ct-section-inner-wrap"><div id="div_block-3-88" class="ct-div-block"><div id="div_block-21-88" class="ct-div-block"><h1 id="headline-22-88" class="ct-headline c-heading-dark c-h4">Résultats de recherche pour : <span id="span-23-88" class="ct-span c-heading-accent"><?php echo sx_kses(sx_fn('the_search_query')); ?></span></h1></div><div id="_dynamic_list-4-88" class="oxy-dynamic-list 1 2"><?php $sx_i1 = 0; while (have_posts()) : the_post(); $sx_i1++; ?><div id="div_block-5-88-<?php echo (int) $sx_i1; ?>" data-id="div_block-5-88" class="ct-div-block c-padding-m"><div id="text_block-12-88-<?php echo (int) $sx_i1; ?>" data-id="text_block-12-88" class="ct-text-block c-h4 c-margin-bottom-s"><span id="span-16-88-<?php echo (int) $sx_i1; ?>" data-id="span-16-88" class="ct-span"><?php echo sx_kses(get_the_title()); ?></span></div><div id="text_block-18-88-<?php echo (int) $sx_i1; ?>" data-id="text_block-18-88" class="ct-text-block"><span id="span-20-88-<?php echo (int) $sx_i1; ?>" data-id="span-20-88" class="ct-span"><?php echo sx_kses(get_the_excerpt()); ?></span></div></div><?php endwhile; ?></div></div></div></section>
