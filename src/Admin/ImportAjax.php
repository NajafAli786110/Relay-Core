<?php
/**
 * ImportAjax class.
 *
 * @package EngineWP\RelayCore\Admin
 */

namespace EngineWP\RelayCore\Admin;

use EngineWP\RelayCore\Import\ImportLog;
use EngineWP\RelayCore\Import\ImportState;

/**
 * Handles the AJAX requests that run an import one batch at a time.
 */
class ImportAjax {

	/**
	 * Reads the rows that failed during an import.
	 *
	 * @var ImportLog
	 */
	private $import_log;

	/**
	 * Reads the progress of an import.
	 *
	 * @var ImportState
	 */
	private $import_state;

	/**
	 * Constructor.
	 *
	 * @param ImportLog   $import_log Reads the rows that failed during an import.
	 * @param ImportState $import_state Provide current state to ensure running or done.
	 */
	public function __construct( ImportLog $import_log, ImportState $import_state ) {
		$this->import_log   = $import_log;
		$this->import_state = $import_state;
	}

	/**
	 * Handle and check eitheir status is done or running.
	 *
	 * @return void
	 */
	public function handle_status() {
		check_ajax_referer( 'relay_core_import_status', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => "You're not capable to perform this action!",
				),
				403
			);
		}

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : null;

		if ( null === $import_id ) {
			wp_send_json_error(
				array(
					'message' => 'Invalid Import ID',
				),
				404
			);
		}

		$state = $this->import_state->get( $import_id );

		if ( ! is_array( $state ) || null === $state ) {
			wp_send_json_error(
				array(
					'message' => 'Invalid Import ID',
				),
				404
			);
		}

		$response = array(
			'offset'  => $state['offset'],
			'created' => $state['created'],
			'updated' => $state['updated'],
			'failed'  => $state['failed'],
			'status'  => $state['status'],
			'total'   => $state['total'] ?? 0,
		);

		if ( 'done' === $state['status'] ) {
			$response['errors'] = $this->import_log->get_for_import( $import_id );
		}

		wp_send_json_success(
			$response
		);
	}
}
