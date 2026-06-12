<?php
/**
 * Sales report renderer tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Reports;

use OmoikaneWorks\SimpleSalesReports\Reports\SalesReportRenderer;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates\FakeTemplateRepository;
use OmoikaneWorks\SimpleSalesReports\Tests\Support\CreatesTemplateRows;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SalesReportRenderer.
 */
final class SalesReportRendererTest extends TestCase {

	use CreatesTemplateRows;

	/**
	 * Test render default sales report renders template content.
	 *
	 * @return  void
	 */
	public function test_render_default_sales_report_renders_template_content(): void {
		$template    = $this->create_template_row(
			array(
				'id'      => 1,
				'name'    => 'Default Sales Report',
				'content' => '<h1>{{report.title}}</h1><p>{{store.name}}</p><p>{{totals.payment_total_label}}円</p>',
			)
		);
		$template_id = (int) $template['id'];

		$renderer = new SalesReportRenderer(
			new FakeTemplateRepository(
				array(
					$template_id => $template,
				)
			)
		);

		$result = $renderer->render_default_sales_report(
			array(
				'report' => array(
					'title' => '売上報告書',
				),
				'store'  => array(
					'name' => 'Example Store',
				),
				'totals' => array(
					'payment_total_label' => '10,200',
				),
			)
		);

		$this->assertSame(
			'<h1>売上報告書</h1><p>Example Store</p><p>10,200円</p>',
			$result
		);
	}

	/**
	 * Test render default sales report escapes template variables.
	 *
	 * @return void
	 */
	public function test_render_default_sales_report_escapes_template_variables(): void {
		$template    = $this->create_template_row(
			array(
				'id'      => 1,
				'name'    => 'Default Sales Report',
				'content' => '<p>{{store.name}}</p>',
			)
		);
		$template_id = (int) $template['id'];

		$renderer = new SalesReportRenderer(
			new FakeTemplateRepository(
				array(
					$template_id => $template,
				)
			)
		);

		$result = $renderer->render_default_sales_report(
			array(
				'store' => array(
					'name' => '<Example Store>',
				),
			)
		);
		$this->assertSame( '<p>&lt;Example Store&gt;</p>', $result );
	}

	/**
	 * Test render default sales report returns error message when template is missing.
	 *
	 * @return void
	 */
	public function test_render_default_sales_report_returns_error_message_when_template_is_missing(): void {
		$renderer = new SalesReportRenderer(
			new FakeTemplateRepository()
		);

		$result = $renderer->render_default_sales_report( array() );

		$this->assertSame(
			'<div class="notice notice-error"><p>The sales report template was not found. Please reactivate the plugin.</p></div>',
			$result
		);
	}

	/**
	 * Test render default sales report returns error message when template content is empty.
	 *
	 * @return void
	 */
	public function test_render_default_sales_report_returns_error_message_when_template_content_is_empty(): void {
		$template    = $this->create_template_row(
			array(
				'id'      => 1,
				'name'    => 'Empty Sales Report',
				'content' => '',
			)
		);
		$template_id = (int) $template['id'];

		$renderer = new SalesReportRenderer(
			new FakeTemplateRepository(
				array(
					$template_id => $template,
				)
			)
		);

		$result = $renderer->render_default_sales_report( array() );

		$this->assertSame(
			'<div class="notice notice-error"><p>The sales report template was not found. Please reactivate the plugin.</p></div>',
			$result
		);
	}
}
