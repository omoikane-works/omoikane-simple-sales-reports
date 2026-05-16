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
	 * Default template file.
	 *
	 * When changing default template content, update the template version.
	 * Default templates with the same content hash are not inserted again.
	 *
	 * @var array<int, array<string, string>>
	 */
	private const DEFAULT_TEMPLATES = array(
		array(
			'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
			'name'         => '標準 売上報告書',
			'type'         => TemplateTypes::SALES_REPORT,
			'file'         => 'templates/default-sales-report@0.1.0.mustache',
			'version'      => '0.1.0',
		),
	);

	/**
	 * Seed default templates.
	 *
	 * @return  void
	 */
	public static function seed(): void {
		foreach ( self::DEFAULT_TEMPLATES as $template ) {
			self::seed_template( $template );
		}
	}

	/**
	 * Seed template.
	 *
	 * @param   array<string, string> $template   Template definition.
	 * @return  void
	 */
	private static function seed_template( array $template ): void {
		global $wpdb;

		$table_name    = TemplateTable::get_table_name();
		$template_path = WSRS_PLUGIN_DIR . $template['file'];

		if ( ! file_exists( $template_path ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $template_path );

		if ( false === $content || '' === $content ) {
			return;
		}

		$content_hash = hash( 'sha256', $content );

		if ( self::template_hash_exists( $template['template_key'], $content_hash ) ) {
			return;
		}

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'template_key' => $template['template_key'],
				'name'         => $template['name'],
				'type'         => $template['type'],
				'content'      => $content,
				'version'      => $template['version'],
				'content_hash' => $content_hash,
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
	 * Check whether template content hash already exists.
	 *
	 * @param   string $template_key   Template key.
	 * @param   string $content_hash   Content hash.
	 * @return  bool
	 */
	private static function template_hash_exists(
		string $template_key,
		string $content_hash
	): bool {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE template_key = %s AND content_hash = %s LIMIT 1',
				$table_name,
				$template_key,
				$content_hash
			)
		);

		return null !== $existing_id;
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
