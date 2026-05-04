<?php
/**
 * Template seeder.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Templates;

use OmoikaneWorks\WelcartSimpleReportSales\Database\TemplateTable;

defined( 'ABSPATH' ) || exit;

/**
 * Seeds default templates.
 */
final class TemplateSeeder {

	/**
	 * Default Template key.
	 *
	 * @var string
	 */
	private const DEFAULT_TEMPLATE_KEY = 'default_sales_report';

	/**
	 * Default template file.
	 *
	 * @var string
	 */
	private const DEFAULT_TEMPLATE_FILE = 'templates/default-sales-report.mustache';

	/**
	 * Seed default templates.
	 *
	 * @return  void
	 */
	public static function seed(): void {
		self::seed_default_sales_report();
	}

	/**
	 * Seed default sales report template.
	 *
	 * @return  void
	 */
	private static function seed_default_sales_report(): void {
		global $wpdb;

		$table_name    = TemplateTable::get_table_name();
		$template_path = WSRS_PLUGIN_DIR . self::DEFAULT_TEMPLATE_FILE;

		if ( ! file_exists( $template_path ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $template_path );

		if ( false === $content ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE template_key = %s LIMIT 1',
				$table_name,
				self::DEFAULT_TEMPLATE_KEY
			)
		);

		$now = current_time( 'mysql' );

		if ( null !== $existing_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_name,
				array(
					'name'       => '標準 売上報告書',
					'type'       => 'sales_report',
					'content'    => $content,
					'version'    => WSRS_VERSION,
					'is_system'  => 1,
					'is_default' => 1,
					'is_active'  => 1,
					'updated_at' => $now,
				),
				array(
					'id' => (int) $existing_id,
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
				),
				array(
					'%d',
				)
			);

			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'template_key' => self::DEFAULT_TEMPLATE_KEY,
				'name'         => '標準 売上報告書',
				'type'         => 'sales_report',
				'content'      => $content,
				'version'      => WSRS_VERSION,
				'is_system'    => 1,
				'is_default'   => 1,
				'is_active'    => 1,
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
