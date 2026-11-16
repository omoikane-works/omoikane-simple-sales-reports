<?php
/**
 * Rendering regressions for templates saved before OSSR 1.1.0.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Reports;

use DOMDocument;
use DOMXPath;
use OmoikaneWorks\SimpleSalesReports\Configs\BrandingImageService;
use OmoikaneWorks\SimpleSalesReports\Configs\ConfigService;
use OmoikaneWorks\SimpleSalesReports\Reports\SalesReportBuilder;
use OmoikaneWorks\SimpleSalesReports\Reports\SalesReportRenderer;
use OmoikaneWorks\SimpleSalesReports\Tests\Support\CreatesTemplateRows;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Configs\FakeConfigRepository;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates\FakeTemplateRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the builder and Mustache renderer together across template versions.
 */
final class SalesReportCompatibilityTest extends TestCase {

	use CreatesTemplateRows;

	/**
	 * Order scenarios for both bundled revisions.
	 *
	 * @return array<string, array{bool}>
	 */
	public static function order_scenarios(): array {
		return array(
			'multiple orders including a cancellation' => array( true ),
			'no orders'                                => array( false ),
		);
	}

	/**
	 * Verify actual table cells in the stored v1.0.3 and bundled v1.1.0 templates.
	 *
	 * @param bool $has_orders Whether to use the orders fixture.
	 */
	#[DataProvider( 'order_scenarios' )]
	public function test_renders_both_standard_template_revisions( bool $has_orders ): void {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Load exact saved and bundled template bodies.
		$legacy  = file_get_contents( dirname( __DIR__, 2 ) . '/fixtures/sales-report-v1.0.3.mustache' );
		$current = file_get_contents( OSSR_PLUGIN_DIR . 'templates/default-sales-report-1.1.0.mustache' );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertIsString( $legacy );
		$this->assertIsString( $current );
		$data = $this->build_report( $has_orders ? $this->orders() : array() );

		$templates = array(
			'legacy'  => $legacy,
			'current' => $current,
		);
		foreach ( $templates as $revision => $template ) {
			$is_legacy = 'legacy' === $revision;
			$html      = $this->render_template( $template, $data, true );
			$document  = new DOMDocument();
			$this->assertTrue( $document->loadHTML( '<meta charset="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET ) );
			$xpath = new DOMXPath( $document );

			$this->assertStringContainsString( '対象期間：2026年5月', $html );
			$this->assertStringContainsString( '店舗名：Example Store', $html );
			$this->assertSame(
				$has_orders ? '3 件' : '0 件',
				$xpath->evaluate( 'normalize-space(//tr[th="受注数"]/td)' )
			);
			$this->assertSame(
				$has_orders ? '2 件' : '0 件',
				$xpath->evaluate( 'normalize-space(//tr[th="売上対象数"]/td)' )
			);
			$this->assertSame(
				$has_orders ? '10,502 円' : '0 円',
				$xpath->evaluate( 'normalize-space(//tr[th="支払い合計金額"]/td)' )
			);
			if ( ! $has_orders ) {
				$this->assertStringContainsString( '対象期間の注文はありません。', $html );
				$this->assertSame( array( '0 円' ), $this->cells( $xpath, '//tr[th="値引き"]' ) );
				$this->assertSame( 0.0, $xpath->evaluate( 'count(//td[@class="order-number"])' ) );
				continue;
			}

			$this->assertSame( array( '10,002 円' ), $this->cells( $xpath, '//tr[th="商品合計"]' ) );
			$this->assertSame( array( $is_legacy ? '-1,000 円' : '▲1,000 円' ), $this->cells( $xpath, '//tr[th="値引き"]' ) );
			$this->assertSame( array( '900 円' ), $this->cells( $xpath, '//tr[th="消費税"]' ) );
			$this->assertSame( array( '800 円' ), $this->cells( $xpath, '//tr[th="送料"]' ) );
			$this->assertSame( array( '300 円' ), $this->cells( $xpath, '//tr[th="代引手数料"]' ) );
			$this->assertSame( array( $is_legacy ? '500 pt' : '▲500 円' ), $this->cells( $xpath, '//tr[th="使用ポイント"]' ) );
			$this->assertSame(
				array( '8,002 円', $is_legacy ? '-800 円' : '▲800 円', '800 円' ),
				$this->cells( $xpath, '//tr[th="標準税率対象"]' )
			);
			$this->assertSame(
				array( '2,000 円', $is_legacy ? '-200 円' : '▲200 円', '100 円' ),
				$this->cells( $xpath, '//tr[th="軽減税率対象"]' )
			);
			$this->assertSame(
				array( '2026/05/10', '1001', '山田 太郎', 'クレジットカード', 'New Order', '10,001', $is_legacy ? '-1,000' : '▲1,000', '900', '800', '300', $is_legacy ? '500' : '▲500', '10,501' ),
				$this->cells( $xpath, '//tr[td[@class="order-number"]="1001"]' )
			);
			$this->assertSame(
				array( '2026/05/12', '1003', '佐藤 花子', '銀行振込', 'Canceled', '5,000', '0', '500', '600', '0', '0', '6,100' ),
				$this->cells( $xpath, '//tr[td[@class="order-number"]="1003"]' )
			);
			$this->assertSame( 3.0, $xpath->evaluate( 'count(//td[@class="order-number"])' ) );
		}
	}

	/**
	 * Check custom legacy tags and their original number and label formats.
	 */
	public function test_renders_legacy_custom_template(): void {
		$template = <<<'MUSTACHE'
{{totals.standard_subtotal_amount}}/{{totals.amounts.standard_subtotal_label}}/{{totals.amounts.standard_discount_amount}}
{{totals.reduced_subtotal_amount}}/{{totals.amounts.reduced_subtotal_label}}/{{totals.amounts.reduced_discount_amount}}
{{totals.used_points}}/{{totals.amounts.used_points}}/{{{totals.amounts.earned_points}}}/{{totals.earned_points_label}}
{{#totals.sales_support}}{{rate}}/{{rate_label}}/{{base_amount}}/{{base_label}}/{{amount}}/{{amount_label}} {{calculation_note}}{{/totals.sales_support}}
{{#orders}}
{{order_datetime}}/{{customer_name_kana}}/{{status.raw}}/{{payment_total_amount}}/{{payment_total_label}}/{{amounts.standard_tax_amount}}/{{amounts.reduced_tax_label}}/{{amounts.used_points}}/{{amounts.earned_points}}
{{/orders}}
MUSTACHE;

		$expected = <<<'TEXT'
8002/8,002/-800
2000/2,000/-200
500/500/1200/1,200
0.025/2.5%/9402/9,402/236/236 The sales support fee is calculated by multiplying 2.5% by the amount after applying discounts to the product total, adding tax, and subtracting used points. Fractions are rounded up.
2026/05/10 14:23:45/ヤマダ タロウ/#none#/10501/10,501/800/100/500/1200
2026/05/11 09:00:00//#none#/1/1/0/0/0/0
2026/05/12 09:00:00/サトウ ハナコ/cancel/6100/6,100/500/0/0/0
TEXT;

		$this->assertSame( $expected, trim( $this->render_template( $template, $this->build_report( $this->orders() ) ) ) );
	}

	/**
	 * Mix versions in one template and retain new-format section behavior at zero.
	 */
	public function test_renders_mixed_tags_and_current_sections(): void {
		$template = <<<'MUSTACHE'
{{totals.item_total_amount}}={{totals.amounts.item_total.amount}};{{totals.discount_amount}}/{{totals.amounts.discount.label}};{{totals.sales_support.amount}}/{{totals.amounts.sales_support.amount}}
{{totals.amounts.earned_points}}:{{#totals.amounts.earned_points}}{{amount}}/{{label}}{{/totals.amounts.earned_points}}
{{#orders}}
{{customer_name}}={{customer.name}};{{customer_name_kana}}={{customer.name_kana}};{{payment_total_amount}}={{amounts.payment_total.amount}}
{{amounts.used_points}}:{{#amounts.used_points}}{{amount}}/{{label}}{{/amounts.used_points}}{{^amounts.used_points}}missing{{/amounts.used_points}}
{{^amounts.used_points.amount}}zero;{{/amounts.used_points.amount}}{{#is_sales_counted}}counted{{/is_sales_counted}}{{^status.is_sales_counted}}excluded{{/status.is_sales_counted}}
{{/orders}}
{{^orders}}empty{{/orders}}
MUSTACHE;

		$expected = <<<'TEXT'
10002=10002;-1000/▲1,000;236/237
1200:1200/1,200
山田 太郎=山田 太郎;ヤマダ タロウ=ヤマダ タロウ;10501=10501
500:500/▲500
counted
=;=;1=1
0:0/0
zero;counted
佐藤 花子=佐藤 花子;サトウ ハナコ=サトウ ハナコ;6100=6100
0:0/0
zero;excluded
TEXT;

		$this->assertSame( $expected, trim( $this->render_template( $template, $this->build_report( $this->orders() ) ) ) );
		$this->assertSame( "0=0;0/0;0/0\n0:0/0\nempty", trim( $this->render_template( $template, $this->build_report( array() ) ) ) );
	}

	/**
	 * A positive signed adjustment affected the legacy total in the opposite direction.
	 */
	public function test_preserves_signed_adjustments_for_legacy_tags(): void {
		$order                      = $this->orders()[0];
		$order['order_discount']    = 1000;
		$order['discount_standard'] = 800;
		$order['discount_reduced']  = 200;
		$template                   = <<<'MUSTACHE'
{{totals.discount_label}}/{{totals.amounts.discount.label}};{{totals.payment_total_label}}/{{totals.amounts.payment_total.label}}
{{#orders}}{{amounts.standard_discount_amount}}/{{amounts.reduced_discount_amount}};{{payment_total_label}}/{{amounts.payment_total.label}}{{/orders}}
MUSTACHE;

		$this->assertSame(
			"1,000/▲1,000;12,501/10,501\n800/200;12,501/10,501",
			$this->render_template( $template, $this->build_report( array( $order ) ) )
		);
	}

	/**
	 * Create a realistic set of Welcart orders with distinct dates and amounts.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function orders(): array {
		return array(
			array(
				'ID'                     => 1001,
				'order_date'             => '2026-05-10 14:23:45',
				'order_name1'            => '山田',
				'order_name2'            => '太郎',
				'order_name3'            => 'ヤマダ',
				'order_name4'            => 'タロウ',
				'order_payment_name'     => 'クレジットカード',
				'order_item_total_price' => 10001,
				'order_getpoint'         => 1200,
				'order_usedpoint'        => 500,
				'order_discount'         => -1000,
				'order_shipping_charge'  => 800,
				'order_cod_fee'          => 300,
				'order_tax'              => 900,
				'order_status'           => '#none#',
				'subtotal_standard'      => 8001,
				'subtotal_reduced'       => 2000,
				'discount_standard'      => -800,
				'discount_reduced'       => -200,
				'tax_standard'           => 800,
				'tax_reduced'            => 100,
			),
			array(
				'ID'                     => 1002,
				'order_date'             => '2026-05-11 09:00:00',
				'order_item_total_price' => 1,
				'subtotal_standard'      => 1,
				'order_status'           => '#none#',
			),
			array(
				'ID'                     => 1003,
				'order_date'             => '2026-05-12 09:00:00',
				'order_name1'            => '佐藤',
				'order_name2'            => '花子',
				'order_name3'            => 'サトウ',
				'order_name4'            => 'ハナコ',
				'order_payment_name'     => '銀行振込',
				'order_item_total_price' => 5000,
				'order_shipping_charge'  => 600,
				'order_tax'              => 500,
				'order_status'           => 'cancel',
				'subtotal_standard'      => 5000,
				'tax_standard'           => 500,
			),
		);
	}

	/**
	 * Build a report with the given Welcart rows.
	 *
	 * @param array<int, array<string, mixed>> $orders Raw order rows.
	 * @return array<string, mixed>
	 */
	private function build_report( array $orders ): array {
		return ( new SalesReportBuilder( new FakeOrderRepository( $orders ) ) )->build(
			array(
				'start_date'   => '2026-05-01',
				'end_date'     => '2026-05-31',
				'period_label' => '2026年5月',
			)
		);
	}

	/**
	 * Render the selected fixture without a database or registered branding images.
	 *
	 * @param string               $content   Mustache template.
	 * @param array<string, mixed> $data      Report data.
	 * @param bool                 $is_system Whether the fixture represents a standard template.
	 * @return string
	 */
	private function render_template( string $content, array $data, bool $is_system = false ): string {
		$template = $this->create_template_row(
			array(
				'id'        => 5,
				'content'   => $content,
				'is_system' => $is_system,
			)
		);
		$renderer = new SalesReportRenderer(
			new FakeTemplateRepository( array( 5 => $template ) ),
			new BrandingImageService( new ConfigService( new FakeConfigRepository() ) )
		);

		return $renderer->render_sales_report( $data, 5 );
	}

	/**
	 * Read the text values of a report row.
	 *
	 * @param DOMXPath $xpath Parsed report.
	 * @param string   $row   XPath to the row.
	 * @return list<string>
	 */
	private function cells( DOMXPath $xpath, string $row ): array {
		$nodes = $xpath->query( $row . '/td' );
		$this->assertNotFalse( $nodes );
		$cells = array();
		foreach ( $nodes as $node ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMNode property.
			$cells[] = trim( $node->textContent );
		}

		return $cells;
	}
}
