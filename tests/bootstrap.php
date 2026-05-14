<?php
/**
 * PHPUnit bootstrap file
 */

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Allow overriding the wp-phpunit dir post-autoload — wp-phpunit's
// composer autoload sets WP_PHPUNIT__DIR to its locked version, which can
// lag the WordPress core under test. CI uses this to point at a matching
// wordpress-develop checkout.
$wp_phpunit_override = getenv( 'WP_PHPUNIT__DIR_OVERRIDE' );
if ( $wp_phpunit_override ) {
	putenv( 'WP_PHPUNIT__DIR=' . $wp_phpunit_override );
	// The wordpress-develop checkout has no wp-tests-config.php shim
	// (only wp-tests-config-sample.php). Point WP's bootstrap directly
	// at our config via the constant it respects.
	define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-config.php' );
}

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function() {
		// test set up, plugin activation, etc.
		require dirname( __DIR__ ) . '/unlist-posts.php';
		require dirname( __DIR__ ) . '/class-unlist-posts-admin.php';
	}
);

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
