<?php
/**
 * Template Name: Página de la boda
 *
 * Página única con navegación por anclas: ensambla las nueve secciones pedidas.
 * Todo el contenido viene del plugin rsvp-boda (rsvp_boda_get_option()), nunca hay
 * texto ni datos fijos aquí para que la pareja no tenga que tocar código.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php get_template_part( 'template-parts/section', 'portada' ); ?>
<?php if ( boda_theme_hay_historia() ) : ?>
	<?php get_template_part( 'template-parts/section', 'historia' ); ?>
<?php endif; ?>
<?php get_template_part( 'template-parts/section', 'ceremonia' ); ?>
<?php if ( '1' === rsvp_boda_get_option( 'bus_activado', '0' ) ) : ?>
	<?php get_template_part( 'template-parts/section', 'transporte' ); ?>
<?php endif; ?>
<?php if ( boda_theme_hay_alojamiento() ) : ?>
	<?php get_template_part( 'template-parts/section', 'alojamiento' ); ?>
<?php endif; ?>
<?php if ( boda_theme_hay_vestimenta() ) : ?>
	<?php get_template_part( 'template-parts/section', 'vestimenta' ); ?>
<?php endif; ?>
<?php if ( '1' === rsvp_boda_get_option( 'regalo_mostrar', '0' ) ) : ?>
	<?php get_template_part( 'template-parts/section', 'regalo' ); ?>
<?php endif; ?>
<?php get_template_part( 'template-parts/section', 'rsvp' ); ?>
<?php get_template_part( 'template-parts/section', 'contacto' ); ?>

<?php get_footer(); ?>
