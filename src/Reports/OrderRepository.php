<?php
/**
 * Order repository.
 *
 * @package WelcartSimpleReportSale
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Welcart orders.
 */
final class OrderRepository {

	/**
	 * Get Welcart order table name.
	 *
	 * @return  string
	 */
	private function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'usces_order';
	}

	/**
	 * Find orders by date period.
	 *
	 * @param   string $start_date Start date.
	 * @param   string $end_date   End date.
	 * @return  array<int, array<string, mixed>>
	 */
	public function find_by_period( string $start_date, string $end_date ): array {
		global $wpdb;

		$exclusive_end_date = $this->get_exclusive_end_date( $end_date );
		$table_name         = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT
					ID,
					order_date,
					order_name1,
					order_name2,
					order_name3,
					order_name4,
					order_payment_name,
					order_item_total_price,
					order_getpoint,
					order_usedpoint,
					order_discount,
					order_shipping_charge,
					order_cod_fee,
					order_tax,
					order_status
				FROM %i
				WHERE order_date >= %s
				  AND order_date < %s
				ORDER BY order_date ASC, ID ASC',
				$table_name,
				$start_date . ' 00:00:00',
				$exclusive_end_date . ' 00:00:00'
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$rows,
				static fn ( mixed $row ): bool => is_array( $row )
			)
		);
	}

	/**
	 * Get exclusive end date.
	 *
	 * @param   string $end_date   End date in Y-m-d.
	 * @return  string
	 */
	private function get_exclusive_end_date( string $end_date ): string {
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $end_date, wp_timezone() );

		if ( ! $date instanceof \DateTimeImmutable ) {
			return $end_date;
		}

		return $date->modify( '+1 day' )->format( 'Y-m-d' );
	}
}
