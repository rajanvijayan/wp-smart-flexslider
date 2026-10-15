<?php
/**
 * Shared helpers for SlideCraft tests.
 *
 * @package SlideCraft
 */

use SlideCraft\Render\Renderer;
use SlideCraft\Sliders\Slider_Post_Type;

/**
 * Base test case.
 */
abstract class SlideCraft_TestCase extends WP_UnitTestCase {

	/**
	 * Register the post type and reset per-request render state.
	 */
	public function set_up() {
		parent::set_up();
		if ( ! post_type_exists( Slider_Post_Type::POST_TYPE ) ) {
			Slider_Post_Type::register();
		}
		Renderer::reset();
	}

	/**
	 * Create image attachments.
	 *
	 * @param int $count Number of images.
	 * @return int[]
	 */
	protected function make_images( $count ) {
		$ids = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$ids[] = self::factory()->attachment->create(
				array(
					'post_mime_type' => 'image/jpeg',
					'post_title'     => "Image $i",
					'file'           => "2026/10/image-$i.jpg",
				)
			);
			wp_update_attachment_metadata(
				end( $ids ),
				array(
					'width'  => 1600,
					'height' => 900,
					'file'   => "2026/10/image-$i.jpg",
					'sizes'  => array(),
				)
			);
		}
		return $ids;
	}

	/**
	 * Dispatch a REST request.
	 *
	 * @param string $method Method.
	 * @param string $route  Route below /slidecraft/v1.
	 * @param array  $params Body or query params.
	 * @return WP_REST_Response
	 */
	protected function rest( $method, $route, array $params = array() ) {
		$request = new WP_REST_Request( $method, '/slidecraft/v1' . $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_get_server()->dispatch( $request );
	}
}
