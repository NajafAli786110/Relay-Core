<?php
/**
 * Imports listings from a CSV file.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Imports listings from a CSV file.
 */
class ListingImporter {

	/**
	 * Imports a listing.
	 *
	 * @param array $listing The listing to import.
	 */
	public function import( array $listing ) {
		$first_key  = array_key_first( $listing );
		$post_title = $listing[ $first_key ] ?? 'No Title';

		// Remove the first key-value pair from the listing array.
		unset( $listing[ $first_key ] );

		$meta_input = array();
		foreach ( $listing as $key => $value ) {
			$meta_input[ sanitize_title( $key ) ] = $value;
		}

		// Insert the post into the database.
		$post_id = wp_insert_post(
			array(
				'post_title'  => $post_title,
				'post_type'   => 'listing',
				'post_status' => 'publish',
				'meta_input'  => $meta_input,
			)
		);

		// Check for errors during post insertion.
		if ( is_wp_error( $post_id ) ) {
			return array(
				'success' => false,
				'post_id' => 0,
				'error'   => $post_id->get_error_message(),
			);
		}

		// Return success response.
		return array(
			'success' => true,
			'post_id' => $post_id,
			'error'   => null,
		);
	}
}
