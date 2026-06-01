<?php
/**
 * Template seeder tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates;

use OmoikaneWorks\SimpleSalesReports\Templates\TemplateKeys;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSeeder;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database\FakeWpdb;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateSeeder.
 */
final class TemplateSeederTest extends TestCase {

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
	 * @return  void
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
	 * @return  void
	 */
	protected function tearDown(): void {
		global $wpdb;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$wpdb = $this->original_wpdb;

		parent::tearDown();
	}

	/**
	 * Test seed inserts default template when content hash does not exist.
	 *
	 * @return  void
	 */
	public function test_seed_inserts_default_template_when_content_hash_does_not_exist(): void {
		$this->wpdb->set_var( null );

		TemplateSeeder::seed();
		$inserted_rows = $this->wpdb->get_inserted_rows();
		$this->assertCount( 1, $inserted_rows );
		$row = $inserted_rows[0];

		$this->assertSame( TemplateKeys::DEFAULT_SALES_REPORT, $row['template_key'] );
		$this->assertSame( '標準 売上報告書', $row['name'] );
		$this->assertSame( TemplateTypes::SALES_REPORT, $row['type'] );
		$this->assertSame( '1.0.0', $row['version'] );
		$this->assertNotSame( '', $row['content'] );
		$this->assertSame( hash( 'sha256', (string) $row['content'] ), $row['content_hash'] );
		$this->assertSame( 1, $row['is_system'] );
		$this->assertSame( 1, $row['is_default'] );
		$this->assertSame( 1, $row['is_active'] );
		$this->assertSame( '2026-05-24 12:34:56', $row['created_at'] );
		$this->assertSame( '2026-05-24 12:34:56', $row['updated_at'] );
	}

	/**
	 * Test seed does not insert default template when content hash already exists.
	 *
	 * @return  void
	 */
	public function test_seed_does_not_insert_default_template_when_content_hash_already_exists(): void {
		$this->wpdb->set_var( '1' );

		TemplateSeeder::seed();

		$this->assertSame( array(), $this->wpdb->get_inserted_rows() );
	}
}
