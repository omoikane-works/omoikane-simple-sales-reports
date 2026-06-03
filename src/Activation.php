<?php
/**
 * Activation handler.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports;

use OmoikaneWorks\SimpleSalesReports\Database\TemplateTable;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSeeder;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation.
 */
final class Activation {

	/**
	 * Run activation tasks.
	 *
	 * @return  void
	 */
	public static function activate(): void {
		self::check_requirements();

		TemplateTable::create();
		TemplateSeeder::seed();

		update_option( 'ossr_version', OSSR_VERSION, false );
	}

	/**
	 * Check plugin requirements.
	 *
	 * @return  void
	 */
	private static function check_requirements(): void {
		// @phpstan-ignore-next-line Runtime requirement check for plugin activation.
		if ( version_compare( PHP_VERSION, OSSR_MINIMUM_PHP_VERSION, '<' ) ) {
			deactivate_plugins( OSSR_PLUGIN_BASENAME );

			$message = ossr_get_unsupported_php_version_message();
			wp_die( esc_html( $message ) );
		}

		if ( ! defined( 'USCES_VERSION' ) ) {
			deactivate_plugins( OSSR_PLUGIN_BASENAME );

			wp_die(
				esc_html__(
					'Omoikane Simple Sales Reports for Welcart requires Welcart to be activated.',
					'omoikane-simple-sales-reports'
				)
			);
		} elseif ( version_compare( \USCES_VERSION, OSSR_MINIMUM_USCES_VERSION, '<' ) ) {
			deactivate_plugins( OSSR_PLUGIN_BASENAME );

			$message = ossr_get_unsupported_welcart_version_message();
			wp_die( esc_html( $message ) );
		}
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
