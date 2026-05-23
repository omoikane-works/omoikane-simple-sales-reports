<?php
/**
 * Template repository test.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Tests\Unit\Templates;

use OmoikaneWorks\WelcartSimpleReportSales\Templates\TemplateKeys;
use OmoikaneWorks\WelcartSimpleReportSales\Templates\TemplateRepository;
use OmoikaneWorks\WelcartSimpleReportSales\Templates\TemplateTypes;
use OmoikaneWorks\WelcartSimpleReportSales\Tests\Unit\Database\FakeWpdb;
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
				'version'      => '0.1.0',
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
				'version'      => '0.1.0',
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
					'version'      => '0.1.0',
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
}
