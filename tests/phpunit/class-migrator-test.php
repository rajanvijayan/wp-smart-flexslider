<?php
/**
 * Migration from WP Smart Flexslider 1.x and 2.x.
 *
 * @package SlideCraft
 */

use SlideCraft\Legacy\Migrator;
use SlideCraft\Sliders\Slider_Repository;

/**
 * @covers \SlideCraft\Legacy\Migrator
 * @covers \SlideCraft\Render\Shortcodes
 */
class Migrator_Test extends SlideCraft_TestCase {

	public function set_up() {
		parent::set_up();
		foreach ( array( Migrator::OPTION_DONE, Migrator::OPTION_MAP, Migrator::OPTION_NOTICE, Migrator::OPTION_LOCK ) as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * A 2.5-style slider post, stored exactly as the old meta boxes saved it.
	 *
	 * @param int[] $images Attachment IDs.
	 * @param array $meta   Settings meta.
	 * @return int
	 */
	private function legacy_v2( array $images, array $meta = array() ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'flexslider',
				'post_title'  => 'Home banner',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $id, 'slide_attachmenid', $images );
		update_post_meta( $id, 'flex-slide-title', array( 'First title', 'Second title', '' ) );
		update_post_meta( $id, 'flex-slide-url', array( 'https://example.org/one', '', 'https://example.org/three' ) );
		update_post_meta( $id, 'flex-slide-desc', array( 'First description', '', '' ) );
		$meta += array(
			'nav_controls'     => 'Yes',
			'pager_controls'   => 'No',
			'interval'         => '3000',
			'hover_pass'       => 'false',
			'show_title'       => 'Yes',
			'show_description' => 'Yes',
			'show_slide_url'   => 'Yes',
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	public function test_v2_slider_is_converted() {
		$images = $this->make_images( 3 );
		$legacy = $this->legacy_v2( $images );

		$result = Migrator::run();
		$this->assertSame( 1, $result['created'] );

		$slider = Slider_Repository::find( Migrator::map()[ $legacy ] );
		$this->assertSame( 'Home banner', $slider->title );
		$this->assertCount( 3, $slider->config['slides'] );

		$first = $slider->config['slides'][0];
		$this->assertSame( $images[0], $first['background']['image_id'] );
		$this->assertSame( 'https://example.org/one', $first['link']['url'] );
		$this->assertTrue( $first['link']['new_tab'] );
		$this->assertSame( array( 'heading', 'text' ), wp_list_pluck( $first['layers'], 'type' ) );
		$this->assertSame( 'First title', $first['layers'][0]['text'] );

		// No title or description: no layers and no overlay.
		$this->assertSame( array(), $slider->config['slides'][2]['layers'] );
		$this->assertSame( 0.0, (float) $slider->config['slides'][2]['background']['overlay']['opacity'] );

		$settings = $slider->config['settings'];
		$this->assertSame( 3000, $settings['autoplay']['delay'] );
		$this->assertFalse( $settings['autoplay']['pause_on_hover'] );
		$this->assertTrue( $settings['navigation']['arrows'] );
		$this->assertSame( 'none', $settings['navigation']['pagination'] );
		$this->assertSame( 'auto', $settings['height']['mode'] );
	}

	public function test_hidden_titles_and_links_stay_hidden() {
		$images = $this->make_images( 1 );
		$legacy = $this->legacy_v2(
			$images,
			array(
				'show_title'       => 'No',
				'show_description' => 'No',
				'show_slide_url'   => 'No',
				'interval'         => '',
			)
		);
		Migrator::run();
		$slider = Slider_Repository::find( Migrator::map()[ $legacy ] );
		$this->assertSame( array(), $slider->config['slides'][0]['layers'] );
		$this->assertSame( '', $slider->config['slides'][0]['link']['url'] );
		$this->assertSame( 5000, $slider->config['settings']['autoplay']['delay'] );
	}

	public function test_migration_is_idempotent_and_keeps_old_data() {
		$legacy = $this->legacy_v2( $this->make_images( 2 ) );
		Migrator::run();
		delete_option( Migrator::OPTION_DONE );
		$second = Migrator::run();

		$this->assertSame( 0, $second['created'] );
		$this->assertSame( 1, $second['skipped'] );
		$this->assertCount( 1, get_posts( array( 'post_type' => 'slidecraft_slider' ) ) );
		$this->assertSame( 'publish', get_post_status( $legacy ) );
		$this->assertCount( 2, get_post_meta( $legacy, 'slide_attachmenid', true ) );
	}

	public function test_v1_posts_become_one_slider() {
		update_option(
			'flexslider_options',
			array(
				'nav_controls'       => 'Yes',
				'direction_controls' => 'No',
				'number_slides'      => '2',
			)
		);
		$images = $this->make_images( 3 );
		foreach ( $images as $i => $image ) {
			$post = self::factory()->post->create(
				array(
					'post_type'    => 'flexslider',
					'post_title'   => "Slide $i",
					'post_content' => "Caption $i",
					'post_status'  => 'publish',
					'post_date'    => '2014-01-0' . ( $i + 1 ) . ' 10:00:00',
				)
			);
			set_post_thumbnail( $post, $image );
		}

		Migrator::run();
		$slider = Slider_Repository::find( Migrator::slider_for( 0 ) );
		$this->assertNotNull( $slider );
		// Newest first, limited to number_slides like 1.x.
		$this->assertCount( 2, $slider->config['slides'] );
		$this->assertSame( 'Slide 2', $slider->config['slides'][0]['title'] );
		$this->assertSame( 'bullets', $slider->config['settings']['navigation']['pagination'] );
		$this->assertFalse( $slider->config['settings']['navigation']['arrows'] );
	}

	public function test_legacy_shortcodes_render_the_new_slider() {
		$legacy = $this->legacy_v2( $this->make_images( 2 ) );
		$html   = do_shortcode( '[display_flexslider id="' . $legacy . '"]' );
		$this->assertStringContainsString( 'class="slidecraft ', $html );
		$this->assertStringContainsString( 'First title', $html );
		$this->assertSame( 2, substr_count( $html, 'aria-roledescription="slide"' ) );
		$this->assertSame( '', do_shortcode( '[display_flexslider id="999999"]' ) );
	}

	public function test_notice_counts_migrated_sliders() {
		$this->legacy_v2( $this->make_images( 1 ) );
		$this->legacy_v2( $this->make_images( 1 ) );
		Migrator::run();
		$this->assertSame( 2, (int) get_option( Migrator::OPTION_NOTICE ) );
	}
}
