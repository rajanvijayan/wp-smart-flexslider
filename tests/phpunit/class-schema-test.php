<?php
/**
 * Slider configuration schema.
 *
 * @package SlideCraft
 */

use SlideCraft\Sliders\Slider_Schema;

/**
 * @covers \SlideCraft\Sliders\Slider_Schema
 * @covers \SlideCraft\Sliders\Spec
 */
class Schema_Test extends SlideCraft_TestCase {

	public function test_defaults_have_every_section() {
		$config = Slider_Schema::defaults();
		$this->assertSame( Slider_Schema::VERSION, $config['schema_version'] );
		$this->assertSame( 'slider', $config['settings']['type'] );
		$this->assertSame( 5000, $config['settings']['autoplay']['delay'] );
		$this->assertSame( 767, $config['breakpoints']['mobile']['max'] );
		$this->assertSame( array(), $config['slides'] );
	}

	public function test_invalid_values_fall_back_and_unknown_keys_are_dropped() {
		$config = Slider_Schema::normalize(
			array(
				'settings' => array(
					'effect'   => 'explode',
					'speed'    => 99999,
					'autoplay' => array( 'delay' => 'soon' ),
					'evil'     => 'x',
				),
				'slides'   => array(
					array(
						'type'       => 'image',
						'background' => array(
							'image_id' => '12abc',
							'color'    => 'red',
						),
						'link'       => array( 'url' => 'javascript:alert(1)' ),
						'hack'       => true,
					),
				),
			)
		);
		$this->assertSame( 'slide', $config['settings']['effect'] );
		$this->assertSame( 5000, $config['settings']['speed'] );
		$this->assertSame( 5000, $config['settings']['autoplay']['delay'] );
		$this->assertArrayNotHasKey( 'evil', $config['settings'] );
		$this->assertSame( 12, $config['slides'][0]['background']['image_id'] );
		$this->assertSame( '#111827', $config['slides'][0]['background']['color'] );
		$this->assertSame( '', $config['slides'][0]['link']['url'] );
		$this->assertArrayNotHasKey( 'hack', $config['slides'][0] );
	}

	public function test_layer_text_keeps_inline_html_only() {
		$config = Slider_Schema::normalize(
			array(
				'slides' => array(
					array(
						'layers' => array(
							array(
								'type' => 'heading',
								'text' => '<strong>Hi</strong> <script>alert(1)</script><img src=x onerror=alert(1)><a href="https://example.org" onclick="x()">link</a>',
							),
						),
					),
				),
			)
		);
		$text = $config['slides'][0]['layers'][0]['text'];
		$this->assertStringContainsString( '<strong>Hi</strong>', $text );
		$this->assertStringNotContainsString( '<script', $text );
		$this->assertStringNotContainsString( '<img', $text );
		$this->assertStringNotContainsString( 'onclick', $text );
		$this->assertStringContainsString( 'href="https://example.org"', $text );
	}

	public function test_slide_and_layer_ids_are_unique() {
		$config = Slider_Schema::normalize(
			array(
				'slides' => array(
					array(
						'id'     => 'same',
						'layers' => array( array( 'id' => 'same' ), array() ),
					),
					array( 'id' => 'same' ),
				),
			)
		);
		$ids = array( $config['slides'][0]['id'], $config['slides'][1]['id'], $config['slides'][0]['layers'][0]['id'], $config['slides'][0]['layers'][1]['id'] );
		$this->assertSame( 'same', $ids[0] );
		$this->assertCount( 4, array_unique( $ids ) );
	}

	public function test_lists_are_capped() {
		$slides = array_fill( 0, Slider_Schema::MAX_SLIDES + 20, array( 'type' => 'image' ) );
		$config = Slider_Schema::normalize( array( 'slides' => $slides ) );
		$this->assertCount( Slider_Schema::MAX_SLIDES, $config['slides'] );
	}

	public function test_start_slide_is_reset_when_out_of_range() {
		$config = Slider_Schema::normalize(
			array(
				'settings' => array( 'start' => 5 ),
				'slides'   => array( array(), array() ),
			)
		);
		$this->assertSame( 0, $config['settings']['start'] );
	}
}
