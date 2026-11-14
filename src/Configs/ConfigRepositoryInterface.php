<?php
/**
 * Config repository interface.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Configs;

defined( 'ABSPATH' ) || exit;

/**
 * Handles config queries.
 */
interface ConfigRepositoryInterface {

	/**
	 * Get config value by key.
	 *
	 * @param  string $key Config key.
	 * @return string|null Config value or null if not found.
	 */
	public function get( string $key ): ?string;

	/**
	 * Set config value by key.
	 *
	 * @param  string $key  Config key.
	 * @param  string $value Config value.
	 * @return bool True if the value was set, false otherwise.
	 */
	public function set( string $key, string $value ): bool;

	/**
	 * Delete config value by key.
	 *
	 * @param  string $key Config key.
	 * @return bool True on success or if the key does not exist, false on failure.
	 */
	public function delete( string $key ): bool;
}
