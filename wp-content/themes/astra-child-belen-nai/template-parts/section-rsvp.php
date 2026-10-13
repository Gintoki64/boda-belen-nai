<?php
/**
 * 8. Formulario de confirmación. El <form> en sí lo pinta el plugin rsvp-boda,
 * que resuelve el enlace personalizado, el permiso de acompañante y el envío por AJAX.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="rsvp" class="boda-seccion boda-seccion--rsvp">
	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Confirma tu asistencia</h2>
		<?php if ( rsvp_boda_get_option( 'fecha_limite_confirmar' ) ) : ?>
			<p class="boda-rsvp__limite">Por favor, confirma antes del <?php echo esc_html( boda_theme_fecha_legible( rsvp_boda_get_option( 'fecha_limite_confirmar' ) ) ); ?>.</p>
		<?php endif; ?>

		<?php
		if ( function_exists( 'rsvp_boda_render_form' ) ) {
			rsvp_boda_render_form();
		}
		?>
	</div>
</section>
