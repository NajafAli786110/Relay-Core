<?php
/**
 * ReaderFactory class.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Creates the reader that matches a file's extension.
 */
class ReaderFactory {

	/**
	 * Returns a reader for the given file.
	 *
	 * @param string $file_path Absolute path to the file to read.
	 * @return ReaderInterface|null A CSV or JSON reader, or null if the file type is not supported.
	 */
	public function make( string $file_path ) {
		$file_ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( 'csv' === $file_ext ) {
			return new CsvReader( $file_path );
		}
		if ( 'json' === $file_ext ) {
			return new JsonReader( $file_path );
		}

		return null;
	}
}
