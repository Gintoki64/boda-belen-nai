<?php
/**
 * 3. Ceremonia y banquete: hora, lugar, mapa embebido, cómo llegar y aparcamiento.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="ceremonia" class="boda-seccion boda-seccion--ceremonia">

	<div class="boda-seccion__contenido">
		<h2 class="boda-seccion__titulo">Ceremonia y banquete</h2>

		<div class="boda-ceremonia__bloques">
			<div class="boda-ceremonia__bloque">
				<h3>Ceremonia</h3>
				<p class="boda-ceremonia__hora"><?php echo esc_html( rsvp_boda_get_option( 'hora_ceremonia' ) ); ?></p>
				<p>
					<strong><?php echo esc_html( rsvp_boda_get_option( 'lugar_ceremonia' ) ); ?></strong><br />
					<?php echo esc_html( rsvp_boda_get_option( 'direccion_ceremonia' ) ); ?>
				</p>
				<?php if ( rsvp_boda_get_option( 'mapa_ceremonia_url' ) ) : ?>
					<iframe
						class="boda-mapa"
						src="<?php echo esc_url( rsvp_boda_get_option( 'mapa_ceremonia_url' ) ); ?>"
						loading="lazy"
						referrerpolicy="no-referrer-when-downgrade"
						title="Mapa de la ceremonia"
					></iframe>
				<?php endif; ?>
			</div>

			<div class="boda-ceremonia__bloque">
				<h3>Banquete</h3>
				<p>
					<strong><?php echo esc_html( rsvp_boda_get_option( 'lugar_banquete' ) ); ?></strong><br />
					<?php echo esc_html( rsvp_boda_get_option( 'direccion_banquete' ) ); ?>
				</p>
				<?php if ( rsvp_boda_get_option( 'mapa_banquete_url' ) ) : ?>
					<iframe
						class="boda-mapa"
						src="<?php echo esc_url( rsvp_boda_get_option( 'mapa_banquete_url' ) ); ?>"
						loading="lazy"
						referrerpolicy="no-referrer-when-downgrade"
						title="Mapa del banquete"
					></iframe>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( rsvp_boda_get_option( 'indicaciones_aparcamiento' ) ) : ?>
			<div class="boda-ceremonia__aparcamiento">
				<h3>Cómo llegar y aparcamiento</h3>
				<p><?php echo nl2br( esc_html( rsvp_boda_get_option( 'indicaciones_aparcamiento' ) ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
