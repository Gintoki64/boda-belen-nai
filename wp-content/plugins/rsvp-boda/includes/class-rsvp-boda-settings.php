<?php
/**
 * Ajustes generales del plugin (Settings API), todo el contenido editable de la web vive aquí.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Settings {

	const OPTION_NAME = 'rsvp_boda_settings';
	const GROUP       = 'rsvp_boda_settings_group';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'registrar' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets( string $hoja ): void {
		if ( false === strpos( $hoja, 'rsvp-boda' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'rsvp-boda-admin', RSVP_BODA_PLUGIN_URL . 'assets/css/rsvp-boda-admin.css', array(), (string) filemtime( RSVP_BODA_PLUGIN_DIR . 'assets/css/rsvp-boda-admin.css' ) );
		wp_enqueue_script( 'rsvp-boda-admin', RSVP_BODA_PLUGIN_URL . 'assets/js/rsvp-boda-admin.js', array( 'jquery' ), (string) filemtime( RSVP_BODA_PLUGIN_DIR . 'assets/js/rsvp-boda-admin.js' ), true );
	}

	/**
	 * Definición de todos los campos editables, agrupados por sección.
	 * Formato de cada campo: [clave, etiqueta, tipo, args opcionales].
	 */
	public static function secciones(): array {
		return array(
			'pareja_evento'     => array(
				'titulo' => 'Pareja y evento',
				'campos' => array(
					array( 'nombre_1', 'Nombre 1', 'text' ),
					array( 'nombre_2', 'Nombre 2', 'text' ),
					array( 'fecha_boda', 'Fecha de la boda', 'date' ),
					array( 'hora_ceremonia', 'Hora de la ceremonia', 'time' ),
					array( 'fecha_limite_confirmar', 'Fecha límite para confirmar', 'date' ),
					array( 'telefono_contacto', 'Teléfono de contacto', 'tel' ),
				),
			),
			'ceremonia_banquete' => array(
				'titulo' => 'Ceremonia y banquete',
				'campos' => array(
					array( 'lugar_ceremonia', 'Lugar de la ceremonia', 'text' ),
					array( 'direccion_ceremonia', 'Dirección de la ceremonia', 'text' ),
					array( 'mapa_ceremonia_url', 'URL del mapa embebido (ceremonia)', 'url' ),
					array( 'lugar_banquete', 'Lugar del banquete', 'text' ),
					array( 'direccion_banquete', 'Dirección del banquete', 'text' ),
					array( 'mapa_banquete_url', 'URL del mapa embebido (banquete)', 'url' ),
					array( 'indicaciones_aparcamiento', 'Cómo llegar y aparcamiento', 'textarea' ),
				),
			),
			'historia'          => array(
				'titulo' => 'Nuestra historia',
				'campos' => array(
					array( 'historia_texto', 'Texto', 'wysiwyg' ),
					array( 'historia_imagenes', 'Imágenes (galería)', 'galeria' ),
				),
			),
			'transporte'        => array(
				'titulo' => 'Transporte / bus',
				'campos' => array(
					array( 'bus_activado', 'Hay bus organizado', 'checkbox' ),
					array( 'bus_horario', 'Horario', 'textarea' ),
					array( 'bus_paradas', 'Paradas (una por línea, formato "Parada;Hora")', 'textarea' ),
				),
			),
			'alojamiento'       => array(
				'titulo' => 'Alojamiento recomendado',
				'campos' => array(
					array( 'alojamiento_html', 'Contenido', 'wysiwyg' ),
				),
			),
			'vestimenta'        => array(
				'titulo' => 'Código de vestimenta',
				'campos' => array(
					array( 'vestimenta_html', 'Contenido', 'wysiwyg' ),
				),
			),
			'regalo'            => array(
				'titulo' => 'Lista de regalos',
				'campos' => array(
					array( 'regalo_mostrar', 'Mostrar esta sección', 'checkbox' ),
					array( 'regalo_texto', 'Texto explicativo', 'textarea' ),
					array( 'regalo_iban', 'IBAN', 'text' ),
					array( 'regalo_titular', 'Titular de la cuenta', 'text' ),
				),
			),
			'formulario_rsvp'   => array(
				'titulo' => 'Formulario RSVP',
				'campos' => array(
					array( 'formulario_activado', 'Formulario abierto (desactívalo tras la fecha límite)', 'checkbox' ),
					array(
						'permite_acompanantes_sin_enlace',
						'Permitir acompañante a quien entra sin enlace personalizado',
						'checkbox',
						array( 'ayuda' => 'Déjalo desmarcado si usas los dos enlaces generales de "Invitados y enlaces": la web a secas es el enlace sin +1, y el enlace con +1 ya lo permite por sí solo. Si lo marcas, cualquiera que entre a la web podrá añadir acompañantes.' ),
					),
					array( 'limite_acompanantes', 'Número máximo de acompañantes seleccionable', 'number' ),
				),
			),
			'whatsapp_redes'    => array(
				'titulo' => 'WhatsApp / redes sociales',
				'campos' => array(
					array( 'og_imagen_id', 'Imagen para compartir (1200x630px, menos de 300KB)', 'imagen' ),
					array( 'og_titulo', 'Título al compartir', 'text' ),
					array( 'og_descripcion', 'Descripción al compartir', 'textarea' ),
				),
			),
			'email_smtp'        => array(
				'titulo' => 'Email (SMTP Brevo)',
				'campos' => array(
					array( 'smtp_host', 'Servidor SMTP', 'text' ),
					array( 'smtp_puerto', 'Puerto', 'number' ),
					array( 'smtp_usuario', 'Usuario SMTP', 'text' ),
					array( 'smtp_clave', 'Clave SMTP', 'password', array( 'ayuda' => 'Si es posible, define RSVP_BODA_SMTP_CLAVE como constante en wp-config.php en vez de guardarla aquí, para no dejarla en la base de datos.' ) ),
					array( 'smtp_remitente_nombre', 'Nombre del remitente', 'text' ),
					array( 'smtp_remitente_email', 'Email del remitente', 'email' ),
					array( 'email_aviso_pareja', 'Email de aviso a la pareja', 'email' ),
					array( 'email_asunto_invitado', 'Asunto del correo al invitado', 'text' ),
					array( 'email_asunto_admin', 'Asunto del correo de aviso', 'text' ),
				),
			),
		);
	}

	public function registrar(): void {
		register_setting(
			self::GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitizar' ),
				'default'           => rsvp_boda_ajustes_por_defecto(),
			)
		);
	}

	/**
	 * Devuelve el tipo declarado de un campo a partir de su clave, buscando en secciones().
	 */
	private function tipo_de_campo( string $clave ): string {
		foreach ( self::secciones() as $seccion ) {
			foreach ( $seccion['campos'] as $campo ) {
				if ( $campo[0] === $clave ) {
					return $campo[2];
				}
			}
		}
		return 'text';
	}

	public function sanitizar( $entrada ): array {
		$actuales   = wp_parse_args( get_option( self::OPTION_NAME, array() ), rsvp_boda_ajustes_por_defecto() );
		$sanitizado = $actuales;

		foreach ( rsvp_boda_ajustes_por_defecto() as $clave => $valor_defecto ) {
			$tipo = $this->tipo_de_campo( $clave );

			if ( 'checkbox' === $tipo ) {
				$sanitizado[ $clave ] = isset( $entrada[ $clave ] ) ? '1' : '0';
				continue;
			}

			if ( ! isset( $entrada[ $clave ] ) ) {
				continue;
			}

			switch ( $tipo ) {
				case 'wysiwyg':
					$sanitizado[ $clave ] = wp_kses_post( $entrada[ $clave ] );
					break;
				case 'textarea':
					$sanitizado[ $clave ] = sanitize_textarea_field( $entrada[ $clave ] );
					break;
				case 'url':
					$sanitizado[ $clave ] = esc_url_raw( $entrada[ $clave ] );
					break;
				case 'email':
					$sanitizado[ $clave ] = sanitize_email( $entrada[ $clave ] );
					break;
				case 'number':
					$sanitizado[ $clave ] = absint( $entrada[ $clave ] );
					break;
				case 'imagen':
					$sanitizado[ $clave ] = absint( $entrada[ $clave ] );
					break;
				case 'galeria':
					$ids                  = array_filter( array_map( 'absint', explode( ',', (string) $entrada[ $clave ] ) ) );
					$sanitizado[ $clave ] = array_values( $ids );
					break;
				default:
					$sanitizado[ $clave ] = sanitize_text_field( $entrada[ $clave ] );
			}
		}

		return $sanitizado;
	}
}
