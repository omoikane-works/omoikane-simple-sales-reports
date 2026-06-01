<?php
/**
 * PHPStan bootstrap for plugin constants.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'WSRS_VERSION' ) ) {
	define( 'WSRS_VERSION', '1.0.0' );
}

if ( ! defined( 'WSRS_PLUGIN_FILE' ) ) {
	define( 'WSRS_PLUGIN_FILE', __DIR__ . '/omoikane-simple-sales-reports.php' );
}

if ( ! defined( 'WSRS_PLUGIN_DIR' ) ) {
	define( 'WSRS_PLUGIN_DIR', __DIR__ . '/' );
}

if ( ! defined( 'WSRS_PLUGIN_URL' ) ) {
	define( 'WSRS_PLUGIN_URL', 'https://example.test/wp-content/plugins/omoikane-simple-sales-reports/' );
}

if ( ! defined( 'WSRS_PLUGIN_BASENAME' ) ) {
	define( 'WSRS_PLUGIN_BASENAME', 'omoikane-simple-sales-reports/omoikane-simple-sales-reports.php' );
}
