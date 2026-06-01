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

if ( ! defined( 'OSSR_VERSION' ) ) {
	define( 'OSSR_VERSION', '1.0.0' );
}

if ( ! defined( 'OSSR_PLUGIN_FILE' ) ) {
	define( 'OSSR_PLUGIN_FILE', __DIR__ . '/omoikane-simple-sales-reports.php' );
}

if ( ! defined( 'OSSR_PLUGIN_DIR' ) ) {
	define( 'OSSR_PLUGIN_DIR', __DIR__ . '/' );
}

if ( ! defined( 'OSSR_PLUGIN_URL' ) ) {
	define( 'OSSR_PLUGIN_URL', 'https://example.test/wp-content/plugins/omoikane-simple-sales-reports/' );
}

if ( ! defined( 'OSSR_PLUGIN_BASENAME' ) ) {
	define( 'OSSR_PLUGIN_BASENAME', 'omoikane-simple-sales-reports/omoikane-simple-sales-reports.php' );
}
