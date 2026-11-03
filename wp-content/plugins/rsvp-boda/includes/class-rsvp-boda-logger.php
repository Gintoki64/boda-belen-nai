<?php
/**
 * Log propio de errores del plugin, para que un fallo de guardado nunca quede en silencio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Logger {

	/**
	 * Registra un error en el log de errores de PHP del servidor (no es público).
	 *
	 * Antes además se escribía en wp-content/uploads/rsvp-boda-errores.log, pero esa carpeta se
	 * puede abrir desde el navegador y el log lleva nombres de invitados y direcciones de email.
	 */
	public static function error( string $contexto, string $mensaje, array $datos = array() ): void {
		error_log( sprintf( '[RSVP Boda] %s: %s %s', $contexto, $mensaje, $datos ? wp_json_encode( $datos ) : '' ) );
	}
}
