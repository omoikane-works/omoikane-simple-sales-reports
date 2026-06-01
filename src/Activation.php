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
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			deactivate_plugins( OSSR_PLUGIN_BASENAME );

			wp_die(
				esc_html__(
					'Omoikane Simple Sales Reports for Welcart requires PHP 8.2 or later.',
					'omoikane-simple-sales-reports'
				)
			);
		}

		if ( ! defined( 'USCES_VERSION' ) ) {
			deactivate_plugins( OSSR_PLUGIN_BASENAME );

			wp_die(
				esc_html__(
					'Omoikane Simple Sales Reports for Welcart requires Welcart to be activated.',
					'omoikane-simple-sales-reports'
				)
			);
		} elseif ( version_compare( \USCES_VERSION, '2.11.10', '<' ) ) {
			deactivate_plugins( OSSR_PLUGIN_BASENAME );

			wp_die(
				esc_html__(
					'Omoikane Simple Sales Reports for Welcart requires Welcart 2.11.10 or later.',
					'omoikane-simple-sales-reports'
				)
			);
		}
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
