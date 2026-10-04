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


	const REQUIRED_ERROR      = 'required';
	const EMPTY_LISTING_ERROR = 'empty_listing';
	/**
	 * Validates a listing.
	 *
	 * @param array $listing The listing to validate.
	 * @param int   $row_number The row number being validated.
	 */
	public function validate( $listing, int $row_number ) {
		$validation_errors = array();

		// Check if the listing is empty.
		$check_row_text = array_filter(
			$listing,
			function ( $value ) {
				return '' !== trim( (string) $value );
			}
		);

		// Check if the listing is empty.
		if ( empty( $check_row_text ) ) {
			$validation_errors[] = array(
				'valid' => false,
				'error' => array(
					'row_number' => $row_number,
					'field'      => 'row',
					'error'      => self::EMPTY_LISTING_ERROR,
					'message'    => 'Either column mismatch or your listing is empty, check please!',
				),
			);

			return $validation_errors;
		}

		// Check for required fields.
		$first = reset( $listing );
		if ( '' === trim( (string) $first ) ) {
			$validation_errors[] = array(
				'valid' => false,
				'error' => array(
					'row_number' => $row_number,
					'field'      => array_key_first( $listing ),
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
