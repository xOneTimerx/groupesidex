<?php
/**
 * Gabarit « Page - Documents techniques » (ex-Oxygen 1640), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* En-tête */ ?><?php sx_oxy_part(1634); ?><div id="code_block-291-1640" class="ct-code-block"><?php 
	if(have_rows('sections')) { ?>
		<style>
			.sections-onglets .oxy-pro-accordion_title {
				display: flex;
    			flex-direction: row;
    			align-items: center;
				grid-gap: var(--m-space);
			}
			
			.sections-onglets .oxy-pro-accordion_title:before {
  				width: 180px;
  				height: 180px;
  				display: block;
			}
		
			<?php while(have_rows('sections')) {
				the_row();
				$indexSection = get_row_index();
	
				if(have_rows('onglets')) {
					while(have_rows('onglets')) {
						the_row();
					
						if(get_sub_field('image')) { ?>
							.section-<?php echo $indexSection; ?>-onglets .onglet-<?php echo get_row_index(); ?> .oxy-pro-accordion_title:before {
								content: '';
  								background:url(<?php the_sub_field('image'); ?>);
								background-size: cover;
							}
				 		<?php }
					}
				}
			} ?>
		</style>
	<?php }
?></div><?php /* Onglets */ ?><section id="section-478-1640" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Sections */ ?><div id="_dynamic_list-473-1640" class="oxy-dynamic-list c-owl-xl sections-onglets"><?php $sx_i1 = 0; if (have_rows('field_649c2d48750f9', sx_row_owner('field_649c2d48750f9'))) : while (have_rows('field_649c2d48750f9', sx_row_owner('field_649c2d48750f9'))) : the_row(); $sx_i1++; ?><?php /* Wrapper */ ?><div id="div_block-474-1640-<?php echo (int) $sx_i1; ?>" data-id="div_block-474-1640" class="ct-div-block c-owl-m"><div id="code_block-484-1640-<?php echo (int) $sx_i1; ?>" data-id="code_block-484-1640" class="ct-code-block c-heading-dark c-h5"><h4 id="documents-section-<?php echo get_row_index(); ?>" class="c-h5 c-bold"><?php the_sub_field("titre_section"); ?></h4></div><?php /* Onglets */ ?><div id="_dynamic_list-485-1640-<?php echo (int) $sx_i1; ?>" data-id="_dynamic_list-485-1640" class="oxy-dynamic-list onglets c-columns-2 c-columns-m-1 c-columns-gap-l"><?php $sx_i2 = 0; if (have_rows('field_649c2d9875100', sx_row_owner('field_649c2d9875100'))) : while (have_rows('field_649c2d9875100', sx_row_owner('field_649c2d9875100'))) : the_row(); $sx_i2++; ?><?php /* Wrapper */ ?><div id="div_block-486-1640-<?php echo (int) $sx_i2; ?>" data-id="div_block-486-1640" class="ct-div-block c-full-width"><?php /* Wrapper */ ?><div id="div_block-488-1640-<?php echo (int) $sx_i2; ?>" data-id="div_block-488-1640" class="ct-div-block c-columns-m-1 c-padding-s c-bg-light c-inline c-columns-2-3 c-columns-gap-l"><div id="-lightbox-975-1640-<?php echo (int) $sx_i2; ?>" data-id="-lightbox-975-1640" class="oxy-lightbox woocommerce"><div id="link-lightbox-975-1640-<?php echo (int) $sx_i2; ?>" data-id="link-lightbox-975-1640" class="oxy-lightbox_link "><img id="image-976-1640-<?php echo (int) $sx_i2; ?>" data-id="image-976-1640" alt="" src="<?php echo esc_url(sx_sub('image')); ?>" class="ct-image"/></div><div class="oxy-lightbox_inner oxy-inner-content" data-src="<?php echo esc_url(sx_sub('image')); ?>" data-multiple="false" data-loop="false" data-type="image" data-small-btn="true" data-iframe-preload="true" data-toolbar="true" data-thumbs="false" data-duration="300" data-fullscreen="" data-autofocus="true" data-backfocus="true" data-trapfocus="true" data-nav-icon="FontAwesomeicon-chevron-left" data-close-icon="FontAwesomeicon-close" data-small-close-icon="Lineariconsicon-cross" data-zoom-icon="FontAwesomeicon-search" data-share-icon="" data-download-icon="FontAwesomeicon-download" data-prepend="false" data-swipe="false"></div></div><div id="div_block-951-1640-<?php echo (int) $sx_i2; ?>" data-id="div_block-951-1640" class="ct-div-block"><div id="div_block-956-1640-<?php echo (int) $sx_i2; ?>" data-id="div_block-956-1640" class="ct-div-block c-full-width c-padding-bottom-m c-margin-bottom-m"><div id="code_block-950-1640-<?php echo (int) $sx_i2; ?>" data-id="code_block-950-1640" class="ct-code-block c-heading-dark c-h5"><h5 id="" class="c-h5"><?php the_sub_field("titre_onglet"); ?></h5></div></div><?php if (have_rows('documents')) { ?>
    <div class="c-margin-bottom-s"> 
        <?php while (have_rows('documents')) {
            the_row();
            $document = get_sub_field("document");
            $lien = get_sub_field("lien"); // ACF link type field
            $titre_document = get_sub_field("titre_document");
            $document_ou_lien_externe = get_sub_field("document_ou_lien_externe"); // Boolean field ?>

            <div>
                <?php if ($document && $document_ou_lien_externe) { ?>
                    <!-- Show document link only if 'document' is not empty and 'document_ou_lien_externe' is true -->
                    <a class="tm-link tm-link-download c-transition" href="<?php echo esc_url($document); ?>" target="_blank">
                        <?php echo esc_html($titre_document); ?>
                    </a>
                <?php } elseif ($lien) { 
                    // Check for external link as fallback
                    $lien_url = esc_url($lien['url']);
                    $lien_title = esc_html($lien['title']);
                    $lien_target = $lien['target'] ? 'target="_blank"' : ''; ?>
                    
                    <!-- Show external link if 'document' does not meet conditions -->
                    <a class="tm-link c-transition" href="<?php echo $lien_url; ?>" <?php echo $lien_target; ?>>
                        <?php echo $lien_title ?: esc_html($titre_document); ?>
                    </a>
                <?php } ?>
            </div>  
        <?php } ?>  
    </div>
<?php } ?>
</div></div></div><?php endwhile; endif; ?></div></div><?php endwhile; endif; ?></div></div></section><?php /* Vidéos */ ?><section id="section-60-1640" class="ct-section c-owl-xl"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="videos" class="ct-div-block c-center c-full-width c-owl-m"><h3 id="code_block-62-1640" class="ct-code-block c-tagline c-heading-accent-alt"><?php the_field("videos_sous_titre"); ?></h3><h5 id="code_block-63-1640" class="ct-code-block c-h3 c-heading-dark"><?php the_field("videos_titre"); ?></h5></div><?php /* Vidéos */ ?><div id="_dynamic_list-64-1640" class="oxy-dynamic-list c-columns-3 c-columns-gap-m c-columns-l-2 c-columns-m-1"><?php $sx_i1 = 0; if (have_rows('field_644c0cb1bc2b0', sx_row_owner('field_644c0cb1bc2b0'))) : while (have_rows('field_644c0cb1bc2b0', sx_row_owner('field_644c0cb1bc2b0'))) : the_row(); $sx_i1++; ?><?php /* Wrapper */ ?><div id="div_block-65-1640-<?php echo (int) $sx_i1; ?>" data-id="div_block-65-1640" class="ct-div-block c-full-width c-owl-s"><div id="code_block-68-1640-<?php echo (int) $sx_i1; ?>" data-id="code_block-68-1640" class="ct-code-block c-full-width"><div class="video_wrapper"><iframe src="https://www.youtube.com/embed/<?php the_sub_field("code_video"); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div></div><div id="code_block-71-1640-<?php echo (int) $sx_i1; ?>" data-id="code_block-71-1640" class="ct-code-block c-text-l c-bold c-heading-dark"><?php the_sub_field("titre"); ?></div></div><?php endwhile; endif; ?></div></div></section>
