<?php
/**
 * Capa de acceso a datos de las respuestas RSVP (tabla wp_rsvp_boda).
 * La base de datos es siempre la fuente de verdad; el email es un aviso secundario.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_DB {

	/**
	 * Inserta o actualiza (upsert atómico) la respuesta de un invitado con enlace personalizado.
	 * Usa la clave única guest_slug para que dos envíos con el mismo slug editen la misma fila
	 * en lugar de crear un duplicado.
	 *
	 * @return int|false ID de la fila resultante, o false si la escritura falla.
	 */
	public static function upsert_por_slug( string $slug, array $datos ) {
		global $wpdb;

		$tabla = rsvp_boda_tabla_respuestas();

		$datos['guest_slug'] = $slug;
		if ( empty( $datos['token_edicion'] ) ) {
			$datos['token_edicion'] = rsvp_boda_generar_token();
		}

		$columnas      = array_keys( $datos );
		$placeholders  = array();
		$actualizables = array();

		foreach ( $columnas as $col ) {
			$placeholders[] = is_int( $datos[ $col ] ) ? '%d' : '%s';
			if ( ! in_array( $col, array( 'guest_slug', 'token_edicion' ), true ) ) {
				$actualizables[] = "{$col} = VALUES({$col})";
			}
		}

		$sql = sprintf(
			'INSERT INTO %1$s (%2$s) VALUES (%3$s) ON DUPLICATE KEY UPDATE %4$s',
			$tabla,
			implode( ', ', $columnas ),
			implode( ', ', $placeholders ),
			implode( ', ', $actualizables )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql se construye con nombres de columna fijos, los valores pasan por prepare().
		$ok = $wpdb->query( $wpdb->prepare( $sql, array_values( $datos ) ) );

		if ( false === $ok ) {
			return false;
		}

		$fila = self::obtener_respuesta_por_slug( $slug );

		return $fila ? (int) $fila['id'] : false;
	}

	/**
	 * Inserta una respuesta nueva sin enlace personalizado (acceso directo a la web).
	 *
	 * @return int|false ID de la fila creada, o false si falla.
	 */
	public static function insertar_respuesta( array $datos ) {
		global $wpdb;

		if ( empty( $datos['token_edicion'] ) ) {
			$datos['token_edicion'] = rsvp_boda_generar_token();
		}

		$ok = $wpdb->insert( rsvp_boda_tabla_respuestas(), $datos, self::formatos_para( $datos ) );

		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Actualiza una respuesta existente por ID (usado en el flujo de edición por cookie firmada).
	 */
	public static function actualizar_respuesta_por_id( int $id, array $datos ): bool {
		global $wpdb;

		$ok = $wpdb->update(
			rsvp_boda_tabla_respuestas(),
			$datos,
			array( 'id' => $id ),
			self::formatos_para( $datos ),
			array( '%d' )
		);

		return false !== $ok;
	}

	public static function obtener_respuesta_por_slug( string $slug ): ?array {
		global $wpdb;

		$fila = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . rsvp_boda_tabla_respuestas() . ' WHERE guest_slug = %s', $slug ),
			ARRAY_A
		);

		return $fila ?: null;
	}

	public static function obtener_respuesta_por_id( int $id ): ?array {
		global $wpdb;

		$fila = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . rsvp_boda_tabla_respuestas() . ' WHERE id = %d', $id ),
			ARRAY_A
		);

		return $fila ?: null;
	}

	/**
	 * Contadores agregados para la cabecera del panel de administración.
	 */
	public static function obtener_contadores(): array {
		global $wpdb;

		$tabla     = rsvp_boda_tabla_respuestas();
		$invitados = rsvp_boda_tabla_invitados();

		return array(
			'confirmados'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla} WHERE asiste = 'si'" ),
			'no_vienen'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla} WHERE asiste = 'no'" ),
			'con_alergias'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla} WHERE alergias IS NOT NULL AND alergias != ''" ),
			'pendientes'     => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$invitados} i LEFT JOIN {$tabla} r ON r.guest_slug = i.slug WHERE r.id IS NULL"
			),
			'bus_por_parada' => $wpdb->get_results(
				"SELECT parada_bus, COUNT(*) as total FROM {$tabla} WHERE necesita_bus = 'si' AND parada_bus IS NOT NULL AND parada_bus != '' GROUP BY parada_bus",
				ARRAY_A
			),
		);
	}

	/**
	 * Filas cuyo nombre normalizado se repite entre invitados sin enlace personalizado
	 * (los que sí tienen slug ya están protegidos por la clave única guest_slug).
	 */
	public static function obtener_duplicados(): array {
		global $wpdb;

		$tabla = rsvp_boda_tabla_respuestas();

		$nombres = $wpdb->get_col(
			"SELECT nombre_normalizado FROM {$tabla} WHERE guest_slug IS NULL GROUP BY nombre_normalizado HAVING COUNT(*) > 1"
		);

		if ( ! $nombres ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $nombres ), '%s' ) );

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$tabla} WHERE nombre_normalizado IN ({$placeholders}) ORDER BY nombre_normalizado, creado_en", $nombres ),
			ARRAY_A
		);
	}

	private static function formatos_para( array $datos ): array {
		return array_map( static fn( $valor ) => is_int( $valor ) ? '%d' : '%s', $datos );
	}
}
