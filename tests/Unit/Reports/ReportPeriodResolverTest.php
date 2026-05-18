<?php
/**
 * Report period resolver tests.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Tests\Unit\Reports;

use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriodResolver;
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
}
