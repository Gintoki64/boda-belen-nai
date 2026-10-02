<?php
/**
 * Aviso a la pareja de que ha entrado una nueva confirmación. Variable disponible: $respuesta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$acompanantes = (array) json_decode( (string) $respuesta['acompanantes'], true );
?>
<div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto;">
	<h2>Nueva confirmación de boda</h2>
	<table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
		<tr><td><strong>Nombre</strong></td><td><?php echo esc_html( $respuesta['nombre_completo'] ); ?></td></tr>
		<tr><td><strong>Asiste</strong></td><td><?php echo 'si' === $respuesta['asiste'] ? 'Sí' : 'No'; ?></td></tr>
		<tr><td><strong>Acompañantes</strong></td><td><?php echo esc_html( $respuesta['num_acompanantes'] . ( $acompanantes ? ' (' . implode( ', ', $acompanantes ) . ')' : '' ) ); ?></td></tr>
		<tr><td><strong>Alergias</strong></td><td><?php echo esc_html( $respuesta['alergias'] ?: '—' ); ?></td></tr>
		<tr><td><strong>Menú infantil</strong></td><td><?php echo 'si' === $respuesta['menu_infantil'] ? 'Sí' : 'No'; ?></td></tr>
		<tr><td><strong>Bus</strong></td><td><?php echo 'si' === $respuesta['necesita_bus'] ? 'Sí (' . esc_html( $respuesta['parada_bus'] ?: 'sin parada' ) . ')' : 'No'; ?></td></tr>
		<tr><td><strong>Canción</strong></td><td><?php echo esc_html( $respuesta['cancion'] ?: '—' ); ?></td></tr>
		<tr><td><strong>Teléfono</strong></td><td><?php echo esc_html( $respuesta['telefono'] ); ?></td></tr>
	</table>
</div>
