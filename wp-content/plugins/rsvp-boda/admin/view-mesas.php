<?php
/**
 * Pantalla "Mesas": reparto de los invitados que han confirmado en las mesas del banquete.
 * Aquí solo va el esqueleto; las mesas y las tarjetas de personas las pinta
 * assets/js/rsvp-boda-mesas.js a partir del estado que se le pasa al encolarlo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap rsvp-boda-wrap rsvp-boda-mesas">
	<h1 class="wp-heading-inline">Mesas</h1>
	<hr class="wp-header-end">

	<noscript>
		<div class="notice notice-error"><p>Esta pantalla necesita JavaScript activado para funcionar.</p></div>
	</noscript>

	<p class="rsvp-boda-mesas-ayuda">
		Arrastra a cada persona a su mesa. En el móvil: toca una persona para seleccionarla y después toca la mesa (o «Sin mesa»).
		Solo aparecen quienes han confirmado que vienen; si alguien no ha confirmado por la web, añádelo a mano.
	</p>

	<div class="rsvp-boda-contadores rsvp-boda-mesas-resumen">
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero" id="rsvp-boda-mesas-total">0</span>
			<span class="rsvp-boda-contador__etiqueta">Personas</span>
		</div>
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero" id="rsvp-boda-mesas-sentadas">0</span>
			<span class="rsvp-boda-contador__etiqueta">Sentadas</span>
		</div>
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero" id="rsvp-boda-mesas-sin-mesa">0</span>
			<span class="rsvp-boda-contador__etiqueta">Sin mesa</span>
		</div>
		<div class="rsvp-boda-contador">
			<span class="rsvp-boda-contador__numero" id="rsvp-boda-mesas-plazas">0</span>
			<span class="rsvp-boda-contador__etiqueta">Plazas en total</span>
		</div>
	</div>

	<div class="rsvp-boda-mesas-controles">
		<label>
			Número de mesas
			<input type="number" id="rsvp-boda-mesas-num" min="0" max="<?php echo esc_attr( (string) RSVP_Boda_Mesas::MAX_MESAS ); ?>" step="1" class="small-text" />
		</label>
		<label>
			Comensales por mesa
			<input type="number" id="rsvp-boda-mesas-comensales" min="1" max="<?php echo esc_attr( (string) RSVP_Boda_Mesas::MAX_CAPACIDAD ); ?>" step="1" class="small-text" />
		</label>
		<button type="button" class="button" id="rsvp-boda-mesas-aplicar">Aplicar</button>

		<span class="rsvp-boda-mesas-controles__separador" aria-hidden="true"></span>

		<button type="button" class="button button-primary" id="rsvp-boda-mesas-guardar">Guardar</button>
		<button type="button" class="button" id="rsvp-boda-mesas-imprimir">Imprimir</button>
		<span class="rsvp-boda-mesas-estado" id="rsvp-boda-mesas-estado" role="status" aria-live="polite"></span>
	</div>

	<div class="rsvp-boda-mesas-tablero">
		<aside class="rsvp-boda-mesas-libres" aria-label="Personas sin mesa">
			<div class="rsvp-boda-mesas-libres__cabecera rsvp-boda-mesas-destino" data-mesa="">
				<h2>Sin mesa <span class="rsvp-boda-mesas-libres__contador" id="rsvp-boda-mesas-libres-contador">0</span></h2>
			</div>
			<input type="search" id="rsvp-boda-mesas-buscar" class="rsvp-boda-mesas-libres__buscar" placeholder="Buscar por nombre…" aria-label="Buscar por nombre" />
			<ul class="rsvp-boda-mesas-lista rsvp-boda-mesas-destino" id="rsvp-boda-mesas-lista-libres" data-mesa=""></ul>

			<details class="rsvp-boda-mesas-manual">
				<summary>Añadir persona a mano</summary>
				<p>
					<label for="rsvp-boda-mesas-manual-nombre">Nombre</label><br>
					<input type="text" id="rsvp-boda-mesas-manual-nombre" class="widefat" maxlength="<?php echo esc_attr( (string) RSVP_Boda_Mesas::MAX_LARGO_NOMBRE ); ?>" />
				</p>
				<p>
					<label for="rsvp-boda-mesas-manual-nota">Nota (opcional)</label><br>
					<input type="text" id="rsvp-boda-mesas-manual-nota" class="widefat" maxlength="<?php echo esc_attr( (string) RSVP_Boda_Mesas::MAX_LARGO_NOTA ); ?>" placeholder="Ej.: fotógrafo, niño de 3 años…" />
				</p>
				<p><button type="button" class="button" id="rsvp-boda-mesas-manual-anadir">Añadir</button></p>
			</details>
		</aside>

		<div class="rsvp-boda-mesas-rejilla" id="rsvp-boda-mesas-rejilla"></div>
	</div>

	<div class="rsvp-boda-mesas-impresion" id="rsvp-boda-mesas-impresion" aria-hidden="true"></div>
</div>
