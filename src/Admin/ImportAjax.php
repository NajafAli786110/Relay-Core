<?php
/**
 * ImportAjax class.
 *
 * @package EngineWP\RelayCore\Admin
 */

namespace EngineWP\RelayCore\Admin;

use EngineWP\RelayCore\Import\BatchProcessor;
use EngineWP\RelayCore\Import\ImportLog;

/**
 * Handles the AJAX requests that run an import one batch at a time.
 */
class ImportAjax {

	/**
	 * Runs one batch of an import.
	 *
	 * @var BatchProcessor
	 */
	private $processor;

	/**
	 * Reads the rows that failed during an import.
	 *
	 * @var ImportLog
	 */
	private $import_log;

	/**
	 * Constructor.
	 *
	 * @param BatchProcessor $processor  Runs one batch of an import.
	 * @param ImportLog      $import_log Reads the rows that failed during an import.
	 */
	public function __construct( BatchProcessor $processor, ImportLog $import_log ) {
		$this->processor  = $processor;
		$this->import_log = $import_log;
	}

	/**
	 * Processes the next batch of an import and sends its progress as JSON.
	 *
	 * Hooked to wp_ajax_relay_core_import_batch. Expects 'nonce' and
	 * 'import_id' in the request. The response never includes the file path.
	 *
	 * @return void
	 */
	public function handle_batch() {
		check_ajax_referer( 'relay_core_import_batch', 'nonce' );

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

		$result = $this->processor->process( $import_id );
		if ( ! is_array( $result ) || null === $result ) {
			wp_send_json_error(
				array(
					'message' => 'Invalid Import ID',
				),
				404
			);
		}

		$response = array(
			'offset'  => $result['offset'],
			'created' => $result['created'],
			'updated' => $result['updated'],
			'failed'  => $result['failed'],
			'status'  => $result['status'],
			'total'   => $result['total'] ?? 0,
		);

		if ( 'done' === $result['status'] ) {
			$response['errors'] = $this->import_log->get_for_import( $import_id );
		}

		wp_send_json_success(
			$response
		);
	}
}
