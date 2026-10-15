<?php
/**
 * Front-end markup.
 *
 * @package SlideCraft
 */

use SlideCraft\Render\Renderer;
use SlideCraft\Sliders\Slider_Repository;

/**
 * @covers \SlideCraft\Render\Renderer
 * @covers \SlideCraft\Render\Dynamic_Source
 */
class Renderer_Test extends SlideCraft_TestCase {

	public function test_markup_is_accessible_and_reserves_space() {
		$images = $this->make_images( 3 );
		$slides = array();
		foreach ( $images as $image ) {
			$slides[] = array(
				'type'       => 'image',
				'alt'        => 'Alt text',
				'background' => array( 'image_id' => $image ),
			);
		}
		$slider = Slider_Repository::create( 'Hero', array( 'slides' => $slides ) );
		$html   = Renderer::render( $slider );

		$this->assertStringContainsString( 'role="region"', $html );
		$this->assertStringContainsString( 'aria-roledescription="carousel"', $html );
		$this->assertStringContainsString( 'aria-label="Hero"', $html );
		$this->assertStringContainsString( 'aria-label="1 of 3"', $html );
		$this->assertStringContainsString( '--sc-ratio:16 / 9', $html );
		// Autoplay is on by default, so a pause button is required.
		$this->assertStringContainsString( 'class="slidecraft__pause"', $html );
		// Only the first image of the first slider is eager.
		$this->assertSame( 1, substr_count( $html, 'fetchpriority="high"' ) );
		$this->assertSame( 2, substr_count( $html, 'loading="lazy"' ) );
	}

	public function test_hidden_slides_are_skipped_and_empty_sliders_render_nothing() {
		$slider = Slider_Repository::create(
			'Hidden',
			array(
				'slides' => array(
					array(
						'type'   => 'content',
						'hidden' => true,
					),
				),
			)
		);
		$this->assertSame( '', Renderer::render( $slider ) );
	}

	public function test_layers_are_grouped_and_escaped() {
		$slider = Slider_Repository::create(
			'Layers',
			array(
				'slides' => array(
					array(
						'type'   => 'content',
						'layers' => array(
							array(
								'type'     => 'heading',
								'text'     => 'Big <em>news</em>',
								'position' => array( 'anchor' => 'center-left' ),
							),
							array(
								'type'     => 'button',
								'text'     => 'Go',
								'url'      => 'https://example.org/"onmouseover="x',
								'position' => array( 'anchor' => 'center-left' ),
							),
						),
					),
				),
			)
		);
		$html = Renderer::render( $slider );
		$this->assertSame( 1, substr_count( $html, 'slidecraft-group--center-left' ) );
		$this->assertStringContainsString( 'Big <em>news</em>', $html );
		$this->assertStringNotContainsString( '"onmouseover', $html );
	}

	public function test_runtime_config_lists_needed_modules() {
		$config = Renderer::runtime_config(
			array_replace_recursive(
				\SlideCraft\Sliders\Slider_Schema::defaults()['settings'],
				array(
					'effect'     => 'fade',
					'navigation' => array( 'pagination' => 'fraction' ),
				)
			),
			\SlideCraft\Sliders\Slider_Schema::defaults()['breakpoints'],
			3
		);
		$this->assertContains( 'effect-fade', $config['modules'] );
		$this->assertContains( 'pagination', $config['modules'] );
		$this->assertContains( 'autoplay', $config['modules'] );
		$this->assertNotContains( 'thumbs', $config['modules'] );
	}

	public function test_dynamic_slides_keep_a_template_without_background() {
		$images = $this->make_images( 1 );
		$post   = self::factory()->post->create( array( 'post_title' => 'Headline only' ) );
		set_post_thumbnail( $post, $images[0] );
		$slider = Slider_Repository::create(
			'Ticker',
			array(
				'slides' => array(
					array(
						'type'       => 'posts',
						'background' => array( 'type' => 'none' ),
						'source'     => array( 'post_type' => 'post' ),
					),
				),
			)
		);
		$html = Renderer::render( $slider );
		$this->assertStringContainsString( 'Headline only', $html );
		$this->assertStringNotContainsString( '<img', $html );
	}

	public function test_dynamic_slides_show_only_public_posts() {
		self::factory()->post->create(
			array(
				'post_title'  => 'Public post',
				'post_status' => 'publish',
			)
		);
		self::factory()->post->create(
			array(
				'post_title'  => 'Private post',
				'post_status' => 'private',
			)
		);
		self::factory()->post->create(
			array(
				'post_title'    => 'Protected post',
				'post_status'   => 'publish',
				'post_password' => 'secret',
			)
		);
		$slider = Slider_Repository::create(
			'Posts',
			array(
				'slides' => array(
					array(
						'type'   => 'posts',
						'source' => array( 'post_type' => 'post' ),
					),
				),
			)
		);
		$html = Renderer::render( $slider );
		$this->assertStringContainsString( 'Public post', $html );
		$this->assertStringNotContainsString( 'Private post', $html );
		$this->assertStringNotContainsString( 'Protected post', $html );

		// A non-public post type is ignored.
		$slider = Slider_Repository::create(
			'Bad type',
			array(
				'slides' => array(
					array(
						'type'   => 'posts',
						'source' => array( 'post_type' => 'slidecraft_slider' ),
					),
				),
			)
		);
		$this->assertSame( '', Renderer::render( $slider ) );
	}
}
