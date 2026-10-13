<?php
/**
 * Renderizado público del formulario RSVP: shortcode [rsvp_boda_form] y función de plantilla
 * rsvp_boda_render_form() para que el child theme la use directamente desde page-boda.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Shortcode_Form {

	public function __construct() {
		add_shortcode( 'rsvp_boda_form', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets(): void {
		// Versión por fecha de modificación (como en el theme): el CDN de Hostinger guarda los
		// estáticos 7 días, y con una versión fija un cambio en el JS no llegaría a los invitados.
		$version = static function ( string $ruta ): string {
			$absoluta = RSVP_BODA_PLUGIN_DIR . $ruta;
			return file_exists( $absoluta ) ? (string) filemtime( $absoluta ) : RSVP_BODA_VERSION;
		};
		wp_register_script( 'rsvp-boda-form', RSVP_BODA_PLUGIN_URL . 'assets/js/rsvp-boda-form.js', array(), $version( 'assets/js/rsvp-boda-form.js' ), true );
		wp_register_style( 'rsvp-boda-form', RSVP_BODA_PLUGIN_URL . 'assets/css/rsvp-boda-form.css', array(), $version( 'assets/css/rsvp-boda-form.css' ) );
	}

	public function shortcode(): string {
		ob_start();
		rsvp_boda_render_form();
		return (string) ob_get_clean();
	}
}

/**
 * Pinta el formulario RSVP completo. Pensada para llamarse directamente desde una plantilla del theme.
 */
function rsvp_boda_render_form(): void {
	RSVP_Boda_Cache::excluir_respuesta_actual();

	wp_enqueue_script( 'rsvp-boda-form' );
	wp_enqueue_style( 'rsvp-boda-form' );

	$slug     = sanitize_title( wp_unslash( $_GET['i'] ?? '' ) );
	$invitado = $slug ? RSVP_Boda_Invitados::obtener_por_slug( $slug ) : null;
	if ( $slug && ! $invitado ) {
		$slug = ''; // Enlace desconocido o caducado: se trata como acceso directo, sin romper la página.
	}

	$respuesta_previa = null;
	if ( $slug ) {
		$respuesta_previa = RSVP_Boda_DB::obtener_respuesta_por_slug( $slug );
	} else {
		$respuesta_previa = rsvp_boda_respuesta_desde_cookie();
	}

	// Enlace general con +1 (?c=clave): solo cuenta si no hay enlace personal, que manda siempre.
	$clave_acceso = sanitize_key( wp_unslash( $_GET['c'] ?? '' ) );
	if ( $slug || ! rsvp_boda_es_clave_mas_uno( $clave_acceso ) ) {
		$clave_acceso = '';
	}

	// Permiso para una respuesta nueva (sin contar la de la cookie): es el que vale si alguien
	// pulsa "¿No eres …? Enviar una respuesta nueva" en este mismo navegador.
	$permite_acompanantes_nueva = $invitado
		? (bool) $invitado['permite_acompanantes']
		: rsvp_boda_permite_acompanantes_sin_slug( $clave_acceso );

	// Sin enlace personal, quien ya tenía acompañantes guardados (respondió por ?c y ahora entra
	// por la web a secas) los sigue viendo y puede editarlos. Con ?i=slug manda la fila del invitado.
	$permite_acompanantes = $permite_acompanantes_nueva
		|| ( ! $slug && rsvp_boda_respuesta_conserva_acompanantes( $respuesta_previa ) );

	$nombre_prefijado = $invitado['nombre'] ?? '';

	$paradas_bus = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) rsvp_boda_get_option( 'bus_paradas', '' ) ) as $linea ) {
		$linea = trim( $linea );
		if ( '' === $linea ) {
			continue;
		}
		$partes        = explode( ';', $linea );
		$paradas_bus[] = trim( $partes[0] );
	}

	wp_localize_script(
		'rsvp-boda-form',
		'rsvpBodaConfig',
		array(
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( RSVP_Boda_Ajax::NONCE_ACTION ),
			'limiteAcompanantes'  => (int) rsvp_boda_get_option( 'limite_acompanantes', 6 ),
			'permiteAcompanantes' => $permite_acompanantes,
			'permiteAcompanantesNueva' => $permite_acompanantes_nueva,
		)
	);

	include RSVP_BODA_PLUGIN_DIR . 'public/partials/form-rsvp.php';
}

/**
 * Recupera la última respuesta de un invitado sin enlace personalizado, a partir de su cookie de edición.
 */
function rsvp_boda_respuesta_desde_cookie(): ?array {
	$cookie = $_COOKIE[ RSVP_Boda_Ajax::COOKIE_EDICION ] ?? '';
	if ( ! str_contains( $cookie, '.' ) ) {
		return null;
	}

	list( $id, $firma ) = explode( '.', $cookie, 2 );
	$id = absint( $id );

	if ( ! $id || ! hash_equals( hash_hmac( 'sha256', (string) $id, wp_salt( 'auth' ) ), $firma ) ) {
		return null;
	}

	return RSVP_Boda_DB::obtener_respuesta_por_id( $id );
}
