<?php
/**
 * Gabarit « Auteurs - Single » (ex-Oxygen 54802), converti par tools/oxy2php.py le 1er oct. 2026 puis entretenu à la main.
 */
defined('ABSPATH') || exit;
?>
<?php /* En-tête */ ?><section id="section-35-54802" class="ct-section page-header"><div class="ct-section-inner-wrap"><?php /* Wrapper */ ?><div id="div_block-36-54802" class="ct-div-block c-columns-2-3 c-columns-m-1 c-columns-gap-xl c-inline c-shadow c-rounded c-padding-m"><?php /* Img */ ?><div id="div_block-37-54802" class="ct-div-block c-rounded c-padding-xl"><img id="image-38-54802" alt="" src="<?php echo esc_url(sx_fn('get_field', 'logo_principal', 'option')); ?>" class="ct-image"/></div><?php /* Info */ ?><div id="div_block-39-54802" class="ct-div-block c-padding-l"><div id="code_block-40-54802" class="ct-code-block c-owl-m"><?php
  $author = get_queried_object();

  if ( $author instanceof WP_User ) {
  
      // Prénom + Nom (champs first_name / last_name)
      $first = get_user_meta( $author->ID, 'first_name', true );
      $last  = get_user_meta( $author->ID, 'last_name', true );
      $full  = trim( $first . ' ' . $last );
  
      // Fallback sur le display_name si prénom/nom vides
      if ( $full === '' ) {
          $full = $author->display_name;
      }
  
      // À propos de l'utilisateur (champ "description")
      $bio = get_user_meta( $author->ID, 'description', true );
  
      echo '<h1 class="c-h4">' . esc_html( $full ) . '</h1>';
  
      if ( $bio !== '' ) {
          echo '<div class="c-text-s">' . wp_kses_post( wpautop( $bio ) ) . '</div>';
      }
  }
?></div></div></div></div></section>
