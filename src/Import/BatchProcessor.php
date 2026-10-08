<?php
/**
 * BatchProcessor Class.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Imports a file in small batches so large files do not hit the request time limit.
 *
 * Each call to process() handles one batch and saves its position in the
 * import state, so the next request can continue where this one stopped.
 */
class BatchProcessor {





	/**
	 * Stores and retrieves the progress of an import.
	 *
	 * @var ImportState
	 */
	private $state;

	/**
	 * Creates the right reader for a file.
	 *
	 * @var ReaderFactory
	 */
	private $reader_factory;

	/**
	 * Maps raw rows to post title, external ID and meta.
	 *
	 * @var ColumnMapper
	 */
	private $mapper;

	/**
	 * Validates a mapped row before it is imported.
	 *
	 * @var ListingValidator
	 */
	private $validator;

	/**
	 * Creates or updates a listing from a mapped row.
	 *
	 * @var ListingImporter
	 */
	private $importer;

	/**
	 * Constructor.
	 *
	 * @param ImportState      $state          Stores and retrieves the progress of an import.
	 * @param ReaderFactory    $reader_factory Creates the right reader for a file.
	 * @param ColumnMapper     $mapper         Maps raw rows to post title, external ID and meta.
	 * @param ListingValidator $validator      Validates a mapped row before it is imported.
	 * @param ListingImporter  $importer       Creates or updates a listing from a mapped row.
	 */
	public function __construct( ImportState $state, ReaderFactory $reader_factory, ColumnMapper $mapper, ListingValidator $validator, ListingImporter $importer ) {
		$this->state          = $state;
		$this->reader_factory = $reader_factory;
		$this->mapper         = $mapper;
		$this->validator      = $validator;
		$this->importer       = $importer;
	}

	/**
	 * Processes the next batch of rows for an import.
	 *
	 * Skips the rows handled by earlier batches, imports up to $batch_size
	 * rows, then saves the new offset, counters and status in the import state.
	 *
	 * @param string $import_id  ID of the import state to continue.
	 * @param int    $batch_size Maximum number of rows to import in this call.
	 * @return array|null The updated import state, or null if the import does not exist.
	 */
	public function process( string $import_id, int $batch_size = 500 ) {
		$state = $this->state->get( $import_id );

		if ( null === $state ) {
			return null;
		}

		$file_path = isset( $state['file_path'] ) ? $state['file_path'] : null;
		if ( null === $file_path ) {
			return null;
		}

		$reader = $this->reader_factory->make( $file_path );
		if ( null === $reader ) {
			return null;
		}

		$mapping = isset( $state['mapping'] ) ? $state['mapping'] : null;
		if ( null === $mapping ) {
			return null;
		}

		$offset              = isset( $state['offset'] ) ? $state['offset'] : 0;
		$prev_updated_number = isset( $state['updated'] ) ? $state['updated'] : 0;
		$prev_created_number = isset( $state['created'] ) ? $state['created'] : 0;
		$prev_failed_number  = isset( $state['failed'] ) ? $state['failed'] : 0;

		$rows = $this->mapper->map( $reader->read_rows(), $mapping );

		$seen      = 0;
		$processed = 0;
		$created   = 0;
		$updated   = 0;
		$failed    = 0;
		$status    = '';

		foreach ( $rows as $row ) {
			++$seen;
			if ( $seen <= $offset ) {
				continue;
			}

			$row_validate = $this->validator->validate( $row )[0];
			if ( false === $row_validate['valid'] ) {
				++$failed;
			} else {
				$row_import = $this->importer->import( $row );
				if ( false === $row_import['success'] ) {
					++$failed;
				} elseif ( 'updated' === $row_import['action'] ) {
					++$updated;
				} else {
					++$created;
				}
			}

			++$processed;
			if ( $processed >= $batch_size ) {
				break;
			}
		}

		if ( $processed < $batch_size ) {
			$status = 'done';
		} else {
			$status = 'running';
		}

		$changes = array(
			'offset'  => $offset + $processed,
			'updated' => $prev_updated_number + $updated,
			'created' => $prev_created_number + $created,
			'failed'  => $prev_failed_number + $failed,
			'status'  => $status,
		);

		$this->state->update( $import_id, $changes );

		return $this->state->get( $import_id );
	}
}
