<?php
/**
 * Front-end assets: loaded only on pages that show a slider.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Render;

/**
 * Registers the runtime and enqueues it early when the page content contains a slider (so the CSS is in
 * <head>), or as soon as a slider renders anywhere else (widgets, templates, page builders).
 */
final class Assets {

	const HANDLE = 'slidecraft';

	/**
	 * Hook registration.
	 */
	public static function register() {
		add_action( 'init', array( self::class, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'maybe_enqueue_early' ) );
	}

	/**
	 * Register the script and style built by @wordpress/scripts.
	 */
	public static function register_assets() {
		$asset = self::asset( 'frontend' );
		wp_register_script(
			self::HANDLE,
			SLIDECRAFT_URL . 'build/frontend.js',
			$asset['dependencies'],
			$asset['version'],
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_register_style( self::HANDLE, SLIDECRAFT_URL . 'build/frontend.css', array(), $asset['version'] );
	}

	/**
	 * Enqueue in <head> when the queried posts contain a slider block or shortcode.
	 */
	public static function maybe_enqueue_early() {
		global $wp_query;
		if ( ! $wp_query instanceof \WP_Query || empty( $wp_query->posts ) ) {
			return;
		}
		foreach ( $wp_query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$content = $post->post_content;
			if ( has_block( 'slidecraft/slider', $content ) || has_shortcode( $content, 'slidecraft' ) || has_shortcode( $content, 'display_flexslider' ) ) {
				self::enqueue();
				return;
			}
		}
	}

	/**
	 * Enqueue the runtime (safe to call many times, also after wp_head).
	 */
	public static function enqueue() {
		if ( ! wp_script_is( self::HANDLE, 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );
	}

	/**
	 * Read a build asset file (dependencies and version).
	 *
	 * @param string $name Entry name.
	 * @return array{dependencies: string[], version: string}
	 */
	public static function asset( $name ) {
		$file  = SLIDECRAFT_DIR . 'build/' . $name . '.asset.php';
		$asset = file_exists( $file ) ? require $file : array();
		return array(
			'dependencies' => isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
			'version'      => isset( $asset['version'] ) ? $asset['version'] : SLIDECRAFT_VERSION,
		);
	}
}
