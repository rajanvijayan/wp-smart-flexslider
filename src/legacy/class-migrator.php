<?php
/**
 * Moves WP Smart Flexslider 1.x and 2.x sliders into SlideCraft.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Legacy;

use SlideCraft\Sliders\Slider_Repository;
use SlideCraft\Sliders\Slider_Schema;

/**
 * Converts the old `flexslider` posts into SlideCraft sliders. The old posts and their meta are never
 * changed or deleted, so a downgrade keeps working and the migration can run again safely.
 *
 * 2.x: one `flexslider` post per slider. Meta: `slide_attachmenid` (attachment IDs), `flex-slide-title`,
 * `flex-slide-url` and `flex-slide-desc` (arrays in the same order), `nav_controls`, `pager_controls`,
 * `show_title`, `show_description`, `show_slide_url` ('Yes'/'No'), `interval` (ms) and `hover_pass`.
 *
 * 1.x: one `flexslider` post per slide (featured image, title, content), placed with `[display_flexslider]`
 * and configured by the `flexslider_options` option.
 */
final class Migrator {

	const LEGACY_POST_TYPE = 'flexslider';
	const OPTION_DONE      = 'slidecraft_migrated';
	const OPTION_MAP       = 'slidecraft_legacy_map';
	const OPTION_NOTICE    = 'slidecraft_migration_notice';
	const OPTION_LOCK      = 'slidecraft_migrating';
	const META_LEGACY      = '_slidecraft_legacy_id';

	/**
	 * Version of the migration routine. Bump it to run a new migration step on existing sites.
	 */
	const VERSION = 1;

	/**
	 * Map key used for the 1.x slider (the shortcode without an id).
	 */
	const V1_KEY = 0;

	/**
	 * Run the migration once per site.
	 */
	public static function maybe_run() {
		if ( (int) get_option( self::OPTION_DONE, 0 ) >= self::VERSION ) {
			return;
		}
		self::run();
	}

	/**
	 * Migrate every legacy slider that has not been migrated yet.
	 *
	 * @return array{created: int, skipped: int}
	 */
	public static function run() {
		$result = array(
			'created' => 0,
			'skipped' => 0,
		);

		// One request at a time; a stale lock (from a fatal error) expires after five minutes.
		if ( ! add_option( self::OPTION_LOCK, time(), '', false ) ) {
			if ( time() - (int) get_option( self::OPTION_LOCK ) < 300 ) {
				return $result;
			}
			update_option( self::OPTION_LOCK, time(), false );
		}

		$map      = self::map();
		$posts    = get_posts(
			array(
				'post_type'   => self::LEGACY_POST_TYPE,
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
		$v1_posts = array();

		foreach ( $posts as $post ) {
			$ids = get_post_meta( $post->ID, 'slide_attachmenid', true );
			if ( is_array( $ids ) && $ids ) {
				if ( isset( $map[ $post->ID ] ) && Slider_Repository::find( $map[ $post->ID ] ) ) {
					++$result['skipped'];
					continue;
				}
				$slider = Slider_Repository::create( self::v2_title( $post ), self::v2_config( $post->ID, $ids ) );
				if ( ! is_wp_error( $slider ) ) {
					update_post_meta( $slider->id, self::META_LEGACY, $post->ID );
					$map[ $post->ID ] = $slider->id;
					++$result['created'];
				}
			} elseif ( 'publish' === $post->post_status && has_post_thumbnail( $post ) ) {
				$v1_posts[] = $post;
			}
		}

		if ( $v1_posts && ! ( isset( $map[ self::V1_KEY ] ) && Slider_Repository::find( $map[ self::V1_KEY ] ) ) ) {
			$slider = Slider_Repository::create( __( 'Slider (from WP Smart Flexslider 1.x)', 'wp-smart-flexslider' ), self::v1_config( $v1_posts ) );
			if ( ! is_wp_error( $slider ) ) {
				update_post_meta( $slider->id, self::META_LEGACY, 'v1' );
				$map[ self::V1_KEY ] = $slider->id;
				++$result['created'];
			}
		}

		update_option( self::OPTION_MAP, $map, false );
		update_option( self::OPTION_DONE, self::VERSION );
		if ( $result['created'] ) {
			update_option( self::OPTION_NOTICE, (int) get_option( self::OPTION_NOTICE, 0 ) + $result['created'], false );
		}
		delete_option( self::OPTION_LOCK );
		return $result;
	}

	/**
	 * Legacy ID => SlideCraft slider ID. Key 0 is the 1.x slider.
	 *
	 * @return int[]
	 */
	public static function map() {
		$map = get_option( self::OPTION_MAP, array() );
		return is_array( $map ) ? array_map( 'intval', $map ) : array();
	}

	/**
	 * SlideCraft slider for a legacy shortcode.
	 *
	 * @param int $legacy_id Legacy slider ID, or 0 for the 1.x shortcode without an id.
	 * @return int Slider ID, or 0.
	 */
	public static function slider_for( $legacy_id ) {
		self::maybe_run();
		$map = self::map();
		return isset( $map[ (int) $legacy_id ] ) ? (int) $map[ (int) $legacy_id ] : 0;
	}

	/**
	 * Title for a migrated 2.x slider.
	 *
	 * @param \WP_Post $post Legacy post.
	 * @return string
	 */
	private static function v2_title( $post ) {
		$title = trim( $post->post_title );
		/* translators: %d: old slider ID */
		return '' !== $title ? $title : sprintf( __( 'Slider #%d', 'wp-smart-flexslider' ), $post->ID );
	}

	/**
	 * Configuration for a 2.x slider.
	 *
	 * @param int   $post_id Legacy post ID.
	 * @param array $ids     Attachment IDs.
	 * @return array
	 */
	public static function v2_config( $post_id, array $ids ) {
		$titles     = self::meta_list( $post_id, 'flex-slide-title' );
		$urls       = self::meta_list( $post_id, 'flex-slide-url' );
		$descs      = self::meta_list( $post_id, 'flex-slide-desc' );
		$show_title = 'Yes' === get_post_meta( $post_id, 'show_title', true );
		$show_desc  = 'Yes' === get_post_meta( $post_id, 'show_description', true );
		$show_url   = 'Yes' === get_post_meta( $post_id, 'show_slide_url', true );
		$interval   = (int) get_post_meta( $post_id, 'interval', true );

		$slides = array();
		foreach ( array_values( $ids ) as $k => $id ) {
			$id = absint( $id );
			if ( ! $id ) {
				continue;
			}
			$title = isset( $titles[ $k ] ) ? sanitize_text_field( $titles[ $k ] ) : '';
			$desc  = isset( $descs[ $k ] ) ? sanitize_text_field( $descs[ $k ] ) : '';
			$url   = isset( $urls[ $k ] ) ? esc_url_raw( $urls[ $k ], array( 'http', 'https' ) ) : '';

			$slide                           = Slider_Schema::new_slide( 'image' );
			$slide['title']                  = $title;
			$slide['alt']                    = $title;
			$slide['background']['image_id'] = $id;
			if ( $show_url && $url ) {
				// 2.x opened slide links in a new tab.
				$slide['link'] = array(
					'url'     => $url,
					'new_tab' => true,
				);
			}
			if ( $show_title && '' !== $title ) {
				$slide['layers'][] = self::caption_layer( 'heading', $title );
			}
			if ( $show_desc && '' !== $desc ) {
				$slide['layers'][] = self::caption_layer( 'text', $desc );
			}
			if ( $slide['layers'] ) {
				$slide['background']['overlay']['opacity'] = 0.25;
			}
			$slides[] = $slide;
		}

		$config                                  = Slider_Schema::defaults();
		$config['slides']                        = $slides;
		$config['settings']['height']['mode']    = 'auto';
		$config['settings']['autoplay']['delay'] = $interval > 0 ? $interval : 5000;
		$config['settings']['autoplay']['pause_on_hover'] = 'false' !== get_post_meta( $post_id, 'hover_pass', true );
		$config['settings']['navigation']['arrows']       = 'Yes' === get_post_meta( $post_id, 'nav_controls', true );
		$config['settings']['navigation']['pagination']   = 'Yes' === get_post_meta( $post_id, 'pager_controls', true ) ? 'bullets' : 'none';
		return $config;
	}

	/**
	 * Configuration for the 1.x slider (one post per slide).
	 *
	 * @param \WP_Post[] $posts Legacy posts, newest first like 1.x.
	 * @return array
	 */
	public static function v1_config( array $posts ) {
		$options = get_option( 'flexslider_options', array() );
		$options = is_array( $options ) ? $options : array();
		$limit   = isset( $options['number_slides'] ) ? (int) $options['number_slides'] : 0;
		if ( $limit > 0 ) {
			$posts = array_slice( $posts, 0, $limit );
		}

		$slides = array();
		foreach ( $posts as $post ) {
			$slide                           = Slider_Schema::new_slide( 'image' );
			$slide['title']                  = $post->post_title;
			$slide['alt']                    = $post->post_title;
			$slide['background']['image_id'] = (int) get_post_thumbnail_id( $post );
			if ( '' !== trim( $post->post_title ) ) {
				$slide['layers'][] = self::caption_layer( 'heading', $post->post_title );
			}
			$text = trim( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
			if ( '' !== $text ) {
				$slide['layers'][] = self::caption_layer( 'text', wp_trim_words( $text, 40 ) );
			}
			if ( $slide['layers'] ) {
				$slide['background']['overlay']['opacity'] = 0.25;
			}
			$slides[] = $slide;
		}

		$config                                     = Slider_Schema::defaults();
		$config['slides']                           = $slides;
		$config['settings']['height']['mode']       = 'auto';
		$config['settings']['navigation']['arrows'] = isset( $options['direction_controls'] ) && 'Yes' === $options['direction_controls'];
		$config['settings']['navigation']['pagination'] = isset( $options['nav_controls'] ) && 'Yes' === $options['nav_controls'] ? 'bullets' : 'none';
		return $config;
	}

	/**
	 * A caption layer placed like the old Bootstrap caption: centred near the bottom.
	 *
	 * @param string $type heading or text.
	 * @param string $text Text.
	 * @return array
	 */
	private static function caption_layer( $type, $text ) {
		return Slider_Schema::new_layer(
			$type,
			array(
				'text'     => esc_html( $text ),
				'level'    => 'heading' === $type ? 'h3' : 'p',
				'size'     => 'heading' === $type ? 'l' : 'm',
				'position' => array( 'anchor' => 'bottom-center' ),
			)
		);
	}

	/**
	 * Array meta value as a zero-indexed list.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @return array
	 */
	private static function meta_list( $post_id, $key ) {
		$value = get_post_meta( $post_id, $key, true );
		return is_array( $value ) ? array_values( $value ) : array();
	}
}
