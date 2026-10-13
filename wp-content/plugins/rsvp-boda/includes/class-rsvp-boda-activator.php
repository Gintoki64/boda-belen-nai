<?php
/**
 * Creación y migración del esquema de base de datos del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Activator {

	/**
	 * Se ejecuta una vez, al activar el plugin: crea las tablas y siembra los ajustes por defecto.
	 */
	public static function activar(): void {
		self::crear_tablas();

		if ( false === get_option( 'rsvp_boda_settings' ) ) {
			add_option( 'rsvp_boda_settings', rsvp_boda_ajustes_por_defecto() );
		}

		update_option( 'rsvp_boda_db_version', RSVP_BODA_DB_VERSION );
	}

	/**
	 * Comprueba en cada carga si el esquema instalado coincide con el que espera esta versión del plugin,
	 * y si no, vuelve a ejecutar dbDelta. dbDelta nunca borra filas ni columnas existentes.
	 */
	public static function comprobar_version_bd(): void {
		if ( get_option( 'rsvp_boda_db_version' ) !== RSVP_BODA_DB_VERSION ) {
			self::crear_tablas();
			update_option( 'rsvp_boda_db_version', RSVP_BODA_DB_VERSION );
		}
	}

	private static function crear_tablas(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$tabla_respuestas = rsvp_boda_tabla_respuestas();
		$tabla_invitados  = rsvp_boda_tabla_invitados();

		$sql_respuestas = "CREATE TABLE {$tabla_respuestas} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			guest_slug VARCHAR(191) NULL,
			token_edicion VARCHAR(64) NOT NULL,
			nombre_completo VARCHAR(191) NOT NULL,
			nombre_normalizado VARCHAR(191) NOT NULL,
			asiste VARCHAR(3) NOT NULL,
			num_acompanantes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			acompanantes LONGTEXT NULL,
			con_mas_uno TINYINT(1) NOT NULL DEFAULT 0,
			alergias TEXT NULL,
			menu_infantil VARCHAR(3) NOT NULL DEFAULT 'no',
			necesita_bus VARCHAR(3) NOT NULL DEFAULT 'no',
			parada_bus VARCHAR(191) NULL,
			cancion TEXT NULL,
			telefono VARCHAR(30) NOT NULL DEFAULT '',
			email VARCHAR(191) NULL,
			ip_hash VARCHAR(64) NULL,
			email_enviado TINYINT(1) NOT NULL DEFAULT 0,
			email_enviado_admin TINYINT(1) NOT NULL DEFAULT 0,
			email_error TEXT NULL,
			creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY guest_slug (guest_slug),
			KEY nombre_normalizado (nombre_normalizado),
			KEY asiste (asiste),
			KEY necesita_bus (necesita_bus)
		) {$charset_collate};";

		$sql_invitados = "CREATE TABLE {$tabla_invitados} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			nombre VARCHAR(191) NOT NULL,
			slug VARCHAR(191) NOT NULL,
			grupo VARCHAR(100) NULL,
			permite_acompanantes TINYINT(1) NOT NULL DEFAULT 0,
			creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$charset_collate};";

		dbDelta( $sql_respuestas );
		dbDelta( $sql_invitados );
	}
}
