<?php
/**
 * Menú "Boda" en el admin de WordPress y registro de las acciones admin-post
 * de exportación CSV y borrado de confirmaciones.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'registrar_menu' ) );
		add_action( 'admin_post_rsvp_boda_export', array( $this, 'exportar_csv' ) );
		add_action( 'admin_post_rsvp_boda_eliminar_respuesta', array( $this, 'eliminar_respuesta' ) );
		add_action( 'admin_notices', array( $this, 'mostrar_avisos' ) );
	}

	public function registrar_menu(): void {
		add_menu_page(
			'Boda',
			'Boda',
			'manage_options',
			'rsvp-boda-confirmaciones',
			array( $this, 'vista_confirmaciones' ),
			'dashicons-heart',
			26
		);

		add_submenu_page( 'rsvp-boda-confirmaciones', 'Confirmaciones', 'Confirmaciones', 'manage_options', 'rsvp-boda-confirmaciones', array( $this, 'vista_confirmaciones' ) );
		add_submenu_page( 'rsvp-boda-confirmaciones', 'Ajustes generales', 'Ajustes generales', 'manage_options', 'rsvp-boda-ajustes', array( $this, 'vista_ajustes' ) );
		add_submenu_page( 'rsvp-boda-confirmaciones', 'Invitados y enlaces', 'Invitados y enlaces', 'manage_options', 'rsvp-boda-invitados', array( $this, 'vista_invitados' ) );
		add_submenu_page( 'rsvp-boda-confirmaciones', 'Mesas', 'Mesas', 'manage_options', 'rsvp-boda-mesas', array( $this, 'vista_mesas' ) );
	}

	public function vista_confirmaciones(): void {
		require_once RSVP_BODA_PLUGIN_DIR . 'admin/view-confirmaciones.php';
	}

	public function vista_ajustes(): void {
		require_once RSVP_BODA_PLUGIN_DIR . 'admin/view-ajustes.php';
	}

	public function vista_invitados(): void {
		require_once RSVP_BODA_PLUGIN_DIR . 'admin/view-invitados.php';
	}

	public function vista_mesas(): void {
		require_once RSVP_BODA_PLUGIN_DIR . 'admin/view-mesas.php';
	}

	public function mostrar_avisos(): void {
		if ( empty( $_GET['rsvp_boda_aviso'] ) ) {
			return;
		}
		$tipo    = 'error' === $_GET['rsvp_boda_aviso'] ? 'error' : 'success';
		$mensaje = sanitize_text_field( rawurldecode( wp_unslash( $_GET['rsvp_boda_msg'] ?? '' ) ) );
		if ( '' === $mensaje ) {
			return;
		}
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $tipo ), esc_html( $mensaje ) );
	}

	public function eliminar_respuesta(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para hacer esto.' );
		}

		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'rsvp_boda_eliminar_respuesta_' . $id );

		global $wpdb;
		$wpdb->delete( rsvp_boda_tabla_respuestas(), array( 'id' => $id ), array( '%d' ) );

		wp_safe_redirect( admin_url( 'admin.php?page=rsvp-boda-confirmaciones&rsvp_boda_aviso=success&rsvp_boda_msg=Confirmaci%C3%B3n%20eliminada.' ) );
		exit;
	}

	/**
	 * Exportación CSV: BOM UTF-8 + delimitador ";" para que Excel en español no destroce las tildes.
	 */
	public function exportar_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para hacer esto.' );
		}
		check_admin_referer( 'rsvp_boda_export' );

		global $wpdb;
		$filas = $wpdb->get_results( 'SELECT * FROM ' . rsvp_boda_tabla_respuestas() . ' ORDER BY creado_en ASC', ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=confirmaciones-boda.csv' );

		$salida = fopen( 'php://output', 'w' );
		fwrite( $salida, "\xEF\xBB\xBF" ); // BOM para que Excel detecte UTF-8.

		fputcsv(
			$salida,
			array( 'Nombre', 'Asiste', 'Acompañantes', 'Nombres acompañantes', 'Alergias', 'Menú infantil', 'Bus', 'Parada', 'Canción', 'Teléfono', 'Email', 'Fecha' ),
			';'
		);

		foreach ( $filas as $fila ) {
			$acompanantes = implode( ', ', (array) json_decode( (string) $fila['acompanantes'], true ) );
			fputcsv(
				$salida,
				array(
					$fila['nombre_completo'],
					'si' === $fila['asiste'] ? 'Sí' : 'No',
					$fila['num_acompanantes'],
					$acompanantes,
					$fila['alergias'],
					'si' === $fila['menu_infantil'] ? 'Sí' : 'No',
					'si' === $fila['necesita_bus'] ? 'Sí' : 'No',
					$fila['parada_bus'],
					$fila['cancion'],
					$fila['telefono'],
					$fila['email'],
					$fila['creado_en'],
				),
				';'
			);
		}

		fclose( $salida );
		exit;
	}
}
