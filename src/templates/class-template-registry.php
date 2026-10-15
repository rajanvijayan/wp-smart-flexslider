<?php
/**
 * Starter sliders for the "New slider" gallery.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Templates;

use SlideCraft\Sliders\Slider_Schema;

/**
 * Each template is a full slider configuration. Image slots are left empty and filled by the builder with
 * the site's own recent images when the template is used; until then gradients keep the slides readable.
 */
final class Template_Registry {

	/**
	 * All templates, keyed by slug.
	 *
	 * @return array[] { name, description, category, config }
	 */
	public static function all() {
		$templates = array(
			'hero-fade'           => array(
				'name'        => __( 'Hero Fade', 'wp-smart-flexslider' ),
				'description' => __( 'Full-width hero with fading slides, a slow zoom and a call to action.', 'wp-smart-flexslider' ),
				'category'    => 'hero',
				'config'      => self::hero_fade(),
			),
			'split-hero'          => array(
				'name'        => __( 'Split Hero', 'wp-smart-flexslider' ),
				'description' => __( 'Headline and buttons on the left, image on the right.', 'wp-smart-flexslider' ),
				'category'    => 'hero',
				'config'      => self::split_hero(),
			),
			'promo-countdown'     => array(
				'name'        => __( 'Promo Countdown', 'wp-smart-flexslider' ),
				'description' => __( 'Sale banner with a live countdown and a button.', 'wp-smart-flexslider' ),
				'category'    => 'hero',
				'config'      => self::promo_countdown(),
			),
			'fullscreen-video'    => array(
				'name'        => __( 'Fullscreen Video', 'wp-smart-flexslider' ),
				'description' => __( 'A muted background video with a headline on top.', 'wp-smart-flexslider' ),
				'category'    => 'hero',
				'config'      => self::fullscreen_video(),
			),
			'blog-carousel'       => array(
				'name'        => __( 'Blog Carousel', 'wp-smart-flexslider' ),
				'description' => __( 'Your latest posts as cards, three at a time.', 'wp-smart-flexslider' ),
				'category'    => 'carousel',
				'config'      => self::posts_carousel( 'post', 3 ),
			),
			'product-showcase'    => array(
				'name'        => __( 'Product Showcase', 'wp-smart-flexslider' ),
				'description' => __( 'WooCommerce products with price and a button.', 'wp-smart-flexslider' ),
				'category'    => 'carousel',
				'config'      => self::posts_carousel( 'product', 4 ),
			),
			'coverflow-portfolio' => array(
				'name'        => __( 'Coverflow Portfolio', 'wp-smart-flexslider' ),
				'description' => __( 'Centred 3D coverflow for portfolios and albums.', 'wp-smart-flexslider' ),
				'category'    => 'carousel',
				'config'      => self::coverflow(),
			),
			'cards-stack'         => array(
				'name'        => __( 'Cards Stack', 'wp-smart-flexslider' ),
				'description' => __( 'Swipeable stacked cards, great on mobile.', 'wp-smart-flexslider' ),
				'category'    => 'carousel',
				'config'      => self::cards(),
			),
			'testimonials'        => array(
				'name'        => __( 'Testimonials', 'wp-smart-flexslider' ),
				'description' => __( 'Customer quotes with photo, name and star rating.', 'wp-smart-flexslider' ),
				'category'    => 'content',
				'config'      => self::testimonials(),
			),
			'logo-ticker'         => array(
				'name'        => __( 'Logo Ticker', 'wp-smart-flexslider' ),
				'description' => __( 'A smooth, endless strip of client or partner logos.', 'wp-smart-flexslider' ),
				'category'    => 'content',
				'config'      => self::logo_ticker(),
			),
			'news-ticker'         => array(
				'name'        => __( 'News Ticker', 'wp-smart-flexslider' ),
				'description' => __( 'Scrolling headlines from your latest posts.', 'wp-smart-flexslider' ),
				'category'    => 'content',
				'config'      => self::news_ticker(),
			),
			'gallery-thumbs'      => array(
				'name'        => __( 'Gallery + Thumbnails', 'wp-smart-flexslider' ),
				'description' => __( 'Large images with a thumbnail strip and a lightbox.', 'wp-smart-flexslider' ),
				'category'    => 'gallery',
				'config'      => self::gallery(),
			),
		);

		/**
		 * Filters the starter templates.
		 *
		 * @param array[] $templates Templates keyed by slug: name, description, category, config.
		 */
		return apply_filters( 'slidecraft_templates', $templates );
	}

	/**
	 * Templates for the admin app, with normalized configurations and thumbnail URLs.
	 *
	 * @return array[]
	 */
	public static function for_js() {
		$out = array();
		foreach ( self::all() as $key => $template ) {
			$thumb = 'templates/thumbs/' . $key . '.jpg';
			$out[] = array(
				'key'         => $key,
				'name'        => $template['name'],
				'description' => $template['description'],
				'category'    => $template['category'],
				'thumb'       => file_exists( SLIDECRAFT_DIR . $thumb ) ? SLIDECRAFT_URL . $thumb : '',
				'config'      => Slider_Schema::normalize( $template['config'] ),
			);
		}
		return $out;
	}

	/**
	 * Base configuration with settings changes.
	 *
	 * @param array $settings Settings to merge.
	 * @param array $slides   Slides.
	 * @return array
	 */
	private static function config( array $settings, array $slides ) {
		$config             = Slider_Schema::defaults();
		$config['settings'] = array_replace_recursive( $config['settings'], $settings );
		$config['slides']   = $slides;
		return $config;
	}

	/**
	 * A content slide with a gradient (or image) background and layers.
	 *
	 * @param string $from   Gradient start.
	 * @param string $to     Gradient end.
	 * @param array  $layers Layers as [type, data] pairs.
	 * @param array  $extra  Slide fields to merge.
	 * @return array
	 */
	private static function content_slide( $from, $to, array $layers, array $extra = array() ) {
		$slide                           = Slider_Schema::new_slide( 'content' );
		$slide['background']['type']     = 'gradient';
		$slide['background']['gradient'] = array(
			'from'  => $from,
			'to'    => $to,
			'angle' => 135,
		);
		foreach ( $layers as $layer ) {
			$slide['layers'][] = Slider_Schema::new_layer( $layer[0], $layer[1] );
		}
		return array_replace_recursive( $slide, $extra );
	}

	/**
	 * Heading, text and button layers sharing an anchor and alignment.
	 *
	 * @param string $heading Heading.
	 * @param string $text    Text.
	 * @param string $button  Button label ('' for none).
	 * @param string $anchor  Anchor.
	 * @param string $align   Alignment.
	 * @return array
	 */
	private static function stack( $heading, $text, $button, $anchor = 'center', $align = 'center' ) {
		$pos    = array(
			'position' => array( 'anchor' => $anchor ),
			'align'    => $align,
		);
		$layers = array(
			array(
				'heading',
				$pos + array(
					'text'      => $heading,
					'level'     => 'h2',
					'size'      => 'xxl',
					'animation' => array( 'in' => 'fade-up' ),
				),
			),
			array(
				'text',
				$pos + array(
					'text'      => $text,
					'size'      => 'l',
					'animation' => array(
						'in'    => 'fade-up',
						'delay' => 150,
					),
				),
			),
		);
		if ( '' !== $button ) {
			$layers[] = array(
				'button',
				$pos + array(
					'text'      => $button,
					'url'       => home_url( '/' ),
					'animation' => array(
						'in'    => 'fade-up',
						'delay' => 300,
					),
				),
			);
		}
		return $layers;
	}

	/**
	 * Hero Fade.
	 *
	 * @return array
	 */
	private static function hero_fade() {
		$extra = array(
			'background' => array(
				'ken_burns' => true,
				'overlay'   => array( 'opacity' => 0.35 ),
			),
		);
		return self::config(
			array(
				'effect' => 'fade',
				'width'  => 'full',
				'height' => array(
					'mode' => 'viewport',
					'vh'   => 80,
				),
			),
			array(
				self::content_slide( '#1e3a8a', '#7c3aed', self::stack( __( 'Make something beautiful', 'wp-smart-flexslider' ), __( 'Tell visitors what you do in one short sentence.', 'wp-smart-flexslider' ), __( 'Get started', 'wp-smart-flexslider' ) ), $extra ),
				self::content_slide( '#0f766e', '#22d3ee', self::stack( __( 'Built for every screen', 'wp-smart-flexslider' ), __( 'Fast, responsive and accessible out of the box.', 'wp-smart-flexslider' ), __( 'Learn more', 'wp-smart-flexslider' ) ), $extra ),
				self::content_slide( '#9a3412', '#f59e0b', self::stack( __( 'Ready when you are', 'wp-smart-flexslider' ), __( 'Swap in your own photos and words in seconds.', 'wp-smart-flexslider' ), __( 'Contact us', 'wp-smart-flexslider' ) ), $extra ),
			)
		);
	}

	/**
	 * Split Hero.
	 *
	 * @return array
	 */
	private static function split_hero() {
		$extra = array(
			'background' => array(
				'focal'   => array( 'x' => 0.75 ),
				'overlay' => array( 'opacity' => 0.45 ),
			),
		);
		return self::config(
			array(
				'width'  => 'wide',
				'height' => array( 'ratio' => '21:9' ),
			),
			array(
				self::content_slide( '#111827', '#374151', self::stack( __( 'New season collection', 'wp-smart-flexslider' ), __( 'Fresh styles, made to last.', 'wp-smart-flexslider' ), __( 'Shop now', 'wp-smart-flexslider' ), 'center-left', 'left' ), $extra ),
				self::content_slide( '#1f2937', '#4b5563', self::stack( __( 'Free shipping over $50', 'wp-smart-flexslider' ), __( 'Fast delivery to your door.', 'wp-smart-flexslider' ), __( 'See details', 'wp-smart-flexslider' ), 'center-left', 'left' ), $extra ),
			)
		);
	}

	/**
	 * Promo Countdown.
	 *
	 * @return array
	 */
	private static function promo_countdown() {
		$layers   = self::stack( __( 'Summer sale ends soon', 'wp-smart-flexslider' ), __( 'Up to 40% off everything.', 'wp-smart-flexslider' ), __( 'Shop the sale', 'wp-smart-flexslider' ) );
		$button   = array_pop( $layers );
		$layers[] = array(
			'countdown',
			array(
				'date'      => gmdate( 'Y-m-d\TH:i', time() + 7 * DAY_IN_SECONDS ),
				'size'      => 'xl',
				'animation' => array(
					'in'    => 'zoom-in',
					'delay' => 250,
				),
			),
		);
		$layers[] = $button;
		$config   = self::config(
			array(
				'height'   => array( 'ratio' => '21:9' ),
				'autoplay' => array( 'enabled' => false ),
			),
			array( self::content_slide( '#be123c', '#f97316', $layers ) )
		);
		return $config;
	}

	/**
	 * Fullscreen Video.
	 *
	 * @return array
	 */
	private static function fullscreen_video() {
		$slide                                  = Slider_Schema::new_slide( 'video' );
		$slide['background']['type']            = 'video';
		$slide['background']['overlay']         = array(
			'color'   => '#000000',
			'opacity' => 0.35,
		);
		$slide['background']['video']['source'] = 'youtube';
		foreach ( self::stack( __( 'See it in motion', 'wp-smart-flexslider' ), __( 'Add your YouTube, Vimeo or MP4 link in the slide settings.', 'wp-smart-flexslider' ), '' ) as $layer ) {
			$slide['layers'][] = Slider_Schema::new_layer( $layer[0], $layer[1] );
		}
		return self::config(
			array(
				'width'    => 'full',
				'height'   => array(
					'mode' => 'viewport',
					'vh'   => 100,
				),
				'autoplay' => array( 'enabled' => false ),
			),
			array( $slide )
		);
	}

	/**
	 * Posts or products carousel.
	 *
	 * @param string $post_type post or product.
	 * @param int    $per_view  Cards per view.
	 * @return array
	 */
	private static function posts_carousel( $post_type, $per_view ) {
		$slide                          = Slider_Schema::new_slide( 'posts' );
		$slide['source']['post_type']   = $post_type;
		$slide['source']['count']       = 8;
		$slide['source']['show']        = 'product' === $post_type ? array( 'title', 'price', 'button' ) : array( 'date', 'title', 'button' );
		$slide['background']['overlay'] = array(
			'color'   => '#000000',
			'opacity' => 0.4,
		);
		$slide['layers'][]              = Slider_Schema::new_layer(
			'heading',
			array(
				'level'     => 'h3',
				'size'      => 'm',
				'position'  => array( 'anchor' => 'bottom-left' ),
				'align'     => 'left',
				'animation' => array( 'in' => 'none' ),
			)
		);
		$slide['layers'][]              = Slider_Schema::new_layer(
			'button',
			array(
				'variant'   => 'outline',
				'position'  => array( 'anchor' => 'bottom-left' ),
				'align'     => 'left',
				'animation' => array( 'in' => 'none' ),
			)
		);
		return self::config(
			array(
				'type'     => 'carousel',
				'height'   => array( 'ratio' => '4:5' ),
				'carousel' => array(
					'per_view' => $per_view,
					'gap'      => 20,
				),
				'autoplay' => array( 'enabled' => false ),
				'style'    => array( 'radius' => 12 ),
			),
			array( $slide )
		);
	}

	/**
	 * Image slides with empty image slots.
	 *
	 * @param int    $count Number of slides.
	 * @param string $type  Slide type.
	 * @return array[]
	 */
	private static function image_slides( $count, $type = 'image' ) {
		$colors = array( '#1e3a8a', '#0f766e', '#9a3412', '#6d28d9', '#be123c', '#15803d', '#a16207', '#334155' );
		$slides = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$slide                        = Slider_Schema::new_slide( $type );
			$slide['background']['color'] = $colors[ $i % count( $colors ) ];
			$slides[]                     = $slide;
		}
		return $slides;
	}

	/**
	 * Coverflow Portfolio.
	 *
	 * @return array
	 */
	private static function coverflow() {
		return self::config(
			array(
				'type'     => 'carousel',
				'effect'   => 'coverflow',
				'height'   => array( 'ratio' => '4:5' ),
				'carousel' => array(
					'per_view' => 3,
					'centered' => true,
					'gap'      => 0,
				),
				'lightbox' => true,
				'style'    => array( 'radius' => 10 ),
			),
			self::image_slides( 6 )
		);
	}

	/**
	 * Cards Stack.
	 *
	 * @return array
	 */
	private static function cards() {
		return self::config(
			array(
				'effect'     => 'cards',
				'height'     => array( 'ratio' => '4:5' ),
				'loop'       => false,
				'navigation' => array( 'pagination' => 'none' ),
				'style'      => array( 'radius' => 18 ),
			),
			self::image_slides( 5 )
		);
	}

	/**
	 * Testimonials.
	 *
	 * @return array
	 */
	private static function testimonials() {
		$people = array(
			array( __( 'This changed how we work. Setup took minutes and our team loves it.', 'wp-smart-flexslider' ), 'Priya Sharma', __( 'Marketing lead', 'wp-smart-flexslider' ) ),
			array( __( 'Clean, fast and easy to customise. Exactly what our site needed.', 'wp-smart-flexslider' ), 'Daniel Okafor', __( 'Founder', 'wp-smart-flexslider' ) ),
			array( __( 'Our customers notice the difference. Highly recommended.', 'wp-smart-flexslider' ), 'Mei Lin', __( 'Store owner', 'wp-smart-flexslider' ) ),
		);
		$slides = array();
		foreach ( $people as $person ) {
			$slide                = Slider_Schema::new_slide( 'testimonial' );
			$slide['testimonial'] = array(
				'quote'  => $person[0],
				'name'   => $person[1],
				'role'   => $person[2],
				'rating' => 5,
			);
			$slides[]             = $slide;
		}
		return self::config(
			array(
				'effect'     => 'fade',
				'height'     => array( 'mode' => 'auto' ),
				'navigation' => array( 'arrow_style' => 'minimal' ),
				'style'      => array( 'text_color' => '#1f2937' ),
			),
			$slides
		);
	}

	/**
	 * Logo Ticker.
	 *
	 * @return array
	 */
	private static function logo_ticker() {
		return self::config(
			array(
				'type'     => 'ticker',
				'height'   => array(
					'mode' => 'fixed',
					'px'   => 120,
				),
				'carousel' => array(
					'per_view' => 5,
					'gap'      => 48,
				),
				'ticker'   => array( 'speed' => 40 ),
			),
			self::image_slides( 8, 'logo' )
		);
	}

	/**
	 * News Ticker.
	 *
	 * @return array
	 */
	private static function news_ticker() {
		$slide                       = Slider_Schema::new_slide( 'posts' );
		$slide['source']['count']    = 10;
		$slide['source']['show']     = array( 'title' );
		$slide['background']['type'] = 'none';
		$slide['layers'][]           = Slider_Schema::new_layer(
			'heading',
			array(
				'level'     => 'p',
				'size'      => 's',
				'position'  => array( 'anchor' => 'center-left' ),
				'align'     => 'left',
				'animation' => array( 'in' => 'none' ),
			)
		);
		return self::config(
			array(
				'type'     => 'ticker',
				'height'   => array(
					'mode' => 'fixed',
					'px'   => 56,
				),
				'carousel' => array(
					'auto_width' => true,
					'gap'        => 40,
				),
				'ticker'   => array( 'speed' => 60 ),
				'style'    => array( 'text_color' => '#111827' ),
			),
			array( $slide )
		);
	}

	/**
	 * Gallery + Thumbnails.
	 *
	 * @return array
	 */
	private static function gallery() {
		return self::config(
			array(
				'type'       => 'gallery',
				'height'     => array( 'ratio' => '3:2' ),
				'navigation' => array( 'pagination' => 'thumbnails' ),
				'autoplay'   => array( 'enabled' => false ),
				'lightbox'   => true,
				'style'      => array( 'caption_style' => 'below' ),
			),
			self::image_slides( 6 )
		);
	}
}
