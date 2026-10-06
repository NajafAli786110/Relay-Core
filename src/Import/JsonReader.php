<?php
/**
 * Reads rows from a JSON file.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Reads a JSON file and returns its rows as associative arrays.
 *
 * Only flat JSON arrays are supported. Nested arrays or objects will cause
 * the entire file to be rejected.
 */
class JsonReader implements ReaderInterface {


	/**
	 * Path to the JSON file on disk.
	 *
	 * @var string
	 */
	private $file_path;

	/**
	 * Constructor.
	 *
	 * @param string $file_path Absolute path to the JSON file.
	 */
	public function __construct( string $file_path ) {
		$this->file_path = $file_path;
	}

	/**
	 * Reads the JSON and returns rows keyed by index.
	 *
	 * @return array[] List of associative arrays, one per row.
	 */
	public function read_rows() {
		$data = $this->load_data();

		if ( empty( $data ) ) {
			return array();
		}

		$row_number = 1;

		foreach ( $data as $entry ) {
			yield array(
				'row_number' => $row_number,
				'data'       => $entry,
			);

			++$row_number;
		}
	}

	/**
	 * Reads the header keys from the first JSON entry.
	 *
	 * @return array List of column names.
	 */
	public function read_headers() {
		$data = $this->load_data();

		if ( empty( $data ) ) {
			return array();
		}

		return array_keys( $data[0] );
	}

	/**
	 * Loads and validates the JSON file.
	 *
	 * Returns an empty array if the file is missing, unreadable, invalid,
	 * or contains nested structures.
	 *
	 * @return array Flat array of associative arrays, or empty array on failure.
	 */
	private function load_data() {
		if ( ! file_exists( $this->file_path ) ) {
			return array();
		}

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read for import.
		$json_content = file_get_contents( $this->file_path );

		if ( false === $json_content ) {
			return array();
		}

		$data = json_decode( $json_content, true );

		if ( null === $data || ! is_array( $data ) ) {
			return array();
		}

		foreach ( $data as $entry ) {
			if ( ! is_array( $entry ) ) {
				return array();
			}

			foreach ( $entry as $value ) {
				if ( is_array( $value ) ) {
					return array();
				}
			}
		}

		return $data;
	}
}
