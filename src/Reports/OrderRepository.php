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
final class OrderRepository implements OrderRepositoryInterface {

	/**
	 * Get Welcart order table name.
	 *
	 * @return  string
	 */
	private function get_order_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'usces_order';
	}

	/**
	 * Get Welcart order meta table name.
	 *
	 * @return  string
	 */
	private function get_order_meta_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'usces_order_meta';
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
		$order_table        = $this->get_order_table_name();
		$order_meta_table   = $this->get_order_meta_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT
					o.ID,
					o.order_date,
					o.order_modified,
					o.order_name1,
					o.order_name2,
					o.order_name3,
					o.order_name4,
					o.order_payment_name,
					o.order_item_total_price,
					o.order_getpoint,
					o.order_usedpoint,
					o.order_discount,
					o.order_shipping_charge,
					o.order_cod_fee,
					o.order_tax,
					o.order_status,
					MAX(CASE WHEN om.meta_key = %s THEN om.meta_value END) AS subtotal_standard,
					MAX(CASE WHEN om.meta_key = %s THEN om.meta_value END) AS subtotal_reduced,
					MAX(CASE WHEN om.meta_key = %s THEN om.meta_value END) AS discount_standard,
					MAX(CASE WHEN om.meta_key = %s THEN om.meta_value END) AS discount_reduced,
					MAX(CASE WHEN om.meta_key = %s THEN om.meta_value END) AS tax_standard,
					MAX(CASE WHEN om.meta_key = %s THEN om.meta_value END) AS tax_reduced
				FROM %i AS o
				LEFT OUTER JOIN %i AS om
					ON om.order_id = o.ID
					AND om.meta_key IN ( %s, %s, %s, %s, %s, %s )
				WHERE order_date >= %s
				  AND order_date < %s
				GROUP BY
					o.ID,
					o.order_date,
					o.order_modified,
					o.order_name1,
					o.order_name2,
					o.order_name3,
					o.order_name4,
					o.order_payment_name,
					o.order_item_total_price,
					o.order_getpoint,
					o.order_usedpoint,
					o.order_discount,
					o.order_shipping_charge,
					o.order_cod_fee,
					o.order_tax,
					o.order_status
				ORDER BY o.order_date ASC, o.ID ASC',
				'subtotal_standard',
				'subtotal_reduced',
				'discount_standard',
				'discount_reduced',
				'tax_standard',
				'tax_reduced',
				$order_table,
				$order_meta_table,
				'subtotal_standard',
				'subtotal_reduced',
				'discount_standard',
				'discount_reduced',
				'tax_standard',
				'tax_reduced',
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
