<?php
/**
 * Config Service test.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Configs;

use OmoikaneWorks\SimpleSalesReports\Configs\ConfigService;
use PHPUnit\Framework\TestCase;

/**
 * Test for ConfigService.
 */
final class ConfigServiceTest extends TestCase {

	/**
	 * Test get existing config.
	 *
	 * @return void
	 */
	public function test_get_existing_config(): void {
		$repository = new FakeConfigRepository(
			array(
				'key' => 'value',
			)
		);

		$service = new ConfigService( $repository );

		$result = $service->get( 'key' );

		$this->assertSame( 'value', $result );
	}

	/**
	 * Test get missing config.
	 *
	 * @return void
	 */
	public function test_get_missing_config(): void {
		$repository = new FakeConfigRepository();
		$service    = new ConfigService( $repository );

		$result = $service->get( 'not_exist_key' );

		$this->assertNull( $result );
	}

	/**
	 * Test get empty config value.
	 *
	 * @return void
	 */
	public function test_get_empty_config_value(): void {
		$repository = new FakeConfigRepository(
			array(
				'key' => '',
			)
		);
		$service    = new ConfigService( $repository );

		$result = $service->get( 'key' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test set config.
	 *
	 * @return void
	 */
	public function test_set_config(): void {
		$repository = new FakeConfigRepository();
		$service    = new ConfigService( $repository );

		$result = $service->set( 'key', 'value' );

		$this->assertTrue( $result );
		$this->assertSame( 'value', $repository->get( 'key' ) );
	}

	/**
	 * Test set update config.
	 *
	 * @return void
	 */
	public function test_set_update_config(): void {
		$repository = new FakeConfigRepository(
			array(
				'key' => 'old_value',
			)
		);
		$service    = new ConfigService( $repository );

		$result = $service->set( 'key', 'new_value' );

		$this->assertTrue( $result );
		$this->assertSame( 'new_value', $service->get( 'key' ) );
	}

	/**
	 * Test delete config.
	 *
	 * @return void
	 */
	public function test_delete_config(): void {
		$repository = new FakeConfigRepository(
			array(
				'key' => 'value',
			)
		);
		$service    = new ConfigService( $repository );

		$result = $service->delete( 'key' );

		$this->assertTrue( $result );
		$this->assertNull( $service->get( 'key' ) );
	}
}
