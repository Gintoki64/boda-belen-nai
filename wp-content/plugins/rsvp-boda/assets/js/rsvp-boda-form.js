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

		// Nombres y alergias ya escritos (o guardados de antes), por posición. Se guardan antes de cada
		// repintado para que cambiar el número de acompañantes o pasar por "No" y volver a "Sí" no los borre.
		// Las respuestas antiguas traen solo el nombre (string) en vez de { nombre, alergias }.
		let datosAcompanantes = acompanantesPrevios.map( function ( item ) {
			return typeof item === 'string'
				? { nombre: item, relacion: '', alergias: '', infantil: false }
				: { nombre: item.nombre || '', relacion: item.relacion || '', alergias: item.alergias || '', infantil: !! item.menu_infantil };
		} );

		function guardarNombresEscritos() {
			contenedorNombres.querySelectorAll( '.rsvp-boda-acompanante' ).forEach( function ( bloque, i ) {
				datosAcompanantes[ i ] = {
					nombre: bloque.querySelector( 'input[name="acompanantes[]"]' ).value,
					relacion: bloque.querySelector( 'select[name="acompanantes_relacion[]"]' ).value,
					alergias: bloque.querySelector( 'input[name="acompanantes_alergias[]"]' ).value,
					infantil: bloque.querySelector( 'input[name="acompanantes_infantil[]"]' ).checked,
				};
			} );
		}

		function crearCampo( id, nombreCampo, etiqueta, valor, obligatorio, maxLength ) {
			const p = document.createElement( 'p' );
			p.className = 'rsvp-boda-campo';
			const label = document.createElement( 'label' );
			label.textContent = etiqueta;
			label.setAttribute( 'for', id );
			const input = document.createElement( 'input' );
			input.type = 'text';
			input.id = id;
			input.name = nombreCampo;
			input.required = obligatorio;
			input.maxLength = maxLength;
			input.value = valor;
			p.appendChild( label );
			p.appendChild( document.createElement( 'br' ) );
			p.appendChild( input );
			return p;
		}

		// Mismas opciones que rsvp_boda_relaciones_acompanante() en helpers.php (el servidor las valida).
		const RELACIONES = [
			[ 'pareja', 'Pareja' ],
			[ 'hijo', 'Hijo/a' ],
			[ 'familiar', 'Otro familiar' ],
			[ 'amigo', 'Amigo/a' ],
			[ 'otro', 'Otro' ],
		];

		function crearSelectRelacion( i, valor ) {
			const p = document.createElement( 'p' );
			p.className = 'rsvp-boda-campo';
			const label = document.createElement( 'label' );
			label.textContent = 'Es tu…';
			label.setAttribute( 'for', 'rsvp-acompanante-relacion-' + i );
			const select = document.createElement( 'select' );
			select.id = 'rsvp-acompanante-relacion-' + i;
			select.name = 'acompanantes_relacion[]';
			select.required = true;
			const vacia = document.createElement( 'option' );
			vacia.value = '';
			vacia.textContent = 'Elige: pareja, hijo/a…';
			select.appendChild( vacia );
			RELACIONES.forEach( function ( relacion ) {
				const opt = document.createElement( 'option' );
				opt.value = relacion[ 0 ];
				opt.textContent = relacion[ 1 ];
				select.appendChild( opt );
			} );
			select.value = valor;
			p.appendChild( label );
			p.appendChild( select );
			return p;
		}

		// El valor es la posición del acompañante: las casillas sin marcar no se envían, así que el
		// servidor no puede emparejarlas por orden como hace con nombres y alergias.
		function crearCasillaInfantil( i, marcada ) {
			const p = document.createElement( 'p' );
			p.className = 'rsvp-boda-campo';
			const label = document.createElement( 'label' );
			const casilla = document.createElement( 'input' );
			casilla.type = 'checkbox';
			casilla.name = 'acompanantes_infantil[]';
			casilla.value = String( i );
			casilla.checked = marcada;
			label.appendChild( casilla );
			label.appendChild( document.createTextNode( ' Menú infantil' ) );
			p.appendChild( label );
			return p;
		}

		function pintarInputsAcompanantes( cantidad ) {
			guardarNombresEscritos();
			contenedorNombres.innerHTML = '';
			for ( let i = 0; i < cantidad; i++ ) {
				const datos = datosAcompanantes[ i ] || { nombre: '', relacion: '', alergias: '', infantil: false };
				const bloque = document.createElement( 'div' );
				bloque.className = 'rsvp-boda-acompanante';
				bloque.appendChild( crearCampo( 'rsvp-acompanante-' + i, 'acompanantes[]', 'Nombre del acompañante ' + ( i + 1 ), datos.nombre, true, 191 ) );
				bloque.appendChild( crearSelectRelacion( i, datos.relacion ) );
				bloque.appendChild( crearCampo( 'rsvp-acompanante-alergias-' + i, 'acompanantes_alergias[]', 'Sus alergias o intolerancias (si tiene)', datos.alergias, false, 500 ) );
				bloque.appendChild( crearCasillaInfantil( i, datos.infantil ) );
				contenedorNombres.appendChild( bloque );
			}
		}

		function cantidadSeleccionada() {
			return selectNumAcomp ? ( parseInt( selectNumAcomp.value, 10 ) || 0 ) : 0;
		}

		function actualizarVisibilidadAcompanantes() {
			const asisteSi = form.querySelector( 'input[name="asiste"]:checked' )?.value === 'si';
			const mostrar  = asisteSi && config.permiteAcompanantes;
			bloqueAcompanantes.hidden = ! mostrar;
			// Al ocultarse se quitan los inputs (para no enviar nombres si no viene); al volver a
			// mostrarse se repintan según lo que marque el select, con los nombres que ya había.
			pintarInputsAcompanantes( mostrar ? cantidadSeleccionada() : 0 );
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
			}
			selectNumAcomp.addEventListener( 'change', function () {
				pintarInputsAcompanantes( cantidadSeleccionada() );
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

		// "¿No eres …? Enviar una respuesta nueva": vacía el formulario precargado desde la cookie
		// y avisa al servidor (nueva=1) de que cree otra respuesta en vez de sobrescribir la anterior.
		// Sin recargar la página, así se conserva tal cual el ?c= de la URL.
		const botonNueva = document.getElementById( 'rsvp-boda-nueva-respuesta' );
		const campoNueva = form.querySelector( 'input[name="nueva"]' );
		if ( botonNueva && campoNueva ) {
			botonNueva.addEventListener( 'click', function () {
				form.querySelectorAll( 'input[type="text"], input[type="tel"], input[type="email"], textarea' ).forEach( function ( campo ) {
					campo.value = '';
				} );
				radiosAsiste.forEach( function ( radio ) {
					radio.checked = false;
				} );
				form.querySelectorAll( 'input[name="necesita_bus"]' ).forEach( function ( radio ) {
					radio.checked = 'no' === radio.value;
				} );
				const selectParada = document.getElementById( 'rsvp-parada-bus' );
				if ( selectParada ) {
					selectParada.value = '';
				}
				if ( bloqueParada ) {
					bloqueParada.hidden = true;
				}

				contenedorNombres.innerHTML = '';
				datosAcompanantes = [];
				if ( selectNumAcomp ) {
					selectNumAcomp.value = '0';
				}
				// El +1 conservado por la respuesta anterior no se hereda: vale el del enlace usado.
				config.permiteAcompanantes = !! config.permiteAcompanantesNueva;
				actualizarVisibilidadAcompanantes();

				campoNueva.value = '1';
				mensajes.textContent = '';
				mensajes.className = '';
				const aviso = document.getElementById( 'rsvp-boda-aviso-previo' );
				if ( aviso ) {
					aviso.hidden = true;
				}
				const campoNombre = document.getElementById( 'rsvp-nombre' );
				if ( campoNombre ) {
					campoNombre.focus();
				}
			} );
		}

		function mostrarMensaje( texto, tipo ) {
			mensajes.textContent = texto;
			mensajes.className = tipo === 'error' ? 'rsvp-boda-mensaje-error' : 'rsvp-boda-mensaje-exito';
			mensajes.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		}

		// Tras un envío correcto se oculta el formulario y se enseña una tarjeta de "enviada": con solo
		// una línea de aviso encima del botón había invitados que creían que no se había mandado.
		function mostrarPantallaEnviada( texto ) {
			mensajes.textContent = '';
			mensajes.className = '';

			let pantalla = document.getElementById( 'rsvp-boda-enviada' );
			if ( ! pantalla ) {
				pantalla = document.createElement( 'div' );
				pantalla.id = 'rsvp-boda-enviada';
				pantalla.className = 'rsvp-boda-enviada';
				pantalla.setAttribute( 'role', 'status' );
				pantalla.tabIndex = -1;

				const icono = document.createElement( 'div' );
				icono.className = 'rsvp-boda-enviada__icono';
				icono.setAttribute( 'aria-hidden', 'true' );
				icono.textContent = '✓';

				const titulo = document.createElement( 'p' );
				titulo.className = 'rsvp-boda-enviada__titulo';
				titulo.textContent = '¡Confirmación enviada!';

				const detalle = document.createElement( 'p' );
				detalle.className = 'rsvp-boda-enviada__mensaje';

				const nota = document.createElement( 'p' );
				nota.className = 'rsvp-boda-enviada__nota';

				const botonModificar = document.createElement( 'button' );
				botonModificar.type = 'button';
				botonModificar.className = 'boda-boton boda-boton--secundario rsvp-boda-enviada__modificar';
				botonModificar.textContent = 'Modificar mi respuesta';
				botonModificar.addEventListener( 'click', function () {
					pantalla.hidden = true;
					form.hidden = false;
					form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				} );

				pantalla.append( icono, titulo, detalle, nota, botonModificar );
				form.parentNode.insertBefore( pantalla, form );
			}

			const email = form.querySelector( 'input[name="email"]' );
			pantalla.querySelector( '.rsvp-boda-enviada__mensaje' ).textContent = texto;
			pantalla.querySelector( '.rsvp-boda-enviada__nota' ).textContent = email && email.value.trim()
				? 'En unos minutos te llegará un correo con tu respuesta. Si necesitas cambiar algo, puedes volver a esta página cuando quieras.'
				: 'Si necesitas cambiar algo, puedes volver a esta página cuando quieras.';

			form.hidden = true;
			const aviso = document.getElementById( 'rsvp-boda-aviso-previo' );
			if ( aviso ) {
				aviso.hidden = true;
			}
			pantalla.hidden = false;
			pantalla.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			pantalla.focus( { preventScroll: true } );
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
						// La respuesta nueva ya existe y la cookie apunta a ella: un segundo envío
						// desde esta misma página debe editarla, no crear otra más.
						if ( campoNueva ) {
							campoNueva.value = '0';
						}
						mostrarPantallaEnviada( json.data.mensaje );
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
