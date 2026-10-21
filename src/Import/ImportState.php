<?php
/**
 * ImportState class.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Manages the state of an import process.
 */
class ImportState {

	/**
	 * Returns every saved import, newest first.
	 *
	 * Each item has the import ID and its full state, including the file path,
	 * so internal code can use it. Callers that send data to the browser must
	 * remove private fields themselves.
	 *
	 * @return array[] List of arrays with 'id' and 'state' keys. Empty if there are no imports.
	 */
	public function all() {
		global $wpdb;

		$prefix = 'relay_core_import_';

		$search_term = $wpdb->esc_like( $prefix ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No WordPress API lists options by name prefix.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value
        FROM {$wpdb->options}
        WHERE option_name LIKE %s
        ORDER BY option_id DESC",
				$search_term
			)
		);

		$imports = array();

		foreach ( $results as $row ) {
			$data      = maybe_unserialize( $row->option_value );
			$imports[] = array(
				'id'    => substr( $row->option_name, strlen( $prefix ) ),
				'state' => $data,
			);
		}

		return $imports;
	}

	/**
	 * Creates a new import state.
	 *
	 * @param array $data The data for the new import state.
	 * @return string The ID of the newly created import state.
	 */
	public function create( array $data ) {
		$session_id = bin2hex( random_bytes( 32 ) );

		$new_data = array_merge(
			array(
				'created_at' => time(),
			),
			$data
		);

		$option_name = $this->create_option_name( $session_id );

		if ( ! add_option( $option_name, $new_data, '', false ) ) {
			return null;
		}

		return $session_id;
	}

	/**
	 * Updates an existing import state.
	 *
	 * @param string $id The ID of the import state to update.
	 * @param array  $changes The changes to apply to the import state.
	 * @return bool The updated import state.
	 */
	public function update( string $id, array $changes ) {
		$prev_data = $this->get( $id );
		if ( null === $prev_data ) {
			return false;
		}

		$new_data = array_merge(
			$prev_data,
			$changes
		);

		$option_name = $this->create_option_name( $id );
		update_option( $option_name, $new_data, false );

		return true;
	}

	/**
	 * Retrieves an import state by its ID.
	 *
	 * @param string $id The ID of the import state to retrieve.
	 * @return array|null The retrieved import state.
	 */
	public function get( string $id ) {
		if ( ! $this->is_valid_id( $id ) ) {
			return null;
		}

		$option_name = $this->create_option_name( $id );

		$state = get_option( $option_name, null );

		if ( is_array( $state ) ) {
			return $state;
		} else {
			return null;
		}
	}

	/**
	 * Deletes an import state by its ID.
	 *
	 * @param string $id The ID of the import state to delete.
	 * @return bool
	 */
	public function delete( string $id ) {
		if ( ! $this->is_valid_id( $id ) ) {
			return false;
		}

		$option_name = $this->create_option_name( $id );

		if ( ! delete_option( $option_name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Generates the option name for storing the import state in the database.
	 *
	 * @param string $id The ID of the import state.
	 * @return string The generated option name.
	 */
	private function create_option_name( string $id ): string {
		return 'relay_core_import_' . $id;
	}

	/**
	 * Check the id is fine or not
	 *
	 * @param string $id take the id which type string.
	 * @return bool return true eitheir false.
	 */
	private function is_valid_id( string $id ): bool {
		return ctype_xdigit( $id ) && 64 === strlen( $id );
	}
}
