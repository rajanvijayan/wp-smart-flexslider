<?php
/**
 * Slider CRUD, duplicate, revisions, import and export.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SlideCraft\Sliders\Slider;
use SlideCraft\Sliders\Slider_Repository;
use SlideCraft\Sliders\Slider_Schema;

/**
 * Routes under /sliders.
 */
final class Sliders_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/sliders',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'index' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'search'   => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 50,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/sliders/import',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/sliders/(?P<id>\d+)',
			array(
				'args' => $this->id_arg(),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/sliders/(?P<id>\d+)/duplicate',
			array(
				'args'                => $this->id_arg(),
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'duplicate' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/sliders/(?P<id>\d+)/export',
			array(
				'args'                => $this->id_arg(),
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/sliders/(?P<id>\d+)/revisions',
			array(
				'args'                => $this->id_arg(),
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'revisions' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/sliders/(?P<id>\d+)/revisions/(?P<index>\d+)/restore',
			array(
				'args'                => $this->id_arg() + array(
					'index' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'restore' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * List sliders.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function index( WP_REST_Request $request ) {
		$result   = Slider_Repository::query(
			array(
				'search'   => (string) $request['search'],
				'page'     => (int) $request['page'],
				'per_page' => (int) $request['per_page'],
			)
		);
		$response = rest_ensure_response( array_map( array( self::class, 'summary' ), $result['items'] ) );
		$response->header( 'X-WP-Total', (string) $result['total'] );
		return $response;
	}

	/**
	 * Create a slider from a title and optional configuration.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$config = $request['config'];
		$slider = Slider_Repository::create( (string) $request['title'], is_array( $config ) ? $config : array() );
		if ( is_wp_error( $slider ) ) {
			return $slider;
		}
		return new WP_REST_Response( self::detail( $slider ), 201 );
	}

	/**
	 * One slider with its configuration.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function show( WP_REST_Request $request ) {
		$slider = $this->slider( $request );
		return is_wp_error( $slider ) ? $slider : self::detail( $slider );
	}

	/**
	 * Update title and/or configuration. `revision: true` keeps the previous version (explicit saves, not autosave).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function update( WP_REST_Request $request ) {
		$slider = $this->slider( $request );
		if ( is_wp_error( $slider ) ) {
			return $slider;
		}
		$config = $request['config'];
		$title  = $request['title'];
		$result = Slider_Repository::update(
			$slider,
			is_string( $title ) ? $title : null,
			is_array( $config ) ? $config : null,
			(bool) $request['revision']
		);
		return is_wp_error( $result ) ? $result : self::detail( $result );
	}

	/**
	 * Delete a slider.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function delete( WP_REST_Request $request ) {
		$slider = $this->slider( $request );
		if ( is_wp_error( $slider ) ) {
			return $slider;
		}
		return array( 'deleted' => Slider_Repository::delete( $slider ) );
	}

	/**
	 * Duplicate a slider.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function duplicate( WP_REST_Request $request ) {
		$slider = $this->slider( $request );
		if ( is_wp_error( $slider ) ) {
			return $slider;
		}
		$copy = Slider_Repository::duplicate( $slider );
		return is_wp_error( $copy ) ? $copy : new WP_REST_Response( self::detail( $copy ), 201 );
	}

	/**
	 * Export a slider as JSON.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function export( WP_REST_Request $request ) {
		$slider = $this->slider( $request );
		return is_wp_error( $slider ) ? $slider : Slider_Repository::export( $slider );
	}

	/**
	 * Import a slider from an export.
	 *
	 * @param WP_REST_Request $request Request whose JSON body is an export.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function import( WP_REST_Request $request ) {
		$data   = $request->get_json_params();
		$slider = Slider_Repository::import( is_array( $data ) ? $data : array() );
		return is_wp_error( $slider ) ? $slider : new WP_REST_Response( self::detail( $slider ), 201 );
	}

	/**
	 * Revision list (time and author only).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function revisions( WP_REST_Request $request ) {
		$slider = $this->slider( $request );
		if ( is_wp_error( $slider ) ) {
			return $slider;
		}
		$out = array();
		foreach ( Slider_Repository::revisions( $slider->id ) as $index => $revision ) {
			$user  = get_userdata( (int) $revision['user'] );
			$out[] = array(
				'index'  => $index,
				'time'   => (int) $revision['time'],
				'author' => $user ? $user->display_name : '',
			);
		}
		return $out;
	}

	/**
	 * Restore a revision.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function restore( WP_REST_Request $request ) {
		$slider = $this->slider( $request );
		if ( is_wp_error( $slider ) ) {
			return $slider;
		}
		$result = Slider_Repository::restore( $slider, (int) $request['index'] );
		return is_wp_error( $result ) ? $result : self::detail( $result );
	}

	/**
	 * Library card data.
	 *
	 * @param Slider $slider Slider.
	 * @return array
	 */
	public static function summary( Slider $slider ) {
		$image = $slider->first_image_id();
		return array(
			'id'        => $slider->id,
			'title'     => $slider->title,
			'modified'  => mysql_to_rfc3339( $slider->modified_gmt ),
			'slides'    => count( $slider->config['slides'] ),
			'type'      => $slider->config['settings']['type'],
			'effect'    => $slider->config['settings']['effect'],
			'thumb'     => $image ? (string) wp_get_attachment_image_url( $image, 'medium_large' ) : '',
			'shortcode' => $slider->shortcode(),
		);
	}

	/**
	 * Editor data: summary plus configuration.
	 *
	 * @param Slider $slider Slider.
	 * @return array
	 */
	public static function detail( Slider $slider ) {
		return self::summary( $slider ) + array(
			'config'         => $slider->config,
			'schema_version' => Slider_Schema::VERSION,
		);
	}
}
