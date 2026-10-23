<?php

use PHPUnit\Framework\TestCase;
use EngineWP\RelayCore\Import\JsonReader;

class JsonReaderTest extends TestCase {

    public function test_blank_array() {

        $file_path = __DIR__ . '/fixtures/single-array.json';
        $this->read_file_path_for_wrong_result_always_blank_array( $file_path );

    }

    public function test_wrong_file_path() {

        $file_path = __DIR__ . '/fixtures/not-exist.json';
        $this->read_file_path_for_wrong_result_always_blank_array( $file_path );

    }

    public function test_cannot_decode_except_json() {

        $file_path = __DIR__ . '/fixtures/blank-file.json';
        $this->read_file_path_for_wrong_result_always_blank_array( $file_path );

    }

    public function test_data_is_not_array() {

        $file_path = __DIR__ . '/fixtures/not-array.json';
        $this->read_file_path_for_wrong_result_always_blank_array( $file_path );

    }

    public function test_found_sub_array() {

        $file_path = __DIR__ . '/fixtures/sub-array.json';
        $this->read_file_path_for_wrong_result_always_blank_array( $file_path );

    }

    public function test_count_data() {

        $file_path = __DIR__ . '/fixtures/five-data.json';

        $reader = new JsonReader( $file_path );
        $result = $reader->read_rows();

        $this->assertCount( 5, iterator_to_array( $result ) );
    }

    public function test_check_fine_array() {
        $file_path = __DIR__ . '/fixtures/okay-array.json';

        $reader = new JsonReader( $file_path );
        $row = $reader->read_rows();
        $header = $reader->read_headers();
        $count_row = $reader->count_rows();

        $row_result = iterator_to_array( $row );

        $this->assertSame( 1, $row_result[0]['row_number'] );
        $this->assertSame( 'J-001', $row_result[0]['data']['External ID'] );
        $this->assertSame( 'External ID', $header[0] );
        $this->assertSame( 1, $count_row );
    }

    private function read_file_path_for_wrong_result_always_blank_array( string $file_path ) {
        $reader = new JsonReader( $file_path );
        $result = $reader->read_rows();

        $this->assertSame( array(), iterator_to_array( $result ) );
    }
}