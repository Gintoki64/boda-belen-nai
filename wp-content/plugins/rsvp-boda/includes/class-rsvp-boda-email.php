<?php
/**
 * Envío de correos vía SMTP externo (Brevo) en vez de mail(). El envío nunca bloquea
 * ni condiciona el guardado en base de datos: es siempre un aviso de mejor esfuerzo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Email {

	public function __construct() {
		add_action( 'phpmailer_init', array( $this, 'configurar_smtp' ) );
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
		$phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
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
	public static function enviar_confirmacion_invitado( array $respuesta ): bool {
		// El email es un campo opcional del formulario (el prompt original solo pedía teléfono,
		// pero el propio prompt exige mandar un correo resumen al invitado, así que se añadió
		// como campo extra opcional para poder cumplir ese requisito sin forzarlo).
		if ( empty( $respuesta['email'] ) || ! is_email( $respuesta['email'] ) ) {
			return false;
		}

		$asunto = rsvp_boda_get_option( 'email_asunto_invitado', 'Hemos recibido tu confirmación' );
		$cuerpo = self::renderizar_plantilla( 'email-invitado.php', array( 'respuesta' => $respuesta ) );

		return wp_mail( $respuesta['email'], $asunto, $cuerpo, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * Aviso a la pareja de que ha entrado una nueva confirmación.
	 */
	public static function enviar_aviso_pareja( array $respuesta ): bool {
		$destino = rsvp_boda_get_option( 'email_aviso_pareja' );
		if ( ! is_email( $destino ) ) {
			return false;
		}

		$asunto = rsvp_boda_get_option( 'email_asunto_admin', 'Nueva confirmación de boda' );
		$cuerpo = self::renderizar_plantilla( 'email-admin.php', array( 'respuesta' => $respuesta ) );

		return wp_mail( $destino, $asunto, $cuerpo, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	private static function renderizar_plantilla( string $archivo, array $variables ): string {
		extract( $variables, EXTR_SKIP );
		ob_start();
		include RSVP_BODA_PLUGIN_DIR . 'templates/' . $archivo;
		return (string) ob_get_clean();
	}
}
