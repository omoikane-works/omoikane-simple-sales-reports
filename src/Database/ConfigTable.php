<?php
/**
 * Config table definition.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Handles config table schema.
 */
final class ConfigTable {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'ossr_configs';
	}

	/**
	 * Create or update table.
	 *
	 * @return void
	 */
	public static function create(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			config_key varchar(191) NOT NULL,
			config_value longtext NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY config_key (config_key)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Check whether the config table exists.
	 *
	 * @return bool True if the table exists, false otherwise.
	 */
	public static function exists(): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like(
					$table_name
				)
			)
		);

		return ( $table_name === $result );
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
