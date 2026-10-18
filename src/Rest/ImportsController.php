<?php

namespace EngineWP\RelayCore\Rest;

use EngineWP\RelayCore\Import\ImportState;

class ImportsController {

	private $import_state;
	public function __construct( ImportState $import_state ) {
		$this->import_state = $import_state;
	}

	public function register_routes() {
		register_rest_route(
			'/relay-core/v1',
			'/imports',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->import_state, 'all' ),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'/relay-core/v1',
			'/imports/(?P<id>[a-f0-9]{64})',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->import_state, 'all' ),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'/relay-core/v1',
			'/imports/(?P<id>[a-f0-9]{64})/logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->import_state, 'all' ),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}
}
