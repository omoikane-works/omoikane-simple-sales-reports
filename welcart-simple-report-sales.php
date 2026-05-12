<?php
/**
 * Plugin Name:       Welcart Simple Report Sales
 * Plugin URI:        https://github.com/omoikane-works/welcart-simple-report-sales
 * Description:       Welcart の売上報告書をかんたんに作成・印刷できるプラグインです。
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Omoikane Works
 * Author URI:        https://github.com/omoikane-works
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       welcart-simple-report-sales
 * Domain Path:       /languages
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

use OmoikaneWorks\WelcartSimpleReportSales\Activation;
use OmoikaneWorks\WelcartSimpleReportSales\Plugin;

defined( 'ABSPATH' ) || exit;

define( 'WSRS_VERSION', '0.1.0' );
define( 'WSRS_PLUGIN_FILE', __FILE__ );
define( 'WSRS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSRS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSRS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WSRS_MINIMUM_PHP_VERSION', '8.2' );

/**
 * Check whether the current PHP version is supported.
 *
 * @return  bool
 */
function wsrs_is_supported_php_version(): bool {
	return version_compare( PHP_VERSION, WSRS_MINIMUM_PHP_VERSION, '>=' );
}

/**
 * Get unsupported PHP version message.
 *
 * @return  string
 */
function wsrs_get_unsupported_php_version_message(): string {
	return sprintf(
		// translators: 1: required PHP version, 2: current PHP version.
		__(
			'Welcart Simple Report Sales requires PHP version %1$s or later. Your current PHP version is %2$s.',
			'welcart-simple-report-sales'
		),
		WSRS_MINIMUM_PHP_VERSION,
		PHP_VERSION
	);
}

/**
 * Stop plugin activation when PHP version is unsupported.
 *
 * @return  void
 */
function wsrs_activate_php_version_guard(): void {
	if ( wsrs_is_supported_php_version() ) {
		return;
	}

	wp_die(
		esc_html( wsrs_get_unsupported_php_version_message() ),
		esc_html__( 'Plugin activation failed.', 'welcart-simple-report-sales' ),
		array(
			'back_link' => true,
		)
	);
}

register_activation_hook(
	__FILE__,
	'wsrs_activate_php_version_guard'
);

if ( ! wsrs_is_supported_php_version() ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>';
			echo esc_html( wsrs_get_unsupported_php_version_message() );
			echo '</p></div>';
		}
	);

	return;
}

$wsrs_autoload = WSRS_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $wsrs_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			echo '<div class="notice notice-error"><p>';
			echo esc_html__(
				'かんたん売上報告書 for Welcart の依存ライブラリが見つかりません。composer install を実行してください。',
				'welcart-simple-report-sales'
			);
			echo '</p></div>';
		}
	);

	return;
}

require_once $wsrs_autoload;

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
