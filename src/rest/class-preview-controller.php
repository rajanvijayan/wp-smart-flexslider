<?php
/**
 * Live preview for the builder canvas.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Rest;

use WP_REST_Request;
use WP_REST_Server;
use SlideCraft\Render\Renderer;
use SlideCraft\Sliders\Slider;
use SlideCraft\Sliders\Slider_Schema;

/**
 * POST /preview renders an unsaved configuration with the real front-end renderer.
 */
final class Preview_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'render' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Render HTML for a configuration.
	 *
	 * @param WP_REST_Request $request Request with config and title.
	 * @return array
	 */
	public function render( WP_REST_Request $request ) {
		$config = $request['config'];
		$slider = new Slider( (int) $request['id'], (string) $request['title'], Slider_Schema::normalize( is_array( $config ) ? $config : array() ) );
		$html   = Renderer::render( $slider );
		return array(
			'html'    => $html,
			'modules' => Renderer::modules(),
		);
	}
}
