<?php
/**
 * Data for dynamic slides: post types, taxonomies and terms.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Rest;

use WP_REST_Request;
use WP_REST_Server;
use SlideCraft\Render\Dynamic_Source;

/**
 * GET /sources and GET /sources/terms.
 */
final class Sources_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/sources',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/sources/terms',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'terms' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'taxonomy' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'search'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Post types with their public taxonomies.
	 *
	 * @return array
	 */
	public function index() {
		$out = array();
		foreach ( Dynamic_Source::post_types() as $type ) {
			$object     = get_post_type_object( $type );
			$taxonomies = array();
			foreach ( get_object_taxonomies( $type, 'objects' ) as $taxonomy ) {
				if ( $taxonomy->public && $taxonomy->show_ui ) {
					$taxonomies[] = array(
						'slug'  => $taxonomy->name,
						'label' => $taxonomy->labels->name,
					);
				}
			}
			$out[] = array(
				'slug'       => $type,
				'label'      => $object->labels->name,
				'taxonomies' => $taxonomies,
			);
		}
		return array(
			'post_types'  => $out,
			'woocommerce' => class_exists( 'WooCommerce' ),
		);
	}

	/**
	 * Terms of a public taxonomy.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array
	 */
	public function terms( WP_REST_Request $request ) {
		$taxonomy = get_taxonomy( (string) $request['taxonomy'] );
		if ( ! $taxonomy || ! $taxonomy->public ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => false,
				'number'     => 100,
				'search'     => (string) $request['search'],
			)
		);
		$out   = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$out[] = array(
				'id'    => $term->term_id,
				'name'  => $term->name,
				'count' => $term->count,
			);
		}
		return $out;
	}
}
