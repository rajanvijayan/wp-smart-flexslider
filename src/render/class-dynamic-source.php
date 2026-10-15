<?php
/**
 * Expands dynamic "posts" slides into one slide per post or product.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Render;

use SlideCraft\Sliders\Slider_Schema;
use WP_Post;
use WP_Query;

/**
 * A "posts" slide is a template: its background overlay and layer style carry over to every generated slide,
 * and its source settings choose the posts. Only published, public, unprotected posts are shown.
 */
final class Dynamic_Source {

	/**
	 * Cache group version, bumped whenever posts change.
	 */
	const CACHE_OPTION = 'slidecraft_source_version';

	/**
	 * Register cache invalidation.
	 */
	public static function register() {
		add_action( 'save_post', array( self::class, 'flush' ) );
		add_action( 'deleted_post', array( self::class, 'flush' ) );
		add_action( 'set_object_terms', array( self::class, 'flush' ) );
	}

	/**
	 * Invalidate cached queries.
	 */
	public static function flush() {
		update_option( self::CACHE_OPTION, (int) get_option( self::CACHE_OPTION, 0 ) + 1, true );
	}

	/**
	 * Replace every "posts" slide by the slides it generates.
	 *
	 * @param array[] $slides Slides.
	 * @return array[]
	 */
	public static function expand( array $slides ) {
		$out = array();
		foreach ( $slides as $slide ) {
			if ( 'posts' !== $slide['type'] ) {
				$out[] = $slide;
				continue;
			}
			foreach ( self::posts( $slide['source'] ) as $post ) {
				$out[] = self::post_slide( $slide, $post );
			}
		}
		return $out;
	}

	/**
	 * Post types a dynamic slide may show: public and viewable.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		$types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' !== $type->name && is_post_type_viewable( $type ) ) {
				$types[] = $type->name;
			}
		}
		return $types;
	}

	/**
	 * Posts for a source.
	 *
	 * @param array $source Source settings.
	 * @return WP_Post[]
	 */
	public static function posts( array $source ) {
		if ( ! in_array( $source['post_type'], self::post_types(), true ) ) {
			return array();
		}
		$args = array(
			'post_type'           => $source['post_type'],
			'post_status'         => 'publish',
			'posts_per_page'      => $source['count'],
			'offset'              => $source['offset'],
			'has_password'        => false,
			'ignore_sticky_posts' => ! $source['sticky_first'],
			'no_found_rows'       => true,
			'order'               => $source['order'],
			'orderby'             => $source['orderby'],
		);

		$is_product = 'product' === $source['post_type'];
		if ( 'price' === $source['orderby'] || 'popularity' === $source['orderby'] ) {
			if ( $is_product ) {
				$args['meta_key'] = 'price' === $source['orderby'] ? '_price' : 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- bounded product query, cached.
				$args['orderby']  = 'meta_value_num';
			} else {
				$args['orderby'] = 'date';
			}
		}

		$tax_query = array();
		if ( '' !== $source['taxonomy'] && $source['terms'] && is_object_in_taxonomy( $source['post_type'], $source['taxonomy'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $source['taxonomy'],
				'field'    => 'term_id',
				'terms'    => $source['terms'],
			);
		}
		if ( $source['featured_only'] ) {
			if ( $is_product && taxonomy_exists( 'product_visibility' ) ) {
				$tax_query[] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'featured' ),
				);
			} else {
				$sticky           = get_option( 'sticky_posts', array() );
				$args['post__in'] = $sticky ? array_map( 'intval', (array) $sticky ) : array( 0 );
			}
		}
		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- bounded query, cached.
		}
		if ( is_singular() ) {
			$args['post__not_in'] = array( (int) get_queried_object_id() ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- excludes only the current post.
		}

		$key = 'slidecraft_q_' . md5( wp_json_encode( $args ) . '|' . get_option( self::CACHE_OPTION, 0 ) );
		$ids = 'rand' === $source['orderby'] ? false : get_transient( $key );
		if ( false === $ids ) {
			$args['fields'] = 'ids';
			$ids            = ( new WP_Query( $args ) )->posts;
			if ( 'rand' !== $source['orderby'] ) {
				set_transient( $key, $ids, 10 * MINUTE_IN_SECONDS );
			}
		}

		$posts = array();
		foreach ( (array) $ids as $id ) {
			$post = get_post( (int) $id );
			if ( $post && 'publish' === $post->post_status && '' === $post->post_password ) {
				$posts[] = $post;
			}
		}
		return $posts;
	}

	/**
	 * Build a content slide for a post from the template slide.
	 *
	 * @param array   $template Template ("posts") slide.
	 * @param WP_Post $post     Post.
	 * @return array
	 */
	public static function post_slide( array $template, WP_Post $post ) {
		$show  = $template['source']['show'];
		$url   = get_permalink( $post );
		$slide = $template;

		$slide['id']    = $template['id'] . '-' . $post->ID;
		$slide['type']  = 'content';
		$slide['title'] = get_the_title( $post );
		$slide['alt']   = $slide['title'];
		if ( 'image' === $template['background']['type'] ) {
			// The featured image, or the template's colour when the post has none.
			$slide['background']['type']     = has_post_thumbnail( $post ) ? 'image' : 'color';
			$slide['background']['image_id'] = (int) get_post_thumbnail_id( $post );
		}
		$slide['link'] = array(
			'url'     => $url,
			'new_tab' => false,
		);

		// The first layer of each type on the template sets the style of the generated layers.
		$style = array();
		foreach ( $template['layers'] as $layer ) {
			if ( ! isset( $style[ $layer['type'] ] ) ) {
				$style[ $layer['type'] ] = $layer;
			}
		}
		$make = static function ( $type, array $data ) use ( $style ) {
			$base = isset( $style[ $type ] ) ? $style[ $type ] : array(
				'position' => array( 'anchor' => 'bottom-left' ),
				'align'    => 'left',
			);
			unset( $base['id'] );
			return Slider_Schema::new_layer( $type, array_replace_recursive( $base, $data ) );
		};

		$layers = array();
		if ( in_array( 'date', $show, true ) || in_array( 'author', $show, true ) ) {
			$meta = array();
			if ( in_array( 'date', $show, true ) ) {
				$meta[] = esc_html( get_the_date( '', $post ) );
			}
			if ( in_array( 'author', $show, true ) ) {
				$meta[] = esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) );
			}
			$layers[] = $make(
				'text',
				array(
					'text'      => implode( ' · ', $meta ),
					'size'      => 's',
					'animation' => array( 'in' => 'fade' ),
				)
			);
		}
		if ( in_array( 'title', $show, true ) ) {
			$layers[] = $make(
				'heading',
				array(
					'text'  => esc_html( get_the_title( $post ) ),
					'level' => isset( $style['heading'] ) ? $style['heading']['level'] : 'h3',
				)
			);
		}
		if ( in_array( 'price', $show, true ) && 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post );
			if ( $product ) {
				$layers[] = $make(
					'text',
					array(
						'text' => wp_strip_all_tags( html_entity_decode( (string) $product->get_price_html(), ENT_QUOTES, 'UTF-8' ) ),
						'size' => 'l',
					)
				);
			}
		}
		if ( in_array( 'rating', $show, true ) && 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post );
			$rating  = $product ? (float) $product->get_average_rating() : 0;
			if ( $rating > 0 ) {
				$stars    = (int) round( $rating );
				$layers[] = $make(
					'text',
					array(
						'text' => str_repeat( '★', $stars ) . str_repeat( '☆', 5 - $stars ),
						'size' => 'm',
					)
				);
			}
		}
		if ( in_array( 'excerpt', $show, true ) ) {
			$excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 24 );
			if ( '' !== $excerpt ) {
				$layers[] = $make(
					'text',
					array(
						'text' => esc_html( $excerpt ),
						'size' => 'm',
					)
				);
			}
		}
		if ( in_array( 'button', $show, true ) ) {
			$label    = '' !== $template['source']['button_label'] ? $template['source']['button_label'] : ( 'product' === $post->post_type ? __( 'View product', 'wp-smart-flexslider' ) : __( 'Read more', 'wp-smart-flexslider' ) );
			$layers[] = $make(
				'button',
				array(
					'text' => esc_html( $label ),
					'url'  => $url,
				)
			);
		}
		$slide['layers'] = $layers;
		return $slide;
	}
}
