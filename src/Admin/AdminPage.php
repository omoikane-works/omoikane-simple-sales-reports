<?php
/**
 * Admin page handler.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Admin;

use OmoikaneWorks\WelcartSimpleReportSales\Reports\DummySalesReportData;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriodResolver;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriods;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\SalesReportRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin menu and pages.
 */
final class AdminPage {

	/**
	 * Menu parent slug.
	 *
	 * @var string
	 */
	private const MENU_PARENT_SLUG = 'welcart-simple-report';

	/**
	 * Menu sales slug.
	 *
	 * @var string
	 */
	private const MENU_SALES_SLUG = 'welcart-simple-report-sales';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'wsrs_view_sales_report';

	/**
	 * Nonce name.
	 *
	 * @var string
	 */
	private const NONCE_NAME = 'wsrs_nonce';

	/**
	 * Sales report renderer.
	 *
	 * @var SalesReportRenderer
	 */
	private SalesReportRenderer $sales_report_renderer;

	/**
	 * Report period resolver.
	 *
	 * @var ReportPeriodResolver
	 */
	private ReportPeriodResolver $period_resolver;

	/**
	 * Constructor.
	 *
	 * @param   SalesReportRenderer  $sales_report_renderer Sales report renderer.
	 * @param   ReportPeriodResolver $period_resolver       Report period resolver.
	 */
	public function __construct(
		SalesReportRenderer $sales_report_renderer,
		ReportPeriodResolver $period_resolver,
	) {
		$this->sales_report_renderer = $sales_report_renderer;
		$this->period_resolver       = $period_resolver;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return  void
	 */
	public function register_hooks(): void {
		add_action(
			'admin_menu',
			array( $this, 'register_admin_menu' )
		);
	}

	/**
	 * Register admin menu.
	 *
	 * @return  void
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'かんたん報告書', 'welcart-simple-report-sales' ),
			__( 'かんたん報告書', 'welcart-simple-report-sales' ),
			self::CAPABILITY,
			self::MENU_PARENT_SLUG,
			array( $this, 'render_sales_report_page' ),
			'dashicons-media-spreadsheet',
			56
		);

		add_submenu_page(
			self::MENU_PARENT_SLUG,
			__( '売上報告書', 'welcart-simple-report-sales' ),
			__( '売上報告書', 'welcart-simple-report-sales' ),
			self::CAPABILITY,
			self::MENU_SALES_SLUG,
			array( $this, 'render_sales_report_page' )
		);
	}

	/**
	 * Render sales report page.
	 *
	 * @return  void
	 */
	public function render_sales_report_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'このページにアクセスする権限がありません。', 'welcart-simple-report-sales' )
			);
		}

		$request     = $this->get_verified_request();
		$period      = $this->period_resolver->resolve( $request );
		$view_data   = DummySalesReportData::build( $period );
		$report_html = $this->sales_report_renderer->render_default_sales_report( $view_data );

		echo '<div class="wrap">';
		echo '<h1 class="wsrs-no-print">' . esc_html__( '売上報告書', 'welcart-simple-report-sales' ) . '</h1>';

		$this->render_period_form( $period );

		echo '<div class="wsrs-no-print" style="margin: 16px 0;">';
		echo '<button type="button" class="button button-primary" onclick="window.print();">';
		echo esc_html__( '印刷', 'welcart-simple-report-sales' );
		echo '</button>';
		echo '</div>';

		echo '<p class="description wsrs-no-print">';
		echo esc_html__( 'PDF保存時に日付やURLが表示される場合は、印刷ダイアログの「ヘッダーとフッター」をオフにしてください。', 'welcart-simple-report-sales' );
		echo '</p>';

		// The report template is rendered from a trusted system template and escaped by Mustache.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $report_html;

		echo '</div>';
	}

	/**
	 * Get verified request parameters.
	 *
	 * @return array<string, mixed>
	 */
	private function get_verified_request(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::NONCE_NAME ] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = sanitize_text_field( wp_unslash( $_GET[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return wp_unslash( $_GET );
	}

	/**
	 * Render period form.
	 *
	 * @param array<string, string> $period Report period.
	 * @return void
	 */
	private function render_period_form( array $period ): void {

		$current_period = $period['period'] ?? ReportPeriods::PREVIOUS_MONTH;
		$start_date     = $period['start_date'] ?? '';
		$end_date       = $period['end_date'] ?? '';

		echo '<form method="get" class="wsrs-no-print" style="margin: 16px 0 24px; padding: 16px; background: #fff; border: 1px solid #c3c4c7;">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::MENU_SALES_SLUG ) . '" />';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		echo '<fieldset>';
		echo '<legend style="font-weight: 600; margin-bottom: 8px;">' . esc_html__( '期間', 'welcart-simple-report-sales' ) . '</legend>';
		$this->render_period_radio(
			ReportPeriods::CURRENT_MONTH,
			__( '今月', 'welcart-simple-report-sales' ),
			$current_period
		);
		$this->render_period_radio(
			ReportPeriods::PREVIOUS_MONTH,
			__( '前月', 'welcart-simple-report-sales' ),
			$current_period
		);
		echo '<label style="margin-right: 16px;">';
		echo '<input type="radio" name="period" value="' . esc_attr( ReportPeriods::CUSTOM ) . '" ' . checked( $current_period, ReportPeriods::CUSTOM, false ) . ' />';
		echo ' ' . esc_html__( '期間指定', 'welcart-simple-report-sales' );
		echo '</label>';
		echo '<input type="date" name="start_date" value="' . esc_attr( $start_date ) . '" />';
		echo ' <span aria-hidden="true">～</span> ';
		echo '<input type="date" name="end_date" value="' . esc_attr( $end_date ) . '" />';
		echo '</fieldset>';
		echo '<p style="margin: 12px 0 0;">';
		echo '<button type="submit" class="button button-secondary">';
		echo esc_html__( '表示', 'welcart-simple-report-sales' );
		echo '</button>';
		echo '</p>';
		echo '</form>';
	}

	/**
	 * Render period radio.
	 *
	 * @param string $value          Radio value.
	 * @param string $label          Radio label.
	 * @param string $current_period Current period.
	 * @return void
	 */
	private function render_period_radio( string $value, string $label, string $current_period ): void {
		echo '<label style="margin-right: 16px;">';
		echo '<input type="radio" name="period" value="' . esc_attr( $value ) . '" ' . checked( $current_period, $value, false ) . ' />';
		echo ' ' . esc_html( $label );
		echo '</label>';
	}
}
