<?php
/**
 * Sales report builder.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Reports;

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
	 * @var OrderRepositoryInterface
	 */
	private OrderRepositoryInterface $order_repository;

	/**
	 * Constructor.
	 *
	 * @param   OrderRepositoryInterface $order_repository   Order repository.
	 */
	public function __construct( OrderRepositoryInterface $order_repository ) {
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

		$sales_order_count = $this->count_sales_orders( $orders );

		$data = array(
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
				$sales_order_count
			),
			'orders' => $orders,
		);

		return $data;
	}

	/**
	 * Create empty totals.
	 *
	 * @return array<string, int>
	 */
	private function create_empty_totals(): array {
		return array(
			'item_total_amount'          => 0,
			'standard_item_total_amount' => 0,
			'reduced_item_total_amount'  => 0,
			'tax_amount'                 => 0,
			'standard_tax_amount'        => 0,
			'reduced_tax_amount'         => 0,
			'shipping_fee_amount'        => 0,
			'cod_fee_amount'             => 0,
			'discount_amount'            => 0,
			'standard_discount_amount'   => 0,
			'reduced_discount_amount'    => 0,
			'used_points'                => 0,
			'earned_points'              => 0,
			'sales_support_amount'       => 0,
			'payment_total_amount'       => 0,
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

		$totals['item_total_amount']          += $this->get_amount_item_amount( $amounts, 'item_total' );
		$totals['standard_item_total_amount'] += $this->get_amount_item_breakdown_amount( $amounts, 'item_total', 'standard' );
		$totals['reduced_item_total_amount']  += $this->get_amount_item_breakdown_amount( $amounts, 'item_total', 'reduced' );
		$totals['tax_amount']                 += $this->get_amount_item_amount( $amounts, 'tax' );
		$totals['standard_tax_amount']        += $this->get_amount_item_breakdown_amount( $amounts, 'tax', 'standard' );
		$totals['reduced_tax_amount']         += $this->get_amount_item_breakdown_amount( $amounts, 'tax', 'reduced' );
		$totals['shipping_fee_amount']        += $this->get_amount_item_amount( $amounts, 'shipping_fee' );
		$totals['cod_fee_amount']             += $this->get_amount_item_amount( $amounts, 'cod_fee' );
		$totals['discount_amount']            += $this->get_amount_item_amount( $amounts, 'discount' );
		$totals['standard_discount_amount']   += $this->get_amount_item_breakdown_amount( $amounts, 'discount', 'standard' );
		$totals['reduced_discount_amount']    += $this->get_amount_item_breakdown_amount( $amounts, 'discount', 'reduced' );
		$totals['used_points']                += $this->get_amount_item_amount( $amounts, 'used_points' );
		$totals['earned_points']              += $this->get_amount_item_amount( $amounts, 'earned_points' );
		$totals['sales_support_amount']       += $this->get_amount_item_amount( $amounts, 'sales_support' );
		$totals['payment_total_amount']       += $this->get_amount_item_amount( $amounts, 'payment_total' );
	}

	/**
	 * Build totals view data.
	 *
	 * @param   array<string, int> $totals             Totals.
	 * @param   int                $order_count        Order count.
	 * @param   int                $sales_order_count  Sales order count.
	 * @return  array<string, mixed>
	 */
	private function build_totals_view_data(
		array $totals,
		int $order_count,
		int $sales_order_count,
	): array {
		$amounts = array(
			'item_total'    => array(
				'amount'   => $totals['item_total_amount'],
				'label'    => $this->format_amount( $totals['item_total_amount'] ),
				'standard' => array(
					'amount' => $totals['standard_item_total_amount'],
					'label'  => $this->format_amount( $totals['standard_item_total_amount'] ),
				),
				'reduced'  => array(
					'amount' => $totals['reduced_item_total_amount'],
					'label'  => $this->format_amount( $totals['reduced_item_total_amount'] ),
				),
			),

			'tax'           => array(
				'amount'   => $totals['tax_amount'],
				'label'    => $this->format_amount( $totals['tax_amount'] ),
				'standard' => array(
					'amount' => $totals['standard_tax_amount'],
					'label'  => $this->format_amount( $totals['standard_tax_amount'] ),
				),
				'reduced'  => array(
					'amount' => $totals['reduced_tax_amount'],
					'label'  => $this->format_amount( $totals['reduced_tax_amount'] ),
				),
			),

			'shipping_fee'  => array(
				'amount' => $totals['shipping_fee_amount'],
				'label'  => $this->format_amount( $totals['shipping_fee_amount'] ),
			),
			'cod_fee'       => array(
				'amount' => $totals['cod_fee_amount'],
				'label'  => $this->format_amount( $totals['cod_fee_amount'] ),
			),

			'discount'      => array(
				'amount'   => $totals['discount_amount'],
				'label'    => $this->format_deduction_label( $totals['discount_amount'] ),
				'standard' => array(
					'amount' => $totals['standard_discount_amount'],
					'label'  => $this->format_deduction_label( $totals['standard_discount_amount'] ),
				),
				'reduced'  => array(
					'amount' => $totals['reduced_discount_amount'],
					'label'  => $this->format_deduction_label( $totals['reduced_discount_amount'] ),
				),
			),

			'used_points'   => array(
				'amount' => $totals['used_points'],
				'label'  => $this->format_deduction_label( $totals['used_points'] ),
			),

			'earned_points' => array(
				'amount' => $totals['earned_points'],
				'label'  => $this->format_number( $totals['earned_points'] ),
			),

			'payment_total' => array(
				'amount' => $totals['payment_total_amount'],
				'label'  => $this->format_amount( $totals['payment_total_amount'] ),
			),

			'sales_support' => array(
				'amount' => $totals['sales_support_amount'],
				'label'  => $this->format_amount( $totals['sales_support_amount'] ),
			),
		);

		return array(
			'order_count'       => $order_count,
			'sales_order_count' => $sales_order_count,
			'amounts'           => $amounts,
		);
	}

	/**
	 * Build order row.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  array<string, mixed>
	 */
	private function build_order_row( array $raw_order ): array {
		$id                 = $this->get_int_value( $raw_order, 'ID' );
		$customer_name      = $this->build_customer_name( $raw_order );
		$customer_name_kana = $this->build_customer_name_kana( $raw_order );
		$order_date_raw     = $this->get_string_value( $raw_order, 'order_date' );
		$order_date         = $this->format_order_date( $order_date_raw );
		$order_datetime     = $this->format_order_datetime( $order_date_raw );
		$payment_method     = $this->get_string_value( $raw_order, 'order_payment_name' );
		$status_raw         = $this->get_string_value( $raw_order, 'order_status' );
		$status_label       = $this->build_status_label( $status_raw );
		$is_sales_counted   = $this->is_sales_counted_status( $status_raw );
		$amounts            = $this->build_order_amounts( $raw_order );

		return array(
			'id'               => $id,
			'order_number'     => (string) $id,
			'order_date'       => $order_date,
			'order_datetime'   => $order_datetime,
			'customer'         => array(
				'name'      => $customer_name,
				'name_kana' => $customer_name_kana,
			),
			'payment'          => array(
				'method' => $payment_method,
			),
			'is_sales_counted' => $is_sales_counted,
			'status'           => array(
				'raw'              => $status_raw,
				'label'            => $status_label,
				'is_sales_counted' => $is_sales_counted,
			),
			'amounts'          => $amounts,
		);
	}

	/**
	 * Build order amounts.
	 *
	 * @param   array<string, mixed> $raw_order  Raw order row.
	 * @return  array<string, mixed>
	 */
	private function build_order_amounts( array $raw_order ): array {
		$item_total_amount            = $this->get_amount_value( $raw_order, 'order_item_total_price' );
		$standard_item_total_amount   = $this->get_amount_value( $raw_order, 'subtotal_standard' );
		$reduced_item_total_amount    = $this->get_amount_value( $raw_order, 'subtotal_reduced' );
		$tax_amount                   = $this->get_amount_value( $raw_order, 'order_tax' );
		$standard_tax_amount          = $this->get_amount_value( $raw_order, 'tax_standard' );
		$reduced_tax_amount           = $this->get_amount_value( $raw_order, 'tax_reduced' );
		$shipping_fee_amount          = $this->get_amount_value( $raw_order, 'order_shipping_charge' );
		$cod_fee_amount               = $this->get_amount_value( $raw_order, 'order_cod_fee' );
		$raw_discount_amount          = $this->get_amount_value( $raw_order, 'order_discount' );
		$discount_amount              = abs( $raw_discount_amount );
		$raw_standard_discount_amount = $this->get_amount_value( $raw_order, 'discount_standard' );
		$standard_discount_amount     = abs( $raw_standard_discount_amount );
		$raw_reduced_discount_amount  = $this->get_amount_value( $raw_order, 'discount_reduced' );
		$reduced_discount_amount      = abs( $raw_reduced_discount_amount );
		$used_points                  = $this->get_int_value( $raw_order, 'order_usedpoint' );
		$earned_points                = $this->get_int_value( $raw_order, 'order_getpoint' );
		$payment_total                = $this->calculate_payment_total(
			$item_total_amount,
			$tax_amount,
			$shipping_fee_amount,
			$cod_fee_amount,
			$discount_amount,
			$used_points,
		);
		$sales_support                = $this->build_sales_support_data(
			$item_total_amount,
			$tax_amount,
			$discount_amount,
			$used_points
		);

		return array(
			'item_total'    => array(
				'amount'   => $item_total_amount,
				'label'    => $this->format_amount( $item_total_amount ),
				'standard' => array(
					'amount' => $standard_item_total_amount,
					'label'  => $this->format_amount( $standard_item_total_amount ),
				),
				'reduced'  => array(
					'amount' => $reduced_item_total_amount,
					'label'  => $this->format_amount( $reduced_item_total_amount ),
				),
			),

			'tax'           => array(
				'amount'   => $tax_amount,
				'label'    => $this->format_amount( $tax_amount ),
				'standard' => array(
					'amount' => $standard_tax_amount,
					'label'  => $this->format_amount( $standard_tax_amount ),
				),
				'reduced'  => array(
					'amount' => $reduced_tax_amount,
					'label'  => $this->format_amount( $reduced_tax_amount ),
				),
			),

			'shipping_fee'  => array(
				'amount' => $shipping_fee_amount,
				'label'  => $this->format_amount( $shipping_fee_amount ),
			),
			'cod_fee'       => array(
				'amount' => $cod_fee_amount,
				'label'  => $this->format_amount( $cod_fee_amount ),
			),

			'discount'      => array(
				'amount'   => $discount_amount,
				'label'    => $this->format_deduction_label( $discount_amount ),
				'standard' => array(
					'amount' => $standard_discount_amount,
					'label'  => $this->format_deduction_label( $standard_discount_amount ),
				),
				'reduced'  => array(
					'amount' => $reduced_discount_amount,
					'label'  => $this->format_deduction_label( $reduced_discount_amount ),
				),
			),

			'used_points'   => array(
				'amount' => $used_points,
				'label'  => $this->format_deduction_label( $used_points ),
			),

			'earned_points' => array(
				'amount' => $earned_points,
				'label'  => $this->format_number( $earned_points ),
			),

			'payment_total' => array(
				'amount' => $payment_total,
				'label'  => $this->format_amount( $payment_total ),
			),

			'sales_support' => $sales_support,
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
		return $item_total_amount + $tax_amount + $shipping_fee_amount + $cod_fee_amount - $discount_amount - $used_points;
	}

	/**
	 * Calculate sales support base amount.
	 *
	 * @param   int $item_total_amount  Item total amount.
	 * @param   int $tax_amount         Tax amount.
	 * @param   int $discount_amount    Discount amount.
	 * @param   int $used_points        Used points.
	 * @return  int
	 */
	private function calculate_sales_support_base_amount(
		int $item_total_amount,
		int $tax_amount,
		int $discount_amount,
		int $used_points
	): int {
		return max(
			0,
			$item_total_amount + $tax_amount - $discount_amount - $used_points
		);
	}

	/**
	 * Build sales support data.
	 *
	 * @param   int $item_total_amount  Item total amount.
	 * @param   int $tax_amount         Tax amount.
	 * @param   int $discount_amount    Discount amount.
	 * @param   int $used_points        Used points.
	 * @return  array<string, mixed>
	 *
	 * @phpstan-return array{
	 *      amount: int,
	 *      label: string,
	 *      rate: array{amount: float, label: string},
	 *      base: array{amount: int, label: string},
	 *      calculation_note: string
	 * }
	 */
	private function build_sales_support_data(
		int $item_total_amount,
		int $tax_amount,
		int $discount_amount,
		int $used_points,
	): array {
		$base_amount = $this->calculate_sales_support_base_amount(
			$item_total_amount,
			$tax_amount,
			$discount_amount,
			$used_points
		);
		$rate_label  = $this->format_rate( (float) self::SALES_SUPPORT_RATE );
		$amount      = (int) ceil( $base_amount * self::SALES_SUPPORT_RATE );

		return array(
			'amount'           => $amount,
			'label'            => $this->format_amount( $amount ),
			'rate'             => array(
				'amount' => (float) self::SALES_SUPPORT_RATE,
				'label'  => $rate_label,
			),
			'base'             => array(
				'amount' => $base_amount,
				'label'  => $this->format_amount( $base_amount ),
			),
			'calculation_note' => sprintf(
				// translators: %s: Sales support rate.
				__(
					'The sales support fee is calculated by multiplying %s by the amount after applying discounts to the product total, adding tax, and subtracting used points. Fractions are rounded up.',
					'omoikane-simple-sales-reports'
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
			return __( 'Canceled', 'omoikane-simple-sales-reports' );
		}

		if ( array() === $statuses || in_array( '#none#', $statuses, true ) ) {
			return __( 'New Order', 'omoikane-simple-sales-reports' );
		}

		if ( in_array( 'duringorder', $statuses, true ) ) {
			return __( 'On Backorder', 'omoikane-simple-sales-reports' );
		}

		if ( in_array( 'completion', $statuses, true ) ) {
			return __( 'Shipped', 'omoikane-simple-sales-reports' );
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
	 * Format deduction label.
	 *
	 * @param   int $amount    Amount.
	 * @return  string
	 */
	private function format_deduction_label( int $amount ): string {
		$amount = abs( $amount );

		if ( 0 === $amount ) {
			return '0';
		}

		return '▲' . $this->format_amount( $amount );
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

	/**
	 * Get amount item amount.
	 *
	 * @param   array<string, mixed> $data   Data.
	 * @param   string               $key    Key.
	 * @return  int
	 */
	private function get_amount_item_amount( array $data, string $key ): int {
		if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
			return 0;
		}

		return $this->get_int_value( $data[ $key ], 'amount' );
	}

	/**
	 * Get amount item breakdown amount.
	 *
	 * @param   array<string, mixed> $data       Data.
	 * @param   string               $key        Key.
	 * @param   string               $breakdown  Breakdown.
	 * @return  int
	 */
	private function get_amount_item_breakdown_amount(
		array $data,
		string $key,
		string $breakdown,
	): int {
		if (
			! isset( $data[ $key ] ) ||
			! is_array( $data[ $key ] ) ||
			! isset( $data[ $key ][ $breakdown ] ) ||
			! is_array( $data[ $key ][ $breakdown ] )
		) {
			return 0;
		}

		return $this->get_int_value( $data[ $key ][ $breakdown ], 'amount' );
	}
}
