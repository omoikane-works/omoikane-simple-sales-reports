<?php
/**
 * Config service.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Configs;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Config operations.
 */
final class ConfigService {

	/**
	 * Config repository.
	 *
	 * @var ConfigRepositoryInterface
	 */
	private ConfigRepositoryInterface $config_repository;

	/**
	 * Constructor.
	 *
	 * @param ConfigRepositoryInterface $config_repository Config repository.
	 */
	public function __construct( ConfigRepositoryInterface $config_repository ) {
		$this->config_repository = $config_repository;
	}

	/**
	 * Get config value by key.
	 *
	 * @param  string $key Config key.
	 * @return string|null Config value or null if not found.
	 */
	public function get( string $key ): ?string {
		return $this->config_repository->get( $key );
	}

	/**
	 * Set config value by key.
	 *
	 * @param  string $key Config key.
	 * @param  string $value Config value.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, string $value ): bool {
		return $this->config_repository->set( $key, $value );
	}

	/**
	 * Delete config by key.
	 *
	 * @param  string $key Config key.
	 * @return bool True on success or if the key does not exist, false on failure.
	 */
	public function delete( string $key ): bool {
		return $this->config_repository->delete( $key );
	}
}
