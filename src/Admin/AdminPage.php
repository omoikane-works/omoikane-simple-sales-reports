<?php
/**
 * Admin page handler.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Admin;

use OmoikaneWorks\WelcartSimpleReportSales\Reports\DummySalesReportData as ReportsDummySalesReportData;
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
	 * Sales report renderer.
	 *
	 * @var SalesReportRenderer
	 */
	private SalesReportRenderer $sales_report_renderer;

	/**
	 * Constructor.
	 *
	 * @param   SalesReportRenderer $sales_report_renderer  Sales report renderer.
	 */
	public function __construct( SalesReportRenderer $sales_report_renderer ) {
		$this->sales_report_renderer = $sales_report_renderer;
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

		$view_data   = ReportsDummySalesReportData::build();
		$report_html = $this->sales_report_renderer->render_default_sales_report( $view_data );

		echo '<div class="wrap">';
		echo '<h1 class="wsrs-no-print">' . esc_html__( '売上報告書', 'welcart-simple-report-sales' ) . '</h1>';

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
}
