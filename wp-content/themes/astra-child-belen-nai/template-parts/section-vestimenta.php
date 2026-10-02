<?php
/**
 * 6. Código de vestimenta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! boda_theme_hay_vestimenta() ) {
	return;
}

$contenido = rsvp_boda_get_option( 'vestimenta_html' );
?>
<section id="vestimenta" class="boda-seccion boda-seccion--vestimenta">
	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Código de vestimenta</h2>
		<div class="boda-contenido-libre"><?php echo wp_kses_post( $contenido ); ?></div>
	</div>
</section>
