<?php
/**
 * Template table definition.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Handles template table schema.
 */
final class TemplateTable {

	/**
	 * Get table name.
	 *
	 * @return  string
	 */
	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'ossr_templates';
	}

	/**
	 * Create or update table.
	 *
	 * @return  void
	 */
	public static function create(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			template_key varchar(191) NOT NULL,
			name varchar(191) NOT NULL,
			type varchar(50) NOT NULL DEFAULT 'sales_report',
			content longtext NOT NULL,
			settings longtext NULL,
			content_hash varchar(64) NOT NULL DEFAULT '',
			version varchar(20) NOT NULL DEFAULT '1.0.0',
			is_system tinyint(1) unsigned NOT NULL DEFAULT 0,
			is_default tinyint(1) unsigned NOT NULL DEFAULT 0,
			is_active tinyint(1) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY template_key_version (template_key, version),
			KEY template_key_hash (template_key, content_hash),
			KEY type (type),
			KEY is_default (is_default),
			KEY is_active (is_active)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Check whether the template table exists.
	 *
	 * @return bool
	 */
	public static function exists(): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		return $table_name === $result;
	}

	/**
	 * Check whether the settings column has been installed.
	 *
	 * @return bool
	 */
	public static function has_settings_column(): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column = $wpdb->get_var(
			$wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', self::get_table_name(), 'settings' )
		);

		return 'settings' === $column;
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
