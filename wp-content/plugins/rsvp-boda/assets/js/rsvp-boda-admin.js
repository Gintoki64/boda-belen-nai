( function ( $ ) {
	'use strict';

	// Selector de imagen única (media uploader nativo de WP).
	$( document ).on( 'click', '.rsvp-boda-elegir-imagen', function ( e ) {
		e.preventDefault();
		const boton = $( this );
		const campo = $( '#' + boton.data( 'target' ) );

		const frame = wp.media( { title: 'Elegir imagen', multiple: false, library: { type: 'image' } } );
		frame.on( 'select', function () {
			const adjunto = frame.state().get( 'selection' ).first().toJSON();
			campo.val( adjunto.id );
			boton.siblings( '.rsvp-boda-campo-imagen__preview' ).html(
				'<img src="' + adjunto.url + '" style="max-width:200px;height:auto;display:block;" />'
			);
		} );
		frame.open();
	} );

	// Selector de galería (varias imágenes, guarda IDs separados por coma).
	$( document ).on( 'click', '.rsvp-boda-elegir-galeria', function ( e ) {
		e.preventDefault();
		const boton = $( this );
		const campo = $( '#' + boton.data( 'target' ) );

		const frame = wp.media( { title: 'Elegir imágenes', multiple: true, library: { type: 'image' } } );
		frame.on( 'select', function () {
			const seleccion = frame.state().get( 'selection' ).toJSON();
			campo.val( seleccion.map( ( a ) => a.id ).join( ',' ) );
			const html = seleccion
				.map( ( a ) => '<img src="' + a.sizes.thumbnail.url + '" style="width:80px;height:80px;object-fit:cover;margin-right:4px;" />' )
				.join( '' );
			boton.siblings( '.rsvp-boda-campo-galeria__preview' ).html( html );
		} );
		frame.open();
	} );

	// Copiar al portapapeles (enlaces individuales y bloques de texto para WhatsApp).
	$( document ).on( 'click', '.rsvp-boda-copiar', function () {
		const boton   = $( this );
		const destino = document.getElementById( boton.data( 'copiar' ) );
		if ( ! destino ) {
			return;
		}

		const texto = 'value' in destino ? destino.value : destino.textContent;

		navigator.clipboard.writeText( texto ).then( function () {
			const original = boton.text();
			boton.text( '¡Copiado!' );
			setTimeout( () => boton.text( original ), 1500 );
		} );
	} );
} )( jQuery );
