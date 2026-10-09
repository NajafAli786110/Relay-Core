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

use EngineWP\RelayCore\RelayCorePlugin;
use EngineWP\RelayCore\Admin\UploadPage;
use EngineWP\RelayCore\RegisterPostType;
use EngineWP\RelayCore\Import\BatchProcessor;
use EngineWP\RelayCore\Admin\ImportAjax;
use EngineWP\RelayCore\Import\ColumnMapper;
use EngineWP\RelayCore\Import\ListingValidator;
use EngineWP\RelayCore\Import\ImportState;
use EngineWP\RelayCore\Import\ListingImporter;
use EngineWP\RelayCore\Import\ReaderFactory;

define( 'RELAY_CORE_FILE', __FILE__ );

$relay_core_plugin = new RelayCorePlugin();
$relay_core_plugin->run();

$upload_page        = new UploadPage();
$register_post_type = new RegisterPostType();

$batch_processor = new BatchProcessor( new ImportState(), new ReaderFactory(), new ColumnMapper(), new ListingValidator(), new ListingImporter() );
$import_ajax     = new ImportAjax( $batch_processor );

add_action( 'admin_menu', array( $upload_page, 'register_menu' ) );
add_action( 'init', array( $register_post_type, 'register' ) );
add_action( 'wp_ajax_relay_core_import_batch', array( $import_ajax, 'handle_batch' ) );
