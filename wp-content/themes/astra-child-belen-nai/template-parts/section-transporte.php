<?php
/**
 * 4. Bus / transporte: horarios y paradas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paradas = array();
foreach ( preg_split( '/\r\n|\r|\n/', (string) rsvp_boda_get_option( 'bus_paradas', '' ) ) as $linea ) {
	$linea = trim( $linea );
	if ( '' === $linea ) {
		continue;
	}
	$partes    = array_map( 'trim', explode( ';', $linea, 2 ) );
	$paradas[] = array(
		'nombre' => $partes[0],
		'hora'   => $partes[1] ?? '',
	);
}
?>
<section id="transporte" class="boda-seccion boda-seccion--transporte">
	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Bus</h2>

		<?php if ( rsvp_boda_get_option( 'bus_horario' ) ) : ?>
			<p><?php echo nl2br( esc_html( rsvp_boda_get_option( 'bus_horario' ) ) ); ?></p>
		<?php endif; ?>

		<?php if ( $paradas ) : ?>
			<ul class="boda-transporte__paradas">
				<?php foreach ( $paradas as $parada ) : ?>
					<li>
						<strong><?php echo esc_html( $parada['nombre'] ); ?></strong>
						<?php if ( $parada['hora'] ) : ?>
							<span class="boda-transporte__hora"><?php echo esc_html( $parada['hora'] ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
