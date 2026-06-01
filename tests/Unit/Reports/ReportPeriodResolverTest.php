<?php
/**
 * Report period resolver tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Reports;

use OmoikaneWorks\SimpleSalesReports\Reports\ReportPeriodResolver;
use OmoikaneWorks\SimpleSalesReports\Reports\ReportPeriods;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ReportPeriodResolver.
 */
final class ReportPeriodResolverTest extends TestCase {

	/**
	 * Tear down test.
	 *
	 * @return  void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['ossr_test_current_datetime'] );

		parent::tearDown();
	}

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

	/**
	 * Test previous month period across year boundary.
	 *
	 * @return  void
	 */
	public function test_previous_month_period_across_year_boundary(): void {
		$GLOBALS['ossr_test_current_datetime'] = '2026-01-15 12:00:00';

		$resolver = new ReportPeriodResolver();

		$result = $resolver->resolve(
			array(
				'period' => ReportPeriods::PREVIOUS_MONTH,
			)
		);

		$this->assertSame( ReportPeriods::PREVIOUS_MONTH, $result['period'] );
		$this->assertSame( '2025-12-01', $result['start_date'] );
		$this->assertSame( '2025-12-31', $result['end_date'] );
		$this->assertSame( '2025年12月1日 ～ 2025年12月31日', $result['period_label'] );
	}

	/**
	 * Test previous month period for leap year February.
	 *
	 * @return  void
	 */
	public function test_previous_month_period_for_leap_year_february(): void {
		$GLOBALS['ossr_test_current_datetime'] = '2024-03-10 12:00:00';

		$resolver = new ReportPeriodResolver();

		$result = $resolver->resolve(
			array(
				'period' => ReportPeriods::PREVIOUS_MONTH,
			)
		);

		$this->assertSame( ReportPeriods::PREVIOUS_MONTH, $result['period'] );
		$this->assertSame( '2024-02-01', $result['start_date'] );
		$this->assertSame( '2024-02-29', $result['end_date'] );
		$this->assertSame( '2024年2月1日 ～ 2024年2月29日', $result['period_label'] );
	}

	/**
	 * Test custom period allows same start and end date.
	 *
	 * @return  void
	 */
	public function test_custom_period_allows_same_start_and_end_date(): void {
		$resolver = new ReportPeriodResolver();

		$result = $resolver->resolve(
			array(
				'period'     => ReportPeriods::CUSTOM,
				'start_date' => '2026-05-01',
				'end_date'   => '2026-05-01',
			)
		);

		$this->assertSame( ReportPeriods::CUSTOM, $result['period'] );
		$this->assertSame( '2026-05-01', $result['start_date'] );
		$this->assertSame( '2026-05-01', $result['end_date'] );
		$this->assertSame( '2026年5月1日 ～ 2026年5月1日', $result['period_label'] );
	}
}
