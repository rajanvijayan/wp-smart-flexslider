<?php
/**
 * The slider post type.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Sliders;

/**
 * Sliders are private posts; their settings live in post meta and the builder is the only UI.
 */
final class Slider_Post_Type {

	const POST_TYPE = 'slidecraft_slider';

	/**
	 * Register the post type.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Sliders', 'wp-smart-flexslider' ),
					'singular_name' => __( 'Slider', 'wp-smart-flexslider' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'author' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}
}
