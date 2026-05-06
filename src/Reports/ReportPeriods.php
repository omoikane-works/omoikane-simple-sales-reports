<?php
/**
 * Report periods.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Defines report periods.
 */
final class ReportPeriods {

	/**
	 * Current month.
	 *
	 * @var string
	 */
	public const CURRENT_MONTH = 'current_month';

	/**
	 * Previous month.
	 *
	 * @var string
	 */
	public const PREVIOUS_MONTH = 'previous_month';

	/**
	 * Custom date range.
	 *
	 * @var string
	 */
	public const CUSTOM = 'custom';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
