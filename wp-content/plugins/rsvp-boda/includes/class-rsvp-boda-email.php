<?php
/**
 * Envío de correos vía SMTP externo (Brevo) en vez de mail(). El envío nunca bloquea
 * ni condiciona el guardado en base de datos: es siempre un aviso de mejor esfuerzo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Email {

	/** Motivo del último fallo de wp_mail() en esta petición, para enseñarlo en el panel. */
	private static string $ultimo_error = '';

	public function __construct() {
		add_action( 'phpmailer_init', array( $this, 'configurar_smtp' ) );
		add_action( 'wp_mail_failed', array( __CLASS__, 'registrar_fallo' ) );
		add_action( 'admin_post_rsvp_boda_email_prueba', array( $this, 'manejar_email_prueba' ) );
	}

	public static function registrar_fallo( WP_Error $error ): void {
		self::$ultimo_error = $error->get_error_message();
	}

	public static function ultimo_error(): string {
		return self::$ultimo_error;
	}

	public function configurar_smtp( PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
		$host = rsvp_boda_get_option( 'smtp_host' );
		if ( '' === $host ) {
			return; // Sin host configurado, se deja el comportamiento por defecto de WordPress.
		}

		$clave = defined( 'RSVP_BODA_SMTP_CLAVE' ) ? RSVP_BODA_SMTP_CLAVE : rsvp_boda_get_option( 'smtp_clave' );

		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->Port       = (int) rsvp_boda_get_option( 'smtp_puerto', 587 );
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = rsvp_boda_get_option( 'smtp_usuario' );
		$phpmailer->Password   = $clave;
		// Brevo admite 587 (STARTTLS) y 465 (SSL directo); cada puerto necesita su cifrado.
		$phpmailer->SMTPSecure = 465 === $phpmailer->Port
			? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
			: PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
		$phpmailer->CharSet    = 'UTF-8';

		$remitente_email = rsvp_boda_get_option( 'smtp_remitente_email' );
		$remitente_nombre = rsvp_boda_get_option( 'smtp_remitente_nombre' );
		if ( $remitente_email ) {
			$phpmailer->setFrom( $remitente_email, $remitente_nombre ?: $remitente_email );
		}
	}

	/**
	 * Envía el correo de resumen al invitado. Devuelve true/false y no lanza excepciones:
	 * un fallo de email nunca debe interrumpir el flujo de guardado del RSVP.
	 */
	public static function enviar_confirmacion_invitado( array $respuesta, bool $es_cambio = false ): bool {
		// El email es un campo opcional del formulario (el prompt original solo pedía teléfono,
		// pero el propio prompt exige mandar un correo resumen al invitado, así que se añadió
		// como campo extra opcional para poder cumplir ese requisito sin forzarlo).
		if ( empty( $respuesta['email'] ) || ! is_email( $respuesta['email'] ) ) {
			return false;
		}

		$asunto = $es_cambio
			? 'Hemos actualizado tu confirmación'
			: rsvp_boda_get_option( 'email_asunto_invitado', 'Hemos recibido tu confirmación' );
		$cuerpo = self::renderizar_plantilla(
			'email-invitado.php',
			array(
				'respuesta' => $respuesta,
				'es_cambio' => $es_cambio,
			)
		);

		return wp_mail( $respuesta['email'], $asunto, $cuerpo, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * Aviso a la pareja de que ha entrado una confirmación nueva o de que alguien ha cambiado la suya.
	 * El nombre va en el asunto para distinguirlas de un vistazo en la bandeja de entrada.
	 */
	public static function enviar_aviso_pareja( array $respuesta, bool $es_cambio = false ): bool {
		$destino = rsvp_boda_get_option( 'email_aviso_pareja' );
		if ( ! is_email( $destino ) ) {
			return false;
		}

		$asunto = $es_cambio
			? 'Cambio en una confirmación'
			: rsvp_boda_get_option( 'email_asunto_admin', 'Nueva confirmación de boda' );
		$asunto .= ': ' . $respuesta['nombre_completo'];

		$cuerpo = self::renderizar_plantilla(
			'email-admin.php',
			array(
				'respuesta' => $respuesta,
				'es_cambio' => $es_cambio,
			)
		);

		$cabeceras = array( 'Content-Type: text/html; charset=UTF-8' );
		// "Responder" en el correo de aviso escribe directamente al invitado.
		if ( ! empty( $respuesta['email'] ) && is_email( $respuesta['email'] ) ) {
			$cabeceras[] = 'Reply-To: ' . $respuesta['email'];
		}

		return wp_mail( $destino, $asunto, $cuerpo, $cabeceras );
	}

	/**
	 * Botón "Enviar correo de prueba" de Ajustes generales: comprueba la configuración SMTP
	 * sin tener que rellenar el formulario de la web.
	 */
	public function manejar_email_prueba(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permiso para hacer esto.' );
		}
		check_admin_referer( 'rsvp_boda_email_prueba' );

		$destino = rsvp_boda_get_option( 'email_aviso_pareja' );
		if ( ! is_email( $destino ) ) {
			$tipo    = 'error';
			$mensaje = 'Rellena y guarda primero el campo "Email de aviso a la pareja".';
		} else {
			$ok = wp_mail(
				$destino,
				'Correo de prueba de la web de la boda',
				'<p>Si lees esto, los avisos de confirmaciones llegarán bien a este correo.</p>',
				array( 'Content-Type: text/html; charset=UTF-8' )
			);
			$tipo    = $ok ? 'success' : 'error';
			$mensaje = $ok
				? "Correo de prueba enviado a {$destino}. Mira la bandeja de entrada (y la de spam)."
				: 'No se ha podido enviar: ' . ( self::$ultimo_error ?: 'error desconocido' );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => 'rsvp-boda-ajustes',
					'rsvp_boda_aviso' => $tipo,
					'rsvp_boda_msg'   => rawurlencode( $mensaje ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function renderizar_plantilla( string $archivo, array $variables ): string {
		extract( $variables, EXTR_SKIP );
		ob_start();
		include RSVP_BODA_PLUGIN_DIR . 'templates/' . $archivo;
		return (string) ob_get_clean();
	}
}
