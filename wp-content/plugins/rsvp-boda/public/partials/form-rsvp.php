<?php
/**
 * Markup del formulario RSVP. Variables recibidas desde rsvp_boda_render_form():
 * $slug, $clave_acceso, $invitado, $respuesta_previa, $permite_acompanantes, $nombre_prefijado, $paradas_bus.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nombre_valor    = $respuesta_previa['nombre_completo'] ?? $nombre_prefijado;
$asiste_valor    = $respuesta_previa['asiste'] ?? '';
$acompanantes_previos = $respuesta_previa ? (array) json_decode( (string) $respuesta_previa['acompanantes'], true ) : array();
$alergias_valor  = $respuesta_previa['alergias'] ?? '';
$menu_inf_valor  = $respuesta_previa['menu_infantil'] ?? 'no';
$bus_valor       = $respuesta_previa['necesita_bus'] ?? 'no';
$parada_valor    = $respuesta_previa['parada_bus'] ?? '';
$cancion_valor   = $respuesta_previa['cancion'] ?? '';
$telefono_valor  = $respuesta_previa['telefono'] ?? '';
$email_valor     = $respuesta_previa['email'] ?? '';
?>
<div class="rsvp-boda-form-wrap">

	<?php if ( $respuesta_previa ) : ?>
		<p class="rsvp-boda-aviso-previo" id="rsvp-boda-aviso-previo" role="status">
			Ya habías confirmado tu asistencia. Puedes modificar tu respuesta y volver a enviarla cuando quieras.
			<?php if ( ! $slug && '1' === rsvp_boda_get_option( 'formulario_activado', '1' ) ) : ?>
				<?php // Mismo móvil para dos personas: sin esto, la segunda sobrescribiría la respuesta de la primera. ?>
				<br />
				<button type="button" class="rsvp-boda-nueva-respuesta" id="rsvp-boda-nueva-respuesta">
					¿No eres <?php echo esc_html( $respuesta_previa['nombre_completo'] ); ?>? Enviar una respuesta nueva
				</button>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( '1' !== rsvp_boda_get_option( 'formulario_activado', '1' ) ) : ?>
		<p class="rsvp-boda-cerrado" role="status">
			El plazo para confirmar ya se ha cerrado. Si necesitas avisarnos de algo, llámanos al
			<?php echo esc_html( rsvp_boda_get_option( 'telefono_contacto', '' ) ); ?>.
		</p>
	<?php else : ?>

	<form id="rsvp-boda-form" class="rsvp-boda-form" novalidate>
		<?php wp_nonce_field( RSVP_Boda_Ajax::NONCE_ACTION, 'nonce' ); ?>
		<input type="hidden" name="guest_slug" value="<?php echo esc_attr( $slug ); ?>" />
		<input type="hidden" name="acceso" value="<?php echo esc_attr( $clave_acceso ); ?>" />
		<input type="hidden" name="nueva" value="0" />

		<p class="rsvp-boda-campo rsvp-boda-honeypot" aria-hidden="true">
			<label for="rsvp_boda_web">Deja este campo vacío</label>
			<input type="text" id="rsvp_boda_web" name="rsvp_boda_web" tabindex="-1" autocomplete="off" />
		</p>

		<p class="rsvp-boda-campo">
			<label for="rsvp-nombre">Nombre y apellidos <span class="rsvp-boda-obligatorio">*</span></label>
			<input type="text" id="rsvp-nombre" name="nombre_completo" required maxlength="191" value="<?php echo esc_attr( $nombre_valor ); ?>" />
		</p>

		<fieldset class="rsvp-boda-campo">
			<legend>¿Asistes? <span class="rsvp-boda-obligatorio">*</span></legend>
			<label><input type="radio" name="asiste" value="si" required <?php checked( 'si', $asiste_valor ); ?> /> Sí, allí estaré</label>
			<label><input type="radio" name="asiste" value="no" <?php checked( 'no', $asiste_valor ); ?> /> No podré ir</label>
		</fieldset>

		<div id="rsvp-boda-bloque-acompanantes" class="rsvp-boda-campo" hidden>
			<label for="rsvp-num-acompanantes">Número de acompañantes</label>
			<select id="rsvp-num-acompanantes"></select>
			<div id="rsvp-boda-nombres-acompanantes"></div>
		</div>

		<p class="rsvp-boda-campo">
			<label for="rsvp-alergias">Alergias, intolerancias o menú especial</label>
			<textarea id="rsvp-alergias" name="alergias" rows="3"><?php echo esc_textarea( $alergias_valor ); ?></textarea>
		</p>

		<fieldset class="rsvp-boda-campo">
			<legend>¿Necesitáis menú infantil?</legend>
			<label><input type="radio" name="menu_infantil" value="si" <?php checked( 'si', $menu_inf_valor ); ?> /> Sí</label>
			<label><input type="radio" name="menu_infantil" value="no" <?php checked( 'no', $menu_inf_valor ); ?> /> No</label>
		</fieldset>

		<?php if ( '1' === rsvp_boda_get_option( 'bus_activado', '0' ) ) : ?>
			<fieldset class="rsvp-boda-campo">
				<legend>¿Necesitas el bus?</legend>
				<label><input type="radio" name="necesita_bus" value="si" <?php checked( 'si', $bus_valor ); ?> /> Sí</label>
				<label><input type="radio" name="necesita_bus" value="no" <?php checked( 'no', $bus_valor ); ?> /> No</label>
			</fieldset>
			<p class="rsvp-boda-campo" id="rsvp-boda-bloque-parada" <?php echo 'si' === $bus_valor ? '' : 'hidden'; ?>>
				<label for="rsvp-parada-bus">Parada</label>
				<select id="rsvp-parada-bus" name="parada_bus">
					<option value="">Elige tu parada</option>
					<?php foreach ( $paradas_bus as $parada ) : ?>
						<option value="<?php echo esc_attr( $parada ); ?>" <?php selected( $parada, $parada_valor ); ?>><?php echo esc_html( $parada ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>

		<p class="rsvp-boda-campo">
			<label for="rsvp-cancion">¿Alguna canción que no puede faltar? <span class="rsvp-boda-opcional">(opcional)</span></label>
			<input type="text" id="rsvp-cancion" name="cancion" value="<?php echo esc_attr( $cancion_valor ); ?>" />
		</p>

		<p class="rsvp-boda-campo">
			<label for="rsvp-telefono">Teléfono de contacto <span class="rsvp-boda-obligatorio">*</span></label>
			<input type="tel" id="rsvp-telefono" name="telefono" required maxlength="30" value="<?php echo esc_attr( $telefono_valor ); ?>" />
		</p>

		<p class="rsvp-boda-campo">
			<label for="rsvp-email">Email <span class="rsvp-boda-opcional">(opcional, para enviarte un resumen)</span></label>
			<input type="email" id="rsvp-email" name="email" maxlength="191" value="<?php echo esc_attr( $email_valor ); ?>" />
		</p>

		<div id="rsvp-boda-mensajes" role="alert" aria-live="assertive"></div>

		<button type="submit" class="rsvp-boda-boton-enviar">Enviar confirmación</button>
	</form>

	<script type="application/json" id="rsvp-boda-acompanantes-previos"><?php echo wp_json_encode( array_values( $acompanantes_previos ) ); ?></script>

	<?php endif; ?>
</div>
