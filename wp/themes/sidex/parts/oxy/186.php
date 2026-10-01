<?php
/**
 * Gabarit « Articles - Card » (ex-Oxygen 186), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<a id="link-10-77" class="ct-link c-owl-l" href="<?php echo esc_attr(get_permalink()); ?>" target="_self"><?php echo sx_img((int) (string) get_post_thumbnail_id(), 'full', 'ct-image c-full-width', 'id="image-15-186"', ''); ?><?php /* Content */ ?><div id="div_block-12-77" class="ct-div-block c-owl-m"><div id="code_block-12-186" class="ct-code-block c-text-l c-heading-dark c-bold"><?php the_title(); ?></div><div id="code_block-13-186" class="ct-code-block c-text-s c-text-dark"><?php the_excerpt(); ?></div><div id="code_block-14-186" class="ct-code-block c-text-s"><?php
$text = apply_filters('wpml_current_language', null) == 'fr' ? "Lire plus" : "Read more";
?>

<span class="tm-link tm-link-arrow-right"><?php echo $text; ?></span></div></div></a>
