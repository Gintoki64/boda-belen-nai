( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const boton    = document.querySelector( '.boda-menu-toggle' );
		const nav      = document.getElementById( 'boda-nav-anclas' );
		const cabecera = document.querySelector( '.boda-cabecera' );
		const etiqueta = document.querySelector( '.boda-menu-toggle__etiqueta' );

		/**
		 * El panel del menú móvil empieza justo debajo de la cabecera. Su alto real depende
		 * del tamaño de fuente (zoom de accesibilidad, nombres largos en dos líneas...), así
		 * que se mide en vez de asumir un número fijo en el CSS.
		 */
		if ( cabecera ) {
			const fijarAltoCabecera = function () {
				document.documentElement.style.setProperty( '--boda-alto-cabecera', cabecera.offsetHeight + 'px' );
			};
			fijarAltoCabecera();
			window.addEventListener( 'resize', fijarAltoCabecera );
			if ( window.ResizeObserver ) {
				new ResizeObserver( fijarAltoCabecera ).observe( cabecera );
			}
		}
		// Coincide con el breakpoint de escritorio de main.css (min-width: 782px).
		const esEscritorio = window.matchMedia( '(min-width: 782px)' );

		/**
		 * En móvil, con el menú cerrado, sus enlaces quedan fuera de la pantalla pero seguirían
		 * siendo alcanzables por Tab o por un lector de pantalla sin esto: los marca `inert`
		 * para que no reciban foco mientras están ocultos. En escritorio el menú siempre está
		 * visible, así que nunca debe quedar `inert` ahí.
		 */
		function sincronizarInert( abierto ) {
			if ( ! nav ) {
				return;
			}
			nav.inert = ! esEscritorio.matches && ! abierto;
		}

		if ( boton && nav ) {
			sincronizarInert( false );
			esEscritorio.addEventListener( 'change', function () {
				sincronizarInert( nav.classList.contains( 'is-abierto' ) );
			} );

			boton.addEventListener( 'click', function () {
				const abierto = nav.classList.toggle( 'is-abierto' );
				boton.setAttribute( 'aria-expanded', abierto ? 'true' : 'false' );
				if ( etiqueta ) {
					etiqueta.textContent = abierto ? 'Cerrar menú' : 'Abrir menú';
				}
				sincronizarInert( abierto );
			} );

			// Cierra el menú móvil al elegir una sección.
			nav.querySelectorAll( 'a' ).forEach( function ( enlace ) {
				enlace.addEventListener( 'click', function () {
					nav.classList.remove( 'is-abierto' );
					boton.setAttribute( 'aria-expanded', 'false' );
					if ( etiqueta ) {
						etiqueta.textContent = 'Abrir menú';
					}
					sincronizarInert( false );
				} );
			} );
		}

		// Portada: pulsar el segundo nombre lo cambia por su apodo ("Joaquín" ⇄ "Nai").
		document.querySelectorAll( '[data-cambio-nombre]' ).forEach( function ( boton ) {
			const nombres = boton.querySelectorAll( '.boda-portada__cambio-nombre' );
			if ( nombres.length !== 2 ) {
				return;
			}
			const etiqueta = function ( activo, otro ) {
				return activo.textContent.trim() + ' (pulsa para verlo como ' + otro.textContent.trim() + ')';
			};

			boton.addEventListener( 'click', function () {
				const actual = boton.querySelector( '.is-activo' );
				const siguiente = actual === nombres[ 0 ] ? nombres[ 1 ] : nombres[ 0 ];

				// El ancho pasa suavemente del nombre actual al nuevo, para que el título se recentre.
				boton.style.width = actual.offsetWidth + 'px';
				void boton.offsetWidth;
				boton.style.width = siguiente.offsetWidth + 'px';

				boton.classList.add( 'ha-cambiado' );
				boton.classList.remove( 'pista', 'brilla' );
				void boton.offsetWidth; // reinicia la animación del destello si se pulsa seguido
				boton.classList.add( 'brilla' );

				actual.classList.remove( 'is-activo' );
				actual.setAttribute( 'aria-hidden', 'true' );
				siguiente.classList.add( 'is-activo' );
				siguiente.removeAttribute( 'aria-hidden' );
				boton.setAttribute( 'aria-label', etiqueta( siguiente, actual ) );
			} );

			boton.addEventListener( 'animationend', function ( e ) {
				if ( e.animationName === 'boda-destello' ) {
					boton.classList.remove( 'brilla' );
				}
			} );

			// El tamaño de letra depende del ancho de pantalla: si cambia, se vuelve a medir.
			window.addEventListener( 'resize', function () {
				if ( boton.style.width ) {
					boton.style.width = boton.querySelector( '.is-activo' ).offsetWidth + 'px';
				}
			} );

			// Pista: si en 3,5 s nadie lo ha pulsado, el nombre brilla una vez.
			setTimeout( function () {
				if ( ! boton.classList.contains( 'ha-cambiado' ) ) {
					boton.classList.add( 'pista' );
				}
			}, 3500 );
		} );

		// "Ver número de cuenta": la cuenta está oculta hasta que se pulsa; el botón desaparece al abrirla.
		document.querySelectorAll( '[data-mostrar-cuenta]' ).forEach( function ( boton ) {
			boton.addEventListener( 'click', function () {
				const cuenta = document.getElementById( boton.getAttribute( 'aria-controls' ) );
				if ( ! cuenta ) {
					return;
				}
				cuenta.hidden = false;
				boton.setAttribute( 'aria-expanded', 'true' );
				boton.hidden = true;
				cuenta.querySelector( 'button' )?.focus();
			} );
		} );

		// Botón "Copiar número" de la sección de regalo. Se copia sin espacios (data-iban).
		document.querySelectorAll( '[data-copiar-iban]' ).forEach( function ( boton ) {
			boton.addEventListener( 'click', function () {
				const destino = document.getElementById( boton.dataset.copiarIban );
				if ( ! destino ) {
					return;
				}
				navigator.clipboard.writeText( destino.dataset.iban || destino.textContent.trim() ).then( function () {
					const original = boton.textContent;
					boton.textContent = '¡Copiado!';
					setTimeout( () => ( boton.textContent = original ), 1500 );
				} );
			} );
		} );
	} );
} )();
