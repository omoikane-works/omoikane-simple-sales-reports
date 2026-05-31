<?php
/**
 * Order repository interface.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Welcart orders.
 */
interface OrderRepositoryInterface {

	/**
	 * Find order by date period.
	 *
	 * @param   string $start_date Start date.
	 * @param   string $end_date   End date.
	 * @return  array<int, array<string, mixed>>
	 */
	public function find_by_period( string $start_date, string $end_date ): array;
}
