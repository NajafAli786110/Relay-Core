<?php
/**
 * ImportLog class.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Stores the rows that failed during an import in a custom database table.
 */
class ImportLog {

	/**
	 * Creates the log table, or updates it if its columns have changed.
	 *
	 * Runs when the plugin is activated.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$table           = $this->table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
          id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          import_id char(64) NOT NULL,
          row_num int(10) unsigned NOT NULL,
          field varchar(100) NOT NULL DEFAULT '',
          message varchar(255) NOT NULL DEFAULT '',
          created_at datetime NOT NULL,
          PRIMARY KEY  (id),
          KEY import_id (import_id)
        ) {$charset_collate}";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Returns the name of the log table, including the site's table prefix.
	 *
	 * @return string
	 */
	private function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'relay_core_logs';
	}

	/**
	 * Saves one failed row.
	 *
	 * @param string $import_id  ID of the import the row belongs to.
	 * @param int    $row_number Row number in the source file.
	 * @param string $field      Field that caused the failure, or an empty string if it is not field specific.
	 * @param string $message    Reason the row failed.
	 * @return void
	 */
	public function add( string $import_id, int $row_number, string $field, string $message ) {
		global $wpdb;

		$entry = array(
			'import_id'  => $import_id,
			'row_num'    => $row_number,
			'field'      => $field,
			'message'    => $message,
			'created_at' => current_time( 'mysql' ),
		);

		$entry_type = array( '%s', '%d', '%s', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table; WordPress has no API for it.
		$wpdb->insert( $this->table_name(), $entry, $entry_type );
	}

	/**
	 * Returns the failed rows of one import, ordered by row number.
	 *
	 * @param string $import_id ID of the import.
	 * @param int    $limit     Maximum number of rows to return.
	 * @return array[] List of rows, each with row_num, field and message.
	 */
	public function get_for_import( string $import_id, int $limit = 100 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; read once when an import finishes.
		$result = $wpdb->get_results(
			$wpdb->prepare( 'SELECT row_num, field, message FROM %i WHERE import_id = %s ORDER BY row_num ASC LIMIT %d', $this->table_name(), $import_id, $limit ),
			ARRAY_A
		);

		return $result;
	}
}
