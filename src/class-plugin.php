<?php
/**
 * Plugin bootstrap: wires every service to WordPress.
 *
 * @package SlideCraft
 */

namespace SlideCraft;

use SlideCraft\Admin\Admin_App;
use SlideCraft\Admin\Migration_Notice;
use SlideCraft\Admin\Preview_Page;
use SlideCraft\Blocks\Slider_Block;
use SlideCraft\Legacy\Migrator;
use SlideCraft\Render\Assets;
use SlideCraft\Render\Dynamic_Source;
use SlideCraft\Render\Shortcodes;
use SlideCraft\Rest\Preview_Controller;
use SlideCraft\Rest\Sliders_Controller;
use SlideCraft\Rest\Sources_Controller;
use SlideCraft\Sliders\Slider_Post_Type;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks. Runs once while plugins load.
	 */
	public function boot() {
		require_once SLIDECRAFT_DIR . 'src/functions.php';

		Capabilities::register();
		add_action( 'init', array( Slider_Post_Type::class, 'register' ) );
		// Existing 1.x/2.x sliders are converted once, right after the post type exists (also on plugin updates,
		// which do not run the activation hook).
		add_action( 'init', array( Migrator::class, 'maybe_run' ), 20 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		Assets::register();
		Shortcodes::register();
		Dynamic_Source::register();
		Slider_Block::register();
		Preview_Page::register();

		if ( is_admin() ) {
			( new Admin_App() )->register();
			Migration_Notice::register();
		}
	}

	/**
	 * Register the REST API routes under slidecraft/v1.
	 */
	public function register_rest_routes() {
		foreach ( array( new Sliders_Controller(), new Sources_Controller(), new Preview_Controller() ) as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Activation: register the post type and migrate old sliders.
	 */
	public static function activate() {
		Slider_Post_Type::register();
		Migrator::maybe_run();
	}

	/**
	 * Deactivation: nothing to clean up; sliders stay until the plugin is deleted.
	 */
	public static function deactivate() {
	}
}
