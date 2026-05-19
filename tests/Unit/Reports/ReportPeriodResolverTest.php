<?php
/**
 * Report period resolver tests.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Tests\Unit\Reports;

use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriodResolver;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriods;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ReportPeriodResolver.
 */
final class ReportPeriodResolverTest extends TestCase {

	/**
	 * Test that resolver can be instantiated.
	 *
	 * @return  void
	 */
	public function test_it_can_be_instantiated(): void {
		$resolver = new ReportPeriodResolver();

		$this->assertInstanceOf( ReportPeriodResolver::class, $resolver );
	}

	/**
	 * Test default period resolves to previous month.
	 *
	 * @return  void
	 */
	public function test_default_period_resolves_to_previous_month(): void {
		$resolver = new ReportPeriodResolver();

		$result = $resolver->resolve( array() );

		$this->assertSame( ReportPeriods::PREVIOUS_MONTH, $result['period'] );
		$this->assertSame( '2026-04-01', $result['start_date'] );
		$this->assertSame( '2026-04-30', $result['end_date'] );
		$this->assertSame( '2026年4月1日 ～ 2026年4月30日', $result['period_label'] );
	}

	/**

	 * Test current month period.
	 *
	 * @return void
	 */
	public function test_current_month_period(): void {
		$resolver = new ReportPeriodResolver();
		$result   = $resolver->resolve(
			array(
				'period' => ReportPeriods::CURRENT_MONTH,
			)
		);
		$this->assertSame( ReportPeriods::CURRENT_MONTH, $result['period'] );
		$this->assertSame( '2026-05-01', $result['start_date'] );
		$this->assertSame( '2026-05-31', $result['end_date'] );
		$this->assertSame( '2026年5月1日 ～ 2026年5月31日', $result['period_label'] );
	}

	/**

	 * Test previous month period.
	 *
	 * @return void
	 */
	public function test_previous_month_period(): void {
		$resolver = new ReportPeriodResolver();
		$result   = $resolver->resolve(
			array(
				'period' => ReportPeriods::PREVIOUS_MONTH,
			)
		);
		$this->assertSame( ReportPeriods::PREVIOUS_MONTH, $result['period'] );
		$this->assertSame( '2026-04-01', $result['start_date'] );
		$this->assertSame( '2026-04-30', $result['end_date'] );
	}
	/**
	 * Test custom period.
	 *
	 * @return void
	 */
	public function test_custom_period(): void {
		$resolver = new ReportPeriodResolver();
		$result   = $resolver->resolve(
			array(
				'period'     => ReportPeriods::CUSTOM,
				'start_date' => '2026-01-10',
				'end_date'   => '2026-01-20',
			)
		);
		$this->assertSame( ReportPeriods::CUSTOM, $result['period'] );
		$this->assertSame( '2026-01-10', $result['start_date'] );
		$this->assertSame( '2026-01-20', $result['end_date'] );
		$this->assertSame( '2026年1月10日 ～ 2026年1月20日', $result['period_label'] );
	}

	/**

	 * Test invalid period falls back to previous month.
	 *
	 * @return void
	 */
	public function test_invalid_period_falls_back_to_previous_month(): void {
		$resolver = new ReportPeriodResolver();
		$result   = $resolver->resolve(
			array(
				'period' => 'invalid',
			)
		);
		$this->assertSame( ReportPeriods::PREVIOUS_MONTH, $result['period'] );
		$this->assertSame( '2026-04-01', $result['start_date'] );
		$this->assertSame( '2026-04-30', $result['end_date'] );
	}
	/**
	 * Test invalid custom date falls back to previous month.
	 *
	 * @return void
	 */
	public function test_invalid_custom_date_falls_back_to_previous_month(): void {
		$resolver = new ReportPeriodResolver();
		$result   = $resolver->resolve(
			array(
				'period'     => ReportPeriods::CUSTOM,
				'start_date' => '2026-02-31',
				'end_date'   => '2026-03-10',
			)
		);
		$this->assertSame( ReportPeriods::PREVIOUS_MONTH, $result['period'] );
		$this->assertSame( '2026-04-01', $result['start_date'] );
		$this->assertSame( '2026-04-30', $result['end_date'] );
	}
	/**
	 * Test reversed custom date range falls back to previous month.
	 *
	 * @return void
	 */
	public function test_reversed_custom_date_range_falls_back_to_previous_month(): void {
		$resolver = new ReportPeriodResolver();
		$result   = $resolver->resolve(
			array(
				'period'     => ReportPeriods::CUSTOM,
				'start_date' => '2026-03-20',
				'end_date'   => '2026-03-10',
			)
		);
		$this->assertSame( ReportPeriods::PREVIOUS_MONTH, $result['period'] );
		$this->assertSame( '2026-04-01', $result['start_date'] );
		$this->assertSame( '2026-04-30', $result['end_date'] );
	}
}
