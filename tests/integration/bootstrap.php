<?php
require dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$tests_dir = getenv( 'WP_TESTS_DIR' );

require $tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__, 2 ) . '/relay-core.php';
	}
);

require $tests_dir . '/includes/bootstrap.php';