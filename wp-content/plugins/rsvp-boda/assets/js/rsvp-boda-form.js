( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const form = document.getElementById( 'rsvp-boda-form' );
		if ( ! form ) {
			return;
		}

		const config              = window.rsvpBodaConfig || {};
		const bloqueAcompanantes  = document.getElementById( 'rsvp-boda-bloque-acompanantes' );
		const selectNumAcomp      = document.getElementById( 'rsvp-num-acompanantes' );
		const contenedorNombres   = document.getElementById( 'rsvp-boda-nombres-acompanantes' );
		const radiosAsiste        = form.querySelectorAll( 'input[name="asiste"]' );
		const radiosBus           = form.querySelectorAll( 'input[name="necesita_bus"]' );
		const bloqueParada        = document.getElementById( 'rsvp-boda-bloque-parada' );
		const mensajes            = document.getElementById( 'rsvp-boda-mensajes' );

		let acompanantesPrevios = [];
		const scriptPrevios = document.getElementById( 'rsvp-boda-acompanantes-previos' );
		if ( scriptPrevios ) {
			try {
				acompanantesPrevios = JSON.parse( scriptPrevios.textContent );
			} catch ( e ) {
				acompanantesPrevios = [];
			}
		}

		function pintarInputsAcompanantes( cantidad ) {
			contenedorNombres.innerHTML = '';
			for ( let i = 0; i < cantidad; i++ ) {
				const idCampo = 'rsvp-acompanante-' + i;
				const label = document.createElement( 'label' );
				label.textContent = 'Nombre del acompañante ' + ( i + 1 );
				label.setAttribute( 'for', idCampo );
				const input = document.createElement( 'input' );
				input.type = 'text';
				input.id = idCampo;
				input.name = 'acompanantes[]';
				input.required = true;
				input.value = acompanantesPrevios[ i ] || '';
				const p = document.createElement( 'p' );
				p.className = 'rsvp-boda-campo';
				p.appendChild( label );
				p.appendChild( document.createElement( 'br' ) );
				p.appendChild( input );
				contenedorNombres.appendChild( p );
			}
		}

		function actualizarVisibilidadAcompanantes() {
			const asisteSi = form.querySelector( 'input[name="asiste"]:checked' )?.value === 'si';
			const mostrar  = asisteSi && config.permiteAcompanantes;
			bloqueAcompanantes.hidden = ! mostrar;
			if ( ! mostrar ) {
				pintarInputsAcompanantes( 0 );
			}
		}

		if ( selectNumAcomp ) {
			// Ojo: `|| 6` convertía un límite de 0 en 6 (el servidor luego los descartaba en silencio).
			const limiteLeido = parseInt( config.limiteAcompanantes, 10 );
			const limite = Number.isNaN( limiteLeido ) ? 6 : limiteLeido;
			selectNumAcomp.innerHTML = '';
			for ( let i = 0; i <= limite; i++ ) {
				const opt = document.createElement( 'option' );
				opt.value = String( i );
				opt.textContent = i;
				selectNumAcomp.appendChild( opt );
			}
			if ( acompanantesPrevios.length ) {
				selectNumAcomp.value = String( Math.min( acompanantesPrevios.length, limite ) );
				pintarInputsAcompanantes( Math.min( acompanantesPrevios.length, limite ) );
			}
			selectNumAcomp.addEventListener( 'change', function () {
				pintarInputsAcompanantes( parseInt( selectNumAcomp.value, 10 ) || 0 );
			} );
		}

		radiosAsiste.forEach( function ( radio ) {
			radio.addEventListener( 'change', actualizarVisibilidadAcompanantes );
		} );
		actualizarVisibilidadAcompanantes();

		if ( bloqueParada ) {
			radiosBus.forEach( function ( radio ) {
				radio.addEventListener( 'change', function () {
					bloqueParada.hidden = form.querySelector( 'input[name="necesita_bus"]:checked' )?.value !== 'si';
				} );
			} );
		}

		function mostrarMensaje( texto, tipo ) {
			mensajes.textContent = texto;
			mensajes.className = tipo === 'error' ? 'rsvp-boda-mensaje-error' : 'rsvp-boda-mensaje-exito';
			mensajes.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		}

		function enviar() {
			const datos = new FormData( form );
			datos.append( 'action', 'rsvp_boda_enviar' );
			return fetch( config.ajaxUrl, { method: 'POST', body: datos, credentials: 'same-origin' } )
				.then( function ( respuesta ) {
					return respuesta.json();
				} );
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			const boton = form.querySelector( '.rsvp-boda-boton-enviar' );
			boton.disabled = true;
			boton.dataset.textoOriginal = boton.dataset.textoOriginal || boton.textContent;
			boton.textContent = 'Enviando…';

			enviar()
				.then( function ( json ) {
					// Página servida desde caché con un nonce caducado: el servidor manda uno
					// nuevo y se reintenta una sola vez, sin que el invitado note nada.
					if ( ! json.success && json.data && 'nonce_caducado' === json.data.codigo && json.data.nonce ) {
						form.querySelector( 'input[name="nonce"]' ).value = json.data.nonce;
						return enviar();
					}
					return json;
				} )
				.then( function ( json ) {
					if ( json.success ) {
						mostrarMensaje( json.data.mensaje, 'exito' );
					} else {
						mostrarMensaje( ( json.data && json.data.mensaje ) || 'No se ha podido enviar el formulario. Inténtalo de nuevo.', 'error' );
					}
				} )
				.catch( function () {
					mostrarMensaje( 'Ha ocurrido un problema de conexión. Vuelve a intentarlo en unos segundos.', 'error' );
				} )
				.finally( function () {
					boton.disabled = false;
					boton.textContent = boton.dataset.textoOriginal;
				} );
		} );
	} );
} )();
