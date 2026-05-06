<?php
/**
 * Dummy sales report data.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Provides dummy sales report data for initial rendering.
 */
final class DummySalesReportData {

	/**
	 * Build dummy view data.
	 *
	 * @param   array<string, string> $period Period.
	 * @return  array<string, mixed>
	 */
	public static function build( array $period ): array {
		$period_label = $period['period_label'] ?? '';

		return array(
			'report' => array(
				'title'        => '売上報告書',
				'period_label' => $period_label,
				'note'         => 'これは初期表示確認用のダミーデータです。',
			),
			'store'  => array(
				'name' => get_bloginfo( 'name' ),
			),
			'totals' => array(
				'order_count'         => 2,
				'payment_total_label' => '18,500',
			),
			'orders' => array(
				array(
					'order_date'          => '2026/04/01',
					'order_number'        => '1001',
					'customer_name'       => '山田太郎',
					'payment_total_label' => '12,000',
				),
				array(
					'order_date'          => '2026/04/02',
					'order_number'        => '1002',
					'customer_name'       => '佐藤花子',
					'payment_total_label' => '6,500',
				),
			),
		);
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
