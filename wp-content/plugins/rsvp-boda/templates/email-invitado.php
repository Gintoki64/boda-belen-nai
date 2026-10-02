<?php
/**
 * Correo de resumen enviado al invitado tras confirmar. Variable disponible: $respuesta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$acompanantes = (array) json_decode( (string) $respuesta['acompanantes'], true );
?>
<div style="font-family: Georgia, 'Times New Roman', serif; color: #1f3a6e; max-width: 480px; margin: 0 auto;">
	<h2 style="color:#1f3a6e;">
		<?php echo esc_html( rsvp_boda_get_option( 'nombre_1' ) . ' & ' . rsvp_boda_get_option( 'nombre_2' ) ); ?>
	</h2>

	<p>Hola <?php echo esc_html( $respuesta['nombre_completo'] ); ?>,</p>

	<?php if ( 'si' === $respuesta['asiste'] ) : ?>
		<p>¡Genial! Hemos apuntado que <strong>vienes</strong> a la boda<?php echo $respuesta['num_acompanantes'] > 0 ? ' junto a ' . esc_html( implode( ', ', $acompanantes ) ) : ''; ?>.</p>
	<?php else : ?>
		<p>Hemos apuntado que <strong>no podrás venir</strong>. ¡Te echaremos de menos!</p>
	<?php endif; ?>

	<p>
		<strong>Fecha:</strong> <?php echo esc_html( rsvp_boda_get_option( 'fecha_boda' ) ); ?><br />
		<strong>Ceremonia:</strong> <?php echo esc_html( rsvp_boda_get_option( 'hora_ceremonia' ) . ' — ' . rsvp_boda_get_option( 'lugar_ceremonia' ) ); ?>
	</p>

	<?php if ( ! empty( $respuesta['alergias'] ) ) : ?>
		<p><strong>Alergias/menú especial que nos has indicado:</strong> <?php echo esc_html( $respuesta['alergias'] ); ?></p>
	<?php endif; ?>

	<p>Si algo cambia, puedes volver a entrar en el mismo enlace y actualizar tu respuesta cuando quieras.</p>

	<p>Cualquier duda, llámanos al <?php echo esc_html( rsvp_boda_get_option( 'telefono_contacto' ) ); ?>.</p>
</div>
