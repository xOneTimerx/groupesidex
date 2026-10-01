<?php
/**
 * Gabarit « Page - Carrières » (ex-Oxygen 120), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* En-tête */ ?><section id="section-2-120" class="ct-section c-bg-dark"><div class="ct-section-inner-wrap"><?php /* Wrapper */ ?><div id="div_block-3-120" class="ct-div-block c-owl-m c-max-width-960 c-center c-center-self c-padding-left-l c-padding-right-l c-padding-top-xxl c-padding-bottom-xxl c-bg-light"><h1 id="code_block-4-120" class="ct-code-block c-tagline c-heading-dark"><?php the_field("entete_sous_titre"); ?></h1><h5 id="code_block-5-120" class="ct-code-block c-heading-dark title-reveal c-h1"><?php the_field("entete_titre"); ?></h5><div id="code_block-6-120" class="ct-code-block"><?php the_field("entete_contenu"); ?></div><?php /* Boutons */ ?><div id="div_block-9-120" class="ct-div-block c-buttons"><div id="code_block-7-120" class="ct-code-block"><a href="#<?php the_field("entete_btn_1_ancre"); ?>" class="c-btn-transparent c-btn-m c-transition"><?php the_field("entete_btn_1_titre"); ?></a></div><div id="code_block-8-120" class="ct-code-block"><a href="#<?php the_field("entete_btn_2_ancre"); ?>" class="c-btn-main c-btn-m c-transition"><?php the_field("entete_btn_2_titre"); ?></a></div></div></div></div></section><?php /* Avantages */ ?><section id="section-11-120" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Avantages */ ?><div id="_dynamic_list-12-120" class="oxy-dynamic-list c-columns-3 c-columns-l-2 c-columns-m-1 c-columns-gap-l"><?php $sx_i1 = 0; if (have_rows('field_644ad8a6660e4', sx_row_owner('field_644ad8a6660e4'))) : while (have_rows('field_644ad8a6660e4', sx_row_owner('field_644ad8a6660e4'))) : the_row(); $sx_i1++; ?><?php /* Wrapper */ ?><div id="div_block-13-120-<?php echo (int) $sx_i1; ?>" data-id="div_block-13-120" class="ct-div-block c-owl-s"><div id="div_block-66-120-<?php echo (int) $sx_i1; ?>" data-id="div_block-66-120" class="ct-div-block c-bg-light" data-aos="fade" data-aos-once="true"><img id="image-16-120-<?php echo (int) $sx_i1; ?>" data-id="image-16-120" alt="" src="<?php echo esc_url(sx_fn('get_sub_field', 'image')); ?>" class="ct-image"/></div><h4 id="code_block-29-120-<?php echo (int) $sx_i1; ?>" data-id="code_block-29-120" class="ct-code-block c-text-l c-bold c-heading-dark"><?php
	the_sub_field("titre");
?></h4><div id="code_block-30-120-<?php echo (int) $sx_i1; ?>" data-id="code_block-30-120" class="ct-code-block"><?php
	the_sub_field("contenu");
?></div></div><?php endwhile; endif; ?></div></div></section><?php /* Vie chez Sidex */ ?><section id="section-42-120" class="ct-section c-bg-light-alt c-inline"><div class="ct-section-inner-wrap"><div id="div_block-78-120" class="ct-div-block c-columns-3 c-columns-l-1"><?php /* Gauche */ ?><div id="div_block-43-120" class="ct-div-block" data-paroller-factor="0.1" data-paroller-type="foreground"><img id="image-44-120" alt="cp-animation-nouveau-projets-01" src="<?php echo esc_url(sx_fn('get_field', 'vie_image_gauche')); ?>" class="ct-image"/></div><?php /* Rangée */ ?><div id="div_block-45-120" class="ct-div-block c-center c-full-width c-center-self c-owl-m c-max-width-960"><h4 id="code_block-46-120" class="ct-code-block c-tagline c-heading-accent-alt"><?php the_field("vie_sous_titre"); ?></h4><h5 id="code_block-47-120" class="ct-code-block c-h3 c-heading-dark"><?php the_field("vie_titre"); ?></h5><div id="code_block-48-120" class="ct-code-block"><?php the_field("vie_contenu"); ?></div></div><?php /* Droite */ ?><div id="div_block-50-120" class="ct-div-block" data-paroller-factor="0.1" data-paroller-type="foreground"><img id="image-51-120" alt="nouveaux-appartements-02" src="<?php echo esc_url(sx_fn('get_field', 'vie_image_droite')); ?>" class="ct-image"/></div></div></div></section><?php /* Galerie photo */ ?><section id="section-80-120" class="ct-section"><div class="ct-section-inner-wrap"><style data-element-id="#_gallery-81-120">#_gallery-81-120.oxy-gallery.oxy-gallery-masonry {
                    column-width: px;
                    column-count: 4;
                    column-gap: 10px;
                }

                #_gallery-81-120.oxy-gallery-masonry .oxy-gallery-item {
                    margin-bottom: 10px;
                }
        
            #_gallery-81-120.oxy-gallery-captions .oxy-gallery-item .oxy-gallery-item-contents figcaption:not(:empty) {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background-color: rgba(0,0,0,0.75); /* caption background color */
                padding: 1em;
                color: #ffffff;  /* caption text color */
                font-weight: bold;
                -webkit-font-smoothing: antialiased;
                font-size: 1em;
                text-align: center;
                line-height: var(--oxy-small-line-height);
                /*pointer-events: none;*/
                transition: 0.3s ease-in-out opacity;
                display: block;
            }

                
            #_gallery-81-120.oxy-gallery-captions .oxy-gallery-item .oxy-gallery-item-contents figcaption:not(:empty) {
                opacity: 0;
            }
            #_gallery-81-120.oxy-gallery-captions .oxy-gallery-item:hover .oxy-gallery-item-contents figcaption {
                opacity: 1;
            }

                    
            /* hover effects */
            #_gallery-81-120.oxy-gallery .oxy-gallery-item {
              opacity: ;
              transition: 0.3s ease-in-out opacity;
            }

            #_gallery-81-120.oxy-gallery .oxy-gallery-item:hover {
              opacity: ;
            }</style><?php sx_gallery('_gallery-81-120', '', '{"gallery_source": "medialibrary", "image_ids": "52456,52458,52452,52454", "layout": "masonry"}'); ?></div></section><?php /* Postes disponibles */ ?><section id="postes" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-67-120" class="ct-div-block c-center c-full-width c-center-self c-owl-m c-padding-m c-max-width-960 c-margin-bottom-l"><h2 id="code_block-68-120" class="ct-code-block c-tagline c-heading-accent-alt"><?php the_field("postes_sous_titre"); ?></h2><h5 id="code_block-69-120" class="ct-code-block c-h3 c-heading-dark"><?php the_field("postes_titre"); ?></h5></div><?php /* Emplois */ ?><div id="_dynamic_list-32-120" class="oxy-dynamic-list"><?php $sx_i1 = 0; if (have_rows('field_644ad8f7660ea', sx_row_owner('field_644ad8f7660ea'))) : while (have_rows('field_644ad8f7660ea', sx_row_owner('field_644ad8f7660ea'))) : the_row(); $sx_i1++; ?><?php /* Wrapper */ ?><div id="div_block-33-120-<?php echo (int) $sx_i1; ?>" data-id="div_block-33-120" class="ct-div-block c-owl-s"><div id="-pro-accordion-65-120-<?php echo (int) $sx_i1; ?>" data-id="-pro-accordion-65-120" class="oxy-pro-accordion accordeons "><div class="oxy-pro-accordion_inner" data-icon="animate" data-expand="300" data-repeater="disable" data-repeater-first="false" data-acf="closed" data-type="manual" data-disablesibling="false"> <div class="oxy-pro-accordion_item " data-init="closed"><button id="header-pro-accordion-65-120-<?php echo (int) $sx_i1; ?>" class="oxy-pro-accordion_header" aria-expanded="false" aria-controls="body-pro-accordion-65-120" data-id="header-pro-accordion-65-120"><span class="oxy-pro-accordion_title-area"><h3 class="oxy-pro-accordion_title"><?php echo sx_kses(sx_fn('get_sub_field', 'titre')); ?></h3><span class="oxy-pro-accordion_subtitle"><?php echo sx_kses(sx_fn('get_sub_field', 'sous_titre')); ?></span></span><span class="oxy-pro-accordion_icon oxy-pro-accordion_icon-animate"><svg id="toggle-pro-accordion-65-120-<?php echo (int) $sx_i1; ?>" class="oxy-pro-accordion_toggle-icon" data-id="toggle-pro-accordion-65-120"><use xlink:href="#Lineariconsicon-arrow-down"></use></svg></span></button><div id="body-pro-accordion-65-120-<?php echo (int) $sx_i1; ?>" class="oxy-pro-accordion_body" aria-labelledby="header-pro-accordion-65-120-<?php echo (int) $sx_i1; ?>" role="region" data-id="body-pro-accordion-65-120"><div class="oxy-pro-accordion_content oxy-inner-content"><div id="code_block-36-120-<?php echo (int) $sx_i1; ?>" data-id="code_block-36-120" class="ct-code-block"><?php
	the_sub_field("contenu");
?></div><div id="code_block-41-120-<?php echo (int) $sx_i1; ?>" data-id="code_block-41-120" class="ct-code-block"><a href="#candidature" class="c-btn-transparent c-btn-m c-transition" data-emploi="<?= get_sub_field("titre") ?>" onclick="updatePoste(this)"><?php the_field("texte_appliquer")?></a></div></div></div></div></div></div></div><?php endwhile; endif; ?></div></div></section><?php /* Candidature */ ?><section id="candidature" class="ct-section c-bg-light c-owl-l"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-53-120" class="ct-div-block c-center c-center-self c-owl-m"><img id="image-70-120" alt="icon-sidex-black" src="/wp-content/uploads/icon-sidex-black.svg" class="ct-image" data-aos="fade-down" data-aos-once="true"/><div id="code_block-54-120" class="ct-code-block c-heading-dark c-h1"><?php the_field("formulaire_titre"); ?></div><div id="code_block-56-120" class="ct-code-block c-max-width-640"><?php the_field("formulaire_contenu"); ?></div></div><div id="-fluent-form-59-120" class="oxy-fluent-form c-max-width-960 c-center-self form-light"><?php sx_fluent_form((int) sx_fn('get_field', 'id_fluent_forms')); ?></div></div></section><?php /* Bande déroulante */ ?><section id="section-62-120" class="ct-section"><div class="ct-section-inner-wrap"><?php /* Rangée */ ?><div id="div_block-63-120" class="ct-div-block"><div id="code_block-64-120" class="ct-code-block"><?php 

$scrollingTypo = get_field("texte_animation");

?>

<div class="marquee marquee-ltr">
	<div class="track">
		<div class="scrolling-typo-text">
			<ul>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
				<li class="stroke-dark"><?php echo $scrollingTypo; ?></li>
			</ul>
	  	<div>
	</div>
</div></div></div></div></section>
