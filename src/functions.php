<?php
/**
 * Template tag for themes.
 *
 * @package SlideCraft
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'slidecraft_slider' ) ) {
	/**
	 * Print or return a slider.
	 *
	 * @param int   $id        Slider ID.
	 * @param array $overrides Optional: align (wide|full), autoplay (bool), height (px), class.
	 * @param bool  $display   Print the slider (default) or return it.
	 * @return string
	 */
	function slidecraft_slider( $id, array $overrides = array(), $display = true ) {
		$html = \SlideCraft\Render\Shortcodes::output( (int) $id, $overrides );
		if ( $display ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped by the renderer.
		}
		return $html;
	}
}
