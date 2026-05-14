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
