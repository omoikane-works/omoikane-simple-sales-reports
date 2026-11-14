<?php
/**
 * Config repository test.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Configs;

use OmoikaneWorks\SimpleSalesReports\Configs\ConfigRepository;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database\FakeWpdb;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ConfigRepository.
 */
final class ConfigRepositoryTest extends TestCase {

	/**
	 * Original Wpdb.
	 *
	 * @var mixed
	 */
	private mixed $original_wpdb;

	/**
	 * Fake wpdb.
	 *
	 * @var FakeWpdb
	 */
	private FakeWpdb $wpdb;

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wpdb;

		$this->original_wpdb = $wpdb ?? null;
		$this->wpdb          = new FakeWpdb();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$wpdb = $this->wpdb;
	}

	/**
	 * Tear down test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wpdb;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$wpdb = $this->original_wpdb;

		parent::tearDown();
	}

	/**
	 * Test get existing_config.
	 *
	 * @return void
	 */
	public function test_get_existing_config(): void {
		$this->wpdb->set_row(
			array(
				'config_value' => 'value',
			)
		);

		$repository = new ConfigRepository();

		$result = $repository->get( 'key' );

		$this->assertSame( 'value', $result );
	}

	/**
	 * Test get missing config.
	 *
	 * @return void
	 */
	public function test_get_missing_config(): void {
		$this->wpdb->set_row( null );

		$repository = new ConfigRepository();

		$result = $repository->get( 'not_exist_key' );

		$this->assertNull( $result );
	}

	/**
	 * Test get uses requested key.
	 *
	 * @return void
	 */
	public function test_get_uses_requested_key(): void {
		$this->wpdb->set_row( null );

		$repository = new ConfigRepository();

		$repository->get( 'branding.logo' );

		$this->assertStringContainsString(
			"WHERE config_key = 'branding.logo'",
			$this->wpdb->get_last_query(),
		);
	}

	/**
	 * Test get empty config value
	 *
	 * @return void
	 */
	public function test_get_empty_config_value(): void {
		$this->wpdb->set_row(
			array(
				'config_value' => '',
			)
		);

		$repository = new ConfigRepository();

		$result = $repository->get( 'key' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test set new config.
	 *
	 * @return void
	 */
	public function test_set_new_config(): void {
		$this->wpdb->set_query_result( 1 );

		$repository = new ConfigRepository();

		$result = $repository->set( 'new_key', 'new_value' );

		$this->assertTrue( $result );
		$this->assertStringContainsString(
			"VALUES ( 'new_key', 'new_value' )",
			$this->wpdb->get_last_query(),
		);
	}

	/**
	 * Test set existing config.
	 *
	 * @return void
	 */
	public function test_set_existing_config(): void {
		$this->wpdb->set_query_result( 2 );

		$repository = new ConfigRepository();

		$result = $repository->set( 'existing_key', 'updated_value' );

		$this->assertTrue( $result );
		$this->assertStringContainsString(
			'ON DUPLICATE KEY UPDATE',
			$this->wpdb->get_last_query(),
		);
		$this->assertStringContainsString(
			"config_value = 'updated_value'",
			$this->wpdb->get_last_query(),
		);
	}

	/**
	 * Test set unchanged config.
	 *
	 * @return void
	 */
	public function test_set_unchanged_config(): void {
		$this->wpdb->set_query_result( 0 );

		$repository = new ConfigRepository();

		$result = $repository->set( 'existing_key', 'same_value' );

		$this->assertTrue( $result );
	}

	/**
	 * Test set returns false on database error.
	 *
	 * @return void
	 */
	public function test_set_returns_false_on_database_error(): void {
		$this->wpdb->set_query_result( false );

		$repository = new ConfigRepository();

		$result = $repository->set( 'key', 'value' );

		$this->assertFalse( $result );
	}

	/**
	 * Test delete existing config.
	 *
	 * @return void
	 */
	public function test_delete_existing_config(): void {
		$this->wpdb->set_delete_result( 1 );

		$repository = new ConfigRepository();

		$result = $repository->delete( 'key' );

		$this->assertTrue( $result );
		$this->assertSame(
			'DELETE wp_ossr_configs',
			$this->wpdb->get_last_query(),
		);
		$this->assertSame(
			array(
				array( 'config_key' => 'key' ),
			),
			$this->wpdb->get_deleted_where_clauses(),
		);
	}

	/**
	 * Test delete missing config.
	 *
	 * @return void
	 */
	public function test_delete_missing_config(): void {
		$this->wpdb->set_delete_result( 0 );

		$repository = new ConfigRepository();

		$result = $repository->delete( 'not_exist_key' );

		$this->assertTrue( $result );
	}

	/**
	 * Test delete returns false on database error.
	 *
	 * @return void
	 */
	public function test_delete_on_database_error(): void {
		$this->wpdb->set_delete_result( false );

		$repository = new ConfigRepository();

		$result = $repository->delete( 'key' );

		$this->assertFalse( $result );
	}
}
