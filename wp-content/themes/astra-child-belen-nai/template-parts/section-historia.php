<?php
/**
 * 2. Nuestra historia: texto e imágenes, editables desde el admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! boda_theme_hay_historia() ) {
	return;
}

$texto    = rsvp_boda_get_option( 'historia_texto' );
$imagenes = (array) rsvp_boda_get_option( 'historia_imagenes', array() );
?>
<section id="historia" class="boda-seccion boda-seccion--historia">
	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Nuestra historia</h2>

		<?php if ( $texto ) : ?>
			<div class="boda-historia__texto"><?php echo wp_kses_post( $texto ); ?></div>
		<?php endif; ?>

		<?php if ( $imagenes ) : ?>
			<div class="boda-historia__galeria">
				<?php foreach ( $imagenes as $id_imagen ) : ?>
					<?php echo wp_get_attachment_image( (int) $id_imagen, 'large', false, array( 'loading' => 'lazy', 'class' => 'boda-historia__imagen' ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
