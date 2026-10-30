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
	 * @param int   $existing_id Id of the exisitng post, so we can verify create or update.
	 */
	public function import( array $listing, int $existing_id ) {
		$post_title          = isset( $listing['post_title'] ) ? sanitize_text_field( $listing['post_title'] ) : '';
		$external_id         = isset( $listing['external_id'] ) ? sanitize_text_field( $listing['external_id'] ) : '';
		$meta                = isset( $listing['meta'] ) && is_array( $listing['meta'] ) ? $this->sanitize_meta( $listing['meta'] ) : array();
		$meta['external_id'] = $external_id;

		if ( 0 !== $existing_id ) {
			$post_id = wp_update_post(
				array(
					'ID'         => $existing_id,
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

	/**
	 * Finds which external IDs already belong to a listing.
	 *
	 * Runs one query for the whole batch, so the importer does not have to
	 * look up each row on its own. Drafts are included; other post types are not.
	 *
	 * @param string[] $ids External IDs from one batch.
	 * @return int[] Post IDs keyed by external ID. IDs with no listing are left out.
	 */
	public function find_existing_ids( array $ids ) {
		global $wpdb;

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return array();
		}

		$placeholder = implode( ', ', array_fill( 0, count( $ids ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One lookup per batch; $placeholder holds only %s placeholders, the values go through prepare().
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.post_id, m.meta_value 
				FROM $wpdb->posts p
				JOIN $wpdb->postmeta m
				ON p.ID = m.post_id
				WHERE post_type = 'listing'
				AND m.meta_key = 'external_id'
				AND m.meta_value IN ( $placeholder )
				",
				$ids
			),
			ARRAY_A
		);

		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['meta_value'] ] = (int) $row['post_id'];
		}

		return $result;
	}
}
