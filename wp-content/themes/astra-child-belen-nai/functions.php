<?php
/**
 * Funciones del child theme. Todo el contenido editable vive en el plugin rsvp-boda;
 * este archivo solo se ocupa de encolar assets y dar soporte de theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BODA_THEME_VERSION', '1.0.0' );
define( 'BODA_THEME_DIR', get_stylesheet_directory() );
define( 'BODA_THEME_URL', get_stylesheet_directory_uri() );

add_action( 'after_setup_theme', 'boda_theme_supports' );
function boda_theme_supports(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
}

/**
 * Versión de un asset a partir de su fecha de modificación, para que un simple
 * cambio en el CSS/JS fuerce cache-busting real (en el navegador y en cualquier
 * caché de Hostinger) sin tener que acordarse de subir BODA_THEME_VERSION a mano.
 */
function boda_theme_asset_version( string $ruta_relativa ): string {
	$ruta_absoluta = BODA_THEME_DIR . $ruta_relativa;
	return file_exists( $ruta_absoluta ) ? (string) filemtime( $ruta_absoluta ) : BODA_THEME_VERSION;
}

add_action( 'wp_enqueue_scripts', 'boda_theme_enqueue_assets' );
function boda_theme_enqueue_assets(): void {
	// Nota: el style.css de la raíz de Astra va vacío a propósito (Astra carga su CSS real
	// por su cuenta desde assets/css/minified/), así que no hace falta encolarlo aquí.

	// Fuentes de pago de Belén vía Adobe Fonts (Typekit): no se pueden auto-alojar por licencia,
	// hay que cargarlas siempre desde el CDN de Adobe. El dominio bodabelennai.es tiene que estar
	// autorizado en el kit desde la cuenta de Adobe de Belén o el navegador no las aplica.
	wp_enqueue_style( 'boda-adobe-fonts', 'https://use.typekit.net/gff0pqi.css', array(), null );

	wp_enqueue_style( 'boda-variables', BODA_THEME_URL . '/assets/css/variables.css', array(), boda_theme_asset_version( '/assets/css/variables.css' ) );
	wp_enqueue_style(
		'boda-main',
		BODA_THEME_URL . '/assets/css/main.css',
		array( 'boda-variables' ),
		boda_theme_asset_version( '/assets/css/main.css' )
	);

	// Antes esto estaba condicionado a is_page_template('page-boda.php'), pero esa comprobación
	// no se cumplía de forma fiable en la portada estática (Ajustes → Lectura), así que la cuenta
	// atrás y el menú móvil no se cargaban ahí. Los dos scripts ya comprueban por su cuenta si sus
	// elementos existen en la página, así que cargarlos siempre es seguro y evita depender de esa
	// comprobación.
	wp_enqueue_script( 'boda-countdown', BODA_THEME_URL . '/assets/js/countdown.js', array(), boda_theme_asset_version( '/assets/js/countdown.js' ), true );
	wp_enqueue_script( 'boda-nav-anchors', BODA_THEME_URL . '/assets/js/nav-anchors.js', array(), boda_theme_asset_version( '/assets/js/nav-anchors.js' ), true );
}

/**
 * Comprobaciones de contenido compartidas entre header.php (menú) y las template-parts
 * correspondientes, para que un ancla del menú nunca apunte a una sección que no se pinta.
 */
function boda_theme_hay_historia(): bool {
	$texto    = rsvp_boda_get_option( 'historia_texto' );
	$imagenes = (array) rsvp_boda_get_option( 'historia_imagenes', array() );
	return '' !== trim( wp_strip_all_tags( $texto ) ) || ! empty( $imagenes );
}

function boda_theme_hay_alojamiento(): bool {
	return '' !== trim( wp_strip_all_tags( rsvp_boda_get_option( 'alojamiento_html' ) ) );
}

function boda_theme_hay_vestimenta(): bool {
	return '' !== trim( wp_strip_all_tags( rsvp_boda_get_option( 'vestimenta_html' ) ) );
}

/**
 * Fecha "13 de febrero de 2027" usando la función del plugin si existe. Con function_exists para
 * que subir el theme antes que el plugin no tumbe la portada (el 500 del 2026-10-08): en ese caso
 * se muestra la fecha tal cual está guardada.
 */
function boda_theme_fecha_legible( $fecha ): string {
	$fecha = (string) $fecha;
	return function_exists( 'rsvp_boda_fecha_legible' ) ? rsvp_boda_fecha_legible( $fecha ) : $fecha;
}

/**
 * Adelanta la conexión a los dominios de Adobe Fonts antes de que el navegador llegue a la
 * hoja de estilos que los pide, para no perder tiempo de DNS/TLS en el camino crítico.
 */
add_action( 'wp_head', 'boda_theme_preconnect_adobe_fonts', 1 );
function boda_theme_preconnect_adobe_fonts(): void {
	foreach ( array( 'https://use.typekit.net', 'https://p.typekit.net' ) as $origen ) {
		printf( '<link rel="preconnect" href="%s" crossorigin>' . "\n", esc_url( $origen ) );
	}
}

/**
 * Imagen de reserva para Open Graph si no se ha subido ninguna en los ajustes del plugin.
 */
function boda_theme_og_imagen_url(): string {
	$id = (int) rsvp_boda_get_option( 'og_imagen_id', 0 );
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	return BODA_THEME_URL . '/assets/images/og-default.jpg';
}
