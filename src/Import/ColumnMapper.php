<?php
/**
 * ColumnMapper class.
 *
 * @package EngineWP\RelayCore\Import
 */

namespace EngineWP\RelayCore\Import;

/**
 * Maps CSV columns to standard keys.
 */
class ColumnMapper {

	/**
	 * Maps CSV rows using user-provided column mapping.
	 *
	 * @param array $rows    Rows from CsvReader.
	 * @param array $mapping Mapping array with post_title and external_id keys.
	 * @return array Mapped rows.
	 */
	public function map( array $rows, array $mapping ) {
		$mapped = array();

		foreach ( $rows as $row ) {
			$row_number = $row['row_number'];
			$data       = $row['data'];

			$post_title  = $data[ $mapping['post_title'] ] ?? '';
			$external_id = $data[ $mapping['external_id'] ] ?? '';

			$meta = array();

			foreach ( $data as $key => $value ) {
				if ( $key !== $mapping['post_title'] && $key !== $mapping['external_id'] ) {
					$meta[ sanitize_title( $key ) ] = $value;
				}
			}

			$mapped[] = array(
				'row_number'  => $row_number,
				'post_title'  => $post_title,
				'external_id' => $external_id,
				'meta'        => $meta,
			);
		}

		return $mapped;
	}
}
