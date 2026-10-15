<?php
/**
 * [slidecraft] and the legacy [display_flexslider] shortcodes.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Render;

use SlideCraft\Legacy\Migrator;
use SlideCraft\Sliders\Slider_Repository;

/**
 * Shortcode handlers.
 */
final class Shortcodes {

	/**
	 * Register the shortcodes.
	 */
	public static function register() {
		add_shortcode( 'slidecraft', array( self::class, 'slidecraft' ) );
		add_shortcode( 'display_flexslider', array( self::class, 'legacy' ) );
	}

	/**
	 * [slidecraft id="12" align="wide|full" autoplay="yes|no" height="500"].
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function slidecraft( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'align'    => '',
				'autoplay' => '',
				'height'   => 0,
				'class'    => '',
			),
			$atts,
			'slidecraft'
		);
		return self::output( (int) $atts['id'], $atts );
	}

	/**
	 * [display_flexslider id="12"] from WP Smart Flexslider 2.x, and [display_flexslider] from 1.x.
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function legacy( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'display_flexslider' );
		return self::output( Migrator::slider_for( (int) $atts['id'] ), array() );
	}

	/**
	 * Render a slider for a shortcode.
	 *
	 * @param int   $id   Slider ID.
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function output( $id, array $atts ) {
		$slider = $id ? Slider_Repository::find( $id ) : null;
		if ( ! $slider ) {
			return current_user_can( 'edit_posts' ) && $id ? '<!-- SlideCraft: slider ' . (int) $id . ' not found -->' : '';
		}
		Assets::enqueue();
		$overrides = array(
			'class' => isset( $atts['class'] ) ? (string) $atts['class'] : '',
		);
		if ( ! empty( $atts['align'] ) && in_array( $atts['align'], array( 'wide', 'full' ), true ) ) {
			$overrides['align'] = $atts['align'];
		}
		if ( isset( $atts['autoplay'] ) && '' !== $atts['autoplay'] ) {
			$overrides['autoplay'] = in_array( strtolower( (string) $atts['autoplay'] ), array( '1', 'yes', 'true', 'on' ), true );
		}
		if ( ! empty( $atts['height'] ) ) {
			$overrides['height_px'] = (int) $atts['height'];
		}
		return Renderer::render( $slider, $overrides );
	}
}
