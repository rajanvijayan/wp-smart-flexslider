<?php
/**
 * The page the builder loads in its canvas iframe.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Admin;

use SlideCraft\Capabilities;
use SlideCraft\Render\Assets;

/**
 * A front-end page (so the theme's fonts and colours apply) that renders whatever configuration the builder
 * sends over postMessage. Only users who can manage sliders can open it.
 */
final class Preview_Page {

	const QUERY_VAR = 'slidecraft_preview';
	const NONCE     = 'slidecraft_preview';

	/**
	 * Hook registration.
	 */
	public static function register() {
		add_action( 'template_redirect', array( self::class, 'maybe_render' ), 0 );
	}

	/**
	 * URL of the preview page for the current user.
	 *
	 * @return string
	 */
	public static function url() {
		return add_query_arg(
			array(
				self::QUERY_VAR => 1,
				'_wpnonce'      => wp_create_nonce( self::NONCE ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Render the preview page and stop.
	 */
	public static function maybe_render() {
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below.
			return;
		}
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) || ! Capabilities::current_user_can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to preview sliders.', 'wp-smart-flexslider' ), '', array( 'response' => 403 ) );
		}

		show_admin_bar( false );
		nocache_headers();
		header( 'X-Robots-Tag: noindex' );
		Assets::enqueue();
		$asset = Assets::asset( 'preview' );
		wp_enqueue_script( 'slidecraft-preview', SLIDECRAFT_URL . 'build/preview.js', array_merge( $asset['dependencies'], array( Assets::HANDLE ) ), $asset['version'], true );
		wp_localize_script(
			'slidecraft-preview',
			'slideCraftPreview',
			array(
				'origin' => untrailingslashit( admin_url() ),
			)
		);
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
		<?php wp_head(); ?>
<style>html,body{margin:0;background:#f0f0f1}body.slidecraft-preview-page{padding:0}#slidecraft-preview-root{margin:0 auto;max-width:none}.slidecraft-preview-empty{display:grid;place-items:center;min-height:100vh;color:#757575;font:15px/1.5 system-ui,sans-serif}</style>
</head>
<body class="slidecraft-preview-page">
<div id="slidecraft-preview-root"><div class="slidecraft-preview-empty"><?php esc_html_e( 'Loading preview…', 'wp-smart-flexslider' ); ?></div></div>
		<?php wp_footer(); ?>
</body>
</html>
		<?php
		exit;
	}
}
