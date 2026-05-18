<?php
/**
 * PHPUnit bootstrap.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/tests/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( __DIR__ ) . '/tests/wp-content' );
}

require dirname( __DIR__ ) . '/vendor/autoload.php';
