<?php
/**
 * Activation handler.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports;

use OmoikaneWorks\SimpleSalesReports\Database\ConfigTable;
use OmoikaneWorks\SimpleSalesReports\Database\TemplateTable;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSeeder;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation.
 */
final class Activation {

	/**
	 * Current database schema version.
	 *
	 * @var string
	 */
	private const DB_SCHEMA_VERSION = '1.1.0';

	/**
	 * Run activation tasks.
	 *
	 * @return  void
	 */
	public static function activate(): void {
		self::check_requirements();

		self::maybe_upgrade();

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
	 * Upgrade database schema if needed.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed_version = (string) get_option( 'ossr_db_version', '1.0.0' );

		if ( version_compare( $installed_version, self::DB_SCHEMA_VERSION, '>=' ) ) {
			return;
		}

		ConfigTable::create();

		// Confirm the schema update succeeded before recording the version.
		if ( ! ConfigTable::exists() ) {
			return;
		}

		update_option( 'ossr_db_version', self::DB_SCHEMA_VERSION, false );
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
