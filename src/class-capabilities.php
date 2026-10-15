<?php
/**
 * Capability used to manage sliders.
 *
 * @package SlideCraft
 */

namespace SlideCraft;

/**
 * Grants the slider capability to users who hold the base capability.
 */
final class Capabilities {

	/**
	 * Capability checked by every admin screen and REST route.
	 */
	const MANAGE = 'slidecraft_manage';

	/**
	 * Hook the capability mapping.
	 */
	public static function register() {
		add_filter( 'user_has_cap', array( self::class, 'grant' ), 10, 1 );
	}

	/**
	 * The core capability that implies slider management: users who can publish (authors and above).
	 *
	 * @return string
	 */
	public static function base_capability() {
		/**
		 * Filters the core capability that lets a user manage sliders.
		 *
		 * @param string $capability Default 'publish_posts'.
		 */
		return (string) apply_filters( 'slidecraft_base_capability', 'publish_posts' );
	}

	/**
	 * Add the slider capability to users who have the base capability.
	 *
	 * @param array $allcaps All capabilities of the user.
	 * @return array
	 */
	public static function grant( $allcaps ) {
		$base = self::base_capability();
		if ( ! empty( $allcaps[ $base ] ) ) {
			$allcaps[ self::MANAGE ] = true;
		}
		return $allcaps;
	}

	/**
	 * Whether the current user can manage sliders.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( self::MANAGE );
	}
}
