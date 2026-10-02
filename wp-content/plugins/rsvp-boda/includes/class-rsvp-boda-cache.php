<?php
/**
 * Exclusión de caché para la página de la boda y el endpoint AJAX del RSVP.
 * Un invitado nunca debe recibir la página cacheada con el saludo de otro invitado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Cache {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'excluir_pagina_boda' ), 99 );
		add_action( 'wp_ajax_rsvp_boda_enviar', array( $this, 'excluir_ajax' ), 1 );
		add_action( 'wp_ajax_nopriv_rsvp_boda_enviar', array( $this, 'excluir_ajax' ), 1 );
	}

	/**
	 * Se decide por la plantilla que WordPress va a usar de verdad, no por is_page_template():
	 * esa comprobación solo mira la plantilla asignada en el editor, y la página "Boda" en
	 * producción no la tiene asignada (se pinta con page-boda.php por coincidir con su slug
	 * "boda"), así que la portada se estaba cacheando con un nonce que acababa caducando y
	 * rompía el envío del formulario (2026-10-02).
	 */
	public function excluir_pagina_boda( string $plantilla ): string {
		if ( 'page-boda.php' === basename( $plantilla ) || is_page_template( 'page-boda.php' ) ) {
			$this->enviar_cabeceras_no_cache();
		}
		return $plantilla;
	}

	/**
	 * Red de seguridad para cualquier otra página que pinte el formulario (p. ej. con el
	 * shortcode): se llama desde rsvp_boda_render_form(). Las cabeceras HTTP ya no se pueden
	 * mandar a esas alturas, pero LiteSpeed decide si cachea al terminar la respuesta.
	 */
	public static function excluir_respuesta_actual(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( has_action( 'litespeed_control_set_nocache' ) ) {
			do_action( 'litespeed_control_set_nocache', 'rsvp-boda: pagina con formulario' );
		}
	}

	public function excluir_ajax(): void {
		$this->enviar_cabeceras_no_cache();
	}

	private function enviar_cabeceras_no_cache(): void {
		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		}

		if ( has_action( 'litespeed_control_set_nocache' ) ) {
			do_action( 'litespeed_control_set_nocache', 'rsvp-boda: pagina personalizada por invitado' );
		}
	}
}
