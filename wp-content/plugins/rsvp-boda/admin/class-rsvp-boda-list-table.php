<?php
/**
 * Listado de confirmaciones en el panel de administración.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class RSVP_Boda_List_Table extends WP_List_Table {

	private array $ids_duplicados = array();

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'confirmacion',
				'plural'   => 'confirmaciones',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'cb'               => '<input type="checkbox" />',
			'nombre_completo'  => 'Nombre',
			'asiste'           => 'Asiste',
			'num_acompanantes' => 'Acompañantes',
			'alergias'         => 'Alergias / menú especial',
			'menu_infantil'    => 'Menú infantil',
			'bus'              => 'Bus',
			'cancion'          => 'Canción',
			'email_estado'     => 'Email',
			'telefono'         => 'Teléfono',
			'creado_en'        => 'Fecha',
			'acciones'         => 'Acciones',
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'nombre_completo' => array( 'nombre_completo', false ),
			'creado_en'       => array( 'creado_en', true ),
		);
	}

	protected function get_views(): array {
		$base = remove_query_arg( array( 'vista', 'paged' ) );

		$duplicados = RSVP_Boda_DB::obtener_duplicados();
		$this->ids_duplicados = wp_list_pluck( $duplicados, 'id' );

		$vista_actual = sanitize_key( $_GET['vista'] ?? 'todos' );

		$vistas = array(
			'todos'      => sprintf( '<a href="%s"%s>Todos</a>', esc_url( $base ), 'todos' === $vista_actual ? ' class="current"' : '' ),
			'alergias'   => sprintf(
				'<a href="%s"%s>Con alergias/menú especial</a>',
				esc_url( add_query_arg( 'vista', 'alergias', $base ) ),
				'alergias' === $vista_actual ? ' class="current"' : ''
			),
			'canciones'  => sprintf(
				'<a href="%s"%s>Con canción</a>',
				esc_url( add_query_arg( 'vista', 'canciones', $base ) ),
				'canciones' === $vista_actual ? ' class="current"' : ''
			),
			'duplicados' => sprintf(
				'<a href="%s"%s>Posibles duplicados (%d)</a>',
				esc_url( add_query_arg( 'vista', 'duplicados', $base ) ),
				'duplicados' === $vista_actual ? ' class="current"' : '',
				count( $this->ids_duplicados )
			),
		);

		return $vistas;
	}

	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$asiste_actual = sanitize_key( $_GET['rsvp_asiste'] ?? '' );
		$bus_actual    = sanitize_key( $_GET['rsvp_bus'] ?? '' );
		?>
		<div class="alignleft actions">
			<select name="rsvp_asiste">
				<option value="">Asiste: todos</option>
				<option value="si" <?php selected( $asiste_actual, 'si' ); ?>>Sí</option>
				<option value="no" <?php selected( $asiste_actual, 'no' ); ?>>No</option>
			</select>
			<select name="rsvp_bus">
				<option value="">Bus: todos</option>
				<option value="si" <?php selected( $bus_actual, 'si' ); ?>>Necesita bus</option>
				<option value="no" <?php selected( $bus_actual, 'no' ); ?>>Sin bus</option>
			</select>
			<?php submit_button( 'Filtrar', '', 'filtrar', false ); ?>
		</div>
		<?php
	}

	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="ids[]" value="%d" />', $item['id'] );
	}

	public function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] ?? '' );
	}

	public function column_nombre_completo( array $item ): string {
		$resaltado = in_array( (int) $item['id'], $this->ids_duplicados, true )
			? ' <span class="rsvp-boda-badge rsvp-boda-badge--aviso" title="Hay más de una respuesta con este mismo nombre normalizado">posible duplicado</span>'
			: '';

		return '<strong>' . esc_html( $item['nombre_completo'] ) . '</strong>' . $resaltado;
	}

	public function column_asiste( array $item ): string {
		$clase = 'si' === $item['asiste'] ? 'rsvp-boda-badge--si' : 'rsvp-boda-badge--no';
		$texto = 'si' === $item['asiste'] ? 'Sí viene' : 'No viene';
		return sprintf( '<span class="rsvp-boda-badge %s">%s</span>', esc_attr( $clase ), esc_html( $texto ) );
	}

	public function column_alergias( array $item ): string {
		$texto = trim( (string) $item['alergias'] );
		if ( '' === $texto ) {
			return '&mdash;';
		}
		$corto = mb_strlen( $texto ) > 40 ? mb_substr( $texto, 0, 40 ) . '…' : $texto;
		return sprintf( '<span title="%s">%s</span>', esc_attr( $texto ), esc_html( $corto ) );
	}

	public function column_cancion( array $item ): string {
		$texto = trim( (string) $item['cancion'] );
		if ( '' === $texto ) {
			return '&mdash;';
		}
		$corto = mb_strlen( $texto ) > 40 ? mb_substr( $texto, 0, 40 ) . '…' : $texto;
		return sprintf( '<span title="%s">%s</span>', esc_attr( $texto ), esc_html( $corto ) );
	}

	public function column_bus( array $item ): string {
		if ( 'si' !== $item['necesita_bus'] ) {
			return '&mdash;';
		}
		return esc_html( $item['parada_bus'] ?: 'Sí (sin parada indicada)' );
	}

	public function column_email_estado( array $item ): string {
		if ( ! empty( $item['email_error'] ) ) {
			return sprintf(
				'<span class="rsvp-boda-badge rsvp-boda-badge--aviso" title="%s">Fallo de envío</span>',
				esc_attr( $item['email_error'] )
			);
		}
		if ( '1' === (string) ( $item['email_enviado'] ?? '' ) ) {
			return '<span class="rsvp-boda-badge rsvp-boda-badge--si">Enviado</span>';
		}
		return empty( $item['email'] ) ? '<span title="El invitado no dejó email">Sin email</span>' : '&mdash;';
	}

	public function column_acciones( array $item ): string {
		$url_borrar = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'rsvp_boda_eliminar_respuesta',
					'id'     => $item['id'],
				),
				admin_url( 'admin-post.php' )
			),
			'rsvp_boda_eliminar_respuesta_' . $item['id']
		);

		return sprintf(
			'<a href="%s" onclick="return confirm(\'¿Eliminar esta confirmación? No se puede deshacer.\');">Eliminar</a>',
			esc_url( $url_borrar )
		);
	}

	public function prepare_items(): void {
		global $wpdb;

		$tabla = rsvp_boda_tabla_respuestas();

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$vista = sanitize_key( $_GET['vista'] ?? 'todos' );
		if ( 'duplicados' === $vista ) {
			// Fuerza el cálculo de $this->ids_duplicados si get_views() no se ha llamado aún en este render.
			$this->ids_duplicados = wp_list_pluck( RSVP_Boda_DB::obtener_duplicados(), 'id' );
		}

		$where  = array( '1=1' );
		$params = array();

		if ( 'alergias' === $vista ) {
			$where[] = "alergias IS NOT NULL AND alergias != ''";
		} elseif ( 'canciones' === $vista ) {
			$where[] = "cancion IS NOT NULL AND cancion != ''";
		} elseif ( 'duplicados' === $vista ) {
			if ( ! $this->ids_duplicados ) {
				$where[] = '1=0';
			} else {
				$placeholders = implode( ',', array_fill( 0, count( $this->ids_duplicados ), '%d' ) );
				$where[]      = "id IN ({$placeholders})";
				array_push( $params, ...$this->ids_duplicados );
			}
		}

		$asiste_filtro = sanitize_key( $_GET['rsvp_asiste'] ?? '' );
		if ( in_array( $asiste_filtro, array( 'si', 'no' ), true ) ) {
			$where[]  = 'asiste = %s';
			$params[] = $asiste_filtro;
		}

		$bus_filtro = sanitize_key( $_GET['rsvp_bus'] ?? '' );
		if ( in_array( $bus_filtro, array( 'si', 'no' ), true ) ) {
			$where[]  = 'necesita_bus = %s';
			$params[] = $bus_filtro;
		}

		$busqueda = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		if ( '' !== $busqueda ) {
			$where[]  = '(nombre_completo LIKE %s OR nombre_normalizado LIKE %s)';
			$like     = '%' . $wpdb->esc_like( rsvp_boda_normalizar_nombre( $busqueda ) ) . '%';
			$params[] = '%' . $wpdb->esc_like( $busqueda ) . '%';
			$params[] = $like;
		}

		$orderby = in_array( $_GET['orderby'] ?? '', array( 'nombre_completo', 'creado_en' ), true ) ? $_GET['orderby'] : 'creado_en';
		$order   = 'asc' === strtolower( $_GET['order'] ?? '' ) ? 'ASC' : 'DESC';

		$por_pagina = 20;
		$pagina_actual = $this->get_pagenum();

		$where_sql = implode( ' AND ', $where );

		$total_sql = "SELECT COUNT(*) FROM {$tabla} WHERE {$where_sql}";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $where_sql se compone solo de fragmentos fijos definidos arriba.
		$total = $params ? (int) $wpdb->get_var( $wpdb->prepare( $total_sql, $params ) ) : (int) $wpdb->get_var( $total_sql );

		$sql = "SELECT * FROM {$tabla} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params_pagina = array_merge( $params, array( $por_pagina, ( $pagina_actual - 1 ) * $por_pagina ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- misma composición fija de columnas/orden anterior.
		$this->items = $wpdb->get_results( $wpdb->prepare( $sql, $params_pagina ), ARRAY_A );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $por_pagina,
				'total_pages' => (int) ceil( $total / $por_pagina ),
			)
		);
	}
}
