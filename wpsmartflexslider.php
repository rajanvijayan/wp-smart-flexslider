<?php

/**
 * Plugin Name:       WP Smart Flexslider
 * Plugin URI:        http://flexslider.wpsmartplugin.com/
 * Description:       This is Flex Slider plugin. Its use Bootstrap 3.3.7x Themes to create easy Sliders.
 * Version:           2.5
 * Author:            Rajan V
 * Author URI:        https://www.fb.com/rajanit2000
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpsmartflexslider
 * Domain Path:       /languages
 */

/*  Copyright 2014-2017 WP Smart Plugin

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpsmartflexslider-activator.php
 */
function activate_wpsmartflexslider() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpsmartflexslider-activator.php';
	Wpsmartflexslider_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpsmartflexslider-deactivator.php
 */
function deactivate_wpsmartflexslider() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpsmartflexslider-deactivator.php';
	Wpsmartflexslider_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpsmartflexslider' );
register_deactivation_hook( __FILE__, 'deactivate_wpsmartflexslider' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpsmartflexslider.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpsmartflexslider() {

	$plugin = new Wpsmartflexslider();
	$plugin->run();

}
run_wpsmartflexslider();
