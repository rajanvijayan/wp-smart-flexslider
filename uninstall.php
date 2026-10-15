<?php
/**
 * Removes SlideCraft data when the plugin is deleted.
 *
 * Old WP Smart Flexslider posts (post type `flexslider`) are left alone: SlideCraft never changed them.
 *
 * @package SlideCraft
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$slidecraft_ids = get_posts(
	array(
		'post_type'   => 'slidecraft_slider',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);
foreach ( $slidecraft_ids as $slidecraft_id ) {
	wp_delete_post( $slidecraft_id, true );
}
foreach ( array( 'slidecraft_migrated', 'slidecraft_legacy_map', 'slidecraft_migration_notice', 'slidecraft_migrating', 'slidecraft_source_version' ) as $slidecraft_option ) {
	delete_option( $slidecraft_option );
}
