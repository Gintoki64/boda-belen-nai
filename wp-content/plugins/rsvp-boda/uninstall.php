<?php
/**
 * Limpieza al desinstalar el plugin (no al simplemente desactivarlo).
 * Por defecto se conservan los datos: borrar confirmaciones de boda por accidente sería irreversible.
 * Si de verdad quieres borrarlo todo, define RSVP_BODA_BORRAR_AL_DESINSTALAR como true en wp-config.php.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'RSVP_BODA_BORRAR_AL_DESINSTALAR' ) || ! RSVP_BODA_BORRAR_AL_DESINSTALAR ) {
	return;
}

global $wpdb;

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'rsvp_boda' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'rsvp_boda_invitados' );

delete_option( 'rsvp_boda_settings' );
delete_option( 'rsvp_boda_db_version' );
delete_option( 'rsvp_boda_clave_mas_uno' );
