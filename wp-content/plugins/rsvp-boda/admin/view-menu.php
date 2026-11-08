<?php
/**
 * Pantalla "Menú": elegir entre los dos el menú del catering.
 * Aquí solo va el esqueleto; las secciones, los platos y el resumen los pinta
 * assets/js/rsvp-boda-menu.js a partir de la carta y el estado que se le pasan al encolarlo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap rsvp-boda-wrap rsvp-boda-menu">
	<h1 class="wp-heading-inline">Menú</h1>
	<hr class="wp-header-end">

	<noscript>
		<div class="notice notice-error"><p>Esta pantalla necesita JavaScript activado para funcionar.</p></div>
	</noscript>

	<p class="rsvp-boda-menu-ayuda">
		Carta del catering del Convento de la Luz, en el orden en que se sirve. Cada uno marca con su
		<span class="rsvp-boda-menu-ayuda__voto rsvp-boda-menu-ayuda__voto--b">♥ Belén</span> o
		<span class="rsvp-boda-menu-ayuda__voto rsvp-boda-menu-ayuda__voto--n">♥ Nai</span> lo que le gusta, y con
		<strong>Elegir</strong> se deja la decisión final. En primeros y segundos, <strong>Cata</strong> marca los 5 platos que probaréis con el catering. Empezar desde un menú cerrado marca sus platos y pone su precio; luego se cambia lo que queráis.
	</p>

	<div class="rsvp-boda-menu-controles">
		<label>
			Menú base
			<select id="rsvp-boda-menu-base"></select>
		</label>
		<button type="button" class="button" id="rsvp-boda-menu-aplicar-base">Marcar sus platos</button>

		<label>
			Invitados
			<input type="number" id="rsvp-boda-menu-invitados" min="1" max="<?php echo esc_attr( (string) RSVP_Boda_Menu::MAX_INVITADOS ); ?>" step="1" class="small-text" />
		</label>

		<span class="rsvp-boda-mesas-controles__separador" aria-hidden="true"></span>

		<button type="button" class="button button-primary" id="rsvp-boda-menu-guardar">Guardar</button>
		<button type="button" class="button" id="rsvp-boda-menu-imprimir">Imprimir menú</button>
		<button type="button" class="button" id="rsvp-boda-menu-imprimir-cata">Imprimir cata</button>
		<span class="rsvp-boda-mesas-estado" id="rsvp-boda-menu-estado" role="status" aria-live="polite"></span>
	</div>

	<div class="rsvp-boda-menu-filtros" role="group" aria-label="Mostrar">
		<span>Mostrar:</span>
		<button type="button" class="button is-activo" data-filtro="todo" aria-pressed="true">Todo</button>
		<button type="button" class="button" data-filtro="votos" aria-pressed="false">Con algún ♥</button>
		<button type="button" class="button" data-filtro="ambos" aria-pressed="false">Os gusta a los dos</button>
		<button type="button" class="button" data-filtro="elegidos" aria-pressed="false">Elegidos</button>
		<button type="button" class="button" data-filtro="cata" aria-pressed="false">Para la cata</button>
	</div>

	<div class="rsvp-boda-menu-tablero">
		<div class="rsvp-boda-menu-carta" id="rsvp-boda-menu-carta"></div>

		<aside class="rsvp-boda-menu-resumen" aria-label="Vuestra elección">
			<div class="rsvp-boda-menu-cata" id="rsvp-boda-menu-cata"></div>

			<h2>Vuestra elección</h2>
			<div id="rsvp-boda-menu-resumen"></div>

			<h3>Notas para el catering</h3>
			<textarea id="rsvp-boda-menu-notas" rows="4" class="widefat" maxlength="<?php echo esc_attr( (string) RSVP_Boda_Menu::MAX_LARGO_NOTAS ); ?>" placeholder="Ej.: preguntar por opción vegetariana, menú infantil…"></textarea>

			<details class="rsvp-boda-menu-incluye">
				<summary>Qué incluyen todos los menús</summary>
				<ul>
					<li>Cóctel de bienvenida: limonada granizada artesanal.</li>
					<li>Aperitivos durante hora y media.</li>
					<li>Bodega: cerveza, refrescos, tinto Finca Resalso, frizzante y blanco.</li>
					<li>Panes y regañás en la mesa, y café.</li>
					<li>Un camarero por mesa de 10 y minuta impresa.</li>
				</ul>
				<p>Precios + IVA, para un mínimo de 200 invitados. Los detalles se cierran como tarde un mes antes con cita con el catering, y el número final de comensales 5 días antes (es el mínimo que se factura).</p>
			</details>
		</aside>
	</div>

	<div class="rsvp-boda-menu-impresion" id="rsvp-boda-menu-impresion" aria-hidden="true"></div>
</div>
