<?php
/**
 * Template seeder.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Templates;

use OmoikaneWorks\SimpleSalesReports\Database\TemplateTable;

defined( 'ABSPATH' ) || exit;

/**
 * Seeds default templates.
 */
final class TemplateSeeder {

	/**
	 * Option for the last successfully seeded template catalog hash.
	 *
	 * @var string
	 */
	private const CATALOG_HASH_OPTION = 'ossr_template_catalog_hash';

	/**
	 * Bundled template definitions.
	 *
	 * The catalog hash covers this metadata, including file paths and versions.
	 * Bump the version when revising a template's body or settings.
	 *
	 * @var array<int, array<string, string>>
	 */
	private const DEFAULT_TEMPLATES = array(
		array(
			'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
			'name'         => '標準 売上報告書',
			'type'         => TemplateTypes::SALES_REPORT,
			'file'         => 'templates/default-sales-report-1.0.0.mustache',
			'version'      => '1.0.0',
		),
	);

	/**
	 * Seed templates when the bundled catalog changes.
	 *
	 * @return void
	 */
	public static function maybe_seed(): void {
		if ( self::catalog_hash() === get_option( self::CATALOG_HASH_OPTION ) ) {
			return;
		}

		if ( ! TemplateTable::exists() ) {
			TemplateTable::create();
		}

		self::seed();
	}

	/**
	 * Seed default templates.
	 *
	 * @return  void
	 */
	public static function seed(): void {
		$complete = true;
		foreach ( self::DEFAULT_TEMPLATES as $template ) {
			if ( ! self::seed_template( $template ) ) {
				$complete = false;
			}
		}

		if ( $complete ) {
			update_option( self::CATALOG_HASH_OPTION, self::catalog_hash(), true );
		}
	}

	/**
	 * Fingerprint the catalog metadata so boot need not read template files.
	 *
	 * @return string
	 */
	private static function catalog_hash(): string {
		return hash( 'sha256', (string) wp_json_encode( self::DEFAULT_TEMPLATES ) );
	}

	/**
	 * Seed template.
	 *
	 * @param   array<string, string> $template   Template definition.
	 * @return  bool True if the template already exists or was inserted.
	 */
	private static function seed_template( array $template ): bool {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT EXISTS(SELECT 1 FROM %i WHERE template_key = %s AND version = %s)',
				$table_name,
				$template['template_key'],
				$template['version']
			)
		);

		if ( '1' === (string) $existing ) {
			// Preserve the stored revision even if the bundled body has changed.
			return true;
		}

		if ( '0' !== (string) $existing ) {
			return false;
		}

		$template_path = OSSR_PLUGIN_DIR . $template['file'];
		if ( ! file_exists( $template_path ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $template_path );

		if ( false === $content || '' === $content ) {
			return false;
		}

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table_name,
			array(
				'template_key' => $template['template_key'],
				'name'         => $template['name'],
				'type'         => $template['type'],
				'content'      => $content,
				'version'      => $template['version'],
				'content_hash' => hash( 'sha256', $content ),
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

		// The unique key also protects against concurrent inserts of the same version.
		return 1 === $result;
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
