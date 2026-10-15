<?php
/**
 * SlideCraft – Responsive Slider & Carousel.
 *
 * The file name is kept from WP Smart Flexslider: WordPress records active plugins by path,
 * so renaming it would deactivate the plugin on update.
 *
 * @package SlideCraft
 *
 * @wordpress-plugin
 * Plugin Name:       SlideCraft – Responsive Slider & Carousel
 * Plugin URI:        https://wordpress.org/plugins/wp-smart-flexslider/
 * Description:       Build beautiful, fast and accessible sliders and carousels with a visual builder: hero banners, product and post carousels, testimonials, logo tickers, galleries and video slides.
 * Version:           3.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Rajan V
 * Author URI:        https://wpsmartcare.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-smart-flexslider
 */

namespace SlideCraft;

defined( 'ABSPATH' ) || exit;

define( 'SLIDECRAFT_VERSION', '3.0.0' );
define( 'SLIDECRAFT_FILE', __FILE__ );
define( 'SLIDECRAFT_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLIDECRAFT_URL', plugin_dir_url( __FILE__ ) );

if ( ! file_exists( SLIDECRAFT_DIR . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'SlideCraft is missing its files. Please reinstall the plugin from WordPress.org.', 'wp-smart-flexslider' ) . '</p></div>';
		}
	);
	return;
}

require_once SLIDECRAFT_DIR . 'vendor/autoload.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

Plugin::instance()->boot();
