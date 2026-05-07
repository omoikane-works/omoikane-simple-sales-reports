<?php
/**
 * Sales report builder.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Builds sales report view data.
 */
final class SalesReportBuilder {

	/**
	 * Order repository.
	 *
	 * @var OrderRepository
	 */
	private OrderRepository $order_repository;

	/**
	 * Constructor.
	 *
	 * @param   OrderRepository $order_repository   Order repository.
	 */
	public function __construct( OrderRepository $order_repository ) {
		$this->order_repository = $order_repository;
	}

	/**
	 * Build sales report view data.
	 *
	 * @param   array<string, string> $period Report period.
	 * @return  array<string, mixed>
	 */
	public function build( array $period ): array {
		$raw_orders = $this->order_repository->find_by_period(
			$period['start_date'] ?? '',
			$period['end_date'] ?? ''
		);

		$orders               = array();
		$payment_total_amount = 0;

		foreach ( $raw_orders as $raw_order ) {
			$order = $this->build_order_row( $raw_order );

			$payment_total_amount += $order['payment_total_amount'];
			$orders[]              = $order;
		}

		return array(
			'report' => array(
				'title'        => '売上報告書',
				'period_label' => $period['period_label'] ?? '',
				'note'         => '',
			),
			'store'  => array(
				'name' => get_bloginfo( 'name' ),
			),
			'totals' => array(
				'order_count'          => count( $orders ),
				'payment_total_amount' => $payment_total_amount,
				'payment_total_label'  => $this->format_amount( $payment_total_amount ),
			),
			'orders' => $orders,
		);
	}

	/**
	 * Build order row.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  array<string, mixed>
	 */
	private function build_order_row( array $raw_order ): array {
		$payment_total_amount = $this->calculate_payment_total( $raw_order );

		return array(
			'order_date'           => $this->format_order_date( $this->get_string_value( $raw_order, 'order_date' ) ),
			'order_number'         => $this->get_string_value( $raw_order, 'ID' ),
			'customer_name'        => $this->build_customer_name( $raw_order ),
			'payment_total_amount' => $payment_total_amount,
			'payment_total_label'  => $this->format_amount( $payment_total_amount ),
		);
	}

	/**
	 * Build customer name.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  string
	 */
	private function build_customer_name( array $raw_order ): string {
		$name_parts = array_filter(
			array(
				$this->get_string_value( $raw_order, 'order_name1' ),
				$this->get_string_value( $raw_order, 'order_name2' ),
			)
		);

		return implode( ' ', $name_parts );
	}

	/**
	 * Calculate fallback payment total.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  int
	 */
	private function calculate_payment_total( array $raw_order ): int {
		$item_total = $this->get_int_value( $raw_order, 'order_item_total_price' );
		$tax        = $this->get_int_value( $raw_order, 'order_tax' );
		$shipping   = $this->get_int_value( $raw_order, 'order_shipping_charge' );
		$cod_fee    = $this->get_int_value( $raw_order, 'order_cod_fee' );
		$discount   = $this->get_int_value( $raw_order, 'order_discount' );
		$points     = $this->get_int_value( $raw_order, 'order_usedpoint' );

		return $item_total + $tax + $shipping + $cod_fee + $discount - $points;
	}

	/**
	 * Format order date.
	 *
	 * @param   string $order_date Order date.
	 * @return  string
	 */
	private function format_order_date( string $order_date ): string {
		$timestamp = strtotime( $order_date );

		if ( false === $timestamp ) {
			return '';
		}

		$formatted_date = wp_date( 'Y/m/d', $timestamp );

		if ( false === $formatted_date ) {
			return '';
		}

		return $formatted_date;
	}

	/**
	 * Format amount.
	 *
	 * @param   int $amount Amount.
	 * @return  string
	 */
	private function format_amount( int $amount ): string {
		return number_format( $amount );
	}

	/**
	 * Get string value.
	 *
	 * @param   array<string, mixed> $data   Data.
	 * @param   string               $key    Key.
	 * @return  string
	 */
	private function get_string_value( array $data, string $key ): string {
		return isset( $data[ $key ] ) ? (string) $data[ $key ] : '';
	}

	/**
	 * Get integer value.
	 *
	 * @param   array<string, mixed> $data   Data.
	 * @param   string               $key    Key.
	 * @return  int
	 */
	private function get_int_value( array $data, string $key ): int {
		return isset( $data[ $key ] ) ? (int) $data[ $key ] : 0;
	}
}
