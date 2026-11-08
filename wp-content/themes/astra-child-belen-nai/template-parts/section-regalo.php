<?php
/**
 * 7. Lista de regalos / número de cuenta. Solo se pinta si el ajuste "regalo_mostrar" está activo
 * (comprobado también en page-boda.php, pero se repite aquí por si se llama a esta parte directamente).
 *
 * El número de cuenta no se ve de primeras: hay que pulsar "Ver número de cuenta" para que aparezca.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '1' !== rsvp_boda_get_option( 'regalo_mostrar', '0' ) ) {
	return;
}

// Se guarda sin espacios para copiarlo y se enseña en grupos de 4, como en el banco.
$iban          = strtoupper( preg_replace( '/\s+/', '', (string) rsvp_boda_get_option( 'regalo_iban' ) ) );
$iban_agrupado = trim( chunk_split( $iban, 4, ' ' ) );
?>
<section id="regalo" class="boda-seccion boda-seccion--regalo">

	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Lista de regalos</h2>

		<?php if ( rsvp_boda_get_option( 'regalo_texto' ) ) : ?>
			<p><?php echo nl2br( esc_html( rsvp_boda_get_option( 'regalo_texto' ) ) ); ?></p>
		<?php endif; ?>

		<?php if ( $iban ) : ?>
			<button type="button" class="boda-boton boda-regalo__mostrar" aria-expanded="false" aria-controls="boda-regalo-cuenta" data-mostrar-cuenta>
				Ver número de cuenta
			</button>

			<div class="boda-regalo__iban" id="boda-regalo-cuenta" hidden>
				<span class="boda-regalo__iban-etiqueta">IBAN</span>
				<span class="boda-regalo__iban-valor" id="boda-iban" data-iban="<?php echo esc_attr( $iban ); ?>"><?php echo esc_html( $iban_agrupado ); ?></span>
				<?php if ( rsvp_boda_get_option( 'regalo_titular' ) ) : ?>
					<p class="boda-regalo__titular">
						<span class="boda-regalo__iban-etiqueta">Beneficiario</span>
						<?php echo esc_html( rsvp_boda_get_option( 'regalo_titular' ) ); ?>
					</p>
				<?php endif; ?>
				<button type="button" class="boda-boton boda-boton--secundario" data-copiar-iban="boda-iban">Copiar número</button>
			</div>
		<?php endif; ?>
	</div>
</section>
