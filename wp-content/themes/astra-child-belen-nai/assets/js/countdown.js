( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const contenedor = document.getElementById( 'boda-countdown' );
		if ( ! contenedor || ! contenedor.dataset.fecha ) {
			return;
		}

		const objetivo = new Date( contenedor.dataset.fecha ).getTime();
		if ( isNaN( objetivo ) ) {
			contenedor.hidden = true;
			return;
		}

		const spanDias    = contenedor.querySelector( '[data-countdown="dias"]' );
		const spanHoras   = contenedor.querySelector( '[data-countdown="horas"]' );
		const spanMinutos = contenedor.querySelector( '[data-countdown="minutos"]' );
		const spanSegundos = contenedor.querySelector( '[data-countdown="segundos"]' );

		// Declarado antes de la primera llamada: si la fecha ya ha pasado, actualizar() hace
		// clearInterval() en esa primera llamada y con `const` más abajo daba ReferenceError.
		let intervalo = null;

		function actualizar() {
			const restante = objetivo - Date.now();

			if ( restante <= 0 ) {
				contenedor.innerHTML = '<p>¡Ya estamos de boda!</p>';
				clearInterval( intervalo );
				return;
			}

			const segundosTotales = Math.floor( restante / 1000 );
			spanDias.textContent     = Math.floor( segundosTotales / 86400 );
			spanHoras.textContent    = String( Math.floor( ( segundosTotales % 86400 ) / 3600 ) ).padStart( 2, '0' );
			spanMinutos.textContent  = String( Math.floor( ( segundosTotales % 3600 ) / 60 ) ).padStart( 2, '0' );
			spanSegundos.textContent = String( segundosTotales % 60 ).padStart( 2, '0' );
		}

		actualizar();
		if ( objetivo > Date.now() ) {
			intervalo = setInterval( actualizar, 1000 );
		}
	} );
} )();
