<?php
/**
 * Gabarit « Projets - Single » (ex-Oxygen 1584), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* Image */ ?><section id="section-22-1584" class="ct-section" style="background-image:linear-gradient(rgba(0,0,0,0.21), rgba(0,0,0,0.21)), url(<?php echo esc_url((string) get_the_post_thumbnail_url(null, 'full')); ?>);background-size:auto,  cover;"><div class="ct-section-inner-wrap"></div></section><?php /* Breadcrumb */ ?><section id="section-23-1584" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-24-1584" class="ct-div-block"><?php /* Breadcrumbs */ ?><?php sx_oxy_part(1819); ?></div></div></section><?php /* Contenu */ ?><section id="section-1-1584" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Rangéee */ ?><div id="div_block-2-1584" class="ct-div-block c-full-width c-owl-s"><h1 id="code_block-3-1584" class="ct-code-block c-heading-dark c-h2"><?php the_title(); ?></h1><?php if (sx_compare(sx_fn('get_field', 'description'), 'is_not_blank', '')) : ?><div id="code_block-68-1584" class="ct-code-block"><?= the_field("description"); ?></div><?php endif; ?><?php if (sx_compare(sx_fn('get_field', 'architecte'), 'is_not_blank', '')) : ?><div id="code_block-4-1584" class="ct-code-block"><span class="c-bold c-heading-dark"><?php the_field("projet_texte_architecte", "option") ?></span>

<?php if(get_field("architecte_url")) { ?>
	<a href="<?php the_field("architecte_url"); ?>" rel="nofollow" target="_blank" class="c-text-dark">
<?php } ?>
		
<div><?php the_field("architecte"); ?></div>
		
<?php if(get_field("architecte_url")) { ?>
	</a>
<?php } ?>		</div><?php endif; ?><?php if (sx_compare(sx_fn('get_field', 'entrepreneur'), 'is_not_blank', '')) : ?><div id="code_block-6-1584" class="ct-code-block"><span class="c-bold c-heading-dark"><?php the_field("projet_texte_entrepreneur", "option") ?></span>

<?php if(get_field("entrepreneur_url")) { ?>
	<a href="<?php the_field("entrepreneur_url"); ?>" rel="nofollow" target="_blank" class="c-text-dark">
<?php } ?>
		
<div><?php the_field("entrepreneur"); ?></div>
		
<?php if(get_field("entrepreneur_url")) { ?>
	</a>
<?php } ?>		</div><?php endif; ?><?php if (sx_compare(sx_fn('get_field', 'photographe'), 'is_not_blank', '')) : ?><div id="code_block-33-1584" class="ct-code-block"><span class="c-bold c-heading-dark"><?php the_field("projet_texte_photographe", "option") ?></span>

<?php if(get_field("photographe_url")) { ?>
	<a href="<?php the_field("photographe_url"); ?>" rel="nofollow" target="_blank" class="c-text-dark">
<?php } ?>
		
<div><?php the_field("photographe"); ?></div>
		
<?php if(get_field("photographe_url")) { ?>
	</a>
<?php } ?>		</div><?php endif; ?><?php if (sx_compare(sx_fn('get_field', 'no_commande'), 'is_not_blank', '')) : ?><div id="code_block-34-1584" class="ct-code-block"><span class="c-bold c-heading-dark"><?php the_field("projet_no_commande", "option") ?></span>

<div class="c-text-dark"><?php the_field("no_commande"); ?></div>	</div><?php endif; ?></div></div></section><?php /* Revêtements */ ?><section id="section-7-1584" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-8-1584" class="ct-div-block c-bg-light c-full-width c-owl-m c-padding-l"><h2 id="code_block-10-1584" class="ct-code-block c-h6 c-heading-dark"><?php the_field("projet_texte_revetements", "option") ?></h2><div id="code_block-21-1584" class="ct-code-block c-full-width"><?php output_tableau(); ?></div></div></div></section><?php /* Galerie */ ?><section id="section-11-1584" class="ct-section c-owl-l"><div class="ct-section-inner-wrap"><?php if ((bool) get_field('galerie')) : ?><?php sx_gallery('_gallery-12-1584', '', '{"gallery_source": "acf", "acf_field": "galerie", "gallery_thumbnail_size": "full", "layout": "false", "display": "grid", "gallery_captions": "no"}'); ?><?php endif; ?><?php /* Retour */ ?><div id="div_block-29-1584" class="ct-div-block c-padding-top-m c-padding-bottom-m c-full-width"><a id="link-31-1584" class="ct-link c-inline c-columns-gap-m c-heading-dark" href="<?php echo esc_attr(sx_fn('get_field', 'lien_projets_btn_lien', 'option')); ?>" target="_self"><div id="fancy_icon-30-1584" class="ct-fancy-icon"><svg id="svg-fancy_icon-30-1584"><use xlink:href="#Lineariconsicon-arrow-left"></use></svg></div><div id="code_block-32-1584" class="ct-code-block"><?php the_field("projet_texte_retour", "option") ?></div></a></div></div></section><?php /* CTA */ ?><?php sx_oxy_part(149); ?>
