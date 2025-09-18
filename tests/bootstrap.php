<?php
/**
 * PHPUnit bootstrap file for Aria plugin tests.
 *
 * @package Aria
 */

// Define test environment.
define( 'ARIA_PHPUNIT_RUNNING', true );

// Define plugin paths.
define( 'ARIA_PLUGIN_DIR', dirname( dirname( __FILE__ ) ) . '/' );
define( 'ARIA_TESTS_DIR', dirname( __FILE__ ) . '/' );

// Load WordPress test environment.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

$polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( $polyfills_path && ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills_path );
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require ARIA_PLUGIN_DIR . 'aria.php';
	require_once ARIA_PLUGIN_DIR . 'includes/class-aria-activator.php';
	Aria_Activator::activate();
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
