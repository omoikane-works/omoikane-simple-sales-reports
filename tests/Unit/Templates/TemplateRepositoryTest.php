<?php
/**
 * Template repository test.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates;

use OmoikaneWorks\SimpleSalesReports\Templates\TemplateKeys;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateRepository;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database\FakeWpdb;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateRepository.
 */
final class TemplateRepositoryTest extends TestCase {

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
	 * Test find by key returns normalized template.
	 *
	 * @return  void
	 */
	public function test_find_by_key_returns_normalized_template(): void {
		$this->wpdb->set_row(
			array(
				'id'           => '10',
				'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
				'name'         => 'Default Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => '<h1>{{report.title}}</h1>',
				'content_hash' => 'abc123',
				'version'      => '1.0.0',
				'is_system'    => '1',
				'is_default'   => '1',
				'is_active'    => '1',
				'created_at'   => '2026-05-01 10:00:00',
				'updated_at'   => '2026-05-02 11:00:00',
			)
		);

		$repository = new TemplateRepository();

		$result = $repository->find_by_key( TemplateKeys::DEFAULT_SALES_REPORT );

		$this->assertSame(
			array(
				'id'           => 10,
				'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
				'name'         => 'Default Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => '<h1>{{report.title}}</h1>',
				'content_hash' => 'abc123',
				'version'      => '1.0.0',
				'is_system'    => true,
				'is_default'   => true,
				'is_active'    => true,
				'created_at'   => '2026-05-01 10:00:00',
				'updated_at'   => '2026-05-02 11:00:00',
			),
			$result
		);
	}

	/**
	 * Test find by key returns null when template is not found.
	 *
	 * @return  void
	 */
	public function test_find_by_key_returns_null_when_template_is_not_found(): void {
		$this->wpdb->set_row( null );

		$repository = new TemplateRepository();

		$result = $repository->find_by_key( TemplateKeys::DEFAULT_SALES_REPORT );

		$this->assertNull( $result );
	}

	/**
	 * Test find selectable by type returns normalized templates.
	 *
	 * @return void
	 */
	public function test_find_selectable_by_type_returns_normalized_templates(): void {
		$this->wpdb->set_results(
			array(
				array(
					'id'           => '11',
					'template_key' => 'default_sales_report',
					'name'         => 'Default Sales Report',
					'type'         => TemplateTypes::SALES_REPORT,
					'content'      => 'Default content',
					'content_hash' => 'hash-default',
					'version'      => '1.0.0',
					'is_system'    => '1',
					'is_default'   => '1',
					'is_active'    => '1',
					'created_at'   => '2026-05-01 10:00:00',
					'updated_at'   => '2026-05-01 10:00:00',
				),
				array(
					'id'           => '12',
					'template_key' => 'custom_sales_report',
					'name'         => 'Custom Sales Report',
					'type'         => TemplateTypes::SALES_REPORT,
					'content'      => 'Custom content',
					'content_hash' => 'hash-custom',
					'version'      => '1.0.0',
					'is_system'    => '0',
					'is_default'   => '0',
					'is_active'    => '1',
					'created_at'   => '2026-05-02 10:00:00',
					'updated_at'   => '2026-05-02 10:00:00',
				),
			)
		);
		$repository = new TemplateRepository();
		$result     = $repository->find_selectable_by_type( TemplateTypes::SALES_REPORT );

		$this->assertCount( 2, $result );

		$this->assertSame( 11, $result[0]['id'] );
		$this->assertSame( 'Default Sales Report', $result[0]['name'] );
		$this->assertTrue( $result[0]['is_system'] );
		$this->assertTrue( $result[0]['is_default'] );

		$this->assertSame( 12, $result[1]['id'] );
		$this->assertSame( 'Custom Sales Report', $result[1]['name'] );
		$this->assertFalse( $result[1]['is_system'] );
		$this->assertFalse( $result[1]['is_default'] );
	}

	/**
	 * Test find default sales report template returns normalized template.
	 *
	 * @return  void
	 */
	public function test_find_default_sales_report_template_returns_normalized_template(): void {
		$this->wpdb->set_row(
			array(
				'id'           => '20',
				'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
				'name'         => 'Default Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => 'Default content',
				'content_hash' => 'hash-default',
				'version'      => '1.0.0',
				'is_system'    => '1',
				'is_default'   => '1',
				'is_active'    => '1',
				'created_at'   => '2026-05-03 10:00:00',
				'updated_at'   => '2026-05-03 10:00:00',
			)
		);

		$repository = new TemplateRepository();

		$result = $repository->find_default_sales_report_template();

		$this->assertIsArray( $result );
		$this->assertSame( 20, $result['id'] );
		$this->assertSame( TemplateKeys::DEFAULT_SALES_REPORT, $result['template_key'] );
		$this->assertSame( TemplateTypes::SALES_REPORT, $result['type'] );
		$this->assertTrue( $result['is_system'] );
		$this->assertTrue( $result['is_default'] );
		$this->assertTrue( $result['is_active'] );
	}

	/**
	 * Test find active default by type returns normalized template.
	 *
	 * @return  void
	 */
	public function test_find_active_default_by_type_returns_normalized_template(): void {
		$this->wpdb->set_row(
			array(
				'id'           => '21',
				'template_key' => 'default_sales_report',
				'name'         => 'Active Default Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => 'Active default content',
				'content_hash' => 'hash-active-default',
				'version'      => '0.2.0',
				'is_system'    => '1',
				'is_default'   => '1',
				'is_active'    => '1',
				'created_at'   => '2026-05-04 10:00:00',
				'updated_at'   => '2026-05-04 10:00:00',
			)
		);

		$repository = new TemplateRepository();

		$result = $repository->find_active_default_by_type( TemplateTypes::SALES_REPORT );

		$this->assertIsArray( $result );
		$this->assertSame( 21, $result['id'] );
		$this->assertSame( 'Active Default Sales Report', $result['name'] );
		$this->assertSame( TemplateTypes::SALES_REPORT, $result['type'] );
		$this->assertTrue( $result['is_default'] );
		$this->assertTrue( $result['is_active'] );
	}

	/**
	 * Test find by id returns normalized template.
	 *
	 * @return  void
	 */
	public function test_find_by_id_returns_normalized_template(): void {
		$this->wpdb->set_row(
			array(
				'id'           => '22',
				'template_key' => 'custom_sales_report',
				'name'         => 'Custom Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => 'Custom content',
				'content_hash' => 'hash-custom',
				'version'      => '1.0.0',
				'is_system'    => '0',
				'is_default'   => '0',
				'is_active'    => '1',
				'created_at'   => '2026-05-05 10:00:00',
				'updated_at'   => '2026-05-05 10:00:00',
			)
		);

		$repository = new TemplateRepository();

		$result = $repository->find_by_id( 22 );

		$this->assertIsArray( $result );
		$this->assertSame( 22, $result['id'] );
		$this->assertSame( 'custom_sales_report', $result['template_key'] );
		$this->assertSame( 'Custom Sales Report', $result['name'] );
		$this->assertFalse( $result['is_system'] );
		$this->assertFalse( $result['is_default'] );
		$this->assertTrue( $result['is_active'] );
	}

	/**
	 * Test find by id returns null when template is not found.
	 *
	 * @return  void
	 */
	public function test_find_by_id_returns_null_when_template_is_not_found(): void {
		$this->wpdb->set_row( null );

		$repository = new TemplateRepository();

		$result = $repository->find_by_id( 999 );

		$this->assertNull( $result );
	}

	/**
	 * Test name exists returns true when name exists.
	 *
	 * @return void
	 */
	public function test_name_exists_returns_true_when_name_exists(): void {
		$this->wpdb->set_var( '1' );

		$repository = new TemplateRepository();

		$result = $repository->name_exists( 'Custom Sales Report' );

		$this->assertTrue( $result );
		$this->assertStringContainsString( 'name = ', $this->wpdb->get_last_query() );
	}

	/**
	 * Test name exists returns false when name does not exist.
	 *
	 * @return void
	 */
	public function test_name_exists_returns_false_when_name_does_not_exist(): void {
		$this->wpdb->set_var( '0' );

		$repository = new TemplateRepository();

		$result = $repository->name_exists( 'New Template' );

		$this->assertFalse( $result );
	}

	/**
	 * Test name exists excludes specified id.
	 *
	 * @return void
	 */
	public function test_name_exists_excludes_specified_id(): void {
		$this->wpdb->set_var( '0' );

		$repository = new TemplateRepository();

		$result = $repository->name_exists( 'Custom Sales Report', 10 );

		$this->assertFalse( $result );
		$this->assertStringContainsString( 'id <> 10', $this->wpdb->get_last_query() );
	}

	/**
	 * Test insert creates custom sales report template.
	 *
	 * @return void
	 */
	public function test_insert_creates_custom_sales_report_template(): void {
		$repository = new TemplateRepository();

		$result = $repository->insert(
			array(
				'template_key' => 'custom_template_key',
				'name'         => 'Custom Sales Report',
				'content'      => '<h1>{{ report.title }}</h1>',
				'content_hash' => 'hash-custom',
				'version'      => '1.0.0',
			)
		);

		$this->assertSame( 1, $result );

		$inserted_rows = $this->wpdb->get_inserted_rows();

		$this->assertCount( 1, $inserted_rows );
		$this->assertSame( 'custom_template_key', $inserted_rows[0]['template_key'] );
		$this->assertSame( 'Custom Sales Report', $inserted_rows[0]['name'] );
		$this->assertSame( TemplateTypes::SALES_REPORT, $inserted_rows[0]['type'] );
		$this->assertSame( '<h1>{{ report.title }}</h1>', $inserted_rows[0]['content'] );
		$this->assertSame( 'hash-custom', $inserted_rows[0]['content_hash'] );
		$this->assertSame( '1.0.0', $inserted_rows[0]['version'] );
		$this->assertSame( 0, $inserted_rows[0]['is_system'] );
		$this->assertSame( 0, $inserted_rows[0]['is_default'] );
		$this->assertSame( 1, $inserted_rows[0]['is_active'] );
		$this->assertArrayHasKey( 'created_at', $inserted_rows[0] );
		$this->assertArrayHasKey( 'updated_at', $inserted_rows[0] );
	}

	/**
	 * Test update updates template content.
	 *
	 * @return void
	 */
	public function test_update_updates_template_content(): void {
		$repository = new TemplateRepository();

		$result = $repository->update(
			10,
			array(
				'name'         => 'Updated Template',
				'content'      => '<p>Updated</p>',
				'content_hash' => 'hash-updated',
			)
		);

		$this->assertTrue( $result );

		$updated_rows = $this->wpdb->get_updated_rows();
		$where        = $this->wpdb->get_updated_where_clauses();

		$this->assertCount( 1, $updated_rows );
		$this->assertSame( 'Updated Template', $updated_rows[0]['name'] );
		$this->assertSame( '<p>Updated</p>', $updated_rows[0]['content'] );
		$this->assertSame( 'hash-updated', $updated_rows[0]['content_hash'] );
		$this->assertArrayHasKey( 'updated_at', $updated_rows[0] );

		$this->assertSame( 10, $where[0]['id'] );
		$this->assertSame( 1, $where[0]['is_active'] );
	}

	/**
	 * Test deactivate marks template as inactivate.
	 *
	 * @return void
	 */
	public function test_deactivate_marks_template_as_inactive(): void {
		$repository = new TemplateRepository();

		$result = $repository->deactivate( 10 );

		$this->assertTrue( $result );

		$updated_rows = $this->wpdb->get_updated_rows();
		$where        = $this->wpdb->get_updated_where_clauses();

		$this->assertCount( 1, $updated_rows );
		$this->assertSame( 0, $updated_rows[0]['is_active'] );
		$this->assertArrayHasKey( 'updated_at', $updated_rows[0] );

		$this->assertSame( 10, $where[0]['id'] );
		$this->assertSame( 1, $where[0]['is_active'] );
	}
}
