<?php
/**
 * Funciones de ayuda compartidas por todo el plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nombre completo de la tabla de respuestas RSVP (con prefijo de WP).
 */
function rsvp_boda_tabla_respuestas(): string {
	global $wpdb;
	return $wpdb->prefix . 'rsvp_boda';
}

/**
 * Nombre completo de la tabla de invitados esperados (con prefijo de WP).
 */
function rsvp_boda_tabla_invitados(): string {
	global $wpdb;
	return $wpdb->prefix . 'rsvp_boda_invitados';
}

/**
 * Ajustes por defecto. Se usan tanto en la activación como de fallback si falta una clave.
 */
function rsvp_boda_ajustes_por_defecto(): array {
	return array(
		'nombre_1'                       => '',
		'nombre_2'                       => '',
		'fecha_boda'                     => '',
		'hora_ceremonia'                 => '',
		'fecha_limite_confirmar'         => '',
		'telefono_contacto'               => '',
		'lugar_ceremonia'                => '',
		'direccion_ceremonia'            => '',
		'mapa_ceremonia_url'             => '',
		'lugar_banquete'                 => '',
		'direccion_banquete'             => '',
		'mapa_banquete_url'              => '',
		'indicaciones_aparcamiento'      => '',
		'historia_texto'                 => '',
		'historia_imagenes'              => array(),
		'bus_activado'                   => '0',
		'bus_horario'                    => '',
		'bus_paradas'                    => '',
		'alojamiento_html'               => '',
		'vestimenta_html'                => '',
		'regalo_mostrar'                 => '0',
		'regalo_texto'                   => '',
		'regalo_iban'                    => '',
		'regalo_titular'                 => '',
		'formulario_activado'            => '1',
		'permite_acompanantes_sin_enlace' => '0',
		'limite_acompanantes'            => '6',
		'og_imagen_id'                   => 0,
		'og_titulo'                      => '',
		'og_descripcion'                 => '',
		'smtp_host'                      => 'smtp-relay.brevo.com',
		'smtp_puerto'                    => '587',
		'smtp_usuario'                   => '',
		'smtp_clave'                     => '',
		'smtp_remitente_nombre'          => '',
		'smtp_remitente_email'           => '',
		'email_aviso_pareja'             => get_option( 'admin_email' ),
		'email_asunto_invitado'          => 'Hemos recibido tu confirmación',
		'email_asunto_admin'             => 'Nueva confirmación de boda',
	);
}

/**
 * Lee un ajuste concreto del plugin, con valor por defecto si no existe.
 */
function rsvp_boda_get_option( string $clave, $default = '' ) {
	static $ajustes = null;

	if ( null === $ajustes ) {
		$ajustes = wp_parse_args( get_option( 'rsvp_boda_settings', array() ), rsvp_boda_ajustes_por_defecto() );
	}

	return $ajustes[ $clave ] ?? $default;
}

/**
 * Normaliza un nombre para comparación de duplicados: minúsculas, sin acentos, espacios colapsados.
 * Dos invitados "María López" y "maria   lopez " deben normalizar igual.
 */
function rsvp_boda_normalizar_nombre( string $nombre ): string {
	$nombre = remove_accents( $nombre );
	$nombre = mb_strtolower( trim( $nombre ) );
	$nombre = preg_replace( '/\s+/', ' ', $nombre );
	return $nombre ?? '';
}

/**
 * Genera un slug único para un invitado a partir de su nombre, evitando colisiones en wp_rsvp_boda_invitados.
 */
function rsvp_boda_generar_slug_unico( string $nombre, int $excluir_id = 0 ): string {
	global $wpdb;

	$tabla     = rsvp_boda_tabla_invitados();
	$base_slug = sanitize_title( $nombre );
	if ( '' === $base_slug ) {
		$base_slug = 'invitado';
	}

	$slug    = $base_slug;
	$sufijo  = 2;

	while ( true ) {
		$sql = "SELECT id FROM {$tabla} WHERE slug = %s";
		$params = array( $slug );
		if ( $excluir_id > 0 ) {
			$sql .= ' AND id != %d';
			$params[] = $excluir_id;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $tabla es un nombre de tabla fijo generado internamente.
		$existente = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );

		if ( ! $existente ) {
			break;
		}

		$slug = $base_slug . '-' . $sufijo;
		++$sufijo;
	}

	return $slug;
}

/**
 * Genera un token de edición aleatorio no adivinable para una respuesta.
 */
function rsvp_boda_generar_token(): string {
	return wp_generate_password( 32, false );
}

/**
 * Hash de la IP del visitante (nunca se guarda en claro), usando una sal fija del propio WordPress.
 */
function rsvp_boda_hash_ip( string $ip ): string {
	return hash( 'sha256', $ip . wp_salt( 'auth' ) );
}

/**
 * Devuelve la URL absoluta de la página de la boda (la que usa la plantilla page-boda.php),
 * o la home si por lo que sea no se encuentra ninguna página con esa plantilla.
 */
function rsvp_boda_get_landing_url(): string {
	static $url = null;

	if ( null !== $url ) {
		return $url;
	}

	$paginas = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => 1,
			'meta_key'       => '_wp_page_template',
			'meta_value'     => 'page-boda.php',
			'fields'         => 'ids',
		)
	);

	$url = $paginas ? get_permalink( $paginas[0] ) : home_url( '/' );

	return $url;
}
