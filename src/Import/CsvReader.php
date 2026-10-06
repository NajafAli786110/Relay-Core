<?php
/**
 * Reads rows from a CSV file.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Reads a CSV file and returns its rows as associative arrays.
 */
class CsvReader implements ReaderInterface {


	/**
	 * Path to the CSV file on disk.
	 *
	 * @var string
	 */
	private $file_path;

	/**
	 * Constructor.
	 *
	 * @param string $file_path Absolute path to the CSV file.
	 */
	public function __construct( string $file_path ) {
		$this->file_path = $file_path;
	}

	/**
	 * Reads the CSV and returns rows keyed by header.
	 *
	 * @return array[] List of associative arrays, one per row.
	 */
	public function read_rows() {

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming line by line.
		$handle = fopen( $this->file_path, 'r' );

		if ( false === $handle ) {
			return array();
		}

		$headers = fgetcsv( $handle );

		if ( false === $headers ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return array();
		}

		// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure -- Standard fgetcsv loop pattern.
		$row        = fgetcsv( $handle );
		$row_number = 2;

		while ( false !== $row ) {
			if ( count( $row ) !== count( $headers ) ) {
				$data = array();
			} else {
				$data = array_combine( $headers, $row );
			}

			yield array(
				'row_number' => $row_number,
				'data'       => $data,
			);
			$row = fgetcsv( $handle );
			++$row_number;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Reads only the header row from the CSV file.
	 *
	 * @return array List of column names, or empty array on failure.
	 */
	public function read_headers() {
		$headers = array();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming line by line.
		$handle = fopen( $this->file_path, 'r' );

		if ( false === $handle ) {
			return array();
		}

		$headers = fgetcsv( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return false === $headers ? array() : $headers;
	}
}
