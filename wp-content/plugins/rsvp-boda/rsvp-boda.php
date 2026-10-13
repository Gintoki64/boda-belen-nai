<?php
/**
 * Plugin Name: RSVP Boda
 * Description: Gestión de confirmaciones de asistencia para la boda de Belén y Nai, con tabla propia, enlaces personalizados por invitado y panel de administración.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Belén y Nai
 * Text Domain: rsvp-boda
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RSVP_BODA_VERSION', '1.0.0' );
define( 'RSVP_BODA_DB_VERSION', '1.0.0' );
define( 'RSVP_BODA_PLUGIN_FILE', __FILE__ );
define( 'RSVP_BODA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RSVP_BODA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once RSVP_BODA_PLUGIN_DIR . 'includes/helpers.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-logger.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-activator.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-db.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-settings.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-invitados.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-email.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-ajax.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-cache.php';
require_once RSVP_BODA_PLUGIN_DIR . 'includes/class-rsvp-boda-mesas.php';
require_once RSVP_BODA_PLUGIN_DIR . 'admin/class-rsvp-boda-list-table.php';
require_once RSVP_BODA_PLUGIN_DIR . 'admin/class-rsvp-boda-admin.php';
require_once RSVP_BODA_PLUGIN_DIR . 'public/class-rsvp-boda-shortcode-form.php';

register_activation_hook( RSVP_BODA_PLUGIN_FILE, array( 'RSVP_Boda_Activator', 'activar' ) );

// Migración de esquema si el plugin ya estaba activo y se sube una versión con cambios de tabla.
add_action( 'plugins_loaded', array( 'RSVP_Boda_Activator', 'comprobar_version_bd' ) );

add_action( 'plugins_loaded', 'rsvp_boda_cargar_modulos' );

/**
 * Instancia los módulos que necesitan engancharse a hooks de WordPress.
 */
function rsvp_boda_cargar_modulos(): void {
	new RSVP_Boda_Settings();
	new RSVP_Boda_Invitados();
	new RSVP_Boda_Email();
	new RSVP_Boda_Ajax();
	new RSVP_Boda_Cache();
	new RSVP_Boda_Mesas();
	new RSVP_Boda_Admin();
	new RSVP_Boda_Shortcode_Form();
}
