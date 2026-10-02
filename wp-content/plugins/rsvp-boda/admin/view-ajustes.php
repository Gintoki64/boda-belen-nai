<?php
/**
 * Pantalla "Ajustes generales": todo el contenido editable de la web, sin tocar código.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ajustes = wp_parse_args( get_option( RSVP_Boda_Settings::OPTION_NAME, array() ), rsvp_boda_ajustes_por_defecto() );

/**
 * Pinta el control adecuado según el tipo de campo declarado en RSVP_Boda_Settings::secciones().
 */
function rsvp_boda_pintar_campo( array $campo, array $ajustes ): void {
	list( $clave, $etiqueta, $tipo ) = $campo;
	$args   = $campo[3] ?? array();
	$valor  = $ajustes[ $clave ] ?? '';
	$nombre = RSVP_Boda_Settings::OPTION_NAME . '[' . $clave . ']';
	$id     = 'rsvp-boda-campo-' . $clave;

	switch ( $tipo ) {
		case 'textarea':
			printf( '<textarea id="%1$s" name="%2$s" rows="4" class="large-text">%3$s</textarea>', esc_attr( $id ), esc_attr( $nombre ), esc_textarea( $valor ) );
			break;

		case 'wysiwyg':
			wp_editor( $valor, $id, array( 'textarea_name' => $nombre, 'textarea_rows' => 6 ) );
			break;

		case 'checkbox':
			printf(
				'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> Activado</label>',
				esc_attr( $id ),
				esc_attr( $nombre ),
				checked( '1', $valor, false )
			);
			break;

		case 'imagen':
			$url = $valor ? wp_get_attachment_image_url( (int) $valor, 'medium' ) : '';
			printf(
				'<div class="rsvp-boda-campo-imagen">
					<input type="hidden" id="%1$s" name="%2$s" value="%3$s" />
					<div class="rsvp-boda-campo-imagen__preview">%4$s</div>
					<button type="button" class="button rsvp-boda-elegir-imagen" data-target="%1$s">Elegir imagen</button>
				</div>',
				esc_attr( $id ),
				esc_attr( $nombre ),
				esc_attr( $valor ),
				$url ? '<img src="' . esc_url( $url ) . '" style="max-width:200px;height:auto;display:block;" />' : ''
			);
			break;

		case 'galeria':
			$ids  = (array) $valor;
			$html = '';
			foreach ( $ids as $id_img ) {
				$url_img = wp_get_attachment_image_url( (int) $id_img, 'thumbnail' );
				if ( $url_img ) {
					$html .= '<img src="' . esc_url( $url_img ) . '" style="width:80px;height:80px;object-fit:cover;margin-right:4px;" />';
				}
			}
			printf(
				'<div class="rsvp-boda-campo-galeria">
					<input type="hidden" id="%1$s" name="%2$s" value="%3$s" />
					<div class="rsvp-boda-campo-galeria__preview">%4$s</div>
					<button type="button" class="button rsvp-boda-elegir-galeria" data-target="%1$s">Elegir imágenes</button>
				</div>',
				esc_attr( $id ),
				esc_attr( $nombre ),
				esc_attr( implode( ',', $ids ) ),
				$html
			);
			break;

		case 'number':
			printf( '<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" min="0" />', esc_attr( $id ), esc_attr( $nombre ), esc_attr( $valor ) );
			break;

		case 'password':
			printf( '<input type="password" id="%1$s" name="%2$s" value="%3$s" class="regular-text" autocomplete="new-password" />', esc_attr( $id ), esc_attr( $nombre ), esc_attr( $valor ) );
			break;

		default: // text, url, email, tel, date, time.
			printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" />', esc_attr( $tipo ), esc_attr( $id ), esc_attr( $nombre ), esc_attr( $valor ) );
	}

	if ( ! empty( $args['ayuda'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['ayuda'] ) );
	}
}
?>
<div class="wrap rsvp-boda-wrap">
	<h1>Ajustes generales</h1>
	<form method="post" action="options.php">
		<?php settings_fields( RSVP_Boda_Settings::GROUP ); ?>

		<?php foreach ( RSVP_Boda_Settings::secciones() as $seccion ) : ?>
			<h2><?php echo esc_html( $seccion['titulo'] ); ?></h2>
			<table class="form-table" role="presentation">
				<?php foreach ( $seccion['campos'] as $campo ) : ?>
					<tr>
						<th scope="row"><label for="rsvp-boda-campo-<?php echo esc_attr( $campo[0] ); ?>"><?php echo esc_html( $campo[1] ); ?></label></th>
						<td><?php rsvp_boda_pintar_campo( $campo, $ajustes ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>

		<?php submit_button( 'Guardar ajustes' ); ?>
	</form>
</div>
