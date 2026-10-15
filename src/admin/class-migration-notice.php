<?php
/**
 * Tells site owners that their old sliders were moved.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Admin;

use SlideCraft\Capabilities;
use SlideCraft\Legacy\Migrator;

/**
 * Dismissible notice after the 1.x/2.x migration.
 */
final class Migration_Notice {

	const ACTION = 'slidecraft_dismiss_migration';

	/**
	 * Hook registration.
	 */
	public static function register() {
		add_action( 'admin_notices', array( self::class, 'render' ) );
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'dismiss' ) );
	}

	/**
	 * Show the notice.
	 */
	public static function render() {
		$count = (int) get_option( Migrator::OPTION_NOTICE, 0 );
		if ( ! $count || ! Capabilities::current_user_can_manage() ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
		echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'WP Smart Flexslider is now SlideCraft.', 'wp-smart-flexslider' ) . '</strong> ';
		echo esc_html(
			sprintf(
				/* translators: %d: number of sliders */
				_n( 'Your %d slider was moved to the new builder.', 'Your %d sliders were moved to the new builder.', $count, 'wp-smart-flexslider' ),
				$count
			)
		);
		echo ' ' . esc_html__( 'Existing [display_flexslider] shortcodes keep working.', 'wp-smart-flexslider' ) . '</p><p>';
		echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=' . Admin_App::SLUG ) ) . '">' . esc_html__( 'Open SlideCraft', 'wp-smart-flexslider' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Dismiss', 'wp-smart-flexslider' ) . '</a></p></div>';
	}

	/**
	 * Hide the notice.
	 */
	public static function dismiss() {
		check_admin_referer( self::ACTION );
		if ( Capabilities::current_user_can_manage() ) {
			delete_option( Migrator::OPTION_NOTICE );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
}
