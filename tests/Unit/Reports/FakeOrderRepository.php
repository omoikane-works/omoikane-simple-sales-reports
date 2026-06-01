<?php
/**
 * Sales report builder test.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Reports;

use OmoikaneWorks\SimpleSalesReports\Reports\OrderRepositoryInterface;

/**
 * Fake order repository.
 */
final class FakeOrderRepository implements OrderRepositoryInterface {

	/**
	 * Orders.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $orders;

	/**
	 * Constructor.
	 *
	 * @param   array<int, array<string, mixed>> $orders Orders.
	 */
	public function __construct( array $orders ) {
		$this->orders = $orders;
	}

	/**
	 * Find orders date period.
	 *
	 * @param   string $start_date Start date.
	 * @param   string $end_date   End date.
	 * @return  array<int, array<string, mixed>>
	 */
	public function find_by_period( string $start_date, string $end_date ): array {
		unset( $start_date, $end_date );

		return $this->orders;
	}
}
