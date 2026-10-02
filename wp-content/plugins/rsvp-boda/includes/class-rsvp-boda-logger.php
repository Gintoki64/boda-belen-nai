<?php
/**
 * Log propio de errores del plugin, para que un fallo de guardado nunca quede en silencio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Logger {

	/**
	 * Registra un error en el log de errores de PHP y, si existe, en un archivo propio dentro de uploads.
	 */
	public static function error( string $contexto, string $mensaje, array $datos = array() ): void {
		$linea = sprintf( '[RSVP Boda] %s: %s %s', $contexto, $mensaje, $datos ? wp_json_encode( $datos ) : '' );

		error_log( $linea );

		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['error'] ) && ! empty( $upload_dir['basedir'] ) ) {
			$archivo = trailingslashit( $upload_dir['basedir'] ) . 'rsvp-boda-errores.log';
			$fecha   = current_time( 'mysql' );
			// No es información sensible de otros usuarios, solo diagnóstico del propio formulario.
			@file_put_contents( $archivo, "[{$fecha}] {$linea}\n", FILE_APPEND | LOCK_EX );
		}
	}
}
