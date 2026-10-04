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

$relay_core_plugin = new RelayCorePlugin();
$relay_core_plugin->run();

$upload_page        = new UploadPage();
$register_post_type = new RegisterPostType();

add_action( 'admin_menu', array( $upload_page, 'register_menu' ) );
add_action( 'init', array( $register_post_type, 'register' ) );
