<?php
/**
 * Activation handler.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales;

use OmoikaneWorks\WelcartSimpleReportSales\Database\TemplateTable;
use OmoikaneWorks\WelcartSimpleReportSales\Templates\TemplateSeeder;

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

		update_option( 'wsrs_version', WSRS_VERSION, false );
	}

	/**
	 * Check plugin requirements.
	 *
	 * @return  void
	 */
	private static function check_requirements(): void {
		// @phpstan-ignore-next-line Runtime requirement check for plugin activation.
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			deactivate_plugins( WSRS_PLUGIN_BASENAME );

			wp_die(
				esc_html__(
					'かんたん売上報告書 for Welcart を利用するには PHP 8.2 以上が必要です。',
					'welcart-simple-report-sales'
				)
			);
		}

		if ( ! defined( 'USCES_VERSION' ) ) {
			deactivate_plugins( WSRS_PLUGIN_BASENAME );

			wp_die(
				esc_html__(
					'かんたん売上報告書 for Welcart を利用するには Welcart が有効化されている必要があります。',
					'welcart-simple-report-sales'
				)
			);
		} elseif ( version_compare( \USCES_VERSION, '2.11.10', '<' ) ) {
			deactivate_plugins( WSRS_PLUGIN_BASENAME );

			wp_die(
				esc_html__(
					'かんたん売上報告書 for Welcart を利用するには Welcart v2.11.10 以上が必要です。',
					'welcart-simple-report-sales'
				)
			);
		}
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
