<?php
/**
 * Plugin Name:       Omoikane Simple Sales Reports for Welcart
 * Plugin URI:        https://github.com/omoikane-works/omoikane-simple-sales-reports
 * Description:       Create simple sales reports for Welcart stores.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Omoikane Works
 * Author URI:        https://github.com/omoikane-works
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       omoikane-simple-sales-reports
 * Domain Path:       /languages
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

use OmoikaneWorks\SimpleSalesReports\Activation;
use OmoikaneWorks\SimpleSalesReports\Plugin;

defined( 'ABSPATH' ) || exit;

define( 'OSSR_VERSION', '1.0.0' );
define( 'OSSR_PLUGIN_FILE', __FILE__ );
define( 'OSSR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OSSR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OSSR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'OSSR_MINIMUM_PHP_VERSION', '8.2' );

/**
 * Check whether the current PHP version is supported.
 *
 * @return  bool
 */
function ossr_is_supported_php_version(): bool {
	return version_compare( PHP_VERSION, OSSR_MINIMUM_PHP_VERSION, '>=' );
}

/**
 * Get unsupported PHP version message.
 *
 * @return  string
 */
function ossr_get_unsupported_php_version_message(): string {
	return sprintf(
		// translators: 1: required PHP version, 2: current PHP version.
		__(
			'Omoikane Simple Sales Reports for Welcart requires PHP version %1$s or later. Your current PHP version is %2$s.',
			'omoikane-simple-sales-reports'
		),
		OSSR_MINIMUM_PHP_VERSION,
		PHP_VERSION
	);
}

/**
 * Stop plugin activation when PHP version is unsupported.
 *
 * @return  void
 */
function ossr_activate_php_version_guard(): void {
	if ( ossr_is_supported_php_version() ) {
		return;
	}

	wp_die(
		esc_html( ossr_get_unsupported_php_version_message() ),
		esc_html__( 'Plugin activation failed.', 'omoikane-simple-sales-reports' ),
		array(
			'back_link' => true,
		)
	);
}

register_activation_hook(
	__FILE__,
	'ossr_activate_php_version_guard'
);

if ( ! ossr_is_supported_php_version() ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>';
			echo esc_html( ossr_get_unsupported_php_version_message() );
			echo '</p></div>';
		}
	);

	return;
}

$ossr_autoload = OSSR_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $ossr_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			echo '<div class="notice notice-error"><p>';
			echo esc_html__(
				'Required libraries for Omoikane Simple Sales Reports for Welcart were not found. Please run composer install.',
				'omoikane-simple-sales-reports'
			);
			echo '</p></div>';
		}
	);

	return;
}

require_once $ossr_autoload;

register_activation_hook(
	__FILE__,
	static function (): void {
		Activation::activate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->boot();
	}
);
