<?php
/**
 * Reader interface.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Contract for all data readers.
 */
interface ReaderInterface {

	/**
	 * Reads all rows from the source.
	 *
	 * @return array
	 */
	public function read_rows();

	/**
	 * Reads the header row from the source.
	 *
	 * @return array
	 */
	public function read_headers();

	/**
	 * Counts the data rows in the source.
	 *
	 * @return int
	 */
	public function count_rows();
}
