<?php
/**
 * Cabecera: metadatos, Open Graph/Twitter Card para la vista previa en WhatsApp, y navegación
 * por anclas mobile-first.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$og_titulo      = rsvp_boda_get_option( 'og_titulo' ) ?: ( rsvp_boda_get_option( 'nombre_1' ) . ' & ' . rsvp_boda_get_option( 'nombre_2' ) );
$og_descripcion = rsvp_boda_get_option( 'og_descripcion' ) ?: 'Confirma tu asistencia a nuestra boda';
$og_imagen      = boda_theme_og_imagen_url();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1f3a6e">

	<meta property="og:type" content="website">
	<meta property="og:title" content="<?php echo esc_attr( $og_titulo ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $og_descripcion ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $og_imagen ); ?>">
	<meta property="og:url" content="<?php echo esc_url( rsvp_boda_get_landing_url() ); ?>">

	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="<?php echo esc_attr( $og_titulo ); ?>">
	<meta name="twitter:description" content="<?php echo esc_attr( $og_descripcion ); ?>">
	<meta name="twitter:image" content="<?php echo esc_url( $og_imagen ); ?>">

	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="boda-skip-link screen-reader-text" href="#contenido-principal">Ir al contenido principal</a>

<?php /* Barra superior con nombres + menú de anclas quitada a petición del usuario (2026-09-08):
         estorbaba en móvil y no hace falta por ahora — todo el CSS/JS asociado
         (.boda-cabecera, .boda-nav, nav-anchors.js) se deja intacto por si se quiere recuperar
         más adelante, solo se quitó el <header> que lo pintaba. */ ?>

<main id="contenido-principal">
