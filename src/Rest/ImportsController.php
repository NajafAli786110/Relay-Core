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
	 * Returns the public fields of one import.
	 *
	 * @param string $id Import ID.
	 * @return array|WP_Error The import, or a 404 error if it does not exist.
	 */
	public function get_by_id_res( string $id ) {
		$state = $this->import_state->get( $id );

		if ( null === $state ) {
			return new WP_Error( 'relay_core_not_found', 'Request not valid!', array( 'status' => 404 ) );
		}

		$filtered_state = $this->create_field( $id, $state );

		return $filtered_state;
	}

	/**
	 * Returns the public fields of every import, newest first.
	 *
	 * @return array[]|WP_Error List of imports in the same shape as a single import. Empty if there are none.
	 */
	public function get_all() {
		$state = $this->import_state->all();

		if ( ! is_array( $state ) ) {
			return new WP_Error( 'relay_core_not_found', 'Request not valid!', array( 'status' => 404 ) );
		}

		$filtered_array = array();

		foreach ( $state as $row ) {
			$state            = $row['state'];
			$filtered_array[] = $this->create_field( $row['id'], $state );
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

	/**
	 * Builds the public shape of one import for API responses.
	 *
	 * Only the fields listed here are sent to the browser. Any other key in
	 * the saved state, such as the file path, is left out.
	 *
	 * @param string $id    Import ID.
	 * @param array  $state Full saved state of the import.
	 * @return array Import ID, status, progress counters and creation time.
	 */
	private function create_field( string $id, array $state ) {
		return array(
			'id'         => $id,
			'status'     => $state['status'] ?? '',
			'offset'     => $state['offset'] ?? 0,
			'total'      => $state['total'] ?? 0,
			'created'    => $state['created'] ?? 0,
			'updated'    => $state['updated'] ?? 0,
			'failed'     => $state['failed'] ?? 0,
			'created_at' => $state['created_at'] ?? 0,
		);
	}
}
