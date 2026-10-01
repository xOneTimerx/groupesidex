<?php
/**
 * Fonctions d'Advanced Scripts appelées par les gabarits convertis (scripts 67, 86, 137, 139, 146, 266),
 * recopiées telles quelles par tools/extract_as_functions.py le 1er oct. 2026. Protégées par function_exists :
 * tant qu'Advanced Scripts est actif, ses versions identiques sont celles qui s'exécutent.
 */

defined('ABSPATH') || exit;

/* ── Advanced Scripts 139 ── */
if (!function_exists('acf_link_subfield')) {
function acf_link_subfield($field_name, $type) {
	$link = get_sub_field($field_name);
	if(!$link) {
	    return;
	}
	$link_output = ($type == 'url') ? esc_url($link['url']) : esc_html($link['title']);
	return $link_output;
}
}

if (!function_exists('acf_link_field')) {
function acf_link_field($field_name, $type, $option = "false") {
	$link = ($option == 'true') ? get_field($field_name, 'option') : get_field($field_name);
	if(!$link) {
	    return;
	}
	$link_output = ($type == 'url') ? esc_url($link['url']) : esc_html($link['title']);
	return $link_output;
}
}

if (!function_exists('get_field_image_alt')) {
function get_field_image_alt($field) {
    if(get_field($field)) {
        return get_alt_from_url(get_field($field));
    }
    return "";
}
}

if (!function_exists('get_option_field_image_alt')) {
function get_option_field_image_alt($field) {
    if(get_field($field, "option")) {
        return get_alt_from_url(get_field($field, "option"));
    }
    return "";
}
}

if (!function_exists('get_sub_field_image_alt')) {
function get_sub_field_image_alt($field) {
    if(get_sub_field($field)) {
        return get_alt_from_url(get_sub_field($field));
    }
    return "";
}
}

if (!function_exists('get_alt_from_url')) {
function get_alt_from_url($url) {
    $id = attachment_url_to_postid($url);
    if($id) {
        return get_post_meta($id, "_wp_attachment_image_alt", true);
    }
    return "";
}
}

if (!function_exists('get_copyright_text')) {
function get_copyright_text() {
	$text = get_field("texte_copyright", 'option');
	$text = str_replace("{{nom_compagnie}}", get_field("nom_compagnie", 'option'), $text);
	$text = str_replace("{{year}}", date("Y"), $text);
	echo $text;
}
}

if (!function_exists('generate_text')) {
function generate_text($field) {
	$text = get_field($field, 'option');
	$text = str_replace("{{nom_compagnie}}", get_field("nom_compagnie", 'option'), $text);
	$text = str_replace("{{nom_contact}}", get_field("nom_contact_politiques", 'option'), $text);
	$text = str_replace("{{titre_contact}}", get_field("titre_contact_politiques", 'option'), $text);
	$courriel = get_field("courriel_contact_politiques", "option");
	$text = str_replace("{{courriel_contact}}", "<a href='mailto:" . $courriel . "'>" . $courriel . "</a>", $text);
	$lien_site_web = get_field("site_web_compagnie_politiques", 'option');
	$text = str_replace("{{site_web_compagnie_politiques}}", "<a href='" . $lien_site_web . "'>" . $lien_site_web . "</a>", $text);
	$lien_nous_joindre = get_field("lien_nous_joindre_politiques", 'option');
	$text = str_replace("{{lien_nous_joindre_politiques}}", "<a href='" . $lien_nous_joindre . "'>" . $lien_nous_joindre . "</a>", $text);
	return $text;
}
}

if (!function_exists('get_nb_posts')) {
function get_nb_posts($post_type) {
    $count_posts = wp_count_posts($post_type);
    $published_posts = $count_posts->publish;
    return $published_posts;
}
}

if (!function_exists('get_translated_link')) {
function get_translated_link() {
	global $wp;
	if ( function_exists('icl_object_id') ) {
	    $current_url = home_url( add_query_arg( array(), $wp->request ) );
	    
	    if(ICL_LANGUAGE_CODE == 'fr') {
	        // À ADAPTER DANS LE CAS OÙ IL Y A DES ARCHIVES
	        




	        return apply_filters( 'wpml_permalink', $current_url, 'en' );
	    }
    
        // À ADAPTER DANS LE CAS OÙ IL Y A DES ARCHIVES
	    




	    return apply_filters( 'wpml_permalink', $current_url, 'fr' );
	}
}
}

if (!function_exists('get_entete_field')) {
function get_entete_field($field) {
    $acf_param = is_singular() ? get_the_ID() : get_acf_param_tax();
    if(get_field($field, $acf_param)) {
        return get_field($field, $acf_param);
    }
    return "";
}
}

if (!function_exists('get_entete_link')) {
function get_entete_link() {
    $link = "";
    $acf_param = is_singular() ? get_the_ID() : get_acf_param_tax();
    
    if(get_field("entete_btn_lien", $acf_param)) {
        $link .= get_field("entete_btn_lien", $acf_param);
    } 
    
    if(get_field("entete_btn_ancre", $acf_param)) {
        $link .= "#" . get_field("entete_btn_ancre", $acf_param);
    } 
    
    return $link;
}
}

if (!function_exists('get_acf_param_tax')) {
function get_acf_param_tax() {
    if(!is_null(get_queried_object())) {
        $tax_name = get_queried_object()->taxonomy;
        $term_id = get_queried_object()->term_id;
        return $tax_name . "_" . $term_id;
    }
    return "";
}
}

/* ── Advanced Scripts 67 ── */
if (!function_exists('output_tableau')) {
function output_tableau() {
    //echo get_field('projet_texte_essence', 'option');
    if(have_rows('revetements')) {
        $headers = array("Image", get_field('projet_texte_essence', 'option'), get_field("projet_texte_modele", "option"), get_field("projet_texte_fini", "option"), get_field("projet_texte_finition", "option"), get_field("projet_texte_couleur", "option"), "");
        output_tableau_header($headers);
		while(have_rows('revetements')) {
		    the_row();
			output_tableau_content_row();
		}
	} 
}
}

if (!function_exists('output_tableau_header')) {
function output_tableau_header($headers) { ?>
    <div class="c-columns-gap-s flexbox flex-row display-none-to-xs padding-vertical-micro border-bottom-slimmest text-current align-items-center">
    <?php foreach($headers as $header) { ?>
        <div class="width-1_4-from-xs c-heading-dark c-bold"><?php echo $header; ?></div>
    <?php } ?>
    </div>
<?php }
}

if (!function_exists('output_tableau_content_row')) {
function output_tableau_content_row() { ?>
    <div class="c-columns-gap-s flexbox-from-xs flex-row padding-vertical-micro border-bottom-slimmest align-items-center">
	    
		<?php 
		
		$modele = get_sub_field("largeur") ? get_value_title("modele") . " - " . get_sub_field("largeur") : get_value_title("modele");
		
		output_tableau_content_row_column_img("Image", get_sub_field("image"));
		output_tableau_content_row_column_link(get_field("projet_texte_essence", "option"), "essence");
		output_tableau_content_row_column(get_field("projet_texte_modele", "option"), $modele);
		output_tableau_content_row_column(get_field("projet_texte_fini", "option"), get_value_title("fini"));
		output_tableau_content_row_column(get_field("projet_texte_finition", "option"), get_value_title("finition"));
		output_tableau_content_row_column(get_field("projet_texte_couleur", "option"), get_sub_field("couleur"));
		
		?>
				
		<div class="width-1_4-from-xs flexbox-to-xs flex-row c-inline c-columns-gap-s">
		    <div class="c-btn-transparent c-btn-s c-transition multi-trigger-soumission btn-tableau" data-essence="<?php echo get_value_title("essence"); ?>" data-modele="<?php echo get_value_title("modele"); ?>"><?php echo get_field("projet_texte_soumission", "option") ?></div> 
		</div>
	</div>
<?php }
}

if (!function_exists('get_field_link')) {
function get_field_link($field) {
    if(get_sub_field($field)) {
        $id = get_sub_field($field);
        return get_permalink($id);
    }
}
}

if (!function_exists('get_value_title')) {
function get_value_title($field) {
    if(get_sub_field($field)) {
        $id = get_sub_field($field);
        return get_the_title($id);
    }
    return "";
}
}

if (!function_exists('output_tableau_content_row_column_img')) {
function output_tableau_content_row_column_img($label, $value) { ?>
    <div class="width-1_4-from-xs flexbox-to-xs flex-row margin-vertical-micro-to-xs">
	    <img class="tableau-img" src="<?php echo $value; ?>"/>
	</div>
<?php }
}

if (!function_exists('output_tableau_content_row_column_link')) {
function output_tableau_content_row_column_link($label, $field) { ?>
    <div class="width-1_4-from-xs flexbox-to-xs flex-row margin-vertical-micro-to-xs c-text-s">
	    <span class="display-none-from-xs"><?php echo $label; ?></span>
		    <div class="display-none-from-xs table-border"></div>
			<a href="<?php echo get_field_link($field); ?>" class="c-text-accent"><?php echo get_value_title($field); ?></a>
	</div>
<?php }
}

if (!function_exists('output_tableau_content_row_column')) {
function output_tableau_content_row_column($label, $value) { ?>
    <div class="width-1_4-from-xs flexbox-to-xs flex-row margin-vertical-micro-to-xs c-text-s">
	    <span class="display-none-from-xs"><?php echo $label; ?></span>
		    <div class="display-none-from-xs table-border"></div>
			<span><?php echo $value; ?></span>
	</div>
<?php }
}

/* ── Advanced Scripts 86 ── */
if (!function_exists('output_types_new')) {
function output_types_new() {
    $ids = get_sub_field("types");
    if($ids) {
        $args = array(
	        'post_type' => array("etapes", "etapes_cachees"),
	        'post__in' => $ids,
	        'orderby' => "post__in",
	        'order' => "orderby",
        );

        $types = new WP_Query($args);
        
        ?>
        <div class="c-columns-3 c-columns-m-1 c-columns-gap-m">
        <?php
        if($types->have_posts()) {
    	    while($types->have_posts()) {
    		    $types->the_post(); ?>

    		    <div class="c-owl-s">
                    <?php (get_post_type() == 'etapes') ? output_image_etape('large') : output_image_couleur('large'); ?>
    			    <div>
    				    <?php if(get_post_type() == 'etapes') { ?><a href="<?php the_permalink(); ?>"><?php } ?>
    				        <h4 class="c-text-m c-bold c-heading-dark"><?php the_title(); ?></h4>
    				    <?php if(get_post_type() == 'etapes') { ?></a><?php } ?>    
    				    <div class="c-text-xs c-max-width-320"><?php the_field("revetements_contenu"); ?></div>
    				    <div style="display: flex;">
    					    <?php if(get_post_type() == 'etapes') { ?>
    					        <a href="<?php the_permalink(); ?>" class="tm-link tm-link-arrow-right panel-learn-more-link"><?php the_field("texte_en_savoir_plus", "option"); ?></a>
    					    <?php } ?>     
    				    </div>
    			    </div>
    		    </div>
    	<?php }
        }?>
        </div>
    <?php
        wp_reset_query();
    }
}
}

if (!function_exists('output_image_couleur')) {
function output_image_couleur($size) { 
    $src = get_the_post_thumbnail_url(get_the_ID(), $size); ?>
    <img class="glightbox-couleurs" src="<?php echo $src; ?>" data-glightbox="title: <?php the_title(); ?>">
<?php }
}

if (!function_exists('output_image_etape')) {
function output_image_etape($size) { 
    $src = get_the_post_thumbnail_url(get_the_ID(), $size); ?>
    <a href="<?php the_permalink(); ?>"><img class="" src="<?php echo $src; ?>"></a>
<?php }
}

/* ── Advanced Scripts 137 ── */
if (!function_exists('faq_get_etape_acf_query')) {
function faq_get_etape_acf_query() {
    $tax = "etapes";
    $terms = get_the_terms(get_the_ID(), $tax);
    if(count($terms) > 0) {
        return $tax . "_" . $terms[0]->term_id;
    }
    return "";
}
}

/* ── Advanced Scripts 146 ── */
if (!function_exists('output_section_class')) {
function output_section_class() {
    return "section-" . get_row_index() .  "-onglets";
}
}

if (!function_exists('output_onglet_class')) {
function output_onglet_class() {
    return "onglet-" . get_row_index();
}
}

/* ── Advanced Scripts 266 ── */
if (!function_exists('count_acf_repeater_rows')) {
function count_acf_repeater_rows($repeater_name) {
    $repeater = get_field($repeater_name);
    return is_array($repeater) ? count($repeater) : 0;
}
}

