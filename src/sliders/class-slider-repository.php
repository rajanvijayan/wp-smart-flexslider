<?php
/**
 * Loads and saves sliders.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Sliders;

use WP_Error;
use WP_Post;

/**
 * Sliders are posts of type slidecraft_slider with the configuration as JSON in post meta.
 */
final class Slider_Repository {

	const META_CONFIG    = '_slidecraft_config';
	const META_REVISIONS = '_slidecraft_revisions';
	const MAX_REVISIONS  = 10;

	/**
	 * Load a slider.
	 *
	 * @param int $id Post ID.
	 * @return Slider|null
	 */
	public static function find( $id ) {
		$post = get_post( (int) $id );
		if ( ! $post instanceof WP_Post || Slider_Post_Type::POST_TYPE !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}
		return self::from_post( $post );
	}

	/**
	 * Build a slider from its post.
	 *
	 * @param WP_Post $post Post.
	 * @return Slider
	 */
	public static function from_post( WP_Post $post ) {
		$raw    = get_post_meta( $post->ID, self::META_CONFIG, true );
		$config = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : array();
		return new Slider( $post->ID, $post->post_title, Slider_Schema::normalize( $config ), $post->post_modified_gmt, (int) $post->post_author );
	}

	/**
	 * List sliders.
	 *
	 * @param array $args { search, page, per_page }.
	 * @return array{items: Slider[], total: int}
	 */
	public static function query( array $args = array() ) {
		$query = new \WP_Query(
			array(
				'post_type'      => Slider_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				's'              => isset( $args['search'] ) ? (string) $args['search'] : '',
				'paged'          => isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1,
				'posts_per_page' => isset( $args['per_page'] ) ? min( 100, max( 1, (int) $args['per_page'] ) ) : 50,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => false,
			)
		);
		return array(
			'items' => array_map( array( self::class, 'from_post' ), $query->posts ),
			'total' => (int) $query->found_posts,
		);
	}

	/**
	 * Create a slider.
	 *
	 * @param string $title  Title.
	 * @param array  $config Configuration (normalized here).
	 * @return Slider|WP_Error
	 */
	public static function create( $title, array $config = array() ) {
		$id = wp_insert_post(
			array(
				'post_type'   => Slider_Post_Type::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => '' !== trim( (string) $title ) ? sanitize_text_field( $title ) : __( 'Untitled slider', 'wp-smart-flexslider' ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		self::write_config( $id, Slider_Schema::normalize( $config ) );
		return self::find( $id );
	}

	/**
	 * Update a slider's title and/or configuration (full replacement).
	 *
	 * @param Slider      $slider   Slider.
	 * @param string|null $title    New title, or null to keep.
	 * @param array|null  $config   New configuration, or null to keep.
	 * @param bool        $revision Store the previous configuration as a revision.
	 * @return Slider|WP_Error
	 */
	public static function update( Slider $slider, $title = null, $config = null, $revision = false ) {
		if ( null !== $title ) {
			$result = wp_update_post(
				array(
					'ID'         => $slider->id,
					'post_title' => sanitize_text_field( $title ),
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		if ( is_array( $config ) ) {
			if ( $revision ) {
				self::push_revision( $slider );
			}
			self::write_config( $slider->id, Slider_Schema::normalize( $config ) );
			if ( null === $title ) {
				// Touch the post so the library sorts by last change.
				wp_update_post( array( 'ID' => $slider->id ) );
			}
		}
		return self::find( $slider->id );
	}

	/**
	 * Copy a slider.
	 *
	 * @param Slider $slider Slider.
	 * @return Slider|WP_Error
	 */
	public static function duplicate( Slider $slider ) {
		/* translators: %s: slider title */
		return self::create( sprintf( __( '%s (copy)', 'wp-smart-flexslider' ), $slider->title ), $slider->config );
	}

	/**
	 * Delete a slider permanently.
	 *
	 * @param Slider $slider Slider.
	 * @return bool
	 */
	public static function delete( Slider $slider ) {
		return (bool) wp_delete_post( $slider->id, true );
	}

	/**
	 * Stored revisions, newest first.
	 *
	 * @param int $id Slider ID.
	 * @return array[] { time, user, config }.
	 */
	public static function revisions( $id ) {
		$revisions = get_post_meta( (int) $id, self::META_REVISIONS, true );
		return is_array( $revisions ) ? array_values( $revisions ) : array();
	}

	/**
	 * Restore a revision (the current configuration becomes a revision itself).
	 *
	 * @param Slider $slider Slider.
	 * @param int    $index  Revision index from revisions().
	 * @return Slider|WP_Error
	 */
	public static function restore( Slider $slider, $index ) {
		$revisions = self::revisions( $slider->id );
		if ( ! isset( $revisions[ $index ] ) ) {
			return new WP_Error( 'slidecraft_no_revision', __( 'That version no longer exists.', 'wp-smart-flexslider' ), array( 'status' => 404 ) );
		}
		$config = json_decode( $revisions[ $index ]['config'], true );
		return self::update( $slider, null, is_array( $config ) ? $config : array(), true );
	}

	/**
	 * Export a slider as portable JSON data. Attachments are exported as URLs so they can be re-imported elsewhere.
	 *
	 * @param Slider $slider Slider.
	 * @return array
	 */
	public static function export( Slider $slider ) {
		$media = array();
		array_walk_recursive(
			$slider->config,
			static function ( $value, $key ) use ( &$media ) {
				if ( in_array( $key, array( 'image_id', 'file_id', 'poster_id', 'avatar_id' ), true ) && $value ) {
					$url = wp_get_attachment_url( $value );
					if ( $url ) {
						$media[ $value ] = $url;
					}
				}
			}
		);
		return array(
			'format'  => 'slidecraft',
			'version' => Slider_Schema::VERSION,
			'title'   => $slider->title,
			'config'  => $slider->config,
			'media'   => $media,
		);
	}

	/**
	 * Import a slider exported by export(). Attachment IDs that exist on this site with the same URL are kept;
	 * others are cleared so the slider still opens (the builder shows empty images to replace).
	 *
	 * @param array $data Export data.
	 * @return Slider|WP_Error
	 */
	public static function import( array $data ) {
		if ( ! isset( $data['format'], $data['config'] ) || 'slidecraft' !== $data['format'] || ! is_array( $data['config'] ) ) {
			return new WP_Error( 'slidecraft_bad_import', __( 'This file is not a SlideCraft export.', 'wp-smart-flexslider' ), array( 'status' => 400 ) );
		}
		$media  = isset( $data['media'] ) && is_array( $data['media'] ) ? $data['media'] : array();
		$config = $data['config'];
		self::remap_media( $config, $media );
		return self::create( isset( $data['title'] ) ? (string) $data['title'] : '', $config );
	}

	/**
	 * Replace attachment IDs that do not match the exported URL on this site.
	 *
	 * @param array $config Configuration (changed in place).
	 * @param array $media  Exported ID => URL map.
	 */
	private static function remap_media( array &$config, array $media ) {
		foreach ( $config as $key => &$value ) {
			if ( is_array( $value ) ) {
				self::remap_media( $value, $media );
			} elseif ( in_array( $key, array( 'image_id', 'file_id', 'poster_id', 'avatar_id' ), true ) && $value ) {
				$url = isset( $media[ $value ] ) ? $media[ $value ] : '';
				if ( ! $url || wp_get_attachment_url( (int) $value ) !== $url ) {
					$local = $url ? attachment_url_to_postid( $url ) : 0;
					$value = $local ? $local : 0;
				}
			}
		}
	}

	/**
	 * Store the configuration.
	 *
	 * @param int   $id     Slider ID.
	 * @param array $config Normalized configuration.
	 */
	private static function write_config( $id, array $config ) {
		update_post_meta( $id, self::META_CONFIG, wp_slash( wp_json_encode( $config ) ) );
	}

	/**
	 * Keep the current configuration as a revision.
	 *
	 * @param Slider $slider Slider.
	 */
	private static function push_revision( Slider $slider ) {
		$revisions = self::revisions( $slider->id );
		array_unshift(
			$revisions,
			array(
				'time'   => time(),
				'user'   => get_current_user_id(),
				'config' => wp_json_encode( $slider->config ),
			)
		);
		update_post_meta( $slider->id, self::META_REVISIONS, wp_slash( array_slice( $revisions, 0, self::MAX_REVISIONS ) ) );
	}
}
