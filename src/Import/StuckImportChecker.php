<?php
/**
 * Restarts background imports whose job chain has broken.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Restarts background imports whose job chain has broken.
 *
 * A running import queues its own next job. If a job dies before doing that,
 * nothing runs again and the import stays "running" forever. This class finds
 * such imports and queues a new job, up to a retry limit.
 */
class StuckImportChecker {

	/**
	 * How long a running import may go without a state update before it counts as stuck.
	 */
	const STUCK_AFTER = 4 * MINUTE_IN_SECONDS;

	/**
	 * If the import's updated_at is soo older than a day, it means it's abondonded not STUCKED.
	 */
	const GIVE_UP_AFTER = DAY_IN_SECONDS;

	/**
	 * Reads and updates import state.
	 *
	 * @var ImportState
	 */
	private $import_state;

	/**
	 * Queues the next batch job.
	 *
	 * @var BackgroundImport
	 */
	private $background_import;

	/**
	 * Sets up the checker.
	 *
	 * @param ImportState      $import_state      Reads and updates import state.
	 * @param BackgroundImport $background_import Queues the next batch job.
	 */
	public function __construct( ImportState $import_state, BackgroundImport $background_import ) {
		$this->import_state      = $import_state;
		$this->background_import = $background_import;
	}

	/**
	 * Restarts one import if it is stuck, or marks it failed after 3 retries.
	 *
	 * An import is stuck when it is running, its state has not changed for
	 * STUCK_AFTER seconds, and no job for it is waiting or running in the queue.
	 *
	 * @param string $import_id ID of the import state to check.
	 * @return void
	 */
	public function check_stuck( string $import_id ) {

		if ( '' === trim( $import_id ) ) {
			return;
		}

		$state = $this->import_state->get( $import_id );
		if ( ! is_array( $state ) ) {
			return;
		}

		if ( 'running' !== $state['status'] ) {
			return;
		}

		if ( ! isset( $state['updated_at'] ) || time() - self::GIVE_UP_AFTER < $state['updated_at'] ) {
			$message = 'Abandoned: no activity for over a day';

			$changes = array(
				'error'  => $message,
				'status' => 'failed',
			);

			$this->import_state->update( $import_id, $changes );
			return;
		}

		if ( self::STUCK_AFTER > time() - $state['updated_at'] ) {
			return;
		}

		$has_any_job_running = as_has_scheduled_action( 'relay_core_run_batch', array( $import_id ), 'relay-core' );
		if ( $has_any_job_running ) {
			return;
		}

		$retries_state = isset( $state['retries'] ) ? $state['retries'] : 0;

		if ( $retries_state < 3 ) {
			$changes = array(
				'retries' => $retries_state + 1,
			);

			$this->import_state->update( $import_id, $changes );
			$this->background_import->start( $import_id );
		} else {
			$message = "Stopped at row {$state['offset']} after 3 retries";

			$changes = array(
				'error'  => $message,
				'status' => 'failed',
			);

			$this->import_state->update( $import_id, $changes );
		}
	}

	/**
	 * Run check_status on all the imports states we have, this is use for recurring but also can use for other, just to track all in good place.
	 *
	 * @return void
	 */
	public function run(): void {
		$imports = $this->import_state->all();

		foreach ( $imports as $import ) {
			$import_id = $import['id'];

			$this->check_stuck( $import_id );
		}
	}
}
