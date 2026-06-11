<?php
/**
 * Template REST controller test.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Rest;

use OmoikaneWorks\SimpleSalesReports\Rest\TemplateController;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateKeys;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateService;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates\FakeTemplateRepository;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for TemplateController.
 */
final class TemplateControllerTest extends TestCase {

	/**
	 * Test validate ID returns true for positive integer.
	 *
	 * @return void
	 */
	public function test_validate_id_returns_true_for_positive_integer(): void {
		$controller = $this->create_controller();

		$this->assertTrue( $controller->validate_id( 1 ) );
		$this->assertTrue( $controller->validate_id( '10' ) );
	}

	/**
	 * Test validate ID returns false for invalid value.
	 *
	 * @return void
	 */
	public function test_validate_id_returns_false_for_invalid_value(): void {
		$controller = $this->create_controller();

		$this->assertFalse( $controller->validate_id( 0 ) );
		$this->assertFalse( $controller->validate_id( '0' ) );
		$this->assertFalse( $controller->validate_id( '' ) );
	}

	/**
	 * Test get items returns templates.
	 *
	 * @return void
	 */
	public function test_get_items_returns_templates(): void {
		$controller = $this->create_controller(
			$this->create_template_row(
				array(
					'id'   => 10,
					'name' => 'Default Sales Report',
				)
			)
		);

		$response = $controller->get_items();

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertSame( 10, $data['items'][0]['id'] );
		$this->assertSame( 'Default Sales Report', $data['items'][0]['name'] );
	}

	/**
	 * Test get item returns template.
	 *
	 * @return void
	 */
	public function test_get_item_returns_template(): void {
		$controller = $this->create_controller(
			$this->create_template_row(
				array(
					'id'   => 10,
					'name' => 'Custom Template',
				)
			)
		);

		$request = new WP_REST_Request();
		$request->set_param( 'id', '10' );

		$response = $controller->get_item( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'item', $data );
		$this->assertSame( 10, $data['item']['id'] );
		$this->assertSame( 'Custom Template', $data['item']['name'] );
	}

	/**
	 * Test get item returns error when template is not found.
	 *
	 * @return void
	 */
	public function test_get_item_returns_error_when_template_is_not_found(): void {
		$controller = $this->create_controller();

		$request = new WP_REST_Request();
		$request->set_param( 'id', '999' );

		$response = $controller->get_item( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'ossr_template_not_found', $response->get_error_code() );

		$data = $response->get_error_data();

		$this->assertIsArray( $data );
		$this->assertSame( 404, $data['status'] );
	}

	/**
	 * Create controller.
	 *
	 * @param array<string, mixed>|null $template Template.
	 * @return TemplateController
	 */
	private function create_controller( ?array $template = null ): TemplateController {
		return new TemplateController(
			new TemplateService(
				new FakeTemplateRepository( $template )
			)
		);
	}

	/**
	 * Create template row.
	 *
	 * @param array<string, mixed> $overrides Overrides.
	 * @return array<string, mixed>
	 */
	private function create_template_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'           => 1,
				'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
				'name'         => 'Default Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => '<h1>{{ report.title }}</h1>',
				'content_hash' => 'hash-default',
				'version'      => '1.0.0',
				'is_system'    => true,
				'is_default'   => true,
				'is_active'    => true,
				'created_at'   => '2026-05-01 10:00:00',
				'updated_at'   => '2026-05-01 10:00:00',
			),
			$overrides
		);
	}
}
