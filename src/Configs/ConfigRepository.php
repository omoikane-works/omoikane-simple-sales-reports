<?php
/**
 * Config repository.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Configs;

use OmoikaneWorks\SimpleSalesReports\Database\ConfigTable;

defined( 'ABSPATH' ) || exit;

/**
 * Handles config queries.
 */
final class ConfigRepository implements ConfigRepositoryInterface {

	/**
	 * Get config value by key.
	 *
	 * @param  string $key Config key.
	 * @return string|null Config value or null if not found.
	 */
	public function get( string $key ): ?string {
		global $wpdb;

		$table_name = ConfigTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT config_value
				 FROM %i
				 WHERE config_key = %s
				 LIMIT 1',
				$table_name,
				$key
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return (string) $row['config_value'];
	}

	/**
	 * Set config value by key.
	 *
	 * @param  string $key  Config key.
	 * @param  string $value Config value.
	 * @return bool True if the value was set, false otherwise.
	 */
	public function set( string $key, string $value ): bool {
		global $wpdb;

		$table_name = ConfigTable::get_table_name();

		$query = $wpdb->prepare(
			'INSERT INTO %i ( config_key, config_value )
			 VALUES ( %s, %s )
			 ON DUPLICATE KEY UPDATE config_value = %s',
			$table_name,
			$key,
			$value,
			$value,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $query );

		return ( false !== $result );
	}

	/**
	 * Delete config value by key.
	 *
	 * @param  string $key Config key.
	 * @return bool True on success or if the key does not exist, false on failure
	 */
	public function delete( string $key ): bool {
		global $wpdb;

		$table_name = ConfigTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'config_key' => $key ),
			array( '%s' ),
		);

		return ( false !== $result );
	}
}
