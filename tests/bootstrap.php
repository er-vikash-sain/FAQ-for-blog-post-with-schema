<?php
/**
 * PHPUnit bootstrap for FAQ plugin.
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/bootstrap.php' ) ) {
	echo "Could not find WordPress tests bootstrap in {$_tests_dir}.\n";
	exit( 1 );
}

require $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin.
 */
function faq_plugin_manually_load_plugin() {
	require dirname( __DIR__ ) . '/faq.php';
}
tests_add_filter( 'muplugins_loaded', 'faq_plugin_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
