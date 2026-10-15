<?php
/**
 * Turns a slider into HTML.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Render;

use SlideCraft\Sliders\Slider;
use SlideCraft\Sliders\Spec;

/**
 * Renders semantic, accessible markup. Before the script runs, CSS shows the slides as a scroll-snap
 * strip with the height already reserved, so there is no layout shift and the slider works without JS.
 */
final class Renderer {

	/**
	 * Sliders rendered on this request (for unique element IDs and the LCP hint).
	 *
	 * @var int
	 */
	private static $count = 0;

	/**
	 * Swiper modules needed by the sliders on this page.
	 *
	 * @var string[]
	 */
	private static $modules = array();

	/**
	 * Render a slider.
	 *
	 * @param Slider $slider    Slider.
	 * @param array  $overrides Per-placement settings: align (''|wide|full), class, autoplay (bool|null), height_px.
	 * @return string
	 */
	public static function render( Slider $slider, array $overrides = array() ) {
		$config = $slider->config;
		$slides = Dynamic_Source::expand( $slider->visible_slides() );
		if ( ! $slides ) {
			return '';
		}
		$settings = self::apply_overrides( $config['settings'], $overrides );
		if ( $settings['random'] ) {
			shuffle( $slides );
		}

		++self::$count;
		$dom_id        = 'slidecraft-' . $slider->id . '-' . self::$count;
		$total         = count( $slides );
		$label         = '' !== $settings['label'] ? $settings['label'] : $slider->title;
		$runtime       = self::runtime_config( $settings, $config['breakpoints'], $total );
		self::$modules = array_values( array_unique( array_merge( self::$modules, $runtime['modules'] ) ) );

		$classes = array(
			'slidecraft',
			'slidecraft--' . $settings['type'],
			'slidecraft--effect-' . $settings['effect'],
			'slidecraft--height-' . $settings['height']['mode'],
			'slidecraft--arrows-' . $settings['navigation']['arrow_style'],
			'slidecraft--arrows-' . $settings['navigation']['arrow_position'],
			'slidecraft--captions-' . $settings['style']['caption_style'],
		);
		if ( 'vertical' === $settings['direction'] ) {
			$classes[] = 'slidecraft--vertical';
		}
		if ( $settings['navigation']['arrows_on_hover'] ) {
			$classes[] = 'slidecraft--arrows-hover';
		}
		if ( $settings['style']['shadow'] ) {
			$classes[] = 'slidecraft--shadow';
		}
		if ( $settings['style']['gray_logos'] ) {
			$classes[] = 'slidecraft--gray-logos';
		}
		if ( 'theme' !== $settings['style']['font'] ) {
			$classes[] = 'slidecraft--font-' . $settings['style']['font'];
		}
		$align = isset( $overrides['align'] ) ? $overrides['align'] : ( 'content' !== $settings['width'] ? $settings['width'] : '' );
		if ( in_array( $align, array( 'wide', 'full' ), true ) ) {
			$classes[] = 'align' . $align;
		}
		if ( ! empty( $overrides['class'] ) ) {
			$classes[] = sanitize_html_class( $overrides['class'] );
		}

		$html  = '<div id="' . esc_attr( $dom_id ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		$html .= ' style="' . esc_attr( self::css_vars( $settings, $slides ) ) . '"';
		$html .= ' role="region" aria-roledescription="' . esc_attr__( 'carousel', 'wp-smart-flexslider' ) . '" aria-label="' . esc_attr( $label ) . '"';
		$html .= ' data-slidecraft="' . esc_attr( wp_json_encode( $runtime ) ) . '">';
		$html .= self::responsive_css( $dom_id, $config['breakpoints'] );
		$html .= '<div class="swiper slidecraft__viewport"><div class="swiper-wrapper slidecraft__track" aria-live="' . ( $runtime['autoplay'] ? 'off' : 'polite' ) . '">';
		foreach ( $slides as $index => $slide ) {
			$html .= self::slide( $slide, $index, $total, $settings );
		}
		$html .= '</div></div>';
		$html .= self::controls( $settings, $total );
		$html .= '</div>';

		/**
		 * Filters the rendered slider HTML.
		 *
		 * @param string $html   HTML.
		 * @param Slider $slider Slider.
		 */
		return (string) apply_filters( 'slidecraft_slider_html', $html, $slider );
	}

	/**
	 * Swiper modules needed by sliders rendered so far.
	 *
	 * @return string[]
	 */
	public static function modules() {
		return self::$modules;
	}

	/**
	 * Number of sliders rendered so far.
	 *
	 * @return int
	 */
	public static function count() {
		return self::$count;
	}

	/**
	 * Placement overrides from the block or shortcode.
	 *
	 * @param array $settings  Slider settings.
	 * @param array $overrides Overrides.
	 * @return array
	 */
	private static function apply_overrides( array $settings, array $overrides ) {
		if ( isset( $overrides['autoplay'] ) && null !== $overrides['autoplay'] ) {
			$settings['autoplay']['enabled'] = (bool) $overrides['autoplay'];
		}
		if ( ! empty( $overrides['height_px'] ) ) {
			$settings['height']['mode'] = 'fixed';
			$settings['height']['px']   = max( 120, min( 2000, (int) $overrides['height_px'] ) );
		}
		return $settings;
	}

	/**
	 * Settings the front-end runtime needs.
	 *
	 * @param array $settings    Slider settings.
	 * @param array $breakpoints Breakpoints.
	 * @param int   $total       Number of slides.
	 * @return array
	 */
	public static function runtime_config( array $settings, array $breakpoints, $total ) {
		$nav      = $settings['navigation'];
		$autoplay = $settings['autoplay']['enabled'] && $total > 1;
		$modules  = array( 'a11y' );
		if ( $nav['arrows'] ) {
			$modules[] = 'navigation';
		}
		if ( 'none' !== $nav['pagination'] && 'thumbnails' !== $nav['pagination'] ) {
			$modules[] = 'pagination';
		}
		if ( 'thumbnails' === $nav['pagination'] || 'gallery' === $settings['type'] ) {
			$modules[] = 'thumbs';
		}
		if ( $autoplay || 'ticker' === $settings['type'] ) {
			$modules[] = 'autoplay';
		}
		if ( $nav['keyboard'] ) {
			$modules[] = 'keyboard';
		}
		if ( $nav['mousewheel'] ) {
			$modules[] = 'mousewheel';
		}
		$carousel = in_array( $settings['type'], array( 'carousel', 'ticker' ), true );
		if ( $carousel && ( $settings['carousel']['free_mode'] || 'ticker' === $settings['type'] ) ) {
			$modules[] = 'free-mode';
		}
		if ( $carousel && $settings['carousel']['rows'] > 1 ) {
			$modules[] = 'grid';
		}
		// Carousels, galleries and tickers always slide; effects apply to one-at-a-time sliders and coverflow.
		$effect = $settings['effect'];
		if ( 'ticker' === $settings['type'] || ( $carousel && 'coverflow' !== $effect ) ) {
			$effect = 'slide';
		}
		if ( 'slide' !== $effect ) {
			$modules[] = 'effect-' . $effect;
		}

		return array(
			'type'        => $settings['type'],
			'effect'      => $effect,
			'creative'    => $settings['creative'],
			'height'      => $settings['height']['mode'],
			'direction'   => $settings['direction'],
			'speed'       => $settings['speed'],
			'loop'        => $settings['loop'] && $total > 1,
			'start'       => min( $settings['start'], max( 0, $total - 1 ) ),
			'autoplay'    => $autoplay ? array(
				'delay'        => $settings['autoplay']['delay'],
				'pauseOnHover' => $settings['autoplay']['pause_on_hover'],
				'progress'     => $settings['autoplay']['progress'],
			) : false,
			'nav'         => array(
				'arrows'     => $nav['arrows'] && $total > 1,
				'pagination' => $total > 1 ? $nav['pagination'] : 'none',
				'keyboard'   => $nav['keyboard'],
				'mousewheel' => $nav['mousewheel'],
				'draggable'  => $nav['draggable'],
			),
			'carousel'    => $settings['carousel'],
			'ticker'      => $settings['ticker'],
			'breakpoints' => array(
				'tablet' => array(
					'max'     => $breakpoints['tablet']['max'],
					'perView' => $breakpoints['tablet']['per_view'],
					'gap'     => $breakpoints['tablet']['gap'],
				),
				'mobile' => array(
					'max'     => $breakpoints['mobile']['max'],
					'perView' => $breakpoints['mobile']['per_view'],
					'gap'     => $breakpoints['mobile']['gap'],
				),
			),
			'lightbox'    => $settings['lightbox'],
			'modules'     => array_values( array_unique( $modules ) ),
			'i18n'        => array(
				'prev'    => __( 'Previous slide', 'wp-smart-flexslider' ),
				'next'    => __( 'Next slide', 'wp-smart-flexslider' ),
				'first'   => __( 'This is the first slide', 'wp-smart-flexslider' ),
				'last'    => __( 'This is the last slide', 'wp-smart-flexslider' ),
				/* translators: The placeholder in double braces is replaced with the slide number. */
				'goto'    => __( 'Go to slide {{index}}', 'wp-smart-flexslider' ),
				/* translators: Placeholders in double braces are replaced with the slide number and the number of slides. */
				'slideOf' => __( '{{index}} of {{slidesLength}}', 'wp-smart-flexslider' ),
				'pause'   => __( 'Pause slideshow', 'wp-smart-flexslider' ),
				'play'    => __( 'Play slideshow', 'wp-smart-flexslider' ),
				'close'   => __( 'Close', 'wp-smart-flexslider' ),
				'days'    => __( 'days', 'wp-smart-flexslider' ),
				'hours'   => __( 'hours', 'wp-smart-flexslider' ),
				'minutes' => __( 'minutes', 'wp-smart-flexslider' ),
				'seconds' => __( 'seconds', 'wp-smart-flexslider' ),
			),
		);
	}

	/**
	 * CSS custom properties on the slider element.
	 *
	 * @param array   $settings Slider settings.
	 * @param array[] $slides   Slides.
	 * @return string
	 */
	private static function css_vars( array $settings, array $slides ) {
		$style = $settings['style'];
		$vars  = array(
			'--sc-accent'   => $style['accent'],
			'--sc-text'     => $style['text_color'],
			'--sc-arrow'    => $style['arrow_color'],
			'--sc-arrow-bg' => $style['arrow_bg'],
			'--sc-radius'   => $style['radius'] . 'px',
			'--sc-speed'    => $settings['speed'] . 'ms',
			'--sc-per-view' => (string) $settings['carousel']['per_view'],
			'--sc-gap'      => $settings['carousel']['gap'] . 'px',
		);
		if ( $settings['autoplay']['enabled'] ) {
			$vars['--sc-delay'] = $settings['autoplay']['delay'] . 'ms';
		}
		$height = $settings['height'];
		switch ( $height['mode'] ) {
			case 'fixed':
				$vars['--sc-height'] = $height['px'] . 'px';
				break;
			case 'viewport':
				$vars['--sc-height'] = $height['vh'] . 'vh';
				break;
			case 'auto':
				$vars['--sc-ratio'] = self::first_image_ratio( $slides );
				break;
			default:
				$vars['--sc-ratio'] = str_replace( ':', ' / ', $height['ratio'] );
		}
		$out = '';
		foreach ( $vars as $name => $value ) {
			$out .= $name . ':' . $value . ';';
		}
		return $out;
	}

	/**
	 * Aspect ratio of the first slide image, for the "auto" height mode (reserves space before images load).
	 *
	 * @param array[] $slides Slides.
	 * @return string
	 */
	private static function first_image_ratio( array $slides ) {
		foreach ( $slides as $slide ) {
			$id = $slide['background']['image_id'];
			if ( ! $id ) {
				continue;
			}
			$meta = wp_get_attachment_metadata( $id );
			if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
				return (int) $meta['width'] . ' / ' . (int) $meta['height'];
			}
		}
		// No image to measure: let the slides set the height (testimonials, text slides).
		return 'auto';
	}

	/**
	 * Tablet and mobile CSS: height, type scale and hidden arrows.
	 *
	 * @param string $dom_id      Element ID.
	 * @param array  $breakpoints Breakpoints.
	 * @return string
	 */
	private static function responsive_css( $dom_id, array $breakpoints ) {
		$css = '';
		foreach ( array( 'tablet', 'mobile' ) as $device ) {
			$bp    = $breakpoints[ $device ];
			$rules = '--sc-scale:' . $bp['font_scale'] . ';--sc-per-view:' . $bp['per_view'] . ';--sc-gap:' . $bp['gap'] . 'px;';
			if ( $bp['height_px'] ) {
				$rules .= '--sc-height:' . $bp['height_px'] . 'px;';
			}
			$css .= '@media (max-width:' . (int) $bp['max'] . 'px){#' . $dom_id . '{' . $rules . '}';
			if ( $bp['hide_arrows'] ) {
				$css .= '#' . $dom_id . ' .slidecraft__arrow{display:none}';
			}
			$css .= '#' . $dom_id . ' .sc-hide-' . $device . '{display:none!important}}';
		}
		// Desktop-only hiding applies above the tablet breakpoint.
		$css .= '@media (min-width:' . ( (int) $breakpoints['tablet']['max'] + 1 ) . 'px){#' . $dom_id . ' .sc-hide-desktop{display:none!important}}';
		return '<style>' . $css . '</style>';
	}

	/**
	 * One slide.
	 *
	 * @param array $slide    Slide.
	 * @param int   $index    Zero-based index.
	 * @param int   $total    Number of slides.
	 * @param array $settings Slider settings.
	 * @return string
	 */
	private static function slide( array $slide, $index, $total, array $settings ) {
		/* translators: 1: slide number, 2: number of slides */
		$label   = sprintf( __( '%1$d of %2$d', 'wp-smart-flexslider' ), $index + 1, $total );
		$classes = 'swiper-slide slidecraft-slide slidecraft-slide--' . $slide['type'];
		$eager   = 1 === self::$count && $index === $settings['start'];
		$html    = '<div class="' . esc_attr( $classes ) . '" role="group" aria-roledescription="' . esc_attr__( 'slide', 'wp-smart-flexslider' ) . '" aria-label="' . esc_attr( $label ) . '" data-slide-id="' . esc_attr( $slide['id'] ) . '">';

		switch ( $slide['type'] ) {
			case 'testimonial':
				$html .= self::testimonial( $slide );
				break;
			case 'logo':
				$html .= self::logo( $slide, $eager );
				break;
			default:
				$html .= self::background( $slide, $eager, $settings );
				$html .= self::layers( $slide['layers'] );
				$html .= self::caption( $slide, $settings );
		}

		if ( 'logo' !== $slide['type'] && '' !== $slide['link']['url'] ) {
			$html .= '<a class="slidecraft-slide__link" href="' . esc_url( $slide['link']['url'] ) . '"' . self::target( $slide['link']['new_tab'] ) . ' aria-label="' . esc_attr( '' !== $slide['title'] ? $slide['title'] : $slide['alt'] ) . '"></a>';
		}
		return $html . '</div>';
	}

	/**
	 * Background: image, colour, gradient or video, plus the overlay.
	 *
	 * @param array $slide    Slide.
	 * @param bool  $eager    Load the image eagerly (LCP candidate).
	 * @param array $settings Slider settings.
	 * @return string
	 */
	private static function background( array $slide, $eager, array $settings ) {
		$bg    = $slide['background'];
		$type  = 'video' === $slide['type'] ? 'video' : $bg['type'];
		$style = '';
		$inner = '';

		switch ( $type ) {
			case 'image':
				$inner = self::image(
					$bg['image_id'],
					'' !== $slide['alt'] ? $slide['alt'] : $slide['title'],
					$eager,
					array(
						'class' => 'slidecraft-slide__image' . ( $bg['ken_burns'] ? ' sc-kenburns' : '' ) . ( 'contain' === $bg['fit'] ? ' sc-contain' : '' ),
						'style' => 'object-position:' . round( $bg['focal']['x'] * 100, 1 ) . '% ' . round( $bg['focal']['y'] * 100, 1 ) . '%',
					),
					self::sizes( $settings )
				);
				if ( '' === $inner ) {
					// No image chosen yet: show the slide colour instead of an empty box.
					$style = 'background:' . $bg['color'];
				}
				break;
			case 'color':
				$style = 'background:' . $bg['color'];
				break;
			case 'gradient':
				$style = 'background:linear-gradient(' . (int) $bg['gradient']['angle'] . 'deg,' . $bg['gradient']['from'] . ',' . $bg['gradient']['to'] . ')';
				break;
			case 'video':
				$inner = self::video( $bg['video'], $eager, $settings );
				break;
		}

		$html = '<div class="slidecraft-slide__bg"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>' . $inner . '</div>';
		if ( $bg['overlay']['opacity'] > 0 ) {
			$html .= '<div class="slidecraft-slide__overlay" style="' . esc_attr( 'background:' . $bg['overlay']['color'] . ';opacity:' . $bg['overlay']['opacity'] ) . '"></div>';
		}
		return $html;
	}

	/**
	 * An attachment image with srcset, lazy loading and an optional LCP hint.
	 *
	 * @param int    $id    Attachment ID.
	 * @param string $alt   Alt text (falls back to the attachment's alt).
	 * @param bool   $eager Eager load with high fetch priority.
	 * @param array  $attr  Extra attributes.
	 * @param string $sizes sizes attribute.
	 * @return string
	 */
	private static function image( $id, $alt, $eager, array $attr = array(), $sizes = '100vw' ) {
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return '';
		}
		if ( '' === $alt ) {
			$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		}
		$attr += array(
			'alt'      => $alt,
			'sizes'    => $sizes,
			'loading'  => $eager ? 'eager' : 'lazy',
			'decoding' => $eager ? 'sync' : 'async',
		);
		if ( $eager ) {
			$attr['fetchpriority'] = 'high';
		}
		return wp_get_attachment_image( $id, 'full', false, $attr );
	}

	/**
	 * The sizes attribute for slide images.
	 *
	 * @param array $settings Slider settings.
	 * @return string
	 */
	private static function sizes( array $settings ) {
		if ( 'carousel' === $settings['type'] && $settings['carousel']['per_view'] > 1 ) {
			return '(max-width: 767px) 100vw, ' . (int) ceil( 100 / $settings['carousel']['per_view'] ) . 'vw';
		}
		return 'full' === $settings['width'] ? '100vw' : '(max-width: 1280px) 100vw, 1280px';
	}

	/**
	 * Video background. YouTube and Vimeo load only when the slide becomes active (the runtime swaps in the
	 * iframe); until then a poster image is shown.
	 *
	 * @param array $video    Video settings.
	 * @param bool  $eager    First visible slide.
	 * @param array $settings Slider settings.
	 * @return string
	 */
	private static function video( array $video, $eager, array $settings ) {
		$poster = self::image( $video['poster_id'], '', $eager, array( 'class' => 'slidecraft-slide__image slidecraft-slide__poster' ), self::sizes( $settings ) );
		if ( 'file' === $video['source'] ) {
			$src = $video['file_id'] ? wp_get_attachment_url( $video['file_id'] ) : $video['url'];
			if ( ! $src ) {
				return $poster;
			}
			$poster_url = $video['poster_id'] ? wp_get_attachment_image_url( $video['poster_id'], 'full' ) : '';
			return '<video class="slidecraft-slide__video" playsinline preload="none"'
				. ( $video['muted'] ? ' muted' : '' )
				. ( $video['loop'] ? ' loop' : '' )
				. ( $video['controls'] ? ' controls' : '' )
				. ( $poster_url ? ' poster="' . esc_url( $poster_url ) . '"' : '' )
				. ' data-src="' . esc_url( $src ) . '"></video>';
		}
		$embed = self::embed_url( $video );
		if ( ! $embed ) {
			return $poster;
		}
		return $poster . '<div class="slidecraft-slide__embed" data-embed="' . esc_url( $embed ) . '"' . ( $video['controls'] ? ' data-controls' : '' ) . ' data-title="' . esc_attr__( 'Video', 'wp-smart-flexslider' ) . '"></div>';
	}

	/**
	 * Privacy-friendly embed URL for YouTube or Vimeo.
	 *
	 * @param array $video Video settings.
	 * @return string
	 */
	public static function embed_url( array $video ) {
		$url = $video['url'];
		if ( 'youtube' === $video['source'] && preg_match( '~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
			$params = array(
				'autoplay'       => 1,
				'mute'           => $video['muted'] ? 1 : 0,
				'controls'       => $video['controls'] ? 1 : 0,
				'loop'           => $video['loop'] ? 1 : 0,
				'playlist'       => $m[1],
				'playsinline'    => 1,
				'rel'            => 0,
				'modestbranding' => 1,
			);
			return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?' . http_build_query( $params );
		}
		if ( 'vimeo' === $video['source'] && preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
			$params = array(
				'autoplay'   => 1,
				'muted'      => $video['muted'] ? 1 : 0,
				'loop'       => $video['loop'] ? 1 : 0,
				'background' => $video['controls'] ? 0 : 1,
				'dnt'        => 1,
			);
			return 'https://player.vimeo.com/video/' . $m[1] . '?' . http_build_query( $params );
		}
		return '';
	}

	/**
	 * Layers, grouped by anchor so layers that share an anchor stack in a column.
	 *
	 * @param array[] $layers Layers.
	 * @return string
	 */
	private static function layers( array $layers ) {
		if ( ! $layers ) {
			return '';
		}
		$groups = array();
		foreach ( $layers as $layer ) {
			$groups[ $layer['position']['anchor'] ][] = $layer;
		}
		$html = '<div class="slidecraft-slide__layers">';
		foreach ( $groups as $anchor => $items ) {
			$html .= '<div class="slidecraft-group slidecraft-group--' . esc_attr( $anchor ) . '">';
			foreach ( $items as $layer ) {
				$html .= self::layer( $layer );
			}
			$html .= '</div>';
		}
		return $html . '</div>';
	}

	/**
	 * One layer.
	 *
	 * @param array $layer Layer.
	 * @return string
	 */
	public static function layer( array $layer ) {
		$classes = array( 'slidecraft-layer', 'slidecraft-layer--' . $layer['type'], 'sc-align-' . $layer['align'] );
		foreach ( $layer['hide_on'] as $device ) {
			$classes[] = 'sc-hide-' . $device;
		}
		$style = '--sc-max-width:' . $layer['max_width'] . 'px;';
		if ( $layer['position']['x'] || $layer['position']['y'] ) {
			$style .= '--sc-x:' . $layer['position']['x'] . '%;--sc-y:' . $layer['position']['y'] . '%;';
		}
		if ( '' !== $layer['color'] ) {
			$style .= '--sc-layer-color:' . $layer['color'] . ';';
		}
		$anim = $layer['animation'];
		if ( 'none' !== $anim['in'] ) {
			$style .= '--sc-anim-delay:' . $anim['delay'] . 'ms;--sc-anim-duration:' . $anim['duration'] . 'ms;';
		}
		$attrs = ' class="%s" style="' . esc_attr( $style ) . '" data-layer-id="' . esc_attr( $layer['id'] ) . '"' . ( 'none' !== $anim['in'] ? ' data-anim="' . esc_attr( $anim['in'] ) . '"' : '' );

		switch ( $layer['type'] ) {
			case 'heading':
			case 'text':
				if ( '' === trim( $layer['text'] ) ) {
					return '';
				}
				$tag       = 'heading' === $layer['type'] ? $layer['level'] : 'p';
				$classes[] = 'sc-size-' . $layer['size'];
				return '<' . $tag . sprintf( $attrs, esc_attr( implode( ' ', $classes ) ) ) . '>' . wp_kses( $layer['text'], Spec::inline_tags() ) . '</' . $tag . '>';
			case 'button':
				if ( '' === trim( $layer['text'] ) ) {
					return '';
				}
				$classes[] = 'slidecraft-btn';
				$classes[] = 'slidecraft-btn--' . $layer['variant'];
				$href      = '' !== $layer['url'] ? $layer['url'] : '#';
				return '<a href="' . esc_url( $href ) . '"' . self::target( $layer['new_tab'] ) . sprintf( $attrs, esc_attr( implode( ' ', $classes ) ) ) . '>' . wp_kses( $layer['text'], Spec::inline_tags() ) . '</a>';
			case 'image':
				$img = self::image(
					$layer['image_id'],
					wp_strip_all_tags( $layer['text'] ),
					false,
					array( 'style' => 'width:' . (int) $layer['width'] . 'px' ),
					(int) $layer['width'] . 'px'
				);
				if ( '' === $img ) {
					return '';
				}
				if ( '' !== $layer['url'] ) {
					$img = '<a href="' . esc_url( $layer['url'] ) . '"' . self::target( $layer['new_tab'] ) . '>' . $img . '</a>';
				}
				return '<div' . sprintf( $attrs, esc_attr( implode( ' ', $classes ) ) ) . '>' . $img . '</div>';
			case 'countdown':
				if ( '' === $layer['date'] ) {
					return '';
				}
				$time = strtotime( get_gmt_from_date( str_replace( 'T', ' ', $layer['date'] ) . ( strlen( $layer['date'] ) > 10 ? ':00' : ' 00:00:00' ) ) . ' UTC' );
				return '<div' . sprintf( $attrs, esc_attr( implode( ' ', $classes ) . ' sc-size-' . $layer['size'] ) ) . ' data-countdown="' . esc_attr( (string) $time ) . '"><time datetime="' . esc_attr( gmdate( 'c', $time ) ) . '">' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time ) ) . '</time></div>';
		}
		return '';
	}

	/**
	 * Caption below the image for the "below" caption style (galleries).
	 *
	 * @param array $slide    Slide.
	 * @param array $settings Slider settings.
	 * @return string
	 */
	private static function caption( array $slide, array $settings ) {
		if ( 'below' !== $settings['style']['caption_style'] || '' === $slide['title'] ) {
			return '';
		}
		return '<div class="slidecraft-slide__caption">' . esc_html( $slide['title'] ) . '</div>';
	}

	/**
	 * Testimonial card.
	 *
	 * @param array $slide Slide.
	 * @return string
	 */
	private static function testimonial( array $slide ) {
		$t    = $slide['testimonial'];
		$html = '<figure class="slidecraft-quote">';
		if ( $t['rating'] > 0 ) {
			/* translators: %d: rating out of 5 */
			$html .= '<div class="slidecraft-quote__stars" role="img" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'wp-smart-flexslider' ), $t['rating'] ) ) . '">' . str_repeat( '<span aria-hidden="true">★</span>', $t['rating'] ) . str_repeat( '<span class="is-empty" aria-hidden="true">★</span>', 5 - $t['rating'] ) . '</div>';
		}
		$html .= '<blockquote class="slidecraft-quote__text"><p>' . nl2br( esc_html( $t['quote'] ) ) . '</p></blockquote>';
		$html .= '<figcaption class="slidecraft-quote__author">';
		if ( $t['avatar_id'] ) {
			$html .= self::image( $t['avatar_id'], $t['name'], false, array( 'class' => 'slidecraft-quote__avatar' ), '64px' );
		}
		$html .= '<span><strong>' . esc_html( $t['name'] ) . '</strong>';
		if ( '' !== $t['role'] ) {
			$html .= '<small>' . esc_html( $t['role'] ) . '</small>';
		}
		return $html . '</span></figcaption></figure>';
	}

	/**
	 * Logo cell.
	 *
	 * @param array $slide Slide.
	 * @param bool  $eager Eager loading.
	 * @return string
	 */
	private static function logo( array $slide, $eager ) {
		$img = self::image( $slide['background']['image_id'], '' !== $slide['alt'] ? $slide['alt'] : $slide['title'], $eager, array( 'class' => 'slidecraft-logo' ), '240px' );
		if ( '' !== $slide['link']['url'] && '' !== $img ) {
			$img = '<a href="' . esc_url( $slide['link']['url'] ) . '"' . self::target( $slide['link']['new_tab'] ) . '>' . $img . '</a>';
		}
		return '<div class="slidecraft-logo-cell">' . $img . '</div>';
	}

	/**
	 * Arrows, pagination, pause button and progress bar.
	 *
	 * @param array $settings Slider settings.
	 * @param int   $total    Number of slides.
	 * @return string
	 */
	private static function controls( array $settings, $total ) {
		if ( $total < 2 || 'ticker' === $settings['type'] ) {
			return '';
		}
		$html = '';
		if ( $settings['navigation']['arrows'] ) {
			$html .= '<button type="button" class="slidecraft__arrow slidecraft__arrow--prev" aria-label="' . esc_attr__( 'Previous slide', 'wp-smart-flexslider' ) . '">' . self::icon( 'prev' ) . '</button>';
			$html .= '<button type="button" class="slidecraft__arrow slidecraft__arrow--next" aria-label="' . esc_attr__( 'Next slide', 'wp-smart-flexslider' ) . '">' . self::icon( 'next' ) . '</button>';
		}
		$pagination = $settings['navigation']['pagination'];
		if ( 'none' !== $pagination && 'thumbnails' !== $pagination ) {
			$html .= '<div class="slidecraft__pagination slidecraft__pagination--' . esc_attr( $pagination ) . '"></div>';
		}
		if ( $settings['autoplay']['enabled'] ) {
			// WCAG 2.2.2: moving content must have a pause control.
			$html .= '<button type="button" class="slidecraft__pause" aria-label="' . esc_attr__( 'Pause slideshow', 'wp-smart-flexslider' ) . '" aria-pressed="false">' . self::icon( 'pause' ) . self::icon( 'play' ) . '</button>';
			if ( $settings['autoplay']['progress'] ) {
				$html .= '<div class="slidecraft__progress" aria-hidden="true"><span></span></div>';
			}
		}
		return $html;
	}

	/**
	 * Inline SVG icons.
	 *
	 * @param string $name prev, next, pause or play.
	 * @return string
	 */
	private static function icon( $name ) {
		$paths = array(
			'prev'  => '<path d="M15 5l-7 7 7 7" />',
			'next'  => '<path d="M9 5l7 7-7 7" />',
			'pause' => '<path d="M9 6v12M15 6v12" />',
			'play'  => '<path d="M8 5l11 7-11 7z" />',
		);
		return '<svg class="sc-icon sc-icon--' . $name . '" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' . $paths[ $name ] . '</svg>';
	}

	/**
	 * The target and rel attributes for links.
	 *
	 * @param bool $new_tab Open in a new tab.
	 * @return string
	 */
	private static function target( $new_tab ) {
		return $new_tab ? ' target="_blank" rel="noopener"' : '';
	}

	/**
	 * Reset per-request state (tests).
	 */
	public static function reset() {
		self::$count   = 0;
		self::$modules = array();
	}
}
