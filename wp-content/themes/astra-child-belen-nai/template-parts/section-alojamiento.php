<?php
/**
 * 5. Alojamiento recomendado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! boda_theme_hay_alojamiento() ) {
	return;
}

$contenido = rsvp_boda_get_option( 'alojamiento_html' );
?>
<section id="alojamiento" class="boda-seccion boda-seccion--alojamiento">
	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Alojamiento recomendado</h2>
		<div class="boda-contenido-libre"><?php echo wp_kses_post( $contenido ); ?></div>
	</div>
</section>
