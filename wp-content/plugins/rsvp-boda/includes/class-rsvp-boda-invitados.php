<?php
/**
 * Lista de invitados esperados y generación de sus enlaces personalizados (?i=slug).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Invitados {

	public function __construct() {
		add_action( 'admin_post_rsvp_boda_importar_invitados', array( $this, 'manejar_importacion' ) );
		add_action( 'admin_post_rsvp_boda_guardar_invitado', array( $this, 'manejar_guardar_invitado' ) );
		add_action( 'admin_post_rsvp_boda_eliminar_invitado', array( $this, 'manejar_eliminar_invitado' ) );
		add_action( 'admin_post_rsvp_boda_regenerar_clave', array( $this, 'manejar_regenerar_clave' ) );
	}

	public static function obtener_por_slug( string $slug ): ?array {
		global $wpdb;

		$fila = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . rsvp_boda_tabla_invitados() . ' WHERE slug = %s', $slug ),
			ARRAY_A
		);

		return $fila ?: null;
	}

	/**
	 * Lista completa de invitados esperados junto con si ya han respondido (LEFT JOIN, nunca un flag guardado).
	 */
	public static function listar(): array {
		global $wpdb;

		$invitados = rsvp_boda_tabla_invitados();
		$respuestas = rsvp_boda_tabla_respuestas();

		return $wpdb->get_results(
			"SELECT i.*, r.id AS respuesta_id, r.asiste
			 FROM {$invitados} i
			 LEFT JOIN {$respuestas} r ON r.guest_slug = i.slug
			 ORDER BY i.nombre ASC",
			ARRAY_A
		);
	}

	/**
	 * @return int|false Id del invitado nuevo, o false si falla (sin tipo de retorno unión: PHP 7.4).
	 */
	public static function agregar( string $nombre, string $grupo, bool $permite_acompanantes, string $slug_deseado = '' ) {
		global $wpdb;

		$slug = $slug_deseado ? rsvp_boda_generar_slug_unico( $slug_deseado ) : rsvp_boda_generar_slug_unico( $nombre );

		$ok = $wpdb->insert(
			rsvp_boda_tabla_invitados(),
			array(
				'nombre'               => $nombre,
				'slug'                 => $slug,
				'grupo'                => $grupo,
				'permite_acompanantes' => $permite_acompanantes ? 1 : 0,
			),
			array( '%s', '%s', '%s', '%d' )
		);

		return $ok ? (int) $wpdb->insert_id : false;
	}

	public static function actualizar_permiso_acompanante( int $id, bool $permite ): bool {
		global $wpdb;

		return false !== $wpdb->update(
			rsvp_boda_tabla_invitados(),
			array( 'permite_acompanantes' => $permite ? 1 : 0 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	public static function eliminar( int $id ): bool {
		global $wpdb;

		return false !== $wpdb->delete( rsvp_boda_tabla_invitados(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Importa un CSV con columnas nombre[,slug][,grupo][,permite_acompanantes].
	 * Si el slug ya existe se actualiza esa fila en vez de duplicarla.
	 */
	public function manejar_importacion(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para hacer esto.' );
		}
		check_admin_referer( 'rsvp_boda_importar_invitados' );

		if ( empty( $_FILES['csv_invitados']['tmp_name'] ) ) {
			$this->redirigir_con_aviso( 'error', 'No se ha subido ningún archivo.' );
		}

		$manejador = fopen( $_FILES['csv_invitados']['tmp_name'], 'r' );
		if ( ! $manejador ) {
			$this->redirigir_con_aviso( 'error', 'No se ha podido leer el archivo.' );
		}

		// Excel/Sheets en Windows exportan el CSV con BOM UTF-8 delante: sin esto, la cabecera
		// "nombre" no se reconoce como tal y se importa como un invitado más.
		if ( "\xEF\xBB\xBF" !== fread( $manejador, 3 ) ) {
			rewind( $manejador );
		}

		$insertados  = 0;
		$actualizados = 0;
		$primera_fila = true;

		while ( ( $columnas = fgetcsv( $manejador, 0, ';' ) ) !== false ) {
			// Si el CSV usa coma en vez de punto y coma, se detecta por tener una sola columna.
			if ( 1 === count( $columnas ) && str_contains( $columnas[0], ',' ) ) {
				$columnas = str_getcsv( $columnas[0], ',' );
			}

			$nombre = sanitize_text_field( $columnas[0] ?? '' );

			if ( $primera_fila ) {
				$primera_fila = false;
				if ( '' === $nombre || 0 === strcasecmp( $nombre, 'nombre' ) ) {
					continue; // Cabecera del CSV, se ignora.
				}
			}

			if ( '' === $nombre ) {
				continue;
			}

			$slug    = sanitize_title( $columnas[1] ?? '' );
			$grupo   = sanitize_text_field( $columnas[2] ?? '' );
			$permite = isset( $columnas[3] ) && in_array( strtolower( trim( $columnas[3] ) ), array( '1', 'si', 'sí', 'true' ), true );

			$existente = $slug ? self::obtener_por_slug( $slug ) : null;

			if ( $existente ) {
				global $wpdb;
				$wpdb->update(
					rsvp_boda_tabla_invitados(),
					array(
						'nombre'               => $nombre,
						'grupo'                => $grupo,
						'permite_acompanantes' => $permite ? 1 : 0,
					),
					array( 'id' => $existente['id'] ),
					array( '%s', '%s', '%d' ),
					array( '%d' )
				);
				++$actualizados;
			} else {
				self::agregar( $nombre, $grupo, $permite, $slug );
				++$insertados;
			}
		}

		fclose( $manejador );

		$this->redirigir_con_aviso( 'success', "Importación completa: {$insertados} nuevos, {$actualizados} actualizados." );
	}

	public function manejar_guardar_invitado(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para hacer esto.' );
		}
		check_admin_referer( 'rsvp_boda_guardar_invitado' );

		$id = absint( $_POST['id'] ?? 0 );
		$permite = ! empty( $_POST['permite_acompanantes'] );

		if ( $id > 0 ) {
			self::actualizar_permiso_acompanante( $id, $permite );
		} else {
			$nombre = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
			$grupo  = sanitize_text_field( wp_unslash( $_POST['grupo'] ?? '' ) );
			if ( '' !== $nombre ) {
				self::agregar( $nombre, $grupo, $permite );
			}
		}

		$this->redirigir_con_aviso( 'success', 'Invitado guardado.' );
	}

	public function manejar_eliminar_invitado(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para hacer esto.' );
		}
		check_admin_referer( 'rsvp_boda_eliminar_invitado' );

		self::eliminar( absint( $_POST['id'] ?? 0 ) );

		$this->redirigir_con_aviso( 'success', 'Invitado eliminado.' );
	}

	/**
	 * "Generar un enlace +1 nuevo": cambia la clave del enlace general ?c por si se ha difundido
	 * más de la cuenta. El enlace viejo deja de dar +1; quien ya respondió con él conserva sus
	 * acompañantes guardados (y los puede seguir editando desde el mismo móvil).
	 */
	public function manejar_regenerar_clave(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para hacer esto.' );
		}
		check_admin_referer( 'rsvp_boda_regenerar_clave' );

		update_option( 'rsvp_boda_clave_mas_uno', rsvp_boda_generar_clave_mas_uno(), false );

		$this->redirigir_con_aviso( 'success', 'Enlace con +1 nuevo generado. El anterior ya no deja añadir acompañantes: manda el nuevo a quien aún no haya respondido.' );
	}

	private function redirigir_con_aviso( string $tipo, string $mensaje ): void {
		$url = add_query_arg(
			array(
				'page'           => 'rsvp-boda-invitados',
				'rsvp_boda_aviso' => $tipo,
				'rsvp_boda_msg'   => rawurlencode( $mensaje ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
