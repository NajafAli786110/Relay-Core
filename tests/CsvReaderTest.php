<?php

use PHPUnit\Framework\TestCase;
use EngineWP\RelayCore\Import\CsvReader;

class CsvReaderTest extends TestCase {

    public function test_reads_all_rows() {

        $path_to_file = __DIR__ . '/fixtures/three-rows.csv';
        $reader = new CsvReader( $path_to_file );

        $rows = iterator_to_array( $reader->read_rows() );

        $this->assertCount( 3, $rows );
        $this->assertSame( 'A-1', $rows[0]['data']['External ID'] );
    }

    public function test_header_only_file_returns_no_rows() {

        $path_to_file = __DIR__ . '/fixtures/header-only.csv';
        $reader = new CsvReader( $path_to_file );

        $rows = iterator_to_array( $reader->read_rows() );

        $this->assertCount( 0, $rows );
    }

    public function test_row_with_wrong_column_count_has_empty_data() {

        $path_to_file = __DIR__ . '/fixtures/short-row.csv';
        $reader = new CsvReader( $path_to_file );

        $rows = iterator_to_array( $reader->read_rows() );

        $this->assertSame( array(), $rows[1]['data'] );
    }
}
