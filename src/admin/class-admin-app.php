<?php
/**
 * The SlideCraft admin screen, a React app.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Admin;

use SlideCraft\Capabilities;
use SlideCraft\Render\Assets;
use SlideCraft\Sliders\Slider_Schema;
use SlideCraft\Templates\Template_Registry;

/**
 * Adds the top-level menu and mounts the builder.
 */
final class Admin_App {

	const SLUG = 'slidecraft';

	/**
	 * Hook suffix of the admin page.
	 *
	 * @var string
	 */
	private $hook = '';

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SLIDECRAFT_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Admin menu: SlideCraft → All Sliders, New Slider.
	 */
	public function menu() {
		$this->hook = (string) add_menu_page(
			__( 'SlideCraft', 'wp-smart-flexslider' ),
			__( 'SlideCraft', 'wp-smart-flexslider' ),
			Capabilities::MANAGE,
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-images-alt2',
			26
		);
		add_submenu_page( self::SLUG, __( 'All Sliders', 'wp-smart-flexslider' ), __( 'All Sliders', 'wp-smart-flexslider' ), Capabilities::MANAGE, self::SLUG, array( $this, 'render' ) );
		add_submenu_page( self::SLUG, __( 'New Slider', 'wp-smart-flexslider' ), __( 'New Slider', 'wp-smart-flexslider' ), Capabilities::MANAGE, self::SLUG . '#/new', '__return_null' );
	}

	/**
	 * Mount point.
	 */
	public function render() {
		echo '<div id="slidecraft-admin" class="slidecraft-admin"></div>';
	}

	/**
	 * Enqueue the app on its own screen only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( $hook !== $this->hook ) {
			return;
		}
		$asset = Assets::asset( 'admin' );
		wp_enqueue_media();
		wp_enqueue_script( 'slidecraft-admin', SLIDECRAFT_URL . 'build/admin.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'slidecraft-admin', SLIDECRAFT_URL . 'build/admin.css', array( 'wp-components' ), $asset['version'] );
		wp_set_script_translations( 'slidecraft-admin', 'wp-smart-flexslider' );
		wp_localize_script(
			'slidecraft-admin',
			'slideCraftSettings',
			array(
				'version'    => SLIDECRAFT_VERSION,
				'previewUrl' => Preview_Page::url(),
				'adminUrl'   => admin_url( 'admin.php?page=' . self::SLUG ),
				'templates'  => Template_Registry::for_js(),
				'pluginUrl'  => SLIDECRAFT_URL,
				'defaults'   => self::defaults(),
			)
		);
	}

	/**
	 * Schema defaults the builder needs to add slides and layers that match the server.
	 *
	 * @return array
	 */
	public static function defaults() {
		$slides = array();
		foreach ( Slider_Schema::SLIDE_TYPES as $type ) {
			$slides[ $type ] = Slider_Schema::new_slide( $type );
		}
		$layers = array();
		foreach ( Slider_Schema::LAYER_TYPES as $type ) {
			$layers[ $type ] = Slider_Schema::new_layer( $type );
		}
		return array(
			'config'     => Slider_Schema::defaults(),
			'slides'     => $slides,
			'layers'     => $layers,
			'maxSlides'  => Slider_Schema::MAX_SLIDES,
			'maxLayers'  => Slider_Schema::MAX_LAYERS,
			'animations' => Slider_Schema::ANIMATIONS,
		);
	}

	/**
	 * "Sliders" link on the plugins screen.
	 *
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Sliders', 'wp-smart-flexslider' ) . '</a>' );
		return $links;
	}
}
