<?php
/**
 * Admin page handler.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Admin;

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

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( '売上報告書', 'welcart-simple-report-sales' ) . '</h1>';
		echo '<p>' . esc_html__( 'かんたん売上報告書 for Welcart の管理画面です。', 'welcart-simple-report-sales' ) . '</p>';
		echo '</div>';
	}
}
