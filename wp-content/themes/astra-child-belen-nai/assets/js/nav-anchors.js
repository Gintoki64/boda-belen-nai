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

		// Botón "Copiar IBAN" de la sección de regalo.
		document.querySelectorAll( '[data-copiar-iban]' ).forEach( function ( boton ) {
			boton.addEventListener( 'click', function () {
				const destino = document.getElementById( boton.dataset.copiarIban );
				if ( ! destino ) {
					return;
				}
				navigator.clipboard.writeText( destino.textContent.trim() ).then( function () {
					const original = boton.textContent;
					boton.textContent = '¡Copiado!';
					setTimeout( () => ( boton.textContent = original ), 1500 );
				} );
			} );
		} );
	} );
} )();
