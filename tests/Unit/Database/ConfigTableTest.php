<?php
/**
 * Config table tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database;

use OmoikaneWorks\SimpleSalesReports\Database\ConfigTable;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ConfigTable.
 */
final class ConfigTableTest extends TestCase {

	/**
	 * Original wpdb.
	 *
	 * @var mixed
	 */
	private mixed $original_wpdb;

	/**
	 * Fake wpdb.
	 *
	 * @var FakeWpdb $wpdb;
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
	 * Test get table name uses WordPress database prefix.
	 *
	 * @return void
	 */
	public function test_get_table_name_uses_wordpress_database_prefix(): void {
		$this->wpdb->prefix = 'wp_test_';

		$this->assertSame(
			'wp_test_ossr_configs',
			ConfigTable::get_table_name()
		);
	}

	/**
	 * Test exists returns true when table exists.
	 *
	 * @return void
	 */
	public function test_exists_returns_true_when_table_exists(): void {
		$this->wpdb->set_var( 'wp_ossr_configs' );

		$this->assertTrue( ConfigTable::exists() );
	}

	/**
	 * Test exists returns false when table does not exist.
	 *
	 * @return void
	 */
	public function test_exists_returns_false_when_table_does_not_exist(): void {
		$this->wpdb->set_var( null );

		$this->assertFalse( ConfigTable::exists() );
	}

	/**
	 * Test exists returns false when a different table exists.
	 *
	 * @return void
	 */
	public function test_exists_returns_false_when_a_different_table_exists(): void {
		$this->wpdb->set_var( 'wp_other_table' );

		$this->assertFalse( ConfigTable::exists() );
	}

	/**
	 * Test the config schema handed to WordPress.
	 *
	 * @return void
	 */
	#[BackupGlobals( true )]
	public function test_create_passes_config_schema_to_db_delta(): void {
		$GLOBALS['ossr_test_calls']  = array();
		$this->wpdb->prefix          = 'wp_test_';
		$this->wpdb->charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

		ConfigTable::create();

		$calls = $GLOBALS['ossr_test_calls'];
		$this->assertSame( array( 'dbDelta' ), array_column( $calls, 0 ) );
		$sql = $calls[0][1];
		$this->assertStringStartsWith( 'CREATE TABLE wp_test_ossr_configs (', $sql );
		$this->assertMatchesRegularExpression( '/^\s*id bigint\(20\) unsigned NOT NULL AUTO_INCREMENT,\s*$/m', $sql );
		$this->assertMatchesRegularExpression( '/^\s*config_key varchar\(191\) NOT NULL,\s*$/m', $sql );
		$this->assertMatchesRegularExpression( '/^\s*config_value longtext NOT NULL,\s*$/m', $sql );
		// dbDelta() expects two spaces between PRIMARY KEY and its column list.
		$this->assertStringContainsString( 'PRIMARY KEY  (id)', $sql );
		$this->assertStringContainsString( 'UNIQUE KEY config_key (config_key)', $sql );
		$this->assertStringEndsWith( ') DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $sql );
	}
}
