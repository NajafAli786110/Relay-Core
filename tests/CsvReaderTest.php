<?php

use PHPUnit\Framework\TestCase;
use EngineWP\RelayCore\Import\CsvReader;

class CsvReaderTest extends TestCase {

    public function test_reads_all_rows() {

        $path_to_file = __DIR__ . '/fixtures/three-rows.csv';

        $reader = new CsvReader( $path_to_file );

        $rows = iterator_to_array( $reader->read_rows() );

        $this->assertCount(3, $rows);
        $this->assertSame('A-1', $rows[0]['data']['External ID']);
    }
}
