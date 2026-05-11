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
	 * Sales support rate.
	 *
	 * @var float
	 */
	private const SALES_SUPPORT_RATE = 0.025;

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

		$orders = array();
		$totals = $this->create_empty_totals();

		foreach ( $raw_orders as $raw_order ) {
			$order = $this->build_order_row( $raw_order );

			$this->add_order_to_totals( $totals, $order );
			$orders[] = $order;
		}

		$sales_support = $this->build_sales_support_data(
			$totals['item_total_amount'],
			$totals['discount_amount'],
			$totals['tax_amount'],
			$totals['used_points']
		);

		$sales_order_count = $this->count_sales_orders( $orders );

		return array(
			'report' => array(
				'title'        => '売上報告書',
				'period'       => $period['period'] ?? '',
				'start_date'   => $period['start_date'] ?? '',
				'end_date'     => $period['end_date'] ?? '',
				'period_label' => $period['period_label'] ?? '',
				'generated_at' => $this->format_generated_at(),
				'note'         => '',
			),
			'store'  => array(
				'name' => get_bloginfo( 'name' ),
				'url'  => home_url(),
			),
			'totals' => $this->build_totals_view_data(
				$totals,
				count( $orders ),
				$sales_order_count,
				$sales_support
			),
			'orders' => $orders,
		);
	}

	/**
	 * Create empty totals.
	 *
	 * @return array<string, int>
	 */
	private function create_empty_totals(): array {
		return array(
			'item_total_amount'        => 0,
			'standard_subtotal_amount' => 0,
			'reduced_subtotal_amount'  => 0,
			'tax_amount'               => 0,
			'standard_tax_amount'      => 0,
			'reduced_tax_amount'       => 0,
			'shipping_fee_amount'      => 0,
			'cod_fee_amount'           => 0,
			'discount_amount'          => 0,
			'standard_discount_amount' => 0,
			'reduced_discount_amount'  => 0,
			'used_points'              => 0,
			'earned_points'            => 0,
			'payment_total_amount'     => 0,
		);
	}

	/**
	 * Add order values to totals.
	 *
	 * @param   array<string, int>   $totals Totals.
	 * @param   array<string, mixed> $order  Order view data.
	 * @return  void
	 */
	private function add_order_to_totals( array &$totals, array $order ): void {
		if ( empty( $order['is_sales_counted'] ) ) {
			return;
		}

		$amounts = isset( $order['amounts'] ) && is_array( $order['amounts'] )
			? $order['amounts']
			: array();

		$totals['item_total_amount']        += $this->get_int_value( $amounts, 'item_total_amount' );
		$totals['standard_subtotal_amount'] += $this->get_int_value( $amounts, 'standard_subtotal_amount' );
		$totals['reduced_subtotal_amount']  += $this->get_int_value( $amounts, 'reduced_subtotal_amount' );
		$totals['tax_amount']               += $this->get_int_value( $amounts, 'tax_amount' );
		$totals['standard_tax_amount']      += $this->get_int_value( $amounts, 'standard_tax_amount' );
		$totals['reduced_tax_amount']       += $this->get_int_value( $amounts, 'reduced_tax_amount' );
		$totals['shipping_fee_amount']      += $this->get_int_value( $amounts, 'shipping_fee_amount' );
		$totals['cod_fee_amount']           += $this->get_int_value( $amounts, 'cod_fee_amount' );
		$totals['discount_amount']          += $this->get_int_value( $amounts, 'discount_amount' );
		$totals['standard_discount_amount'] += $this->get_int_value( $amounts, 'standard_discount_amount' );
		$totals['reduced_discount_amount']  += $this->get_int_value( $amounts, 'reduced_discount_amount' );
		$totals['used_points']              += $this->get_int_value( $amounts, 'used_points' );
		$totals['earned_points']            += $this->get_int_value( $amounts, 'earned_points' );
		$totals['payment_total_amount']     += $this->get_int_value( $amounts, 'payment_total_amount' );
	}

	/**
	 * Build totals view data.
	 *
	 * @param   array<string, int>   $totals             Totals.
	 * @param   int                  $order_count        Order count.
	 * @param   int                  $sales_order_count  Sales order count.
	 * @param   array<string, mixed> $sales_support         Sales support data.
	 * @return  array<string, mixed>
	 */
	private function build_totals_view_data(
		array $totals,
		int $order_count,
		int $sales_order_count,
		array $sales_support
	): array {
		$amounts = array(
			'item_total_amount'        => $totals['item_total_amount'],
			'item_total_label'         => $this->format_amount( $totals['item_total_amount'] ),
			'standard_subtotal_amount' => $totals['standard_subtotal_amount'],
			'standard_subtotal_label'  => $this->format_amount( $totals['standard_subtotal_amount'] ),
			'reduced_subtotal_amount'  => $totals['reduced_subtotal_amount'],
			'reduced_subtotal_label'   => $this->format_amount( $totals['reduced_subtotal_amount'] ),

			'tax_amount'               => $totals['tax_amount'],
			'tax_label'                => $this->format_amount( $totals['tax_amount'] ),
			'standard_tax_amount'      => $totals['standard_tax_amount'],
			'standard_tax_label'       => $this->format_amount( $totals['standard_tax_amount'] ),
			'reduced_tax_amount'       => $totals['reduced_tax_amount'],
			'reduced_tax_label'        => $this->format_amount( $totals['reduced_tax_amount'] ),

			'shipping_fee_amount'      => $totals['shipping_fee_amount'],
			'shipping_fee_label'       => $this->format_amount( $totals['shipping_fee_amount'] ),
			'cod_fee_amount'           => $totals['cod_fee_amount'],
			'cod_fee_label'            => $this->format_amount( $totals['cod_fee_amount'] ),

			'discount_amount'          => $totals['discount_amount'],
			'discount_label'           => $this->format_amount( $totals['discount_amount'] ),
			'standard_discount_amount' => $totals['standard_discount_amount'],
			'standard_discount_label'  => $this->format_amount( $totals['standard_discount_amount'] ),
			'reduced_discount_amount'  => $totals['reduced_discount_amount'],
			'reduced_discount_label'   => $this->format_amount( $totals['reduced_discount_amount'] ),

			'used_points'              => $totals['used_points'],
			'used_points_label'        => $this->format_number( $totals['used_points'] ),
			'earned_points'            => $totals['earned_points'],
			'earned_points_label'      => $this->format_number( $totals['earned_points'] ),
			'payment_total_amount'     => $totals['payment_total_amount'],
			'payment_total_label'      => $this->format_amount( $totals['payment_total_amount'] ),
		);

		return array_merge(
			array(
				'order_count'       => $order_count,
				'sales_order_count' => $sales_order_count,
				'amounts'           => $amounts,
				'sales_support'     => $sales_support,
			),
			$amounts
		);
	}

	/**
	 * Build order row.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  array<string, mixed>
	 */
	private function build_order_row( array $raw_order ): array {
		$id                   = $this->get_int_value( $raw_order, 'ID' );
		$customer_name        = $this->build_customer_name( $raw_order );
		$customer_name_kana   = $this->build_customer_name_kana( $raw_order );
		$order_date_raw       = $this->get_string_value( $raw_order, 'order_date' );
		$order_date           = $this->format_order_date( $order_date_raw );
		$order_datetime       = $this->format_order_datetime( $order_date_raw );
		$payment_method       = $this->get_string_value( $raw_order, 'order_payment_name' );
		$status_raw           = $this->get_string_value( $raw_order, 'order_status' );
		$status_label         = $this->build_status_label( $status_raw );
		$is_sales_counted     = $this->is_sales_counted_status( $status_raw );
		$amounts              = $this->build_order_amounts( $raw_order );
		$payment_total_amount = $this->get_int_value( $amounts, 'payment_total_amount' );
		$payment_total_label  = $this->format_amount( $payment_total_amount );

		return array(
			'id'                   => $id,
			'order_number'         => (string) $id,
			'order_date'           => $order_date,
			'order_datetime'       => $order_datetime,
			'customer_name'        => $customer_name,
			'customer_name_kana'   => $customer_name_kana,
			'customer'             => array(
				'name'      => $customer_name,
				'name_kana' => $customer_name_kana,
			),
			'payment'              => array(
				'method' => $payment_method,
			),
			'is_sales_counted'     => $is_sales_counted,
			'status'               => array(
				'raw'              => $status_raw,
				'label'            => $status_label,
				'is_sales_counted' => $is_sales_counted,
			),
			'amounts'              => $amounts,

			// Backward-compatible aliases for simple templates.
			'payment_total_amount' => $payment_total_amount,
			'payment_total_label'  => $payment_total_label,
		);
	}

	/**
	 * Build order amounts.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  array<string, int|string>
	 */
	private function build_order_amounts( array $raw_order ): array {
		$item_total_amount        = $this->get_amount_value( $raw_order, 'order_item_total_price' );
		$standard_subtotal_amount = $this->get_amount_value( $raw_order, 'subtotal_standard' );
		$reduced_subtotal_amount  = $this->get_amount_value( $raw_order, 'subtotal_reduced' );
		$tax_amount               = $this->get_amount_value( $raw_order, 'order_tax' );
		$standard_tax_amount      = $this->get_amount_value( $raw_order, 'tax_standard' );
		$reduced_tax_amount       = $this->get_amount_value( $raw_order, 'tax_reduced' );
		$shipping_fee_amount      = $this->get_amount_value( $raw_order, 'order_shipping_charge' );
		$cod_fee_amount           = $this->get_amount_value( $raw_order, 'order_cod_fee' );
		$discount_amount          = $this->get_amount_value( $raw_order, 'order_discount' );
		$standard_discount_amount = $this->get_amount_value( $raw_order, 'discount_standard' );
		$reduced_discount_amount  = $this->get_amount_value( $raw_order, 'discount_reduced' );
		$used_points              = $this->get_int_value( $raw_order, 'order_usedpoint' );
		$earned_points            = $this->get_int_value( $raw_order, 'order_getpoint' );
		$payment_total            = $this->calculate_payment_total(
			$item_total_amount,
			$tax_amount,
			$shipping_fee_amount,
			$cod_fee_amount,
			$discount_amount,
			$used_points,
		);

		return array(
			'item_total_amount'        => $item_total_amount,
			'item_total_label'         => $this->format_amount( $item_total_amount ),
			'standard_subtotal_amount' => $standard_subtotal_amount,
			'standard_subtotal_label'  => $this->format_amount( $standard_subtotal_amount ),
			'reduced_subtotal_amount'  => $reduced_subtotal_amount,
			'reduced_subtotal_label'   => $this->format_amount( $reduced_subtotal_amount ),

			'tax_amount'               => $tax_amount,
			'tax_label'                => $this->format_amount( $tax_amount ),
			'standard_tax_amount'      => $standard_tax_amount,
			'standard_tax_label'       => $this->format_amount( $standard_tax_amount ),
			'reduced_tax_amount'       => $reduced_tax_amount,
			'reduced_tax_label'        => $this->format_amount( $reduced_tax_amount ),

			'shipping_fee_amount'      => $shipping_fee_amount,
			'shipping_fee_label'       => $this->format_amount( $shipping_fee_amount ),
			'cod_fee_amount'           => $cod_fee_amount,
			'cod_fee_label'            => $this->format_amount( $cod_fee_amount ),

			'discount_amount'          => $discount_amount,
			'discount_label'           => $this->format_amount( $discount_amount ),
			'standard_discount_amount' => $standard_discount_amount,
			'standard_discount_label'  => $this->format_amount( $standard_discount_amount ),
			'reduced_discount_amount'  => $reduced_discount_amount,
			'reduced_discount_label'   => $this->format_amount( $reduced_discount_amount ),

			'used_points'              => $used_points,
			'used_points_label'        => $this->format_number( $used_points ),
			'earned_points'            => $earned_points,
			'earned_points_label'      => $this->format_number( $earned_points ),
			'payment_total_amount'     => $payment_total,
			'payment_total_label'      => $this->format_amount( $payment_total ),
		);
	}

	/**
	 * Calculate payment total.
	 *
	 * @param   int $item_total_amount      Item total amount.
	 * @param   int $tax_amount             Tax amount.
	 * @param   int $shipping_fee_amount    Shipping fee amount.
	 * @param   int $cod_fee_amount         Cash on delivery fee amount.
	 * @param   int $discount_amount        Discount amount.
	 * @param   int $used_points            Used points.
	 * @return  int
	 */
	private function calculate_payment_total(
		int $item_total_amount,
		int $tax_amount,
		int $shipping_fee_amount,
		int $cod_fee_amount,
		int $discount_amount,
		int $used_points,
	): int {
		return $item_total_amount + $tax_amount + $shipping_fee_amount + $cod_fee_amount + $discount_amount - $used_points;
	}

	/**
	 * Build sales support data.
	 *
	 * @param   int $item_total_amount  Item total amount.
	 * @param   int $discount_amount    Discount amount.
	 * @param   int $tax_amount         Tax amount.
	 * @param   int $used_points        Used points.
	 * @return  array<string, int|float|string>
	 */
	private function build_sales_support_data(
		int $item_total_amount,
		int $discount_amount,
		int $tax_amount,
		int $used_points,
	): array {
		$base_amount = max( 0, $item_total_amount + $discount_amount + $tax_amount - $used_points );
		$rate_label  = $this->format_rate( (float) self::SALES_SUPPORT_RATE );
		$amount      = (int) ceil( $base_amount * self::SALES_SUPPORT_RATE );

		return array(
			'rate'             => self::SALES_SUPPORT_RATE,
			'rate_label'       => $rate_label,
			'base_amount'      => $base_amount,
			'base_label'       => $this->format_amount( $base_amount ),
			'amount'           => $amount,
			'amount_label'     => $this->format_amount( $amount ),
			'calculation_note' => sprintf(
				// translators: %s: Sales support rate.
				__(
					'販売支援料は「商品合計から値引きを反映し、消費税を加算後、使用ポイントを差し引いた金額」に対して %s を乗じ、小数点以下を切り上げて算出しています。',
					'welcart-simple-report-sales'
				),
				$rate_label
			),
		);
	}

	/**
	 * Build customer name.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  string
	 */
	private function build_customer_name( array $raw_order ): string {
		return $this->join_name_parts(
			$this->get_string_value( $raw_order, 'order_name1' ),
			$this->get_string_value( $raw_order, 'order_name2' ),
		);
	}

	/**
	 * Build customer name kana.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  string
	 */
	private function build_customer_name_kana( array $raw_order ): string {
		return $this->join_name_parts(
			$this->get_string_value( $raw_order, 'order_name3' ),
			$this->get_string_value( $raw_order, 'order_name4' ),
		);
	}

	/**
	 * Join name parts.
	 *
	 * @param   string $first_name First name.
	 * @param   string $last_name  Last name.
	 * @return  string
	 */
	private function join_name_parts( string $first_name, string $last_name ): string {
		$name_parts = array_filter(
			array(
				$first_name,
				$last_name,
			)
		);

		return implode( ' ', $name_parts );
	}

	/**
	 * Count sales target orders.
	 *
	 * @param   array<int, array<string, mixed>> $orders Orders.
	 * @return  int
	 */
	private function count_sales_orders( array $orders ): int {
		$count = 0;

		foreach ( $orders as $order ) {
			if ( ! empty( $order['is_sales_counted'] ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Parse order statuses.
	 *
	 * @param   string $status_raw Raw status.
	 * @return  array<int, string>
	 */
	private function parse_order_statuses( string $status_raw ): array {
		$statuses = array_map(
			'trim',
			explode( ',', $status_raw )
		);

		$statuses = array_filter(
			$statuses,
			static fn ( string $status ): bool => '' !== $status
		);

		return array_values( $statuses );
	}

	/**
	 * Build status label.
	 *
	 * @param   string $status_raw Raw status.
	 * @return  string
	 */
	private function build_status_label( string $status_raw ): string {
		$statuses = $this->parse_order_statuses( $status_raw );

		if ( in_array( 'cancel', $statuses, true ) ) {
			return __( 'キャンセル', 'welcart-simple-report-sales' );
		}

		if ( array() === $statuses || in_array( '#none#', $statuses, true ) ) {
			return __( '新規受付', 'welcart-simple-report-sales' );
		}

		if ( in_array( 'duringorder', $statuses, true ) ) {
			return __( '取り寄せ中', 'welcart-simple-report-sales' );
		}

		if ( in_array( 'completion', $statuses, true ) ) {
			return __( '発送済み', 'welcart-simple-report-sales' );
		}

		return implode( ', ', $statuses );
	}

	/**
	 * Check whether order should be counted as sales.
	 *
	 * @param   string $status_raw Raw status.
	 * @return  bool
	 */
	private function is_sales_counted_status( string $status_raw ): bool {
		$statuses = $this->parse_order_statuses( $status_raw );

		if ( in_array( 'cancel', $statuses, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Format generated at.
	 *
	 * @return string
	 */
	private function format_generated_at(): string {
		return current_datetime()->format( 'Y/m/d H:i:s' );
	}

	/**
	 * Format order date.
	 *
	 * @param   string $order_date Order date.
	 * @return  string
	 */
	private function format_order_date( string $order_date ): string {
		$date = \DateTimeImmutable::createFromFormat(
			'Y-m-d H:i:s',
			$order_date,
			wp_timezone()
		);

		if ( ! $date instanceof \DateTimeImmutable ) {
			return '';
		}

		return $date->format( 'Y/m/d' );
	}

	/**
	 * Format order datetime.
	 *
	 * @param   string $order_date Order date.
	 * @return  string
	 */
	private function format_order_datetime( string $order_date ): string {
		$date = \DateTimeImmutable::createFromFormat(
			'Y-m-d H:i:s',
			$order_date,
			wp_timezone()
		);

		if ( ! $date instanceof \DateTimeImmutable ) {
			return '';
		}

		return $date->format( 'Y/m/d H:i:s' );
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
	 * Format number.
	 *
	 * @param   int $number Number.
	 * @return  string
	 */
	private function format_number( int $number ): string {
		return number_format( $number );
	}

	/**
	 * Format rate.
	 *
	 * @param   float $rate   Rate.
	 * @return  string
	 */
	private function format_rate( float $rate ): string {
		return rtrim( rtrim( number_format( $rate * 100, 2 ), '0' ), '.' ) . '%';
	}

	/**
	 * Get string value.
	 *
	 * @param   array<string, mixed> $data   Data.
	 * @param   string               $key    Key.
	 * @return  string
	 */
	private function get_string_value( array $data, string $key ): string {
		if ( ! array_key_exists( $key, $data ) || null === $data[ $key ] ) {
			return '';
		}

		if ( ! is_scalar( $data[ $key ] ) ) {
			return '';
		}

		return (string) $data[ $key ];
	}

	/**
	 * Get integer value.
	 *
	 * @param   array<string, mixed> $data   Data.
	 * @param   string               $key    Key.
	 * @return  int
	 */
	private function get_int_value( array $data, string $key ): int {
		if ( ! array_key_exists( $key, $data ) || null === $data[ $key ] ) {
			return 0;
		}

		if ( ! is_scalar( $data[ $key ] ) ) {
			return 0;
		}

		return (int) $data[ $key ];
	}

	/**
	 * Get amount value.
	 *
	 * @param   array<string, mixed> $data   Data.
	 * @param   string               $key    Key.
	 * @return  int
	 */
	private function get_amount_value( array $data, string $key ): int {
		if ( ! array_key_exists( $key, $data ) || null === $data[ $key ] ) {
			return 0;
		}

		if ( ! is_scalar( $data[ $key ] ) || ! is_numeric( $data[ $key ] ) ) {
			return 0;
		}

		return (int) round( (float) $data[ $key ] );
	}
}
