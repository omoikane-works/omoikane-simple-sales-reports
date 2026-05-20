<?php
/**
 * Sales report builder test.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Tests\Unit\Reports;

use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriods;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\SalesReportBuilder;
use OmoikaneWorks\WelcartSimpleReportSales\Tests\Unit\Reports\FakeOrderRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SalesReportBuilder.
 */
final class SalesReportBuilderTest extends TestCase {

	/**
	 * Tear down test.
	 *
	 * @return  void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wsrs_test_current_datetime'] );

		parent::tearDown();
	}

	/**
	 * Test build returns empty report data when no orders exist.
	 *
	 * @return  void
	 */
	public function test_build_returns_empty_report_data_when_no_orders_exist(): void {
		$GLOBALS['wsrs_test_current_datetime'] = '2026-05-19 12:34:56';

		$builder = new SalesReportBuilder(
			new FakeOrderRepository( array() )
		);

		$result = $builder->build(
			array(
				'period'       => ReportPeriods::CURRENT_MONTH,
				'start_date'   => '2026-05-01',
				'end_date'     => '2026-05-31',
				'period_label' => '2026年5月1日 ～ 2026年5月31日',
			)
		);

		$this->assertSame( '売上報告書', $result['report']['title'] );

		$this->assertSame( ReportPeriods::CURRENT_MONTH, $result['report']['period'] );
		$this->assertSame( '2026-05-01', $result['report']['start_date'] );
		$this->assertSame( '2026-05-31', $result['report']['end_date'] );
		$this->assertSame( '2026年5月1日 ～ 2026年5月31日', $result['report']['period_label'] );
		$this->assertSame( '2026/05/19 12:34:56', $result['report']['generated_at'] );
		$this->assertSame( 'Example Store', $result['store']['name'] );
		$this->assertSame( 'https://example.test', $result['store']['url'] );
		$this->assertSame( 0, $result['totals']['order_count'] );
		$this->assertSame( 0, $result['totals']['sales_order_count'] );
		$this->assertSame( 0, $result['totals']['item_total_amount'] );
		$this->assertSame( 0, $result['totals']['tax_amount'] );
		$this->assertSame( 0, $result['totals']['payment_total_amount'] );
		$this->assertSame( 0, $result['totals']['sales_support']['base_amount'] );
		$this->assertSame( 0, $result['totals']['sales_support']['amount'] );
		$this->assertSame( array(), $result['orders'] );
	}
}
