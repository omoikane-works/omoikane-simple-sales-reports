<?php
/**
 * Template table tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database;

use OmoikaneWorks\SimpleSalesReports\Database\TemplateTable;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateTable.
 */
final class TemplateTableTest extends TestCase {

	/**
	 * Original wpdb.
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
	 * Test get table name uses WordPress database prefix.
	 *
	 * @return void
	 */
	public function test_get_table_name_uses_wordpress_database_prefix(): void {
		$this->wpdb->prefix = 'wp_test_';

		$this->assertSame(
			'wp_test_ossr_templates',
			TemplateTable::get_table_name()
		);
	}

	/**
	 * Template creation uses the same upgrade boundary and retains version uniqueness.
	 *
	 * @return void
	 */
	#[BackupGlobals( true )]
	public function test_create_passes_versioned_template_schema_to_db_delta(): void {
		$GLOBALS['ossr_test_calls']  = array();
		$this->wpdb->prefix          = 'wp_test_';
		$this->wpdb->charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

		TemplateTable::create();

		$calls = $GLOBALS['ossr_test_calls'];
		$this->assertSame( array( 'dbDelta' ), array_column( $calls, 0 ) );
		$sql = $calls[0][1];
		$this->assertStringStartsWith( 'CREATE TABLE wp_test_ossr_templates (', $sql );
		$this->assertStringContainsString( 'settings longtext NULL,', $sql );
		$this->assertStringContainsString( 'PRIMARY KEY  (id)', $sql );
		$this->assertStringContainsString( 'UNIQUE KEY template_key_version (template_key, version)', $sql );
		$this->assertStringEndsWith( ') DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $sql );
	}
}
