<?php
/**
 * Template Service test.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates;

use OmoikaneWorks\SimpleSalesReports\Templates\TemplateKeys;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateService;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;
use PHPUnit\Framework\TestCase;

/**
 * Test for TemplateService.
 */
final class TemplateServiceTest extends TestCase {

	/**
	 * Test list templates returns repository templates.
	 *
	 * @return  void
	 */
	public function test_list_templates_returns_repository_templates(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'   => 1,
					'name' => 'Default Sales Report',
				)
			)
		);

		$service = new TemplateService( $repository );

		$result = $service->list_templates();

		$this->assertCount( 1, $result );
		$this->assertSame( 1, $result[0]['id'] );
		$this->assertSame( 'Default Sales Report', $result[0]['name'] );
	}

	/**
	 * Test get_template returns repository template.
	 *
	 * @return  void
	 */
	public function test_get_template_returns_repository_template(): void {
		$repository = new fakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'   => 10,
					'name' => 'Custom Template',
				)
			)
		);

		$service = new TemplateService( $repository );

		$result = $service->get_template( 10 );

		$this->assertSame( 10, $result['id'] );
		$this->assertSame( 'Custom Template', $result['name'] );
	}

	/**
	 * Test get_template throws exception when template is not found.
	 *
	 * @return  void
	 */
	public function test_get_template_throws_exception_when_template_is_not_found(): void {
		$repository = new FakeTemplateRepository();

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template not found.' );

		$service->get_template( 999 );
	}

	/**
	 * Test duplicate template inserts copied template.
	 *
	 * @return  void
	 */
	public function test_duplicate_template_inserts_copied_template(): void {
		$repository                = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'   => 10,
					'name' => 'Default Sales Report',
				)
			)
		);
		$repository->insert_result = 20;

		$service = new TemplateService( $repository );

		$result = $service->duplicate_template( 10, 'Copied Template' );

		$this->assertSame( 20, $result );
		$this->assertIsArray( $repository->inserted_data );
		$this->assertSame( 'Copied Template', $repository->inserted_data['name'] );
		$this->assertSame( '<h1>{{report.title}}</h1>', $repository->inserted_data['content'] );
		$this->assertSame( hash( 'sha256', '<h1>{{report.title}}</h1>' ), $repository->inserted_data['content_hash'] );
		$this->assertSame( '1.0.0', $repository->inserted_data['version'] );
		$this->assertStringStartswith( 'custom_', $repository->inserted_data['template_key'] );
	}

	/**
	 * Test duplicate template throws exception when name exists.
	 *
	 * @return  void
	 */
	public function test_duplicate_template_throws_exception_when_name_exists(): void {
		$repository                     = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id' => 10,
				)
			)
		);
		$repository->name_exists_result = true;

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template name already exists.' );

		$service->duplicate_template( 10, 'Existing Template' );
	}

	/**
	 * Test duplicate template throws exception when insert fails.
	 *
	 * @return  void
	 */
	public function test_duplicate_template_throws_exception_when_insert_fails(): void {
		$repository                = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => true,
				)
			)
		);
		$repository->insert_result = 0;

		$service = new TemplateService( $repository );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Failed to duplicate template.' );

		$service->duplicate_template( 10, 'Copied Template' );
	}

	/**
	 * Test duplicate template throws exception when name is empty.
	 *
	 * @return  void
	 */
	public function test_duplicate_template_throws_exception_when_name_is_empty(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id' => 10,
				)
			)
		);

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template name is required.' );

		$service->duplicate_template( 10, '   ' );
	}

	/**
	 * Test update template updates template.
	 *
	 * @return  void
	 */
	public function test_update_template_updates_custom_template(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);

		$service = new TemplateService( $repository );

		$service->update_template( 10, 'Updated Template', '<p>Updated</p>' );

		$this->assertIsArray( $repository->updated_data );
		$this->assertSame( 10, $repository->updated_data['id'] );
		$this->assertSame( 'Updated Template', $repository->updated_data['data']['name'] );
		$this->assertSame( '<p>Updated</p>', $repository->updated_data['data']['content'] );
		$this->assertSame( hash( 'sha256', '<p>Updated</p>' ), $repository->updated_data['data']['content_hash'] );
	}

	/**
	 * Test update template throws exception when template is system template.
	 *
	 * @return  void
	 */
	public function test_update_template_throws_exception_when_template_is_system_template(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => true,
				)
			)
		);

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'System templates cannot be edited.' );

		$service->update_template( 10, 'Updated Template', '<p>Updated</p>' );
	}

	/**
	 * Test update template throws exception when template name already exists.
	 *
	 * @return  void
	 */
	public function test_update_template_throws_exception_when_template_name_already_exists(): void {
		$repository                     = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);
		$repository->name_exists_result = true;

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template name already exists.' );

		$service->update_template( 10, 'Updated Template', '<p>Updated</p>' );
	}

	/**
	 * Test update template throws exception when update fails.
	 *
	 * @return  void
	 */
	public function test_update_template_throws_exception_when_update_fails(): void {
		$repository                = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);
		$repository->update_result = false;

		$service = new TemplateService( $repository );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Failed to update template.' );

		$service->update_template( 10, 'Updated Template', '<p>Updated</p>' );
	}

	/**
	 * Test update template throws exception when name is empty.
	 *
	 * @return  void
	 */
	public function test_update_template_throws_exception_when_name_is_empty(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template name is required.' );

		$service->update_template( 10, '   ', '<p>Updated</p>' );
	}

	/**
	 * Test update template throws exception when content is empty.
	 *
	 * @return  void
	 */
	public function test_update_template_throws_exception_when_content_is_empty(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template content is required.' );

		$service->update_template( 10, 'Updated Template', '     ' );
	}

	/**
	 * Test update template throws exception when content syntax is invalid.
	 *
	 * @return  void
	 */
	public function test_update_template_throws_exception_when_content_syntax_is_invalid(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template syntax is invalid.' );

		$service->update_template( 10, 'Updated Template', '{{#items}}{{/orders}}' );
	}

	/**
	 * Test delete template deactivates custom templates.
	 *
	 * @return  void
	 */
	public function test_delete_template_deactivates_custom_template(): void {
		$repository = new FakeTEmplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);

		$service = new TemplateService( $repository );

		$service->delete_template( 10 );

		$this->assertSame( 10, $repository->deactivated_id );
	}

	/**
	 * Test delete template throws exception when template is system template.
	 *
	 * @return  void
	 */
	public function test_delete_template_throws_exception_when_template_is_system_template(): void {
		$repository = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => true,
				)
			)
		);

		$service = new TemplateService( $repository );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'System template cannot be deleted.' );

		$service->delete_template( 10 );
	}

	/**
	 * Test delete template throws exception when delete fails.
	 *
	 * @return  void
	 */
	public function test_delete_template_throws_exception_when_delete_fails(): void {
		$repository                    = new FakeTemplateRepository(
			$this->create_template_row(
				array(
					'id'        => 10,
					'is_system' => false,
				)
			)
		);
		$repository->deactivate_result = false;

		$service = new TemplateService( $repository );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Failed to delete template.' );

		$service->delete_template( 10 );
	}

	/**
	 * Create template row.
	 *
	 * @param   array<string, mixed> $override  Overrides.
	 * @return  array<string, mixed>
	 */
	private function create_template_row( array $override = array() ): array {
		return array_merge(
			array(
				'id'           => 1,
				'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
				'name'         => 'default Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => '<h1>{{report.title}}</h1>',
				'content_hash' => 'hash - default',
				'version'      => '1.0.0',
				'is_system'    => true,
				'is_default'   => true,
				'is_active'    => true,
				'created_at'   => '2026 - 05 - 01 10:00:00',
				'updated_at'   => '2026 - 05 - 01 10:00:00',
			),
			$override
		);
	}
}
