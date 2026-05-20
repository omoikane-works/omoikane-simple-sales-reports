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

	/**
	 * Test build summarizes a single sales order.
	 *
	 * @return  void
	 */
	public function test_build_summarizes_single_sales_order(): void {
		$builder = new SalesReportBuilder(
			new FakeOrderRepository(
				array(
					array(
						'ID'                     => 1001,
						'order_date'             => '2026-05-10 14:23:45',
						'order_name1'            => '山田',
						'order_name2'            => '太郎',
						'order_name3'            => 'ヤマダ',
						'order_name4'            => 'タロウ',
						'order_payment_name'     => 'クレジットカード',
						'order_item_total_price' => 10000,
						'order_getpoint'         => 100,
						'order_usedpoint'        => 500,
						'order_discount'         => -1000,
						'order_shipping_charge'  => 800,
						'order_cod_fee'          => 0,
						'order_tax'              => 900,
						'order_status'           => '#none#',
						'subtotal_standard'      => 8000,
						'subtotal_reduced'       => 2000,
						'discount_standard'      => -800,
						'discount_reduced'       => -200,
						'tax_standard'           => 800,
						'tax_reduced'            => 100,
					),
				)
			)
		);

		$result = $builder->build(
			array(
				'period'       => ReportPeriods::CURRENT_MONTH,
				'start_date'   => '2026-05-01',
				'end_date'     => '2026-05-31',
				'period_label' => '2026年5月1日 ～ 2026年5月31日',
			)
		);

		$this->assertSame( 1, $result['totals']['order_count'] );
		$this->assertSame( 1, $result['totals']['sales_order_count'] );
		$this->assertSame( 10000, $result['totals']['item_total_amount'] );
		$this->assertSame( '10,000', $result['totals']['item_total_label'] );
		$this->assertSame( 8000, $result['totals']['standard_subtotal_amount'] );
		$this->assertSame( 2000, $result['totals']['reduced_subtotal_amount'] );
		$this->assertSame( 900, $result['totals']['tax_amount'] );
		$this->assertSame( 800, $result['totals']['standard_tax_amount'] );
		$this->assertSame( 100, $result['totals']['reduced_tax_amount'] );
		$this->assertSame( 800, $result['totals']['shipping_fee_amount'] );
		$this->assertSame( 0, $result['totals']['cod_fee_amount'] );
		$this->assertSame( -1000, $result['totals']['discount_amount'] );
		$this->assertSame( -800, $result['totals']['standard_discount_amount'] );
		$this->assertSame( -200, $result['totals']['reduced_discount_amount'] );
		$this->assertSame( 500, $result['totals']['used_points'] );
		$this->assertSame( 100, $result['totals']['earned_points'] );
		// 10,000 + 900 + 800 + 0 - 1,000 - 500 = 10,200.
		$this->assertSame( 10200, $result['totals']['payment_total_amount'] );
		$this->assertSame( '10,200', $result['totals']['payment_total_label'] );
		// max( 0, 10,000 - 1,000 + 900 - 500 ) = 9,400.
		// ceil( 9,400 * 0.025 ) = 235.
		$this->assertSame( 9400, $result['totals']['sales_support']['base_amount'] );
		$this->assertSame( '9,400', $result['totals']['sales_support']['base_label'] );
		$this->assertSame( 235, $result['totals']['sales_support']['amount'] );
		$this->assertSame( '235', $result['totals']['sales_support']['amount_label'] );
		$this->assertSame( 0.025, $result['totals']['sales_support']['rate'] );
		$this->assertSame( '2.5%', $result['totals']['sales_support']['rate_label'] );
		$this->assertCount( 1, $result['orders'] );
		$order = $result['orders'][0];
		$this->assertSame( 1001, $order['id'] );
		$this->assertSame( '1001', $order['order_number'] );
		$this->assertSame( '2026/05/10', $order['order_date'] );
		$this->assertSame( '2026/05/10 14:23:45', $order['order_datetime'] );
		$this->assertSame( '山田 太郎', $order['customer_name'] );
		$this->assertSame( 'ヤマダ タロウ', $order['customer_name_kana'] );
		$this->assertSame( 'クレジットカード', $order['payment']['method'] );
		$this->assertTrue( $order['is_sales_counted'] );
		$this->assertSame( '#none#', $order['status']['raw'] );
		$this->assertSame( '新規受付', $order['status']['label'] );
		$this->assertTrue( $order['status']['is_sales_counted'] );
		$this->assertSame( 10200, $order['payment_total_amount'] );
		$this->assertSame( '10,200', $order['payment_total_label'] );
		$this->assertSame( 10200, $order['amounts']['payment_total_amount'] );
		$this->assertSame( '10,200', $order['amounts']['payment_total_label'] );
	}
}
