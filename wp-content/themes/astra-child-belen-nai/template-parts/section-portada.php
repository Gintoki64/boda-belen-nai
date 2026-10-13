<?php
/**
 * 1. Portada: nombres, fecha y cuenta atrás. Si llega por un enlace personalizado (?i=slug),
 * saluda al invitado por su nombre.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug          = sanitize_title( wp_unslash( $_GET['i'] ?? '' ) );
$invitado      = $slug ? RSVP_Boda_Invitados::obtener_por_slug( $slug ) : null;
$fecha_boda    = rsvp_boda_get_option( 'fecha_boda' );
$hora_ceremonia = rsvp_boda_get_option( 'hora_ceremonia' );
$fecha_iso     = trim( $fecha_boda . 'T' . ( $hora_ceremonia ?: '00:00' ) );
?>
<section id="portada" class="boda-seccion boda-seccion--portada">

	<div class="boda-seccion__contenido">
		<?php if ( $invitado ) : ?>
			<p class="boda-portada__saludo">Querido/a <?php echo esc_html( $invitado['nombre'] ); ?>,</p>
		<?php endif; ?>

		<h1 class="boda-portada__nombres">
			<?php echo esc_html( rsvp_boda_get_option( 'nombre_1' ) ); ?>
			<span class="boda-portada__y">&amp;</span>
			<?php echo esc_html( rsvp_boda_get_option( 'nombre_2' ) ); ?>
		</h1>

		<?php if ( $fecha_boda ) : ?>
			<p class="boda-portada__fecha"><?php echo esc_html( boda_theme_fecha_legible( $fecha_boda ) ); ?></p>
		<?php endif; ?>

		<div id="boda-countdown" class="boda-countdown" data-fecha="<?php echo esc_attr( $fecha_iso ); ?>" aria-live="off">
			<div class="boda-countdown__unidad"><span data-countdown="dias">--</span><small>días</small></div>
			<div class="boda-countdown__unidad"><span data-countdown="horas">--</span><small>horas</small></div>
			<div class="boda-countdown__unidad"><span data-countdown="minutos">--</span><small>min</small></div>
			<div class="boda-countdown__unidad"><span data-countdown="segundos">--</span><small>seg</small></div>
		</div>

		<a href="#rsvp" class="boda-boton boda-boton--portada">Confirmar asistencia</a>
	</div>
</section>
