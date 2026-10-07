<?php
/**
 * FileValidator class.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Class FileValidator
 *
 * Validates the uploaded CSV file.
 *
 * @package EngineWP\RelayCore\Import
 */
class FileValidator {


	/**
	 * Validates the uploaded CSV file.
	 *
	 * @param array $file The uploaded file information from $_FILES.
	 *
	 * @return array An array of validation results, including any errors found.
	 */
	public function validate( array $file ) {

		$name_file_error = isset( $file['error'] ) ? intval( $file['error'] ) : '';
		if ( UPLOAD_ERR_OK !== $name_file_error ) {
			return array(
				'success' => false,
				'message' => 'No file uploaded or upload error occurred.',
			);
		}

		$file_name = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';
		$file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext && 'json' !== $file_ext ) {
			return array(
				'success' => false,
				'message' => 'Only CSV and JSON files are allowed.',
			);
		}

		$max_file_size  = 20 * 1024 * 1024;
		$name_file_size = isset( $file['size'] ) ? intval( $file['size'] ) : 0;
		if ( $name_file_size > $max_file_size ) {
			return array(
				'success' => false,
				'message' => 'File is too large. Max size is 20MB.',
			);
		}

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is server-generated, not user input.
		$tmp_file_path      = isset( $file['tmp_name'] ) ? $file['tmp_name'] : '';
		$allowed_mime_types = array( 'text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'application/json' );

		if ( '' === $tmp_file_path || ! file_exists( $tmp_file_path ) ) {
			return array(
				'success' => false,
				'message' => 'Temporary file not found.',
			);
		}

		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $tmp_file_path );
		finfo_close( $finfo );

		if ( ! in_array( $mime_type, $allowed_mime_types, true ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid file type detected.',
			);
		}

		return array(
			'success' => true,
			'message' => 'File is valid.',
		);
	}
}
