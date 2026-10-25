<?php

use EngineWP\RelayCore\Import\ImportState;

class ImportsRestTest extends WP_UnitTestCase {
    public function test_logged_out_user_gets_401() {
        $request = new WP_REST_Request( 'GET', '/relay-core/v1/imports' );
        $result = rest_do_request( $request );

        $this->assertSame( 401, $result->get_status() );
    }

    public function test_logged_in_users_get_200() {
        $request = new WP_REST_Request( 'GET', '/relay-core/v1/imports' );
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

        wp_set_current_user( $admin_id );
        $response = rest_do_request( $request );

        $this->assertSame( 200, $response->get_status() );
    }

    public function test_except_admin_users_get_403() {
        $request = new WP_REST_Request( "GET", '/relay-core/v1/imports' );
        $admin_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

        wp_set_current_user( $admin_id );
        $response = rest_do_request( $request );

        $this->assertSame( 403, $response->get_status() );
    }

    public function test_import_and_check_file_path_and_count() {
        $import_state = new ImportState();

        $data = array(
            'file_path' => 'http://localhost:8888/wp-content/uploads/2026/10/customers-100.csv',
            'status' => 'running',
            'offset' => 20,
            'total' => 100,
            'created' => 40,
            'updated' => 30,
            'failed' => 0,
        );

        $create_id = $import_state->create( $data );
        $request = new WP_REST_Request( 'GET', "/relay-core/v1/imports/{$create_id}" );
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

        wp_set_current_user( $admin_id );

        $response = rest_do_request( $request );
        $response_data = $response->get_data();

        $this->assertCount( 1, array( $response ) );
        $this->assertSame( $create_id, $response_data['id'] );
        $this->assertSame( 200, $response->get_status() );
        $this->assertArrayNotHasKey( 'file_path', $response_data );
        $this->assertIsArray( $response_data );
    }

    public function test_admin_just_get_an_array_after_rollback() {
        $request = new WP_REST_Request( "GET", '/relay-core/v1/imports' );
        $admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

        wp_set_current_user( $admin_id );
        $response = rest_do_request( $request );

        $this->assertSame( array(), $response->get_data() );
    }

    public static function providePermissionCases() {
        $hex_character_64 = str_repeat( 'a', 64 );
        return array(
            array( '/relay-core/v1/imports', 'logged_out', 401 ),
            array( '/relay-core/v1/imports', 'subscriber', 403 ),
            array( "/relay-core/v1/imports/{$hex_character_64}", 'logged_out', 401 ),
            array( "/relay-core/v1/imports/{$hex_character_64}", 'subscriber', 403 ),
            array( "/relay-core/v1/imports/{$hex_character_64}/logs", 'logged_out', 401 ),
            array( "/relay-core/v1/imports/{$hex_character_64}/logs", 'subscriber', 403 ),
        );
    }

    /**
     * @dataProvider providePermissionCases
     */
    public function test_only_admin_can_see_logs( string $route, string $role, int $status ) {
        if ( 'logged_out' !== $role ) {
            $subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
            wp_set_current_user( $subscriber_id );

            $request = new WP_REST_Request( "GET", $route );
            $response = rest_do_request( $request );

            $this->assertEquals( $status, $response->get_status() );
        }

        $request = new WP_REST_Request( "GET", $route );
        $response = rest_do_request( $request );

        $this->assertEquals( $status, $response->get_status() );
    }
}