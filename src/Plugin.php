<?php
/**
 * Main plugin class.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin bootstrap.
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Whether the plugin has already booted.
	 *
	 * @var bool
	 */
	private bool $booted = false;

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
	 * Get plugin instance.
	 *
	 * @return  self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot plugin.
	 *
	 * @return  void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->load_textdomain();
		$this->register_hooks();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return  void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'welcart-simple-report-sales',
			false,
			dirname( WSRS_PLUGIN_BASENAME ) . '/languages'
		);
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
			'manage_options',
			self::MENU_PARENT_SLUG,
			array( $this, 'render_sales_report_page' ),
			'dashicons-media-spreadsheet',
			56
		);

		add_submenu_page(
			self::MENU_PARENT_SLUG,
			__( '売上報告書', 'welcart-simple-report-sales' ),
			__( '売上報告書', 'welcart-simple-report-sales' ),
			'manage_options',
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
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'このページにアクセスする権限がありません。', 'welcart-simple-report-sales' )
			);
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( '売上報告書', 'welcart-simple-report-sales' ) . '</h1>';
		echo '<p>' . esc_html__( 'かんたん売上報告書 for Welcart の管理画面です。', 'welcart-simple-report-sales' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Prevent direct instantiation.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws  \LogicException Always throws because singleton instances must not be unserialized.
	 */
	public function __wakeup(): void {
		throw new \LogicException( 'Cannot unserialize singleton.' );
	}
}
