<?php
/**
 * Pantalla "Invitados y enlaces": lista de invitados esperados, importación CSV
 * y generador de enlaces personalizados agrupados por si permiten acompañante o no.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$invitados   = RSVP_Boda_Invitados::listar();
$url_boda    = rsvp_boda_get_landing_url();
$con_mas_uno = array_filter( $invitados, static fn( $i ) => (int) $i['permite_acompanantes'] === 1 );
$sin_mas_uno = array_filter( $invitados, static fn( $i ) => (int) $i['permite_acompanantes'] === 0 );

function rsvp_boda_url_invitado( string $slug, string $url_base ): string {
	return add_query_arg( 'i', rawurlencode( $slug ), $url_base );
}
?>
<div class="wrap rsvp-boda-wrap">
	<h1>Invitados y enlaces</h1>

	<h2>Enlaces generales</h2>
	<p>Dos enlaces iguales para todo el mundo, sin tener que dar de alta a nadie: manda cada uno a quien corresponda. Si alguien vuelve a entrar desde el mismo móvil, edita su respuesta en vez de duplicarla.</p>
	<?php $url_con_mas_uno = add_query_arg( 'c', rsvp_boda_clave_enlace_mas_uno(), $url_boda ); ?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">Con acompañante (+1)</th>
			<td>
				<input type="text" readonly id="rsvp-boda-enlace-general-con" class="regular-text" value="<?php echo esc_url( $url_con_mas_uno ); ?>" onclick="this.select()" />
				<button type="button" class="button rsvp-boda-copiar" data-copiar="rsvp-boda-enlace-general-con">Copiar</button>
				<p class="description">El formulario deja añadir acompañantes. La parte final del enlace es una clave: si se cambia a mano, deja de dar el +1.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Generar un enlace +1 nuevo?\n\nEl enlace actual dejará de permitir acompañantes: tendrás que mandar el nuevo a quien aún no haya respondido.\n\nQuien ya respondió con el enlace actual conserva sus acompañantes.');" style="margin-top:0.5em;">
					<input type="hidden" name="action" value="rsvp_boda_regenerar_clave" />
					<?php wp_nonce_field( 'rsvp_boda_regenerar_clave' ); ?>
					<button type="submit" class="button">Generar un enlace +1 nuevo</button>
				</form>
			</td>
		</tr>
		<tr>
			<th scope="row">Sin acompañante</th>
			<td>
				<input type="text" readonly id="rsvp-boda-enlace-general-sin" class="regular-text" value="<?php echo esc_url( $url_boda ); ?>" onclick="this.select()" />
				<button type="button" class="button rsvp-boda-copiar" data-copiar="rsvp-boda-enlace-general-sin">Copiar</button>
				<?php if ( '1' === rsvp_boda_get_option( 'permite_acompanantes_sin_enlace', '0' ) ) : ?>
					<p class="description" style="color:#b32d2e;"><strong>Ojo:</strong> ahora mismo este enlace SÍ deja añadir acompañantes, porque está marcado "Permitir acompañante a quien entra sin enlace personalizado" en Ajustes generales → Formulario RSVP. Desmárcalo para que este sea de verdad el enlace sin +1.</p>
				<?php else : ?>
					<p class="description">Es la dirección normal de la web: el formulario no muestra la opción de acompañantes.</p>
				<?php endif; ?>
			</td>
		</tr>
	</table>

	<h2>Importar lista de invitados</h2>
	<p>Sube un CSV con columnas <code>nombre;slug;grupo;permite_acompanantes</code> (las tres últimas son opcionales; <code>permite_acompanantes</code> acepta "si"/"no"). Si el slug ya existe, se actualiza esa fila en vez de duplicarla.</p>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="rsvp_boda_importar_invitados" />
		<?php wp_nonce_field( 'rsvp_boda_importar_invitados' ); ?>
		<input type="file" name="csv_invitados" accept=".csv" required />
		<?php submit_button( 'Importar CSV', 'secondary', 'submit', false ); ?>
	</form>

	<h2>Añadir invitado suelto</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="rsvp_boda_guardar_invitado" />
		<?php wp_nonce_field( 'rsvp_boda_guardar_invitado' ); ?>
		<input type="text" name="nombre" placeholder="Nombre y apellidos" required class="regular-text" />
		<input type="text" name="grupo" placeholder="Grupo (opcional)" class="regular-text" />
		<label><input type="checkbox" name="permite_acompanantes" value="1" /> Permite acompañante (+1)</label>
		<?php submit_button( 'Añadir', 'secondary', 'submit', false ); ?>
	</form>

	<h2>Invitados y enlaces (<?php echo count( $invitados ); ?>)</h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th>Nombre</th>
				<th>Grupo</th>
				<th>Respondido</th>
				<th>+1</th>
				<th>Enlace</th>
				<th>Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $invitados as $invitado ) : ?>
				<tr>
					<td><?php echo esc_html( $invitado['nombre'] ); ?></td>
					<td><?php echo esc_html( $invitado['grupo'] ); ?></td>
					<td><?php echo $invitado['respuesta_id'] ? '✅ ' . esc_html( 'si' === $invitado['asiste'] ? 'viene' : 'no viene' ) : '—'; ?></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="rsvp_boda_guardar_invitado" />
							<input type="hidden" name="id" value="<?php echo esc_attr( $invitado['id'] ); ?>" />
							<?php wp_nonce_field( 'rsvp_boda_guardar_invitado' ); ?>
							<label>
								<input type="checkbox" name="permite_acompanantes" value="1" onchange="this.form.submit()" <?php checked( 1, (int) $invitado['permite_acompanantes'] ); ?> />
							</label>
						</form>
					</td>
					<td>
						<input type="text" readonly id="enlace-<?php echo esc_attr( $invitado['id'] ); ?>" class="rsvp-boda-enlace-input" value="<?php echo esc_url( rsvp_boda_url_invitado( $invitado['slug'], $url_boda ) ); ?>" onclick="this.select()" />
						<button type="button" class="button rsvp-boda-copiar" data-copiar="enlace-<?php echo esc_attr( $invitado['id'] ); ?>">Copiar</button>
					</td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Eliminar este invitado de la lista?');">
							<input type="hidden" name="action" value="rsvp_boda_eliminar_invitado" />
							<input type="hidden" name="id" value="<?php echo esc_attr( $invitado['id'] ); ?>" />
							<?php wp_nonce_field( 'rsvp_boda_eliminar_invitado' ); ?>
							<button type="submit" class="button-link-delete">Eliminar</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h2>Enlaces listos para pegar en WhatsApp</h2>
	<p>Copia cada bloque y pégalo tal cual en tus listas de difusión: cada línea trae el nombre y su enlace personalizado.</p>

	<div class="rsvp-boda-bloque-enlaces">
		<h3>Con acompañante (+1) — <?php echo count( $con_mas_uno ); ?></h3>
		<textarea readonly rows="8" class="large-text rsvp-boda-textarea-enlaces" id="rsvp-boda-lista-con-mas-uno"><?php
		foreach ( $con_mas_uno as $invitado ) {
			echo esc_html( $invitado['nombre'] . ': ' . rsvp_boda_url_invitado( $invitado['slug'], $url_boda ) ) . "\n";
		}
		?></textarea>
		<button type="button" class="button rsvp-boda-copiar" data-copiar="rsvp-boda-lista-con-mas-uno">Copiar todos</button>
	</div>

	<div class="rsvp-boda-bloque-enlaces">
		<h3>Sin acompañante — <?php echo count( $sin_mas_uno ); ?></h3>
		<textarea readonly rows="8" class="large-text rsvp-boda-textarea-enlaces" id="rsvp-boda-lista-sin-mas-uno"><?php
		foreach ( $sin_mas_uno as $invitado ) {
			echo esc_html( $invitado['nombre'] . ': ' . rsvp_boda_url_invitado( $invitado['slug'], $url_boda ) ) . "\n";
		}
		?></textarea>
		<button type="button" class="button rsvp-boda-copiar" data-copiar="rsvp-boda-lista-sin-mas-uno">Copiar todos</button>
	</div>
</div>
