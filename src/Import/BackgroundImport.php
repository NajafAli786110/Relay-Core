<?php
/**
 * BackgroundImport class.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Runs an import on the server with Action Scheduler, one batch per job.
 *
 * Each job imports one batch and, if rows remain, queues the next job.
 * The import keeps going when the browser is closed.
 */
class BackgroundImport {

	/**
	 * Runs one batch of an import.
	 *
	 * @var BatchProcessor
	 */
	private $processor;

	/**
	 * Constructor.
	 *
	 * @param BatchProcessor $processor Runs one batch of an import.
	 */
	public function __construct( BatchProcessor $processor ) {
		$this->processor = $processor;
	}

	/**
	 * Queues a job that imports the next batch.
	 *
	 * @param string $import_id ID of the import state to continue.
	 * @return void
	 */
	public function start( string $import_id ) {
		as_enqueue_async_action( 'relay_core_run_batch', array( $import_id ), 'relay-core' );
	}

	/**
	 * Imports one batch and queues the next job if the import is still running.
	 *
	 * Hooked to relay_core_run_batch, which Action Scheduler fires for each job.
	 *
	 * @param string $import_id ID of the import state to continue.
	 * @return void
	 */
	public function run_batch( string $import_id ) {
		$state = $this->processor->process( $import_id );
		if ( null === $state ) {
			return;
		}

		if ( 'running' === $state['status'] ) {
			$this->start( $import_id );
		}
	}
}
