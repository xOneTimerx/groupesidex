<?php
/**
 * Plugin Name: Staging mail killswitch (Tactik)
 * Description: Bloque tout wp_mail() sur le staging et journalise l'envoi intercepté. Ne jamais déployer sur le live.
 */
if ( strpos( (string) ( $_SERVER['HTTP_HOST'] ?? '' ), 'groupesidex.com' ) !== false && strpos( (string) ( $_SERVER['HTTP_HOST'] ?? '' ), 'staging' ) === false ) {
	return; // garde-fou : inactif si jamais servi sous le vrai domaine
}
add_filter( 'pre_wp_mail', function ( $null, $atts ) {
	error_log( '[staging-mail-killswitch] to=' . wp_json_encode( $atts['to'] ?? '' ) . ' subject=' . ( $atts['subject'] ?? '' ) );
	return true;
}, PHP_INT_MAX, 2 );
