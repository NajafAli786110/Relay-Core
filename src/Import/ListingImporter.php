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
		$post_title          = isset( $listing['post_title'] ) ? sanitize_text_field( $listing['post_title'] ) : '';
		$external_id         = isset( $listing['external_id'] ) ? sanitize_text_field( $listing['external_id'] ) : '';
		$meta                = isset( $listing['meta'] ) && is_array( $listing['meta'] ) ? $this->sanitize_meta( $listing['meta'] ) : array();
		$meta['external_id'] = $external_id;

		$existing_post = get_posts(
			array(
				'post_type'      => 'listing',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- external_id lookup, small dataset.
				'meta_key'       => 'external_id',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- external_id lookup, small dataset.
				'meta_value'     => $external_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing_post ) ) {
			$post_id = wp_update_post(
				array(
					'ID'         => $existing_post[0],
					'post_title' => $post_title,
					'meta_input' => $meta,
				)
			);

			// Check for errors during post update.
			if ( is_wp_error( $post_id ) || 0 === $post_id ) {
				return array(
					'success' => false,
					'post_id' => 0,
					'action'  => null,
					'error'   => is_wp_error( $post_id ) ? $post_id->get_error_message() : 'Update failed.',
				);
			}

			// Return success response.
			return array(
				'success' => true,
				'post_id' => $post_id,
				'action'  => 'updated',
				'error'   => null,
			);
		}

		// Insert the post into the database.
		$post_id = wp_insert_post(
			array(
				'post_title'  => $post_title,
				'post_type'   => 'listing',
				'post_status' => 'publish',
				'meta_input'  => $meta,
			)
		);

		// Check for errors during post insertion.
		if ( is_wp_error( $post_id ) || 0 === $post_id ) {
			return array(
				'success' => false,
				'post_id' => 0,
				'action'  => null,
				'error'   => is_wp_error( $post_id ) ? $post_id->get_error_message() : 'Insert failed.',
			);
		}

		// Return success response.
		return array(
			'success' => true,
			'post_id' => $post_id,
			'action'  => 'created',
			'error'   => null,
		);
	}

	/**
	 * Sanitizes meta values based on their type.
	 *
	 * @param array $meta The meta data to sanitize.
	 * @return array The sanitized meta data.
	 */
	private function sanitize_meta( array $meta ) {
		$sanitized_meta = array();

		foreach ( $meta as $key => $value ) {
			if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
				$sanitized_meta[ $key ] = esc_url_raw( $value );
			} else {
				$sanitized_meta[ $key ] = sanitize_text_field( $value );
			}
		}
		return $sanitized_meta;
	}
}
