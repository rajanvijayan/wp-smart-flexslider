<?php
/**
 * REST API: permissions and CRUD.
 *
 * @package SlideCraft
 */

use SlideCraft\Sliders\Slider_Repository;

/**
 * @covers \SlideCraft\Rest\Sliders_Controller
 * @covers \SlideCraft\Rest\Preview_Controller
 * @covers \SlideCraft\Capabilities
 */
class Rest_Test extends SlideCraft_TestCase {

	public function test_subscribers_and_contributors_are_refused() {
		$slider = Slider_Repository::create( 'Private' );
		foreach ( array( 'subscriber', 'contributor' ) as $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
			foreach ( array( array( 'GET', '/sliders' ), array( 'POST', '/sliders' ), array( 'GET', '/sliders/' . $slider->id ), array( 'DELETE', '/sliders/' . $slider->id ), array( 'POST', '/preview' ), array( 'GET', '/sources' ) ) as $call ) {
				$this->assertContains( $this->rest( $call[0], $call[1] )->get_status(), array( 401, 403 ), "$role {$call[0]} {$call[1]}" );
			}
		}
	}

	public function test_authors_can_create_update_duplicate_and_delete() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$created = $this->rest(
			'POST',
			'/sliders',
			array(
				'title'  => 'Hero',
				'config' => array( 'slides' => array( array( 'type' => 'content' ) ) ),
			)
		);
		$this->assertSame( 201, $created->get_status() );
		$id = $created->get_data()['id'];
		$this->assertSame( '[slidecraft id="' . $id . '"]', $created->get_data()['shortcode'] );

		$config                       = $created->get_data()['config'];
		$config['settings']['effect'] = 'fade';
		$updated                      = $this->rest(
			'POST',
			'/sliders/' . $id,
			array(
				'config'   => $config,
				'revision' => true,
			)
		);
		$this->assertSame( 'fade', $updated->get_data()['config']['settings']['effect'] );
		$this->assertCount( 1, $this->rest( 'GET', '/sliders/' . $id . '/revisions' )->get_data() );

		$restored = $this->rest( 'POST', '/sliders/' . $id . '/revisions/0/restore' );
		$this->assertSame( 'slide', $restored->get_data()['config']['settings']['effect'] );

		$copy = $this->rest( 'POST', '/sliders/' . $id . '/duplicate' );
		$this->assertSame( 201, $copy->get_status() );
		$this->assertSame( 'Hero (copy)', $copy->get_data()['title'] );

		$this->assertTrue( $this->rest( 'DELETE', '/sliders/' . $id )->get_data()['deleted'] );
		$this->assertSame( 404, $this->rest( 'GET', '/sliders/' . $id )->get_status() );
	}

	public function test_export_and_import_round_trip() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$images = $this->make_images( 1 );
		$slider = Slider_Repository::create(
			'Export me',
			array(
				'slides' => array(
					array(
						'type'       => 'image',
						'background' => array( 'image_id' => $images[0] ),
					),
				),
			)
		);
		$export = $this->rest( 'GET', '/sliders/' . $slider->id . '/export' )->get_data();
		$this->assertSame( 'slidecraft', $export['format'] );
		$this->assertArrayHasKey( $images[0], $export['media'] );

		$request = new WP_REST_Request( 'POST', '/slidecraft/v1/sliders/import' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $export ) );
		$imported = rest_get_server()->dispatch( $request );
		$this->assertSame( 201, $imported->get_status() );
		$this->assertSame( $images[0], $imported->get_data()['config']['slides'][0]['background']['image_id'] );

		$request->set_body( wp_json_encode( array( 'format' => 'other' ) ) );
		$this->assertSame( 400, rest_get_server()->dispatch( $request )->get_status() );
	}

	public function test_preview_renders_unsaved_config() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$response = $this->rest(
			'POST',
			'/preview',
			array(
				'title'  => 'Preview',
				'config' => array(
					'settings' => array( 'effect' => 'cube' ),
					'slides'   => array(
						array(
							'type'   => 'content',
							'layers' => array(
								array(
									'type' => 'heading',
									'text' => 'Hello preview',
								),
							),
						),
					),
				),
			)
		);
		$this->assertStringContainsString( 'Hello preview', $response->get_data()['html'] );
		$this->assertContains( 'effect-cube', $response->get_data()['modules'] );
	}
}
