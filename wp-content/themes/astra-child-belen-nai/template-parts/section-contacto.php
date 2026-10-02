<?php
/**
 * 9. Contacto: teléfono para quien se atasque con el formulario.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$telefono = rsvp_boda_get_option( 'telefono_contacto' );
if ( '' === $telefono ) {
	return;
}
?>
<section id="contacto" class="boda-seccion boda-seccion--contacto">
	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">¿Alguna duda?</h2>
		<p>
			Escríbenos o llámanos al
			<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $telefono ) ); ?>"><?php echo esc_html( $telefono ); ?></a>
		</p>
	</div>
</section>
