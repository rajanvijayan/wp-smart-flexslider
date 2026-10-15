<?php
/**
 * A slider: post data plus its configuration.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Sliders;

/**
 * Immutable slider value.
 */
final class Slider {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Title shown in the library.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Normalized configuration.
	 *
	 * @var array
	 */
	public $config;

	/**
	 * Last modified time (GMT, MySQL format).
	 *
	 * @var string
	 */
	public $modified_gmt;

	/**
	 * Author user ID.
	 *
	 * @var int
	 */
	public $author;

	/**
	 * Constructor.
	 *
	 * @param int    $id           Post ID.
	 * @param string $title        Title.
	 * @param array  $config       Normalized configuration.
	 * @param string $modified_gmt Modified time.
	 * @param int    $author       Author ID.
	 */
	public function __construct( $id, $title, array $config, $modified_gmt = '', $author = 0 ) {
		$this->id           = (int) $id;
		$this->title        = (string) $title;
		$this->config       = $config;
		$this->modified_gmt = (string) $modified_gmt;
		$this->author       = (int) $author;
	}

	/**
	 * Slides that are not hidden.
	 *
	 * @return array[]
	 */
	public function visible_slides() {
		return array_values(
			array_filter(
				$this->config['slides'],
				static function ( $slide ) {
					return ! $slide['hidden'];
				}
			)
		);
	}

	/**
	 * First image attachment used by the slider, for thumbnails.
	 *
	 * @return int
	 */
	public function first_image_id() {
		foreach ( $this->config['slides'] as $slide ) {
			if ( $slide['background']['image_id'] ) {
				return $slide['background']['image_id'];
			}
			if ( $slide['background']['video']['poster_id'] ) {
				return $slide['background']['video']['poster_id'];
			}
		}
		return 0;
	}

	/**
	 * Shortcode that places this slider.
	 *
	 * @return string
	 */
	public function shortcode() {
		return '[slidecraft id="' . $this->id . '"]';
	}
}
