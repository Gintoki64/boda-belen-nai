<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<footer class="boda-pie">
	<p>
		<?php echo esc_html( rsvp_boda_get_option( 'nombre_1' ) . ' & ' . rsvp_boda_get_option( 'nombre_2' ) ); ?>
		&middot;
		<?php echo esc_html( rsvp_boda_get_option( 'fecha_boda' ) ); ?>
	</p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
