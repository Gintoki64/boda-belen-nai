<?php
/**
 * Reparto de invitados en mesas (pantalla Boda → Mesas).
 *
 * Todo el reparto vive en una sola opción `rsvp_boda_mesas` (autoload desactivado), sin tablas
 * nuevas. Las personas no se copian ahí: se calculan cada vez a partir de las confirmaciones
 * (titular + acompañantes de cada respuesta con asiste = 'si') más las añadidas a mano, y solo se
 * guarda qué persona va en qué mesa. Si una respuesta se borra o pierde acompañantes, su
 * asignación se descarta sola al leer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Mesas {

	const OPCION = 'rsvp_boda_mesas';

	const MAX_MESAS          = 200;
	const MAX_MANUALES       = 500;
	const MAX_CAPACIDAD      = 50;
	const MAX_LARGO_NOMBRE   = 100;
	const MAX_LARGO_NOTA     = 200;
	const COMENSALES_DEFECTO = 10;

	public function __construct() {
		add_action( 'wp_ajax_rsvp_boda_mesas_guardar', array( $this, 'ajax_guardar' ) );
	}

	/**
	 * Estado vacío por defecto.
	 */
	public static function estado_vacio(): array {
		return array(
			'version'            => 0,
			'comensales_defecto' => self::COMENSALES_DEFECTO,
			'mesas'              => array(),
			'manuales'           => array(),
			'asignaciones'       => array(),
		);
	}

	/**
	 * Lee el estado guardado, saneado y sin asignaciones huérfanas
	 * (personas que ya no existen o mesas que ya no existen).
	 */
	public static function obtener(): array {
		$guardado = get_option( self::OPCION, '' );
		if ( ! is_array( $guardado ) ) {
			$guardado = array();
		}

		$estado            = self::sanear_estado( $guardado );
		$estado['version'] = isset( $guardado['version'] ) ? absint( $guardado['version'] ) : 0;

		$personas               = self::personas( $estado['manuales'] );
		$estado['asignaciones'] = self::filtrar_asignaciones( $estado['asignaciones'], $personas, $estado['mesas'] );

		return $estado;
	}

	/**
	 * Personas que hay que sentar: titular y acompañantes de cada confirmación con asiste = 'si',
	 * más las personas añadidas a mano.
	 *
	 * Cada persona: clave, nombre, tipo (titular|acompanante|manual), de (nombre del titular, solo
	 * acompañantes), grupo, nota, menu_infantil (bool), alergias.
	 *
	 * @param array $manuales Personas añadidas a mano (de la opción).
	 */
	public static function personas( array $manuales ): array {
		global $wpdb;

		$tabla_r = rsvp_boda_tabla_respuestas();
		$tabla_i = rsvp_boda_tabla_invitados();

		$filas = $wpdb->get_results(
			"SELECT r.id, r.nombre_completo, r.num_acompanantes, r.acompanantes, r.alergias, r.menu_infantil, i.grupo
			FROM {$tabla_r} r
			LEFT JOIN {$tabla_i} i ON i.slug = r.guest_slug
			WHERE r.asiste = 'si'
			ORDER BY r.nombre_completo ASC",
			ARRAY_A
		);

		$personas = array();

		foreach ( (array) $filas as $fila ) {
			$id       = (int) $fila['id'];
			$titular  = (string) $fila['nombre_completo'];
			$grupo    = (string) ( $fila['grupo'] ?? '' );
			$alergias = trim( (string) ( $fila['alergias'] ?? '' ) );
			$infantil = 'si' === $fila['menu_infantil'];

			$personas[] = array(
				'clave'         => 'r' . $id . '-0',
				'nombre'        => $titular,
				'tipo'          => 'titular',
				'de'            => '',
				'grupo'         => $grupo,
				'nota'          => '',
				'menu_infantil' => $infantil,
				'alergias'      => $alergias,
			);

			$nombres = json_decode( (string) $fila['acompanantes'], true );
			$nombres = is_array( $nombres ) ? array_values( $nombres ) : array();
			$total   = max( count( $nombres ), (int) $fila['num_acompanantes'] );

			for ( $n = 1; $n <= $total; $n++ ) {
				$nombre = isset( $nombres[ $n - 1 ] ) && is_scalar( $nombres[ $n - 1 ] ) ? trim( (string) $nombres[ $n - 1 ] ) : '';
				if ( '' === $nombre ) {
					$nombre = 'Acompañante ' . $n;
				}
				// El menú infantil y las alergias se recogen por respuesta, no por persona:
				// solo se muestran en el titular para no dar a entender que aplican a todos.
				$personas[] = array(
					'clave'         => 'r' . $id . '-' . $n,
					'nombre'        => $nombre,
					'tipo'          => 'acompanante',
					'de'            => $titular,
					'grupo'         => $grupo,
					'nota'          => '',
					'menu_infantil' => false,
					'alergias'      => '',
				);
			}
		}

		foreach ( $manuales as $manual ) {
			$personas[] = array(
				'clave'         => 'm' . $manual['id'],
				'nombre'        => $manual['nombre'],
				'tipo'          => 'manual',
				'de'            => '',
				'grupo'         => '',
				'nota'          => $manual['nota'],
				'menu_infantil' => false,
				'alergias'      => '',
			);
		}

		return $personas;
	}

	/**
	 * Quita las asignaciones a personas o mesas que no existen.
	 */
	public static function filtrar_asignaciones( array $asignaciones, array $personas, array $mesas ): array {
		$claves_personas = array_flip( array_column( $personas, 'clave' ) );
		$ids_mesas       = array_flip( array_column( $mesas, 'id' ) );

		$resultado = array();
		foreach ( $asignaciones as $clave => $mesa_id ) {
			if ( isset( $claves_personas[ $clave ], $ids_mesas[ $mesa_id ] ) ) {
				$resultado[ $clave ] = $mesa_id;
			}
		}
		return $resultado;
	}

	/**
	 * Sanea un estado recibido (del navegador o de la opción). No toca la versión.
	 */
	public static function sanear_estado( array $entrada ): array {
		$estado = self::estado_vacio();

		$comensales = isset( $entrada['comensales_defecto'] ) ? absint( $entrada['comensales_defecto'] ) : self::COMENSALES_DEFECTO;
		$estado['comensales_defecto'] = max( 1, min( self::MAX_CAPACIDAD, $comensales ) );

		$vistos = array();
		foreach ( array_slice( (array) ( $entrada['mesas'] ?? array() ), 0, self::MAX_MESAS ) as $indice => $mesa ) {
			if ( ! is_array( $mesa ) ) {
				continue;
			}
			$id = self::sanear_id( $mesa['id'] ?? '' );
			if ( '' === $id || isset( $vistos[ $id ] ) ) {
				continue;
			}
			$vistos[ $id ] = true;

			$nombre = self::sanear_texto( $mesa['nombre'] ?? '', self::MAX_LARGO_NOMBRE );
			if ( '' === $nombre ) {
				$nombre = 'Mesa ' . ( count( $estado['mesas'] ) + 1 );
			}
			$capacidad = isset( $mesa['capacidad'] ) ? absint( $mesa['capacidad'] ) : $estado['comensales_defecto'];

			$estado['mesas'][] = array(
				'id'        => $id,
				'nombre'    => $nombre,
				'capacidad' => max( 1, min( self::MAX_CAPACIDAD, $capacidad ) ),
			);
		}

		$vistos = array();
		foreach ( array_slice( (array) ( $entrada['manuales'] ?? array() ), 0, self::MAX_MANUALES ) as $manual ) {
			if ( ! is_array( $manual ) ) {
				continue;
			}
			$id     = self::sanear_id( $manual['id'] ?? '' );
			$nombre = self::sanear_texto( $manual['nombre'] ?? '', self::MAX_LARGO_NOMBRE );
			if ( '' === $id || '' === $nombre || isset( $vistos[ $id ] ) ) {
				continue;
			}
			$vistos[ $id ] = true;

			$estado['manuales'][] = array(
				'id'     => $id,
				'nombre' => $nombre,
				'nota'   => self::sanear_texto( $manual['nota'] ?? '', self::MAX_LARGO_NOTA ),
			);
		}

		$asignaciones = $entrada['asignaciones'] ?? array();
		if ( is_array( $asignaciones ) ) {
			foreach ( $asignaciones as $clave => $mesa_id ) {
				$clave   = (string) $clave;
				$mesa_id = is_scalar( $mesa_id ) ? self::sanear_id( $mesa_id ) : '';
				if ( '' !== $mesa_id && preg_match( '/^(r\d+-\d+|m[a-z0-9_-]{1,40})$/', $clave ) ) {
					$estado['asignaciones'][ $clave ] = $mesa_id;
				}
			}
		}

		return $estado;
	}

	private static function sanear_id( $valor ): string {
		$valor = is_scalar( $valor ) ? strtolower( (string) $valor ) : '';
		return preg_match( '/^[a-z0-9_-]{1,40}$/', $valor ) ? $valor : '';
	}

	private static function sanear_texto( $valor, int $largo ): string {
		$valor = is_scalar( $valor ) ? sanitize_text_field( (string) $valor ) : '';
		return function_exists( 'mb_substr' ) ? mb_substr( $valor, 0, $largo ) : substr( $valor, 0, $largo );
	}

	/**
	 * AJAX: guarda el estado completo. Control de versión optimista para que dos personas
	 * editando a la vez no se pisen sin darse cuenta.
	 */
	public function ajax_guardar(): void {
		if ( ! check_ajax_referer( 'rsvp_boda_mesas', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'codigo'  => 'nonce',
					'mensaje' => 'La sesión ha caducado. Recarga la página (tus cambios no guardados se perderán).',
				),
				403
			);
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'codigo'  => 'permiso',
					'mensaje' => 'No tienes permiso para hacer esto.',
				),
				403
			);
		}

		$entrada = json_decode( wp_unslash( (string) ( $_POST['estado'] ?? '' ) ), true );
		if ( ! is_array( $entrada ) ) {
			wp_send_json_error(
				array(
					'codigo'  => 'datos',
					'mensaje' => 'No se han recibido datos válidos. Inténtalo de nuevo.',
				),
				400
			);
		}

		$guardado = get_option( self::OPCION, '' );
		$version  = is_array( $guardado ) && isset( $guardado['version'] ) ? absint( $guardado['version'] ) : 0;

		if ( ! isset( $_POST['version'] ) || absint( wp_unslash( $_POST['version'] ) ) !== $version ) {
			wp_send_json_error(
				array(
					'codigo'  => 'conflicto',
					'mensaje' => 'Otra persona ha cambiado las mesas mientras editabas. Recarga la página (tus cambios no guardados se perderán).',
				),
				409
			);
		}

		$estado                 = self::sanear_estado( $entrada );
		$personas               = self::personas( $estado['manuales'] );
		$estado['asignaciones'] = self::filtrar_asignaciones( $estado['asignaciones'], $personas, $estado['mesas'] );
		$estado['version']      = $version + 1;

		// add_option si no existe para fijar autoload = no desde el principio.
		if ( false === get_option( self::OPCION, false ) ) {
			add_option( self::OPCION, $estado, '', 'no' );
		} else {
			update_option( self::OPCION, $estado, false );
		}

		wp_send_json_success(
			array(
				'version' => $estado['version'],
				'mensaje' => 'Mesas guardadas.',
			)
		);
	}
}
