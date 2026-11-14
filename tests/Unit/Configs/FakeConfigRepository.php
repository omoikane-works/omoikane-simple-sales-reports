<?php
/**
 * Fake config repository.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Configs;

use OmoikaneWorks\SimpleSalesReports\Configs\ConfigRepositoryInterface;

/**
 * Fake config repository.
 */
final class FakeConfigRepository implements ConfigRepositoryInterface {

	/**
	 * Config data.
	 *
	 * @var array<string, string>
	 */
	private array $configs = array();

	/**
	 * Get config value.
	 *
	 * @param  string $key Config key.
	 * @return string|null
	 */
	public function get( string $key ): ?string {
		return $this->configs[ $key ] ?? null;
	}

	/**
	 * Set config value.
	 *
	 * @param  string $key Config key.
	 * @param  string $value Config value.
	 * @return bool
	 */
	public function set( string $key, string $value ): bool {
		$this->configs[ $key ] = $value;

		return true;
	}

	/**
	 * Delete config value.
	 *
	 * @param  string $key Config key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		unset( $this->configs[ $key ] );

		return true;
	}

	/**
	 * Constructor.
	 *
	 * @param array<string, string> $configs Configs.
	 */
	public function __construct( array $configs = array() ) {
		$this->configs = $configs;
	}
}
