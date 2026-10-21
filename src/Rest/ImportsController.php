<?php
/**
 * ImportsController class.
 *
 * @package EngineWP\RelayCore\Rest
 */

namespace EngineWP\RelayCore\Rest;

use EngineWP\RelayCore\Import\ImportLog;
use EngineWP\RelayCore\Import\ImportState;
use WP_REST_Request;
use WP_Error;

/**
 * REST API endpoints for reading imports and their failed rows.
 *
 * Routes live under the relay-core/v1 namespace and are limited to users
 * who can manage options. Responses never include the uploaded file path.
 */
class ImportsController {

	/**
	 * Reads the saved state of imports.
	 *
	 * @var ImportState
	 */
	private $import_state;

	/**
	 * Reads the rows that failed during an import.
	 *
	 * @var ImportLog
	 */
	private $import_log;

	/**
	 * Constructor.
	 *
	 * @param ImportState $import_state Reads the saved state of imports.
	 * @param ImportLog   $import_log   Reads the rows that failed during an import.
	 */
	public function __construct( ImportState $import_state, ImportLog $import_log ) {
		$this->import_state = $import_state;
		$this->import_log   = $import_log;
	}

	/**
	 * Registers the imports routes.
	 *
	 * Hooked to rest_api_init.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'relay-core/v1',
			'/imports',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					return $this->get_all();
				},
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'relay-core/v1',
			'/imports/(?P<id>[a-f0-9]{64})',
			array(
				'methods'             => 'GET',
				'callback'            => function ( WP_REST_Request $request ) {
					$import_id = $request->get_param( 'id' );

					return $this->get_by_id_res( $import_id );
				},
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'relay-core/v1',
			'/imports/(?P<id>[a-f0-9]{64})/logs',
			array(
				'methods'             => 'GET',
				'callback'            => function ( WP_REST_Request $request ) {
					return $this->get_logs_by_id( $request );
				},
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 20,
						'minimum' => 1,
						'maximum' => 100,
					),
				),
			)
		);
	}

	/**
	 * Returns one import without its file path.
	 *
	 * @param string $id Import ID.
	 * @return array|WP_Error The import state, or a 404 error if it does not exist.
	 */
	public function get_by_id_res( string $id ) {
		$state = $this->import_state->get( $id );

		if ( null === $state ) {
			return new WP_Error( 'relay_core_not_found', 'Request not valid!', array( 'status' => 404 ) );
		}

		unset( $state['file_path'] );

		return $state;
	}

	/**
	 * Returns all imports, newest first, without their file paths.
	 *
	 * @return array[]|WP_Error List of imports, each with an id and its state.
	 */
	public function get_all() {
		$state = $this->import_state->all();

		if ( ! is_array( $state ) ) {
			return new WP_Error( 'relay_core_not_found', 'Request not valid!', array( 'status' => 404 ) );
		}

		$filtered_array = array();

		foreach ( $state as $row ) {
			unset( $row['state']['file_path'] );

			$filtered_array[] = array(
				'id'    => $row['id'],
				'state' => $row['state'],
			);
		}

		return $filtered_array;
	}

	/**
	 * Returns one page of an import's failed rows.
	 *
	 * Converts the page and per_page parameters into the offset and limit
	 * that ImportLog expects.
	 *
	 * @param WP_REST_Request $request Request with id, page and per_page.
	 * @return array|WP_Error Items, total, page and per_page, or an error if the import does not exist.
	 */
	public function get_logs_by_id( WP_REST_Request $request ) {
		$id       = $request->get_param( 'id' );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$offset   = ( $page - 1 ) * $per_page;

		$state = $this->import_state->get( $id );
		if ( ! is_array( $state ) ) {
			return new WP_Error(
				'relay_core_not_found',
				'Import not found.',
				array( 'status' => 404 )
			);
		}

		$logs = $this->import_log->get_for_import(
			$id,
			$per_page,
			$offset
		);

		if ( ! is_array( $logs ) ) {
			return new WP_Error(
				'logs_fetch_failed',
				'Unable to retrieve import logs.',
				array( 'status' => 500 )
			);
		}

		$total = $this->import_log->count_for_import( $id );

		return array(
			'items'    => $logs,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}
}
