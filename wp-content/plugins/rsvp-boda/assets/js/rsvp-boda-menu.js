/**
 * Pantalla Boda → Menú: elegir entre los dos el menú del catering.
 *
 * La carta llega fija desde PHP (RSVP_Boda_Menu::catalogo()); el estado (votos ♥ de Belén y de
 * Nai, «Elegido», menú base, invitados y extras) vive aquí en memoria y se guarda entero con el
 * botón "Guardar" (AJAX rsvp_boda_menu_guardar), igual que Mesas. Tras cada cambio se vuelve a
 * pintar todo: son unos 140 platos, así que es más simple que ir parcheando el DOM.
 */
( function ( $ ) {
	'use strict';

	var datos = window.rsvpBodaMenu;
	if ( ! datos || ! $( '#rsvp-boda-menu-carta' ).length ) {
		return;
	}

	var carta = datos.catalogo;
	var APERITIVOS_REFERENCIA = 15;
	var MAX_CATA = datos.limites.cata;
	var SECCIONES_CATA = datos.limites.seccionesCata;

	var estado = {
		version: parseInt( datos.estado.version, 10 ) || 0,
		base: parseInt( datos.estado.base, 10 ) || 0,
		invitados: parseInt( datos.estado.invitados, 10 ) || 200,
		horas: parseInt( datos.estado.horas, 10 ) || 0,
		montaditos: datos.estado.montaditos || { sencillos: 0, especiales: 0 },
		// PHP manda [] cuando está vacío; aquí necesitamos un objeto id → {b, n, e}.
		platos: Array.isArray( datos.estado.platos ) ? {} : ( datos.estado.platos || {} ),
		notas: datos.estado.notas || ''
	};

	var filtro = 'todo';
	var sucio = false;
	var cambios = 0;
	var guardando = false;

	var $carta = $( '#rsvp-boda-menu-carta' );
	var $resumen = $( '#rsvp-boda-menu-resumen' );
	var $estadoTexto = $( '#rsvp-boda-menu-estado' );

	/* ---------- Utilidades ---------- */

	function euros( n ) {
		return ( Math.round( n * 100 ) / 100 ).toLocaleString( 'es-ES', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 } ) + ' €';
	}

	function limitar( valor, minimo, maximo, porDefecto ) {
		var n = parseInt( valor, 10 );
		if ( isNaN( n ) ) {
			n = porDefecto;
		}
		return Math.max( minimo, Math.min( maximo, n ) );
	}

	function marcas( id ) {
		return estado.platos[ id ] || { b: false, n: false, e: false, c: false };
	}

	function fijarMarca( id, clave, valor ) {
		var m = $.extend( {}, marcas( id ) );
		m[ clave ] = valor;
		if ( m.b || m.n || m.e || m.c ) {
			estado.platos[ id ] = m;
		} else {
			delete estado.platos[ id ];
		}
	}

	function menuBase() {
		return $.grep( carta.menus, function ( m ) {
			return m.id === estado.base;
		} )[ 0 ] || null;
	}

	function enBase( plato ) {
		return estado.base > 0 && ( plato.menus || [] ).indexOf( estado.base ) !== -1;
	}

	function elegidos( seccion ) {
		return $.grep( seccion.platos, function ( p ) {
			return marcas( p.id ).e;
		} );
	}

	// Platos marcados para la cata, en el orden de la carta (primeros y luego segundos).
	function platosCata() {
		var lista = [];
		$.each( carta.secciones, function ( _, seccion ) {
			if ( SECCIONES_CATA.indexOf( seccion.id ) !== -1 ) {
				$.each( seccion.platos, function ( _, plato ) {
					if ( marcas( plato.id ).c ) {
						lista.push( { plato: plato, tipo: 'primeros' === seccion.id ? 'primer plato' : 'segundo plato' } );
					}
				} );
			}
		} );
		return lista;
	}

	function seccionPorId( id ) {
		return $.grep( carta.secciones, function ( s ) {
			return s.id === id;
		} )[ 0 ];
	}

	function marcarSucio() {
		sucio = true;
		cambios++;
		mostrarEstado( 'Cambios sin guardar', 'pendiente' );
	}

	function mostrarEstado( texto, tipo ) {
		$estadoTexto.removeClass( 'is-pendiente is-ok is-error' ).addClass( 'is-' + tipo ).text( texto );
	}

	function pasaFiltro( m ) {
		switch ( filtro ) {
			case 'votos':
				return m.b || m.n;
			case 'ambos':
				return m.b && m.n;
			case 'elegidos':
				return m.e;
			case 'cata':
				return m.c;
		}
		return true;
	}

	/* ---------- Precio ---------- */

	function calcularPrecio() {
		var base = menuBase();
		var lineas = [];
		var porPersona = 0;

		if ( base ) {
			lineas.push( [ base.nombre, base.precio ] );
			porPersona += base.precio;
		}

		$.each( carta.secciones, function ( _, seccion ) {
			$.each( seccion.platos, function ( _, plato ) {
				if ( plato.precio && marcas( plato.id ).e && ! enBase( plato ) ) {
					lineas.push( [ plato.nombre.split( /[:(]/ )[ 0 ].trim(), plato.precio ] );
					porPersona += plato.precio;
				}
			} );
		} );

		if ( estado.horas > 0 ) {
			var horas = estado.horas * carta.barra.hora_extra;
			lineas.push( [ estado.horas + ( 1 === estado.horas ? ' hora extra' : ' horas extra' ) + ' de barra', horas ] );
			porPersona += horas;
		}

		var montaditos = 0;
		$.each( carta.barra.montaditos, function ( _, tipo ) {
			montaditos += ( parseInt( estado.montaditos[ tipo.id ], 10 ) || 0 ) * tipo.precio;
		} );

		return {
			lineas: lineas,
			porPersona: porPersona,
			montaditos: montaditos,
			total: porPersona * estado.invitados + montaditos
		};
	}

	/* ---------- Pintar ---------- */

	function pintarPlato( plato, seccion ) {
		var m = marcas( plato.id );
		var $li = $( '<li class="rsvp-boda-menu-plato"></li>' )
			.attr( 'data-id', plato.id )
			.toggleClass( 'is-elegido', m.e )
			.toggleClass( 'is-ambos', m.b && m.n )
			.toggleClass( 'is-cata', !! m.c );

		var $texto = $( '<div class="rsvp-boda-menu-plato__texto"></div>' );
		$texto.append( $( '<span class="rsvp-boda-menu-plato__nombre"></span>' ).text( plato.nombre ) );

		var $etiquetas = $( '<span class="rsvp-boda-menu-plato__etiquetas"></span>' );
		$.each( plato.menus || [], function ( _, n ) {
			$etiquetas.append(
				$( '<span class="rsvp-boda-menu-etiqueta"></span>' )
					.toggleClass( 'is-base', n === estado.base )
					.attr( 'title', 'Viene en el Menú ' + n )
					.text( 'M' + n )
			);
		} );
		if ( plato.precio ) {
			$etiquetas.append(
				$( '<span class="rsvp-boda-menu-etiqueta rsvp-boda-menu-etiqueta--precio"></span>' )
					.text( enBase( plato ) ? 'incluida en el menú' : '+' + euros( plato.precio ) + '/pers.' )
			);
		}
		$texto.append( $etiquetas );
		if ( plato.nota ) {
			$texto.append( $( '<span class="rsvp-boda-menu-plato__nota"></span>' ).text( plato.nota ) );
		}

		var $acciones = $( '<div class="rsvp-boda-menu-plato__acciones"></div>' );
		$acciones.append(
			$( '<button type="button" class="rsvp-boda-menu-voto rsvp-boda-menu-voto--b" data-accion="b"></button>' )
				.attr( 'aria-pressed', m.b ? 'true' : 'false' )
				.attr( 'title', m.b ? 'A Belén le gusta (quitar)' : 'Me gusta (Belén)' )
				.html( '<span aria-hidden="true">♥</span> Belén' ),
			$( '<button type="button" class="rsvp-boda-menu-voto rsvp-boda-menu-voto--n" data-accion="n"></button>' )
				.attr( 'aria-pressed', m.n ? 'true' : 'false' )
				.attr( 'title', m.n ? 'A Nai le gusta (quitar)' : 'Me gusta (Nai)' )
				.html( '<span aria-hidden="true">♥</span> Nai' )
		);
		if ( SECCIONES_CATA.indexOf( seccion.id ) !== -1 ) {
			$acciones.append(
				$( '<button type="button" class="rsvp-boda-menu-voto rsvp-boda-menu-voto--cata" data-accion="c"></button>' )
					.attr( 'aria-pressed', m.c ? 'true' : 'false' )
					.attr( 'title', m.c ? 'Quitar de la cata' : 'Probarlo en la cata' )
					.text( m.c ? '✓ Cata' : 'Cata' )
			);
		}
		$acciones.append(
			$( '<button type="button" class="button rsvp-boda-menu-elegir" data-accion="e"></button>' )
				.toggleClass( 'button-primary', m.e )
				.attr( 'aria-pressed', m.e ? 'true' : 'false' )
				.text( m.e ? '✓ Elegido' : ( 'varios' === seccion.tipo ? 'Elegir' : 'Elegir este' ) )
		);

		return $li.append( $texto, $acciones );
	}

	function etiquetaSeccion( seccion ) {
		var n = elegidos( seccion ).length;
		if ( 'uno' === seccion.tipo ) {
			return n ? [ 'Elegido', 'ok' ] : [ 'Falta elegir', 'falta' ];
		}
		if ( 'uno_opcional' === seccion.tipo ) {
			return n ? [ 'Elegido', 'ok' ] : [ 'Ninguno', 'neutro' ];
		}
		return [ n + ( 1 === n ? ' elegido' : ' elegidos' ), n ? 'ok' : 'neutro' ];
	}

	function pintarExtrasBarra() {
		var $extras = $( '<div class="rsvp-boda-menu-extras"></div>' );
		$extras.append(
			$( '<label></label>' ).append(
				document.createTextNode( 'Horas extra de barra (' + euros( carta.barra.hora_extra ) + '/pers. cada una) ' ),
				$( '<input type="number" min="0" step="1" class="small-text" data-extra="horas" />' ).attr( 'max', datos.limites.horas ).val( estado.horas )
			)
		);
		$.each( carta.barra.montaditos, function ( _, tipo ) {
			$extras.append(
				$( '<label></label>' ).append(
					document.createTextNode( tipo.nombre + ' (' + euros( tipo.precio ) + ' la unidad) ' ),
					$( '<input type="number" min="0" step="10" class="small-text" />' )
						.attr( { 'data-extra': 'montaditos', 'data-tipo': tipo.id, max: datos.limites.montaditos } )
						.val( estado.montaditos[ tipo.id ] || 0 ),
					document.createTextNode( ' unidades' )
				)
			);
		} );
		return $extras;
	}

	function pintarCarta() {
		// Conserva el foco en el mismo botón tras volver a pintar.
		var $foco = $( document.activeElement );
		var focoId = $foco.closest( '.rsvp-boda-menu-plato' ).attr( 'data-id' );
		var focoAccion = $foco.attr( 'data-accion' );

		$carta.empty();

		$.each( carta.secciones, function ( i, seccion ) {
			var $seccion = $( '<section class="rsvp-boda-menu-seccion"></section>' ).attr( 'data-seccion', seccion.id );
			var etiqueta = etiquetaSeccion( seccion );

			$seccion.append(
				$( '<div class="rsvp-boda-menu-seccion__cabecera"></div>' ).append(
					$( '<h2></h2>' ).append(
						$( '<span class="rsvp-boda-menu-seccion__numero"></span>' ).text( ( i + 1 ) + '.' ),
						document.createTextNode( ' ' + seccion.titulo )
					),
					$( '<span class="rsvp-boda-menu-seccion__estado"></span>' ).addClass( 'is-' + etiqueta[ 1 ] ).text( etiqueta[ 0 ] )
				),
				$( '<p class="rsvp-boda-menu-seccion__ayuda"></p>' ).text( seccion.ayuda )
			);

			var $lista = $( '<ul class="rsvp-boda-menu-lista"></ul>' );
			$.each( seccion.platos, function ( _, plato ) {
				if ( pasaFiltro( marcas( plato.id ) ) ) {
					$lista.append( pintarPlato( plato, seccion ) );
				}
			} );
			if ( ! $lista.children().length ) {
				$lista.append( $( '<li class="rsvp-boda-menu-vacio"></li>' ).text( 'Nada con este filtro.' ) );
			}
			$seccion.append( $lista );

			if ( 'barra' === seccion.id ) {
				$seccion.append( pintarExtrasBarra() );
			}

			$carta.append( $seccion );
		} );

		if ( focoId && focoAccion ) {
			$carta.find( '.rsvp-boda-menu-plato[data-id="' + focoId + '"] [data-accion="' + focoAccion + '"]' ).trigger( 'focus' );
		}
	}

	function pintarCata() {
		var lista = platosCata();
		var $cata = $( '#rsvp-boda-menu-cata' ).empty();
		$cata.append(
			$( '<h2></h2>' ).append(
				document.createTextNode( 'Para la cata ' ),
				$( '<span class="rsvp-boda-menu-cata__contador"></span>' )
					.toggleClass( 'is-completo', lista.length === MAX_CATA )
					.text( lista.length + '/' + MAX_CATA )
			)
		);
		if ( ! lista.length ) {
			$cata.append( $( '<p class="rsvp-boda-menu-resumen__vacio"></p>' ).text( 'Marca con «Cata» hasta ' + MAX_CATA + ' primeros o segundos.' ) );
			return;
		}
		var $ol = $( '<ol></ol>' );
		$.each( lista, function ( _, item ) {
			$ol.append(
				$( '<li></li>' ).append(
					document.createTextNode( item.plato.nombre + ' ' ),
					$( '<span class="rsvp-boda-menu-cata__tipo"></span>' ).text( '(' + item.tipo + ')' )
				)
			);
		} );
		$cata.append( $ol );
	}

	function pintarResumen() {
		pintarCata();
		$resumen.empty();
		var base = menuBase();
		var anadidos = 0;
		var quitados = 0;

		$.each( carta.secciones, function ( _, seccion ) {
			var lista = elegidos( seccion );
			$.each( seccion.platos, function ( _, plato ) {
				if ( base && ! plato.precio ) {
					if ( marcas( plato.id ).e && ! enBase( plato ) ) {
						anadidos++;
					} else if ( ! marcas( plato.id ).e && enBase( plato ) ) {
						quitados++;
					}
				}
			} );

			var $bloque = $( '<div class="rsvp-boda-menu-resumen__bloque"></div>' );
			$bloque.append( $( '<h3></h3>' ).text( seccion.titulo ) );

			if ( ! lista.length ) {
				$bloque.append(
					$( '<p class="rsvp-boda-menu-resumen__vacio"></p>' )
						.toggleClass( 'is-falta', 'uno' === seccion.tipo )
						.text( 'uno' === seccion.tipo ? 'Falta elegir' : 'Nada' )
				);
			} else {
				var $ul = $( '<ul></ul>' );
				$.each( lista, function ( _, plato ) {
					$ul.append( $( '<li></li>' ).text( plato.nombre ) );
				} );
				$bloque.append( $ul );
			}
			$resumen.append( $bloque );
		} );

		// Aviso suave del número de aperitivos (los menús cerrados traen unos 15).
		var aperitivos = elegidos( seccionPorId( 'frios' ) ).length + elegidos( seccionPorId( 'calientes' ) ).length;
		var $aperitivos = $( '<p class="rsvp-boda-menu-resumen__aviso"></p>' ).text( 'Aperitivos: ' + aperitivos + ' (los menús cerrados traen unos ' + APERITIVOS_REFERENCIA + ')' );
		$aperitivos.toggleClass( 'is-lejos', aperitivos > 0 && Math.abs( aperitivos - APERITIVOS_REFERENCIA ) > 3 );
		$resumen.find( '.rsvp-boda-menu-resumen__bloque' ).eq( 1 ).after( $aperitivos );

		// Precio.
		var precio = calcularPrecio();
		var $precio = $( '<div class="rsvp-boda-menu-precio"></div>' );
		$precio.append( $( '<h3></h3>' ).text( 'Precio aproximado' ) );
		var $tabla = $( '<table></table>' );
		$.each( precio.lineas, function ( _, linea ) {
			$tabla.append( $( '<tr></tr>' ).append( $( '<td></td>' ).text( linea[ 0 ] ), $( '<td></td>' ).text( euros( linea[ 1 ] ) ) ) );
		} );
		$tabla.append(
			$( '<tr class="is-total"></tr>' ).append( $( '<td></td>' ).text( 'Por persona' ), $( '<td></td>' ).text( euros( precio.porPersona ) ) ),
			$( '<tr></tr>' ).append( $( '<td></td>' ).text( '× ' + estado.invitados + ' invitados' ), $( '<td></td>' ).text( euros( precio.porPersona * estado.invitados ) ) )
		);
		if ( precio.montaditos ) {
			$tabla.append( $( '<tr></tr>' ).append( $( '<td></td>' ).text( 'Montaditos' ), $( '<td></td>' ).text( euros( precio.montaditos ) ) ) );
		}
		$tabla.append( $( '<tr class="is-total"></tr>' ).append( $( '<td></td>' ).text( 'Total (+ IVA)' ), $( '<td></td>' ).text( euros( precio.total ) ) ) );
		$precio.append( $tabla );

		var avisos = [];
		if ( ! base ) {
			avisos.push( 'Sin menú base no hay precio de los platos: elige un menú arriba para tener una referencia.' );
		} else if ( anadidos || quitados ) {
			avisos.push( 'Respecto al ' + base.nombre + ': ' + anadidos + ' plato' + ( 1 === anadidos ? '' : 's' ) + ' cambiado' + ( 1 === anadidos ? '' : 's' ) + ' o añadido' + ( 1 === anadidos ? '' : 's' ) + ' y ' + quitados + ' quitado' + ( 1 === quitados ? '' : 's' ) + '. El precio final lo confirma el catering.' );
		}
		if ( estado.invitados < 200 ) {
			avisos.push( 'Los precios son para un mínimo de 200 invitados.' );
		}
		$.each( avisos, function ( _, aviso ) {
			$precio.append( $( '<p class="rsvp-boda-menu-precio__aviso"></p>' ).text( aviso ) );
		} );
		$resumen.append( $precio );
	}

	function pintar() {
		pintarCarta();
		pintarResumen();
	}

	/* ---------- Controles ---------- */

	var $base = $( '#rsvp-boda-menu-base' );
	$base.append( $( '<option value="0"></option>' ).text( 'Ninguno (a la carta)' ) );
	$.each( carta.menus, function ( _, m ) {
		$base.append( $( '<option></option>' ).val( m.id ).text( m.nombre + ' · ' + euros( m.precio ) + ' + IVA' ) );
	} );
	$base.val( String( estado.base ) );

	function hayElegidosDePlatos() {
		var hay = false;
		$.each( carta.secciones, function ( _, seccion ) {
			if ( 'barra' !== seccion.id && elegidos( seccion ).length ) {
				hay = true;
			}
		} );
		return hay;
	}

	// Marca como elegidos los platos del menú base (los ♥ no se tocan; la barra tampoco).
	function aplicarBase() {
		$.each( carta.secciones, function ( _, seccion ) {
			if ( 'barra' === seccion.id ) {
				return;
			}
			$.each( seccion.platos, function ( _, plato ) {
				fijarMarca( plato.id, 'e', enBase( plato ) );
			} );
		} );
	}

	$base.on( 'change', function () {
		var anterior = estado.base;
		estado.base = parseInt( $base.val(), 10 ) || 0;
		if ( estado.base && ( ! hayElegidosDePlatos() || window.confirm( '¿Marcar los platos del ' + menuBase().nombre + '? Se cambiarán los elegidos actuales (los ♥ se quedan como están).' ) ) ) {
			aplicarBase();
		}
		if ( anterior !== estado.base ) {
			marcarSucio();
		}
		pintar();
	} );

	$( '#rsvp-boda-menu-aplicar-base' ).on( 'click', function () {
		if ( ! estado.base ) {
			window.alert( 'Elige primero un menú base.' );
			return;
		}
		if ( hayElegidosDePlatos() && ! window.confirm( '¿Volver a marcar los platos del ' + menuBase().nombre + '? Se cambiarán los elegidos actuales (los ♥ se quedan como están).' ) ) {
			return;
		}
		aplicarBase();
		marcarSucio();
		pintar();
	} );

	$( '#rsvp-boda-menu-invitados' ).val( estado.invitados ).on( 'change', function () {
		estado.invitados = limitar( $( this ).val(), 1, datos.limites.invitados, estado.invitados );
		$( this ).val( estado.invitados );
		marcarSucio();
		pintarResumen();
	} );

	$( '#rsvp-boda-menu-notas' ).val( estado.notas ).on( 'input', function () {
		estado.notas = $( this ).val();
		marcarSucio();
	} );

	$carta.on( 'change', '[data-extra]', function () {
		var $input = $( this );
		if ( 'horas' === $input.attr( 'data-extra' ) ) {
			estado.horas = limitar( $input.val(), 0, datos.limites.horas, 0 );
			$input.val( estado.horas );
		} else {
			var tipo = $input.attr( 'data-tipo' );
			estado.montaditos[ tipo ] = limitar( $input.val(), 0, datos.limites.montaditos, 0 );
			$input.val( estado.montaditos[ tipo ] );
		}
		marcarSucio();
		pintarResumen();
	} );

	$carta.on( 'click', '[data-accion]', function () {
		var accion = $( this ).attr( 'data-accion' );
		var id = $( this ).closest( '.rsvp-boda-menu-plato' ).attr( 'data-id' );
		var seccion = seccionPorId( $( this ).closest( '.rsvp-boda-menu-seccion' ).attr( 'data-seccion' ) );
		var valor = ! marcas( id )[ accion ];

		if ( 'c' === accion && valor && platosCata().length >= MAX_CATA ) {
			window.alert( 'Ya hay ' + MAX_CATA + ' platos para la cata. Quita uno antes de añadir otro.' );
			return;
		}

		// En las secciones de «uno», elegir un plato desmarca el que estuviera elegido.
		if ( 'e' === accion && valor && 'varios' !== seccion.tipo ) {
			$.each( seccion.platos, function ( _, plato ) {
				fijarMarca( plato.id, 'e', false );
			} );
		}
		fijarMarca( id, accion, valor );
		marcarSucio();
		pintar();
	} );

	$( '.rsvp-boda-menu-filtros' ).on( 'click', '[data-filtro]', function () {
		filtro = $( this ).attr( 'data-filtro' );
		$( '.rsvp-boda-menu-filtros [data-filtro]' ).each( function () {
			var activo = $( this ).attr( 'data-filtro' ) === filtro;
			$( this ).toggleClass( 'is-activo', activo ).attr( 'aria-pressed', activo ? 'true' : 'false' );
		} );
		pintarCarta();
	} );

	/* ---------- Guardar ---------- */

	function mensajeDeError( respuesta ) {
		if ( respuesta && respuesta.data && respuesta.data.mensaje ) {
			return respuesta.data.mensaje;
		}
		if ( '-1' === String( respuesta ) || -1 === respuesta ) {
			return 'La sesión ha caducado. Recarga la página (tus cambios no guardados se perderán).';
		}
		return 'No se ha podido guardar el menú. Comprueba la conexión e inténtalo de nuevo.';
	}

	function fallo( respuesta ) {
		var mensaje = mensajeDeError( respuesta );
		mostrarEstado( mensaje, 'error' );
		window.alert( mensaje );
	}

	$( '#rsvp-boda-menu-guardar' ).on( 'click', function () {
		if ( guardando ) {
			return;
		}
		guardando = true;
		var $boton = $( this ).prop( 'disabled', true );
		mostrarEstado( 'Guardando…', 'pendiente' );
		var cambiosEnviados = cambios;

		$.post( datos.ajaxUrl, {
			action: 'rsvp_boda_menu_guardar',
			nonce: datos.nonce,
			version: estado.version,
			estado: JSON.stringify( {
				base: estado.base,
				invitados: estado.invitados,
				horas: estado.horas,
				montaditos: estado.montaditos,
				platos: estado.platos,
				notas: estado.notas
			} )
		} )
			.done( function ( respuesta ) {
				if ( respuesta && respuesta.success ) {
					estado.version = parseInt( respuesta.data.version, 10 ) || estado.version + 1;
					if ( cambios === cambiosEnviados ) {
						sucio = false;
						mostrarEstado( 'Guardado', 'ok' );
					} else {
						mostrarEstado( 'Cambios sin guardar', 'pendiente' );
					}
				} else {
					fallo( respuesta );
				}
			} )
			.fail( function ( xhr ) {
				fallo( xhr.responseJSON || xhr.responseText );
			} )
			.always( function () {
				guardando = false;
				$boton.prop( 'disabled', false );
			} );
	} );

	$( window ).on( 'beforeunload', function ( evento ) {
		if ( sucio ) {
			evento.preventDefault();
			evento.originalEvent.returnValue = '';
			return '';
		}
	} );

	/* ---------- Imprimir ---------- */

	// Hoja para la cata: los platos marcados, numerados, con hueco para apuntar qué os parece cada uno.
	$( '#rsvp-boda-menu-imprimir-cata' ).on( 'click', function () {
		var lista = platosCata();
		if ( ! lista.length ) {
			window.alert( 'Todavía no hay platos marcados para la cata.' );
			return;
		}
		var $impresion = $( '#rsvp-boda-menu-impresion' ).empty();
		$impresion.append( $( '<h1></h1>' ).text( 'Cata del menú · Belén y Nai' ) );
		var $ol = $( '<ol class="rsvp-boda-menu-impresion__cata"></ol>' );
		$.each( lista, function ( _, item ) {
			$ol.append(
				$( '<li></li>' ).append(
					$( '<strong></strong>' ).text( item.plato.nombre ),
					document.createTextNode( ' (' + item.tipo + ')' )
				)
			);
		} );
		$impresion.append( $ol );
		window.print();
	} );

	$( '#rsvp-boda-menu-imprimir' ).on( 'click', function () {
		// Hoja para pasar al catering: solo lo elegido, sin precios ni menú base.
		var $impresion = $( '#rsvp-boda-menu-impresion' ).empty();
		$impresion.append( $( '<h1></h1>' ).text( 'Menú de la boda de Belén y Nai' ) );

		$.each( carta.secciones, function ( _, seccion ) {
			var lista = elegidos( seccion );
			var $bloque = $( '<div class="rsvp-boda-menu-impresion__bloque"></div>' ).append( $( '<h2></h2>' ).text( seccion.titulo ) );
			if ( lista.length ) {
				var $ul = $( '<ul></ul>' );
				$.each( lista, function ( _, plato ) {
					$ul.append( $( '<li></li>' ).text( plato.nombre ) );
				} );
				$bloque.append( $ul );
			} else {
				$bloque.append( $( '<p></p>' ).text( 'uno' === seccion.tipo ? 'Falta elegir' : '—' ) );
			}
			if ( 'barra' === seccion.id ) {
				if ( estado.horas ) {
					$bloque.append( $( '<p></p>' ).text( 'Horas extra de barra: ' + estado.horas ) );
				}
				$.each( carta.barra.montaditos, function ( _, tipo ) {
					if ( estado.montaditos[ tipo.id ] ) {
						$bloque.append( $( '<p></p>' ).text( tipo.nombre + ': ' + estado.montaditos[ tipo.id ] + ' unidades' ) );
					}
				} );
			}
			$impresion.append( $bloque );
		} );

		if ( estado.notas ) {
			$impresion.append( $( '<h2></h2>' ).text( 'Notas' ), $( '<p class="rsvp-boda-menu-impresion__notas"></p>' ).text( estado.notas ) );
		}
		window.print();
	} );

	pintar();
} )( jQuery );
