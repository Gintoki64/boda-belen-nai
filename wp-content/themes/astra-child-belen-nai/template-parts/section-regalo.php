<?php
/**
 * 7. Lista de regalos / número de cuenta. Solo se pinta si el ajuste "regalo_mostrar" está activo
 * (comprobado también en page-boda.php, pero se repite aquí por si se llama a esta parte directamente).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '1' !== rsvp_boda_get_option( 'regalo_mostrar', '0' ) ) {
	return;
}
?>
<section id="regalo" class="boda-seccion boda-seccion--regalo">

	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Lista de regalos</h2>

		<?php if ( rsvp_boda_get_option( 'regalo_texto' ) ) : ?>
			<p><?php echo nl2br( esc_html( rsvp_boda_get_option( 'regalo_texto' ) ) ); ?></p>
		<?php endif; ?>

		<?php if ( rsvp_boda_get_option( 'regalo_iban' ) ) : ?>
			<div class="boda-regalo__iban">
				<span class="boda-regalo__iban-valor" id="boda-iban"><?php echo esc_html( rsvp_boda_get_option( 'regalo_iban' ) ); ?></span>
				<button type="button" class="boda-boton boda-boton--secundario" data-copiar-iban="boda-iban">Copiar IBAN</button>
				<?php if ( rsvp_boda_get_option( 'regalo_titular' ) ) : ?>
					<p class="boda-regalo__titular">Titular: <?php echo esc_html( rsvp_boda_get_option( 'regalo_titular' ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
