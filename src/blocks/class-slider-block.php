<?php
/**
 * The slidecraft/slider block.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Blocks;

use SlideCraft\Render\Assets;
use SlideCraft\Render\Shortcodes;

/**
 * Server-rendered block that places a library slider.
 */
final class Slider_Block {

	/**
	 * Hook registration.
	 */
	public static function register() {
		add_action( 'init', array( self::class, 'register_block' ), 20 );
	}

	/**
	 * Register the editor script and the block type.
	 */
	public static function register_block() {
		Assets::register_assets();
		$asset = Assets::asset( 'block' );
		wp_register_script( 'slidecraft-block', SLIDECRAFT_URL . 'build/block.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'slidecraft-block', 'wp-smart-flexslider' );
		wp_localize_script(
			'slidecraft-block',
			'slideCraftBlock',
			array(
				'adminUrl' => admin_url( 'admin.php?page=slidecraft' ),
			)
		);
		register_block_type(
			SLIDECRAFT_DIR . 'blocks/slider',
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$atts = array(
			'align'    => isset( $attributes['align'] ) ? (string) $attributes['align'] : '',
			'autoplay' => isset( $attributes['autoplay'] ) && 'default' !== $attributes['autoplay'] ? ( 'on' === $attributes['autoplay'] ? 'yes' : 'no' ) : '',
			'height'   => isset( $attributes['height'] ) ? (int) $attributes['height'] : 0,
			'class'    => isset( $attributes['className'] ) ? (string) $attributes['className'] : '',
		);
		$html = Shortcodes::output( isset( $attributes['sliderId'] ) ? (int) $attributes['sliderId'] : 0, $atts );
		if ( '' !== $html && ! empty( $attributes['anchor'] ) ) {
			$html = '<div id="' . esc_attr( $attributes['anchor'] ) . '">' . $html . '</div>';
		}
		return $html;
	}
}
