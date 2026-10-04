<?php
/**
 * Validates a listing.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Validates a listing.
 */
class ListingValidator {

	const REQUIRED_ERROR = 'required';
	/**
	 * Validates a listing.
	 *
	 * @param array $listing The listing to validate.
	 */
	public function validate( array $listing ) {
		$validation_errors = array();
		$row_number        = isset( $listing['row_number'] ) ? $listing['row_number'] : 0;
		$post_title        = isset( $listing['post_title'] ) ? $listing['post_title'] : '';
		$external_id       = isset( $listing['external_id'] ) ? $listing['external_id'] : '';

		if ( ! isset( $post_title ) || '' === trim( (string) $post_title ) ) {
			$validation_errors[] = array(
				'valid' => false,
				'error' => array(
					'row_number' => $row_number,
					'field'      => 'post_title',
					'error'      => self::REQUIRED_ERROR,
					'message'    => 'Required field is empty.',
				),
			);

			return $validation_errors;
		}

		if ( ! isset( $external_id ) || '' === trim( (string) $external_id ) ) {
			$validation_errors[] = array(
				'valid' => false,
				'error' => array(
					'row_number' => $row_number,
					'field'      => 'external_id',
					'error'      => self::REQUIRED_ERROR,
					'message'    => 'Required field is empty.',
				),
			);

			return $validation_errors;
		}

		// Successful validation, no errors found.
		$validation_errors[] = array(
			'valid' => true,
			'error' => null,
		);

		return $validation_errors;
	}
}
