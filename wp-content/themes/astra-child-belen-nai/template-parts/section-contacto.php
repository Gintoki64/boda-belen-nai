<?php
/**
 * 9. Contacto: teléfonos de Nai y de Belén para quien se atasque con el formulario.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$telefonos = array_filter(
	array(
		'Nai'   => rsvp_boda_get_option( 'telefono_contacto' ),
		'Belén' => rsvp_boda_get_option( 'telefono_contacto_2' ),
	)
);
if ( ! $telefonos ) {
	return;
}?>
<section id="contacto" class="boda-seccion boda-seccion--contacto">
	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">¿Alguna duda?</h2>
		<p>Escríbenos o llámanos:</p>
		<ul class="boda-contacto__telefonos">
			<?php foreach ( $telefonos as $nombre => $telefono ) : ?>
				<li>
					<span class="boda-contacto__nombre"><?php echo esc_html( $nombre ); ?></span>
					<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $telefono ) ); ?>"><?php echo esc_html( boda_theme_telefono_legible( $telefono ) ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
