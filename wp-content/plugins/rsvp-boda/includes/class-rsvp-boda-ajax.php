<?php
/**
 * Recepción del formulario RSVP por AJAX: nonce, honeypot, saneado y validación
 * tanto de asistencia como del permiso de acompañante, upsert en base de datos y
 * disparo (best-effort) de los correos de aviso.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Ajax {

	const NONCE_ACTION  = 'rsvp_boda_form';
	const COOKIE_EDICION = 'rsvp_boda_edicion';

	public function __construct() {
		add_action( 'wp_ajax_rsvp_boda_enviar', array( $this, 'manejar_envio' ) );
		add_action( 'wp_ajax_nopriv_rsvp_boda_enviar', array( $this, 'manejar_envio' ) );
	}

	public function manejar_envio(): void {
		// Si alguna capa de caché (LiteSpeed, CDN de Hostinger, el propio navegador) llega a
		// servir la página con un nonce caducado, en vez de un "-1" que el JS solo puede mostrar
		// como "problema de conexión" se devuelve uno nuevo y el formulario reintenta solo.
		// Para visitantes anónimos el nonce no protege nada real; el antispam es el honeypot.
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'codigo'  => 'nonce_caducado',
					'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
					'mensaje' => 'La página llevaba mucho tiempo abierta. Recárgala y vuelve a enviar, por favor.',
				)
			);
		}

		if ( '1' !== rsvp_boda_get_option( 'formulario_activado', '1' ) ) {
			wp_send_json_error( array( 'mensaje' => 'El plazo para confirmar ya se ha cerrado.' ) );
		}

		// Honeypot: si el campo trampa viene relleno, es un bot. No delatamos la detección:
		// respondemos como si todo hubiera ido bien pero sin guardar nada.
		if ( ! empty( $_POST['rsvp_boda_web'] ) ) {
			wp_send_json_success( array( 'mensaje' => '¡Gracias por tu confirmación!' ) );
		}

		$slug = sanitize_title( wp_unslash( $_POST['guest_slug'] ?? '' ) );

		$nombre_completo = sanitize_text_field( wp_unslash( $_POST['nombre_completo'] ?? '' ) );
		$asiste          = in_array( $_POST['asiste'] ?? '', array( 'si', 'no' ), true ) ? $_POST['asiste'] : '';
		$telefono        = sanitize_text_field( wp_unslash( $_POST['telefono'] ?? '' ) );
		$email           = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		$errores = array();
		if ( '' === $nombre_completo ) {
			$errores['nombre_completo'] = 'Indica tu nombre y apellidos.';
		}
		if ( '' === $asiste ) {
			$errores['asiste'] = 'Indica si asistes o no.';
		}
		if ( '' === $telefono ) {
			$errores['telefono'] = 'Indica un teléfono de contacto.';
		}

		if ( $errores ) {
			wp_send_json_error( array( 'mensaje' => 'Revisa los campos marcados.', 'errores' => $errores ) );
		}

		// Resuelve si este invitado concreto tiene permiso de traer acompañante,
		// SIEMPRE en servidor a partir del slug -- nunca a partir de lo que mande el formulario.
		$invitado = $slug ? RSVP_Boda_Invitados::obtener_por_slug( $slug ) : null;
		if ( $slug && ! $invitado ) {
			// Slug desconocido (enlace manipulado o caducado): se trata como acceso directo.
			$slug = '';
		}
		// Enlace general con +1: la clave se vuelve a comprobar aquí, nunca se fía del formulario.
		$clave_acceso = $slug ? '' : sanitize_key( wp_unslash( $_POST['acceso'] ?? '' ) );

		$permite_acompanantes = $invitado
			? (bool) $invitado['permite_acompanantes']
			: rsvp_boda_permite_acompanantes_sin_slug( $clave_acceso );

		list( $num_acompanantes, $acompanantes ) = $this->procesar_acompanantes( $asiste, $permite_acompanantes );

		$necesita_bus = in_array( $_POST['necesita_bus'] ?? '', array( 'si', 'no' ), true ) ? $_POST['necesita_bus'] : 'no';
		$parada_bus   = 'si' === $necesita_bus ? sanitize_text_field( wp_unslash( $_POST['parada_bus'] ?? '' ) ) : '';
		$menu_infantil = in_array( $_POST['menu_infantil'] ?? '', array( 'si', 'no' ), true ) ? $_POST['menu_infantil'] : 'no';

		$datos = array(
			'nombre_completo'    => $nombre_completo,
			'nombre_normalizado' => rsvp_boda_normalizar_nombre( $nombre_completo ),
			'asiste'             => $asiste,
			'num_acompanantes'   => $num_acompanantes,
			'acompanantes'       => wp_json_encode( $acompanantes ),
			'alergias'           => sanitize_textarea_field( wp_unslash( $_POST['alergias'] ?? '' ) ),
			'menu_infantil'      => $menu_infantil,
			'necesita_bus'       => $necesita_bus,
			'parada_bus'         => $parada_bus,
			'cancion'            => sanitize_text_field( wp_unslash( $_POST['cancion'] ?? '' ) ),
			'telefono'           => $telefono,
			'email'              => $email,
			'ip_hash'            => rsvp_boda_hash_ip( $this->obtener_ip() ),
		);

		// Se mira antes de guardar si ya había respuesta, para avisar de "cambio" y no de "nueva".
		$es_cambio = $slug
			? null !== RSVP_Boda_DB::obtener_respuesta_por_slug( $slug )
			: null !== $this->respuesta_desde_cookie_edicion();

		$id = $slug ? $this->guardar_con_slug( $slug, $datos ) : $this->guardar_sin_slug( $datos );

		if ( false === $id ) {
			RSVP_Boda_Logger::error( 'guardar_respuesta', 'Fallo al guardar en base de datos', array( 'nombre' => $nombre_completo ) );
			wp_send_json_error(
				array(
					'mensaje' => 'No hemos podido guardar tu confirmación por un problema técnico. Escríbenos o llámanos al ' .
						rsvp_boda_get_option( 'telefono_contacto', '' ) . ' para confirmar a mano, por favor.',
				)
			);
		}

		$respuesta_guardada = RSVP_Boda_DB::obtener_respuesta_por_id( $id );
		$this->enviar_correos( $id, $respuesta_guardada, $es_cambio );

		wp_send_json_success(
			array(
				// Sin esc_html(): el JS lo pinta con textContent, y escapar aquí mostraba
				// "O&#039;Donnell" literalmente en nombres con apóstrofo.
				'mensaje' => 'si' === $asiste
					? '¡Gracias, ' . $nombre_completo . '! Hemos registrado que vienes.'
					: 'Gracias por avisar, ' . $nombre_completo . '. ¡Te echaremos de menos!',
			)
		);
	}

	/**
	 * Recalcula el número de acompañantes a partir de los nombres realmente enviados,
	 * sin confiar nunca en un contador declarado por el cliente, y respetando el permiso
	 * de acompañante resuelto en servidor.
	 */
	private function procesar_acompanantes( string $asiste, bool $permite_acompanantes ): array {
		if ( 'si' !== $asiste || ! $permite_acompanantes ) {
			return array( 0, array() );
		}

		$nombres_recibidos = $_POST['acompanantes'] ?? array();
		if ( ! is_array( $nombres_recibidos ) ) {
			return array( 0, array() );
		}

		$limite  = max( 0, (int) rsvp_boda_get_option( 'limite_acompanantes', 6 ) );
		$limpios = array();

		foreach ( $nombres_recibidos as $nombre_acompanante ) {
			if ( count( $limpios ) >= $limite ) {
				break;
			}
			$nombre_acompanante = sanitize_text_field( wp_unslash( (string) $nombre_acompanante ) );
			if ( '' !== $nombre_acompanante ) {
				$limpios[] = $nombre_acompanante;
			}
		}

		return array( count( $limpios ), $limpios );
	}

	private function guardar_con_slug( string $slug, array $datos ) {
		return RSVP_Boda_DB::upsert_por_slug( $slug, $datos );
	}

	/**
	 * Invitados sin enlace personalizado: se identifican por una cookie firmada puesta tras
	 * su primer envío, para que puedan volver a editar su respuesta sin crear un duplicado.
	 */
	private function guardar_sin_slug( array $datos ) {
		$existente = $this->respuesta_desde_cookie_edicion();

		if ( $existente ) {
			$id_existente = (int) $existente['id'];
			$ok = RSVP_Boda_DB::actualizar_respuesta_por_id( $id_existente, $datos );
			return $ok ? $id_existente : false;
		}

		$nuevo_id = RSVP_Boda_DB::insertar_respuesta( $datos );

		if ( $nuevo_id ) {
			$this->fijar_cookie_edicion( $nuevo_id );
		}

		return $nuevo_id;
	}

	private function respuesta_desde_cookie_edicion(): ?array {
		$id = $this->id_desde_cookie_edicion();
		return $id ? RSVP_Boda_DB::obtener_respuesta_por_id( $id ) : null;
	}

	private function id_desde_cookie_edicion(): int {
		$cookie = $_COOKIE[ self::COOKIE_EDICION ] ?? '';
		if ( ! str_contains( $cookie, '.' ) ) {
			return 0;
		}

		list( $id, $firma ) = explode( '.', $cookie, 2 );
		$id = absint( $id );

		if ( $id && hash_equals( $this->firmar( $id ), $firma ) ) {
			return $id;
		}

		return 0;
	}

	private function fijar_cookie_edicion( int $id ): void {
		$valor = $id . '.' . $this->firmar( $id );
		setcookie( self::COOKIE_EDICION, $valor, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
	}

	private function firmar( int $id ): string {
		return hash_hmac( 'sha256', (string) $id, wp_salt( 'auth' ) );
	}

	private function obtener_ip(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	}

	/**
	 * Dispara los correos de aviso. Nunca condiciona el resultado ya devuelto al usuario:
	 * el guardado en base de datos ya se ha completado antes de llegar aquí.
	 */
	private function enviar_correos( int $id, ?array $respuesta, bool $es_cambio ): void {
		if ( ! $respuesta ) {
			return;
		}

		$actualizacion = array();

		// Sin email del invitado (es opcional) no hay nada que enviarle: eso no es un fallo,
		// antes se marcaba como "Fallo de envío" en el panel a todo el que no lo rellenaba.
		$tiene_email = ! empty( $respuesta['email'] ) && is_email( $respuesta['email'] );
		$ok_invitado = $tiene_email ? RSVP_Boda_Email::enviar_confirmacion_invitado( $respuesta, $es_cambio ) : true;
		$actualizacion['email_enviado'] = $tiene_email && $ok_invitado ? 1 : 0;

		$ok_admin = RSVP_Boda_Email::enviar_aviso_pareja( $respuesta, $es_cambio );
		$actualizacion['email_enviado_admin'] = $ok_admin ? 1 : 0;

		if ( ! $ok_invitado || ! $ok_admin ) {
			$fallidos = array();
			if ( ! $ok_invitado ) {
				$fallidos[] = 'al invitado';
			}
			if ( ! $ok_admin ) {
				$fallidos[] = 'aviso a la pareja';
			}
			$motivo = RSVP_Boda_Email::ultimo_error();

			$actualizacion['email_error'] = 'Fallo de envío (' . implode( ' y ', $fallidos ) . ') el ' . current_time( 'mysql' ) .
				( $motivo ? ': ' . $motivo : '' );
			RSVP_Boda_Logger::error(
				'enviar_correos',
				'Fallo al enviar el correo de la confirmación',
				array(
					'id_respuesta'  => $id,
					'email_invitado_ok' => $ok_invitado,
					'email_admin_ok'    => $ok_admin,
					'motivo'            => $motivo,
				)
			);
		} else {
			// Limpia un error de un intento anterior si esta vez el envío ha ido bien.
			$actualizacion['email_error'] = '';
		}

		RSVP_Boda_DB::actualizar_respuesta_por_id( $id, $actualizacion );
	}
}
