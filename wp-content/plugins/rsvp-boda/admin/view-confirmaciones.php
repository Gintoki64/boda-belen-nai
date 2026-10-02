<?php
/**
 * Pantalla "Confirmaciones": contadores, tabla filtrable y exportación CSV.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$contadores = RSVP_Boda_DB::obtener_contadores();

$tabla = new RSVP_Boda_List_Table();
$tabla->prepare_items();

$url_export = wp_nonce_url( admin_url( 'admin-post.php?action=rsvp_boda_export' ), 'rsvp_boda_export' );
?>
<div class="wrap rsvp-boda-wrap">
	<h1 class="wp-heading-inline">Confirmaciones</h1>
	<a href="<?php echo esc_url( $url_export ); ?>" class="page-title-action">Exportar a CSV</a>
	<hr class="wp-header-end">

	<div class="rsvp-boda-contadores">
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero"><?php echo esc_html( $contadores['confirmados'] ); ?></span>
			<span class="rsvp-boda-contador__etiqueta">Confirmados</span>
		</div>
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero"><?php echo esc_html( $contadores['no_vienen'] ); ?></span>
			<span class="rsvp-boda-contador__etiqueta">No vienen</span>
		</div>
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero"><?php echo esc_html( $contadores['pendientes'] ); ?></span>
			<span class="rsvp-boda-contador__etiqueta">Pendientes de responder</span>
		</div>
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero"><?php echo esc_html( $contadores['con_alergias'] ); ?></span>
			<span class="rsvp-boda-contador__etiqueta">Con alergias/menú especial</span>
		</div>
	</div>

	<?php if ( ! empty( $contadores['bus_por_parada'] ) ) : ?>
		<p class="rsvp-boda-bus-resumen">
			<strong>Plazas de bus por parada:</strong>
			<?php
			$partes = array();
			foreach ( $contadores['bus_por_parada'] as $fila ) {
				$partes[] = esc_html( $fila['parada_bus'] ) . ' (' . esc_html( $fila['total'] ) . ')';
			}
			echo implode( ' &middot; ', $partes );
			?>
		</p>
	<?php endif; ?>

	<form method="get">
		<input type="hidden" name="page" value="rsvp-boda-confirmaciones" />
		<?php
		$tabla->views();
		$tabla->search_box( 'Buscar por nombre', 'rsvp-boda-buscar' );
		$tabla->display();
		?>
	</form>
</div>
