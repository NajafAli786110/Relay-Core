<?php

use PHPUnit\Framework\TestCase;
use EngineWP\RelayCore\Import\ListingValidator;

class ListingValidatorTest extends TestCase {

    public function test_empty_title() {

        $empty_title = array(
            'row_number' => 1,
            'post_title' => '',
            'external_id' => 'EXT001',
            'meta' => array(
                'business_name' => 'ABC Traders',
                'city' => 'Karachi',
            ),
        );

        $validator = new ListingValidator();
        $validate = $validator->validate( $empty_title );

        $this->assertSame( false, $validate[0]['valid'] );
        $this->assertSame( 'post_title', $validate[0]['error']['field'] );
    }

    public function test_spaces_only_title() {

        $spaces_only_title = array(
            'row_number' => 2,
            'post_title' => '    ',
            'external_id' => 'EXT002',
            'meta' => array(
                'business_name' => 'Test Business',
                'city' => 'Lahore',
            ),
        );

        $validator = new ListingValidator();
        $validate = $validator->validate( $spaces_only_title );

        $this->assertSame( false, $validate[0]['valid'] );
        $this->assertSame( 'post_title', $validate[0]['error']['field'] );
    }

    public function test_empty_external_id() {

        $empty_external_id = array(
            'row_number' => 3,
            'post_title' => 'Test Business',
            'external_id' => '',
            'meta' => array(
                'business_name' => 'Test Business',
                'city' => 'Islamabad',
            ),
        );

        $validator = new ListingValidator();
        $validate = $validator->validate( $empty_external_id );

        $this->assertSame( false, $validate[0]['valid'] );
        $this->assertSame( 'external_id', $validate[0]['error']['field'] );
    }

    public function test_spaces_only_external_id() {

        $spaces_only_external_id = array(
            'row_number' => 4,
            'post_title' => 'Demo Business',
            'external_id' => '    ',
            'meta' => array(
                'business_name' => 'Demo Business',
                'city' => 'Peshawar',
            ),
        );

        $validator = new ListingValidator();
        $validate = $validator->validate( $spaces_only_external_id );

        $this->assertSame( false, $validate[0]['valid'] );
        $this->assertSame( 'external_id', $validate[0]['error']['field'] );
    }

    public function test_valid_row() {

        $valid_row = array(
            'row_number' => 5,
            'post_title' => 'Bright Star Enterprises',
            'external_id' => 'EXT005',
            'meta' => array(
                'business_name' => 'Bright Star Enterprises',
                'city' => 'Multan',
            ),
        );

        $validator = new ListingValidator();
        $validate = $validator->validate( $valid_row );

        $this->assertSame( true, $validate[0]['valid'] );
        $this->assertSame( null, $validate[0]['error'] );
    }
}