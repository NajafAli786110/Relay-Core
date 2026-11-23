<?php
/**
 * Plugin Name: Relay Core
 * Description: CSV importer for Listings CPT.
 * Version: 0.1.0
 * Author: EngineWP
 * License: GPL-2.0-or-later
 * Text Domain: relay-core
 *
 * @package EngineWP\RelayCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

use EngineWP\RelayCore\Admin\UploadPage;
use EngineWP\RelayCore\RegisterPostType;
use EngineWP\RelayCore\Import\BatchProcessor;
use EngineWP\RelayCore\Admin\ImportAjax;
use EngineWP\RelayCore\Import\ColumnMapper;
use EngineWP\RelayCore\Import\ListingValidator;
use EngineWP\RelayCore\Import\ImportState;
use EngineWP\RelayCore\Import\ListingImporter;
use EngineWP\RelayCore\Import\ReaderFactory;
use EngineWP\RelayCore\Import\ImportLog;
use EngineWP\RelayCore\Import\BackgroundImport;
use EngineWP\RelayCore\Rest\ImportsController;
use EngineWP\RelayCore\Import\StuckImportChecker;
use EngineWP\RelayCore\Admin\LogsPage;

define( 'RELAY_CORE_FILE', __FILE__ );
const RELAY_CORE_DB_VERSION = '0.1.0';

$register_post_type = new RegisterPostType();

$import_log        = new ImportLog();
$import_state      = new ImportState();
$import_controller = new ImportsController( $import_state, $import_log );
$batch_processor   = new BatchProcessor( $import_state, new ReaderFactory(), new ColumnMapper(), new ListingValidator(), new ListingImporter(), $import_log );
$import_ajax       = new ImportAjax( $import_log, $import_state );
$logs_page         = new LogsPage();

add_action( 'init', array( $register_post_type, 'register' ) );
add_action( 'rest_api_init', array( $import_controller, 'register_routes' ) );
add_action( 'wp_ajax_relay_core_import_status', array( $import_ajax, 'handle_status' ) );

register_activation_hook( RELAY_CORE_FILE, array( $import_log, 'create_table' ) );

add_action(
	'plugins_loaded',
	function () use ( $import_log ) {
		if ( RELAY_CORE_DB_VERSION !== get_option( 'relay_core_db_version' ) ) {
			$import_log->create_table();
			update_option( 'relay_core_db_version', RELAY_CORE_DB_VERSION );
		}
	}
);

$background_import    = new BackgroundImport( $batch_processor );
$stuck_import_checker = new StuckImportChecker( $import_state, $background_import );

$upload_page = new UploadPage( $background_import );
add_action( 'admin_menu', array( $upload_page, 'register_menu' ) );
add_action( 'admin_menu', array( $logs_page, 'register_page_menu' ) );
add_action( 'admin_enqueue_scripts', array( $logs_page, 'enqueue' ) );
add_action( 'admin_enqueue_scripts', array( $upload_page, 'enqueue_styles' ) );

add_action( 'relay_core_run_batch', array( $background_import, 'run_batch' ) );
add_action( 'relay_core_check_stuck', array( $stuck_import_checker, 'run' ) );

add_action(
	'init',
	function () {
		$is_scheduled = as_has_scheduled_action( 'relay_core_check_stuck', array(), 'relay-core' );

		if ( ! $is_scheduled ) {
			as_schedule_recurring_action( time(), 5 * MINUTE_IN_SECONDS, 'relay_core_check_stuck', array(), 'relay-core', true );
		}
	}
);
