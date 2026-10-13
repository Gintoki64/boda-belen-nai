/**
 * Pantalla Boda → Mesas: reparto de personas en mesas.
 *
 * Todo el estado vive aquí en memoria y se guarda entero con el botón "Guardar" (AJAX
 * rsvp_boda_mesas_guardar). Tras cada cambio de reparto se vuelve a pintar todo: son pocas
 * decenas de mesas y unos cientos de personas como mucho, así que es más simple que ir
 * parcheando el DOM. Los datos de personas se insertan siempre con .text(), nunca como HTML.
 *
 * Mover gente: arrastrar (jQuery UI sortable entre listas conectadas) o, en móvil, tocar una
 * tarjeta para seleccionarla y después tocar la mesa o «Sin mesa».
 */
( function ( $ ) {
	'use strict';

	var datos = window.rsvpBodaMesas;
	if ( ! datos || ! $( '#rsvp-boda-mesas-rejilla' ).length ) {
		return;
	}

	var MAX_MESAS = datos.limites.mesas;
	var MAX_CAPACIDAD = datos.limites.capacidad;

	var estado = {
		version: parseInt( datos.estado.version, 10 ) || 0,
		comensales_defecto: parseInt( datos.estado.comensales_defecto, 10 ) || 10,
		mesas: datos.estado.mesas || [],
		manuales: datos.estado.manuales || [],
		// PHP manda [] cuando está vacío; aquí necesitamos un objeto clave → mesa.
		asignaciones: Array.isArray( datos.estado.asignaciones ) ? {} : ( datos.estado.asignaciones || {} )
	};

	// Personas que vienen de las confirmaciones (las manuales se recalculan desde estado.manuales).
	var personasConfirmadas = $.grep( datos.personas || [], function ( p ) {
		return 'manual' !== p.tipo;
	} );

	var sucio = false;
	// Cuenta los cambios: si se toca algo mientras se está guardando, al terminar sigue "sin guardar".
	var cambios = 0;
	var guardando = false;
	var seleccionada = null;

	var $rejilla = $( '#rsvp-boda-mesas-rejilla' );
	var $libres = $( '#rsvp-boda-mesas-lista-libres' );
	var $estadoTexto = $( '#rsvp-boda-mesas-estado' );
	var $buscar = $( '#rsvp-boda-mesas-buscar' );

	/* ---------- Utilidades ---------- */

	function nuevoId( prefijo ) {
		return prefijo + Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 6 );
	}

	function normalizar( texto ) {
		texto = String( texto || '' ).toLowerCase();
		if ( texto.normalize ) {
			texto = texto.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
		}
		return texto;
	}

	function limitar( valor, minimo, maximo, porDefecto ) {
		var n = parseInt( valor, 10 );
		if ( isNaN( n ) ) {
			n = porDefecto;
		}
		return Math.max( minimo, Math.min( maximo, n ) );
	}

	function todasLasPersonas() {
		var manuales = $.map( estado.manuales, function ( m ) {
			return {
				clave: 'm' + m.id,
				nombre: m.nombre,
				tipo: 'manual',
				de: '',
				grupo: '',
				nota: m.nota || '',
				menu_infantil: false,
				alergias: ''
			};
		} );
		return personasConfirmadas.concat( manuales );
	}

	function existeMesa( id ) {
		for ( var i = 0; i < estado.mesas.length; i++ ) {
			if ( estado.mesas[ i ].id === id ) {
				return true;
			}
		}
		return false;
	}

	function mesaDe( clave ) {
		var id = estado.asignaciones[ clave ];
		return id && existeMesa( id ) ? id : '';
	}

	function marcarSucio() {
		sucio = true;
		cambios++;
		$estadoTexto.removeClass( 'is-ok is-error' ).addClass( 'is-pendiente' ).text( 'Cambios sin guardar' );
	}

	function mostrarEstado( texto, tipo ) {
		$estadoTexto.removeClass( 'is-ok is-error is-pendiente' ).addClass( 'is-' + tipo ).text( texto );
	}

	/* ---------- Pintado ---------- */

	function tarjeta( persona, enLibres ) {
		var $li = $( '<li class="rsvp-boda-mesas-persona" tabindex="0"></li>' )
			.attr( 'data-clave', persona.clave )
			.attr( 'data-buscar', normalizar( persona.nombre + ' ' + persona.de + ' ' + persona.grupo ) );

		if ( persona.clave === seleccionada ) {
			$li.addClass( 'is-seleccionada' );
		}
		$li.addClass( 'rsvp-boda-mesas-persona--' + persona.tipo );

		$( '<span class="rsvp-boda-mesas-persona__nombre"></span>' ).text( persona.nombre ).appendTo( $li );

		var $insignias = $( '<span class="rsvp-boda-mesas-persona__insignias"></span>' );
		if ( persona.menu_infantil ) {
			$( '<span class="rsvp-boda-mesas-insignia rsvp-boda-mesas-insignia--infantil">Infantil</span>' )
				.attr( 'title', 'En esta confirmación han pedido menú infantil' )
				.appendTo( $insignias );
		}
		if ( persona.alergias ) {
			$( '<span class="rsvp-boda-mesas-insignia rsvp-boda-mesas-insignia--alergias">Alergias</span>' )
				.attr( 'title', persona.alergias )
				.appendTo( $insignias );
		}
		if ( 'manual' === persona.tipo ) {
			$( '<span class="rsvp-boda-mesas-insignia">A mano</span>' )
				.attr( 'title', 'Añadida a mano, no ha confirmado por la web' )
				.appendTo( $insignias );
		}
		if ( $insignias.children().length ) {
			$insignias.appendTo( $li );
		}

		var detalles = [];
		if ( 'acompanante' === persona.tipo && persona.de ) {
			detalles.push( 'acompañante de ' + persona.de );
		}
		if ( persona.grupo ) {
			detalles.push( persona.grupo );
		}
		if ( persona.nota ) {
			detalles.push( persona.nota );
		}
		if ( detalles.length ) {
			$( '<span class="rsvp-boda-mesas-persona__detalle"></span>' ).text( detalles.join( ' · ' ) ).appendTo( $li );
		}

		if ( enLibres && 'manual' === persona.tipo ) {
			$( '<button type="button" class="rsvp-boda-mesas-persona__borrar" aria-label="Quitar">&times;</button>' )
				.attr( 'aria-label', 'Quitar a ' + persona.nombre )
				.attr( 'title', 'Quitar a esta persona' )
				.appendTo( $li );
		}

		return $li;
	}

	function pintar() {
		var personas = todasLasPersonas();
		var porMesa = {};
		var libres = [];
		var plazas = 0;

		$.each( estado.mesas, function ( i, mesa ) {
			porMesa[ mesa.id ] = [];
			plazas += mesa.capacidad;
		} );
		$.each( personas, function ( i, persona ) {
			var mesa = mesaDe( persona.clave );
			if ( mesa ) {
				porMesa[ mesa ].push( persona );
			} else {
				libres.push( persona );
			}
		} );

		if ( seleccionada && ! $.grep( personas, function ( p ) {
			return p.clave === seleccionada;
		} ).length ) {
			seleccionada = null;
		}

		// Sin mesa.
		destruirSortables();
		$libres.empty();
		$.each( libres, function ( i, persona ) {
			$libres.append( tarjeta( persona, true ) );
		} );
		$( '#rsvp-boda-mesas-libres-contador' ).text( libres.length );
		filtrarLibres();

		// Mesas.
		$rejilla.empty();
		if ( ! estado.mesas.length ) {
			$( '<p class="rsvp-boda-mesas-vacio"></p>' )
				.text( 'Todavía no hay mesas. Indica cuántas quieres arriba y pulsa «Aplicar».' )
				.appendTo( $rejilla );
		}
		$.each( estado.mesas, function ( i, mesa ) {
			var ocupados = porMesa[ mesa.id ].length;
			var $mesa = $( '<section class="rsvp-boda-mesas-mesa rsvp-boda-mesas-destino"></section>' ).attr( 'data-mesa', mesa.id );
			var $cabecera = $( '<div class="rsvp-boda-mesas-mesa__cabecera"></div>' ).appendTo( $mesa );

			$( '<input type="text" class="rsvp-boda-mesas-mesa__nombre">' )
				.val( mesa.nombre )
				.attr( { maxlength: datos.limites.nombre, 'aria-label': 'Nombre de la mesa' } )
				.appendTo( $cabecera );

			var $fila = $( '<div class="rsvp-boda-mesas-mesa__fila"></div>' ).appendTo( $cabecera );
			$( '<span class="rsvp-boda-mesas-mesa__contador"></span>' )
				.text( ocupados + ' / ' )
				.toggleClass( 'is-lleno', ocupados > mesa.capacidad )
				.append(
					$( '<input type="number" class="rsvp-boda-mesas-mesa__capacidad" min="1" step="1">' )
						.attr( { max: MAX_CAPACIDAD, 'aria-label': 'Plazas de la mesa', title: 'Plazas de la mesa' } )
						.val( mesa.capacidad )
				)
				.appendTo( $fila );
			$( '<button type="button" class="button-link rsvp-boda-mesas-mesa__vaciar">Vaciar</button>' )
				.prop( 'disabled', 0 === ocupados )
				.appendTo( $fila );

			var $lista = $( '<ul class="rsvp-boda-mesas-lista"></ul>' ).attr( 'data-mesa', mesa.id ).appendTo( $mesa );
			$.each( porMesa[ mesa.id ], function ( j, persona ) {
				$lista.append( tarjeta( persona, false ) );
			} );

			$rejilla.append( $mesa );
		} );

		// Resumen.
		$( '#rsvp-boda-mesas-total' ).text( personas.length );
		$( '#rsvp-boda-mesas-sentadas' ).text( personas.length - libres.length );
		$( '#rsvp-boda-mesas-sin-mesa' ).text( libres.length );
		$( '#rsvp-boda-mesas-plazas' ).text( plazas );
		$( '.rsvp-boda-mesas' ).toggleClass( 'hay-seleccion', !! seleccionada );

		activarSortables();
	}

	function filtrarLibres() {
		var termino = normalizar( String( $buscar.val() ).trim() );
		$libres.children( '.rsvp-boda-mesas-persona' ).each( function () {
			var $li = $( this );
			$li.toggle( '' === termino || -1 !== String( $li.attr( 'data-buscar' ) ).indexOf( termino ) );
		} );
	}

	/* ---------- Arrastrar y soltar ---------- */

	function destruirSortables() {
		$( '.rsvp-boda-mesas-lista.ui-sortable' ).sortable( 'destroy' );
	}

	function activarSortables() {
		if ( ! $.fn.sortable ) {
			return;
		}
		$( '.rsvp-boda-mesas-lista' ).sortable( {
			connectWith: '.rsvp-boda-mesas-lista',
			items: '> .rsvp-boda-mesas-persona',
			cancel: 'button, input',
			placeholder: 'rsvp-boda-mesas-hueco',
			tolerance: 'pointer',
			cursor: 'move',
			stop: function ( evento, ui ) {
				var clave = ui.item.attr( 'data-clave' );
				var destino = String( ui.item.parent().attr( 'data-mesa' ) || '' );
				// Repintar fuera del callback: destruir el sortable dentro de su propio "stop" da guerra.
				window.setTimeout( function () {
					mover( clave, destino );
				}, 0 );
			}
		} );
	}

	function mover( clave, mesaId ) {
		var anterior = mesaDe( clave );
		if ( mesaId && existeMesa( mesaId ) ) {
			estado.asignaciones[ clave ] = mesaId;
		} else {
			mesaId = '';
			delete estado.asignaciones[ clave ];
		}
		seleccionada = null;
		if ( anterior !== mesaId ) {
			marcarSucio();
		}
		pintar();
	}

	/* ---------- Eventos ---------- */

	// Tocar una tarjeta la selecciona (o la deselecciona).
	$( document ).on( 'click', '.rsvp-boda-mesas-persona', function ( evento ) {
		if ( $( evento.target ).closest( 'button' ).length ) {
			return;
		}
		evento.stopPropagation();
		var clave = $( this ).attr( 'data-clave' );
		seleccionada = seleccionada === clave ? null : clave;
		$( '.rsvp-boda-mesas-persona' ).removeClass( 'is-seleccionada' );
		if ( seleccionada ) {
			$( this ).addClass( 'is-seleccionada' );
		}
		$( '.rsvp-boda-mesas' ).toggleClass( 'hay-seleccion', !! seleccionada );
	} );

	$( document ).on( 'keydown', '.rsvp-boda-mesas-persona', function ( evento ) {
		if ( 13 === evento.which || 32 === evento.which ) {
			evento.preventDefault();
			$( this ).trigger( 'click' );
		}
	} );

	// Con alguien seleccionado, tocar una mesa (o «Sin mesa») lo lleva allí.
	$( document ).on( 'click', '.rsvp-boda-mesas-destino', function ( evento ) {
		if ( ! seleccionada || $( evento.target ).closest( 'input, button, .rsvp-boda-mesas-persona' ).length ) {
			return;
		}
		evento.stopPropagation();
		mover( seleccionada, String( $( this ).attr( 'data-mesa' ) || '' ) );
	} );

	$rejilla.on( 'input', '.rsvp-boda-mesas-mesa__nombre', function () {
		var id = $( this ).closest( '.rsvp-boda-mesas-mesa' ).attr( 'data-mesa' );
		var valor = $( this ).val();
		$.each( estado.mesas, function ( i, mesa ) {
			if ( mesa.id === id ) {
				mesa.nombre = valor;
			}
		} );
		marcarSucio();
	} );

	$rejilla.on( 'change', '.rsvp-boda-mesas-mesa__nombre', function () {
		if ( '' === String( $( this ).val() ).trim() ) {
			var indice = $( this ).closest( '.rsvp-boda-mesas-mesa' ).index();
			$( this ).val( 'Mesa ' + ( indice + 1 ) ).trigger( 'input' );
		}
	} );

	$rejilla.on( 'change', '.rsvp-boda-mesas-mesa__capacidad', function () {
		var id = $( this ).closest( '.rsvp-boda-mesas-mesa' ).attr( 'data-mesa' );
		var valor = limitar( $( this ).val(), 1, MAX_CAPACIDAD, estado.comensales_defecto );
		$.each( estado.mesas, function ( i, mesa ) {
			if ( mesa.id === id ) {
				mesa.capacidad = valor;
			}
		} );
		marcarSucio();
		pintar();
	} );

	$rejilla.on( 'click', '.rsvp-boda-mesas-mesa__vaciar', function () {
		var id = $( this ).closest( '.rsvp-boda-mesas-mesa' ).attr( 'data-mesa' );
		$.each( estado.asignaciones, function ( clave, mesa ) {
			if ( mesa === id ) {
				delete estado.asignaciones[ clave ];
			}
		} );
		marcarSucio();
		pintar();
	} );

	$( '#rsvp-boda-mesas-aplicar' ).on( 'click', function () {
		var numero = limitar( $( '#rsvp-boda-mesas-num' ).val(), 0, MAX_MESAS, estado.mesas.length );
		var comensales = limitar( $( '#rsvp-boda-mesas-comensales' ).val(), 1, MAX_CAPACIDAD, estado.comensales_defecto );
		$( '#rsvp-boda-mesas-num' ).val( numero );
		$( '#rsvp-boda-mesas-comensales' ).val( comensales );

		if ( numero < estado.mesas.length ) {
			var quitadas = {};
			$.each( estado.mesas.slice( numero ), function ( i, mesa ) {
				quitadas[ mesa.id ] = true;
			} );
			var afectadas = [];
			$.each( estado.asignaciones, function ( clave, mesa ) {
				if ( quitadas[ mesa ] ) {
					afectadas.push( clave );
				}
			} );
			if ( afectadas.length && ! window.confirm(
				'Vas a quitar ' + ( estado.mesas.length - numero ) + ' mesa(s) en las que hay ' + afectadas.length +
				' persona(s) sentada(s). Volverán a «Sin mesa». ¿Seguir?'
			) ) {
				return;
			}
			$.each( afectadas, function ( i, clave ) {
				delete estado.asignaciones[ clave ];
			} );
			estado.mesas = estado.mesas.slice( 0, numero );
		}

		while ( estado.mesas.length < numero ) {
			estado.mesas.push( {
				id: nuevoId( 't' ),
				nombre: 'Mesa ' + ( estado.mesas.length + 1 ),
				capacidad: comensales
			} );
		}

		estado.comensales_defecto = comensales;
		marcarSucio();
		pintar();
	} );

	$buscar.on( 'input', filtrarLibres );

	$( '#rsvp-boda-mesas-manual-anadir' ).on( 'click', function () {
		var $nombre = $( '#rsvp-boda-mesas-manual-nombre' );
		var $nota = $( '#rsvp-boda-mesas-manual-nota' );
		var nombre = String( $nombre.val() ).trim();
		if ( ! nombre ) {
			$nombre.trigger( 'focus' );
			return;
		}
		estado.manuales.push( {
			id: nuevoId( 'p' ),
			nombre: nombre.slice( 0, datos.limites.nombre ),
			nota: String( $nota.val() ).trim().slice( 0, datos.limites.nota )
		} );
		$nombre.val( '' );
		$nota.val( '' );
		marcarSucio();
		pintar();
		$nombre.trigger( 'focus' );
	} );

	$( '#rsvp-boda-mesas-manual-nombre, #rsvp-boda-mesas-manual-nota' ).on( 'keydown', function ( evento ) {
		if ( 13 === evento.which ) {
			evento.preventDefault();
			$( '#rsvp-boda-mesas-manual-anadir' ).trigger( 'click' );
		}
	} );

	$libres.on( 'click', '.rsvp-boda-mesas-persona__borrar', function ( evento ) {
		evento.stopPropagation();
		var clave = $( this ).closest( '.rsvp-boda-mesas-persona' ).attr( 'data-clave' );
		var nombre = $( this ).closest( '.rsvp-boda-mesas-persona' ).find( '.rsvp-boda-mesas-persona__nombre' ).text();
		if ( ! window.confirm( '¿Quitar a «' + nombre + '» de la lista?' ) ) {
			return;
		}
		estado.manuales = $.grep( estado.manuales, function ( m ) {
			return 'm' + m.id !== clave;
		} );
		delete estado.asignaciones[ clave ];
		marcarSucio();
		pintar();
	} );

	/* ---------- Guardar ---------- */

	function mensajeDeError( respuesta ) {
		if ( respuesta && respuesta.data && respuesta.data.mensaje ) {
			return respuesta.data.mensaje;
		}
		if ( '-1' === String( respuesta ) || -1 === respuesta ) {
			return 'La sesión ha caducado. Recarga la página (tus cambios no guardados se perderán).';
		}
		return 'No se han podido guardar las mesas. Comprueba la conexión e inténtalo de nuevo.';
	}

	function fallo( respuesta ) {
		var mensaje = mensajeDeError( respuesta );
		mostrarEstado( mensaje, 'error' );
		window.alert( mensaje );
	}

	$( '#rsvp-boda-mesas-guardar' ).on( 'click', function () {
		if ( guardando ) {
			return;
		}
		guardando = true;
		var $boton = $( this ).prop( 'disabled', true );
		mostrarEstado( 'Guardando…', 'pendiente' );
		var cambiosEnviados = cambios;

		$.post( datos.ajaxUrl, {
			action: 'rsvp_boda_mesas_guardar',
			nonce: datos.nonce,
			version: estado.version,
			estado: JSON.stringify( {
				comensales_defecto: estado.comensales_defecto,
				mesas: estado.mesas,
				manuales: estado.manuales,
				asignaciones: estado.asignaciones
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

	function lineaImpresion( persona ) {
		var extras = [];
		if ( 'acompanante' === persona.tipo && persona.de ) {
			extras.push( 'acompañante de ' + persona.de );
		}
		if ( persona.nota ) {
			extras.push( persona.nota );
		}
		if ( persona.menu_infantil ) {
			extras.push( 'menú infantil' );
		}
		if ( persona.alergias ) {
			extras.push( 'alergias: ' + persona.alergias );
		}
		var $li = $( '<li></li>' ).text( persona.nombre );
		if ( extras.length ) {
			$( '<span class="rsvp-boda-mesas-impresion__extra"></span>' ).text( ' (' + extras.join( '; ' ) + ')' ).appendTo( $li );
		}
		return $li;
	}

	function prepararImpresion() {
		var $zona = $( '#rsvp-boda-mesas-impresion' ).empty();
		var personas = todasLasPersonas();
		var libres = [];

		$( '<h1></h1>' ).text( 'Mesas' ).appendTo( $zona );

		$.each( estado.mesas, function ( i, mesa ) {
			var sentadas = $.grep( personas, function ( p ) {
				return mesaDe( p.clave ) === mesa.id;
			} );
			var $bloque = $( '<div class="rsvp-boda-mesas-impresion__mesa"></div>' ).appendTo( $zona );
			$( '<h2></h2>' ).text( mesa.nombre + ' (' + sentadas.length + ' / ' + mesa.capacidad + ')' ).appendTo( $bloque );
			var $ol = $( '<ol></ol>' ).appendTo( $bloque );
			$.each( sentadas, function ( j, persona ) {
				$ol.append( lineaImpresion( persona ) );
			} );
		} );

		$.each( personas, function ( i, p ) {
			if ( ! mesaDe( p.clave ) ) {
				libres.push( p );
			}
		} );
		if ( libres.length ) {
			var $bloque = $( '<div class="rsvp-boda-mesas-impresion__mesa"></div>' ).appendTo( $zona );
			$( '<h2></h2>' ).text( 'Sin mesa (' + libres.length + ')' ).appendTo( $bloque );
			var $ul = $( '<ul></ul>' ).appendTo( $bloque );
			$.each( libres, function ( j, persona ) {
				$ul.append( lineaImpresion( persona ) );
			} );
		}
	}

	$( '#rsvp-boda-mesas-imprimir' ).on( 'click', function () {
		prepararImpresion();
		window.print();
	} );
	// También si imprimen con Ctrl+P.
	$( window ).on( 'beforeprint', prepararImpresion );

	/* ---------- Arranque ---------- */

	$( '#rsvp-boda-mesas-num' ).val( estado.mesas.length );
	$( '#rsvp-boda-mesas-comensales' ).val( estado.comensales_defecto );
	pintar();
}( jQuery ) );
