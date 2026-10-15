<?php
/**
 * Shared REST helpers.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Rest;

use WP_Error;
use SlideCraft\Capabilities;
use SlideCraft\Sliders\Slider;
use SlideCraft\Sliders\Slider_Repository;

/**
 * Base class for the slidecraft/v1 controllers.
 */
abstract class Rest_Controller {

	const NS = 'slidecraft/v1';

	/**
	 * Register routes.
	 */
	abstract public function register_routes();

	/**
	 * Permission callback: the user must be able to manage sliders.
	 *
	 * @return bool|WP_Error
	 */
	public function can_manage() {
		if ( Capabilities::current_user_can_manage() ) {
			return true;
		}
		return new WP_Error( 'slidecraft_forbidden', __( 'You are not allowed to manage sliders.', 'wp-smart-flexslider' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Load the slider named in the route or return a 404 error.
	 *
	 * @param \WP_REST_Request $request Request with an id parameter.
	 * @return Slider|WP_Error
	 */
	protected function slider( $request ) {
		$slider = Slider_Repository::find( (int) $request['id'] );
		if ( ! $slider ) {
			return new WP_Error( 'slidecraft_not_found', __( 'Slider not found.', 'wp-smart-flexslider' ), array( 'status' => 404 ) );
		}
		return $slider;
	}

	/**
	 * Route argument for a numeric ID.
	 *
	 * @return array
	 */
	protected function id_arg() {
		return array(
			'id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);
	}
}
