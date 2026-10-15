<?php
/**
 * Versioned slider configuration: defaults, sanitizing and migrations.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Sliders;

/**
 * A slider is one JSON document: settings, responsive overrides and a list of slides, each with layers.
 */
final class Slider_Schema {

	/**
	 * Current configuration version. Bump it and add a step to migrate() when the shape changes.
	 */
	const VERSION = 1;

	/**
	 * Most slides a slider may hold.
	 */
	const MAX_SLIDES = 200;

	/**
	 * Most layers a slide may hold.
	 */
	const MAX_LAYERS = 12;

	/**
	 * Slide types.
	 */
	const SLIDE_TYPES = array( 'image', 'video', 'content', 'posts', 'testimonial', 'logo' );

	/**
	 * Layer types.
	 */
	const LAYER_TYPES = array( 'heading', 'text', 'button', 'image', 'countdown' );

	/**
	 * Entrance animations for layers.
	 */
	const ANIMATIONS = array( 'none', 'fade', 'fade-up', 'fade-down', 'fade-left', 'fade-right', 'zoom-in', 'zoom-out', 'blur', 'typewriter' );

	/**
	 * Nine-point anchor for layer positions.
	 */
	const ANCHORS = array( 'top-left', 'top-center', 'top-right', 'center-left', 'center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right' );

	/**
	 * Devices a layer can be hidden on.
	 */
	const DEVICES = array( 'desktop', 'tablet', 'mobile' );

	/**
	 * Spec of the whole configuration (see Spec for the format).
	 *
	 * @return array
	 */
	public static function spec() {
		return array(
			'object',
			null,
			array(
				'settings'    => self::settings_spec(),
				'breakpoints' => self::breakpoints_spec(),
				'slides'      => array( 'list', array(), array( self::slide_spec(), self::MAX_SLIDES ) ),
			),
		);
	}

	/**
	 * Slider settings.
	 *
	 * @return array
	 */
	public static function settings_spec() {
		return array(
			'object',
			null,
			array(
				'type'       => array( 'enum', 'slider', array( 'slider', 'carousel', 'gallery', 'ticker' ) ),
				'effect'     => array( 'enum', 'slide', array( 'slide', 'fade', 'coverflow', 'cube', 'flip', 'cards', 'creative' ) ),
				'creative'   => array( 'enum', 'zoom-out', array( 'zoom-out', 'push', 'rotate', 'stack' ) ),
				'direction'  => array( 'enum', 'horizontal', array( 'horizontal', 'vertical' ) ),
				'height'     => array(
					'object',
					null,
					array(
						'mode'  => array( 'enum', 'ratio', array( 'ratio', 'fixed', 'viewport', 'auto' ) ),
						'ratio' => array( 'enum', '16:9', array( '21:9', '16:9', '3:2', '4:3', '1:1', '4:5', '3:4', '9:16' ) ),
						'px'    => array( 'int', 520, array( 120, 2000 ) ),
						'vh'    => array( 'int', 80, array( 20, 100 ) ),
					),
				),
				'width'      => array( 'enum', 'content', array( 'content', 'wide', 'full' ) ),
				'speed'      => array( 'int', 600, array( 100, 5000 ) ),
				'loop'       => array( 'bool', true ),
				'start'      => array( 'int', 0, array( 0, self::MAX_SLIDES - 1 ) ),
				'random'     => array( 'bool', false ),
				'autoplay'   => array(
					'object',
					null,
					array(
						'enabled'        => array( 'bool', true ),
						'delay'          => array( 'int', 5000, array( 1000, 30000 ) ),
						'pause_on_hover' => array( 'bool', true ),
						'progress'       => array( 'bool', true ),
					),
				),
				'navigation' => array(
					'object',
					null,
					array(
						'arrows'          => array( 'bool', true ),
						'arrow_style'     => array( 'enum', 'circle', array( 'circle', 'square', 'minimal', 'chevron', 'pill', 'outline' ) ),
						'arrow_position'  => array( 'enum', 'inside', array( 'inside', 'outside' ) ),
						'arrows_on_hover' => array( 'bool', false ),
						'pagination'      => array( 'enum', 'bullets', array( 'bullets', 'dynamic', 'fraction', 'progressbar', 'thumbnails', 'none' ) ),
						'keyboard'        => array( 'bool', true ),
						'mousewheel'      => array( 'bool', false ),
						'draggable'       => array( 'bool', true ),
					),
				),
				'carousel'   => array(
					'object',
					null,
					array(
						'per_view'   => array( 'float', 3, array( 1, 8 ) ),
						'gap'        => array( 'int', 16, array( 0, 120 ) ),
						'centered'   => array( 'bool', false ),
						'free_mode'  => array( 'bool', false ),
						'rows'       => array( 'int', 1, array( 1, 4 ) ),
						'auto_width' => array( 'bool', false ),
					),
				),
				'ticker'     => array(
					'object',
					null,
					array(
						'speed'     => array( 'int', 40, array( 5, 300 ) ),
						'direction' => array( 'enum', 'left', array( 'left', 'right' ) ),
					),
				),
				'style'      => array(
					'object',
					null,
					array(
						'radius'        => array( 'int', 0, array( 0, 64 ) ),
						'accent'        => array( 'color', '#3858e9' ),
						'text_color'    => array( 'color', '#ffffff' ),
						'arrow_color'   => array( 'color', '#ffffff' ),
						'arrow_bg'      => array( 'color', '#111827' ),
						'font'          => array( 'enum', 'theme', array( 'theme', 'inter', 'playfair', 'montserrat', 'lora' ) ),
						'shadow'        => array( 'bool', false ),
						'gray_logos'    => array( 'bool', true ),
						'caption_style' => array( 'enum', 'overlay', array( 'overlay', 'below', 'none' ) ),
					),
				),
				'lightbox'   => array( 'bool', false ),
				'schema'     => array( 'bool', false ),
				'label'      => array( 'text', '' ),
			),
		);
	}

	/**
	 * Tablet and mobile overrides.
	 *
	 * @return array
	 */
	public static function breakpoints_spec() {
		$device = static function ( $max, $per_view, $gap, $scale, $hide_arrows ) {
			return array(
				'object',
				null,
				array(
					'max'         => array( 'int', $max, array( 320, 1600 ) ),
					'per_view'    => array( 'float', $per_view, array( 1, 8 ) ),
					'gap'         => array( 'int', $gap, array( 0, 120 ) ),
					'height_px'   => array( 'int', 0, array( 0, 2000 ) ),
					'font_scale'  => array( 'float', $scale, array( 0.4, 1.5 ) ),
					'hide_arrows' => array( 'bool', $hide_arrows ),
				),
			);
		};
		return array(
			'object',
			null,
			array(
				'tablet' => $device( 1024, 2, 16, 0.85, false ),
				'mobile' => $device( 767, 1, 12, 0.7, true ),
			),
		);
	}

	/**
	 * One slide.
	 *
	 * @return array
	 */
	public static function slide_spec() {
		return array(
			'object',
			null,
			array(
				'id'          => array( 'key', '' ),
				'type'        => array( 'enum', 'image', self::SLIDE_TYPES ),
				'hidden'      => array( 'bool', false ),
				'title'       => array( 'text', '' ),
				'alt'         => array( 'text', '' ),
				'background'  => array(
					'object',
					null,
					array(
						'type'      => array( 'enum', 'image', array( 'image', 'color', 'gradient', 'video', 'none' ) ),
						'image_id'  => array( 'id', 0 ),
						'focal'     => array(
							'object',
							null,
							array(
								'x' => array( 'float', 0.5, array( 0, 1 ) ),
								'y' => array( 'float', 0.5, array( 0, 1 ) ),
							),
						),
						'fit'       => array( 'enum', 'cover', array( 'cover', 'contain' ) ),
						'color'     => array( 'color', '#111827' ),
						'gradient'  => array(
							'object',
							null,
							array(
								'from'  => array( 'color', '#3858e9' ),
								'to'    => array( 'color', '#9b51e0' ),
								'angle' => array( 'int', 135, array( 0, 360 ) ),
							),
						),
						'ken_burns' => array( 'bool', false ),
						'overlay'   => array(
							'object',
							null,
							array(
								'color'   => array( 'color', '#000000' ),
								'opacity' => array( 'float', 0, array( 0, 0.95 ) ),
							),
						),
						'video'     => array(
							'object',
							null,
							array(
								'source'    => array( 'enum', 'youtube', array( 'youtube', 'vimeo', 'file' ) ),
								'url'       => array( 'url', '' ),
								'file_id'   => array( 'id', 0 ),
								'poster_id' => array( 'id', 0 ),
								'muted'     => array( 'bool', true ),
								'loop'      => array( 'bool', true ),
								'controls'  => array( 'bool', false ),
							),
						),
					),
				),
				'link'        => array(
					'object',
					null,
					array(
						'url'     => array( 'url', '' ),
						'new_tab' => array( 'bool', false ),
					),
				),
				'layers'      => array( 'list', array(), array( self::layer_spec(), self::MAX_LAYERS ) ),
				'testimonial' => array(
					'object',
					null,
					array(
						'quote'     => array( 'textarea', '' ),
						'name'      => array( 'text', '' ),
						'role'      => array( 'text', '' ),
						'avatar_id' => array( 'id', 0 ),
						'rating'    => array( 'int', 5, array( 0, 5 ) ),
					),
				),
				'source'      => array(
					'object',
					null,
					array(
						'post_type'     => array( 'key', 'post' ),
						'taxonomy'      => array( 'key', '' ),
						'terms'         => array( 'ids', array(), 50 ),
						'orderby'       => array( 'enum', 'date', array( 'date', 'modified', 'title', 'menu_order', 'rand', 'comment_count', 'price', 'popularity' ) ),
						'order'         => array( 'enum', 'DESC', array( 'ASC', 'DESC' ) ),
						'count'         => array( 'int', 6, array( 1, 30 ) ),
						'offset'        => array( 'int', 0, array( 0, 100 ) ),
						'featured_only' => array( 'bool', false ),
						'sticky_first'  => array( 'bool', false ),
						'show'          => array( 'flags', array( 'title', 'excerpt', 'button' ), array( 'title', 'excerpt', 'date', 'author', 'price', 'rating', 'button' ) ),
						'button_label'  => array( 'text', '' ),
					),
				),
			),
		);
	}

	/**
	 * One layer on a slide.
	 *
	 * @return array
	 */
	public static function layer_spec() {
		return array(
			'object',
			null,
			array(
				'id'        => array( 'key', '' ),
				'type'      => array( 'enum', 'heading', self::LAYER_TYPES ),
				'text'      => array( 'inline', '' ),
				'level'     => array( 'enum', 'h2', array( 'h1', 'h2', 'h3', 'h4', 'p' ) ),
				'size'      => array( 'enum', 'l', array( 's', 'm', 'l', 'xl', 'xxl' ) ),
				'url'       => array( 'url', '' ),
				'new_tab'   => array( 'bool', false ),
				'variant'   => array( 'enum', 'primary', array( 'primary', 'secondary', 'outline', 'link' ) ),
				'image_id'  => array( 'id', 0 ),
				'width'     => array( 'int', 160, array( 16, 1200 ) ),
				'date'      => array( 'date', '' ),
				'position'  => array(
					'object',
					null,
					array(
						'anchor' => array( 'enum', 'center', self::ANCHORS ),
						'x'      => array( 'int', 0, array( -50, 50 ) ),
						'y'      => array( 'int', 0, array( -50, 50 ) ),
					),
				),
				'max_width' => array( 'int', 720, array( 80, 1600 ) ),
				'align'     => array( 'enum', 'center', array( 'left', 'center', 'right' ) ),
				'color'     => array( 'color', '' ),
				'animation' => array(
					'object',
					null,
					array(
						'in'       => array( 'enum', 'fade-up', self::ANIMATIONS ),
						'delay'    => array( 'int', 0, array( 0, 5000 ) ),
						'duration' => array( 'int', 700, array( 100, 4000 ) ),
					),
				),
				'hide_on'   => array( 'flags', array(), self::DEVICES ),
			),
		);
	}

	/**
	 * Default configuration for a new slider.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array( 'schema_version' => self::VERSION ) + Spec::default_value( self::spec() );
	}

	/**
	 * A new slide of a type with its defaults.
	 *
	 * @param string $type Slide type.
	 * @return array
	 */
	public static function new_slide( $type = 'image' ) {
		$slide         = Spec::default_value( self::slide_spec() );
		$slide['id']   = self::new_id();
		$slide['type'] = in_array( $type, self::SLIDE_TYPES, true ) ? $type : 'image';
		return $slide;
	}

	/**
	 * A new layer of a type with its defaults.
	 *
	 * @param string $type Layer type.
	 * @param array  $data Values to set.
	 * @return array
	 */
	public static function new_layer( $type = 'heading', array $data = array() ) {
		$layer         = Spec::default_value( self::layer_spec() );
		$layer['id']   = self::new_id();
		$layer['type'] = in_array( $type, self::LAYER_TYPES, true ) ? $type : 'heading';
		return Spec::sanitize( array_replace_recursive( $layer, $data ), self::layer_spec() );
	}

	/**
	 * Bring stored configuration up to the current version, then sanitize it.
	 *
	 * @param mixed $config Stored or submitted configuration.
	 * @return array
	 */
	public static function normalize( $config ) {
		return self::sanitize( self::migrate( is_array( $config ) ? $config : array() ) );
	}

	/**
	 * Upgrade older configurations. Version 1 is the first, so nothing to do yet.
	 *
	 * @param array $config Configuration.
	 * @return array
	 */
	public static function migrate( array $config ) {
		$config['schema_version'] = self::VERSION;
		return $config;
	}

	/**
	 * Sanitize a configuration and make sure every slide and layer has a unique ID.
	 *
	 * @param array $config Configuration.
	 * @return array
	 */
	public static function sanitize( array $config ) {
		$out  = array( 'schema_version' => self::VERSION ) + Spec::sanitize( $config, self::spec() );
		$seen = array();
		foreach ( $out['slides'] as $i => $slide ) {
			$out['slides'][ $i ]['id'] = self::unique_id( $slide['id'], $seen );
			foreach ( $slide['layers'] as $j => $layer ) {
				$out['slides'][ $i ]['layers'][ $j ]['id'] = self::unique_id( $layer['id'], $seen );
			}
		}
		if ( $out['settings']['start'] >= max( 1, count( $out['slides'] ) ) ) {
			$out['settings']['start'] = 0;
		}
		return $out;
	}

	/**
	 * A short random ID for slides and layers.
	 *
	 * @return string
	 */
	public static function new_id() {
		return strtolower( wp_generate_password( 8, false, false ) );
	}

	/**
	 * Keep an ID if it is set and unused, otherwise make a new one.
	 *
	 * @param string $id   Candidate.
	 * @param array  $seen IDs used so far (updated).
	 * @return string
	 */
	private static function unique_id( $id, array &$seen ) {
		while ( '' === $id || isset( $seen[ $id ] ) ) {
			$id = self::new_id();
		}
		$seen[ $id ] = true;
		return $id;
	}
}
