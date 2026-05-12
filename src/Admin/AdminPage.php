<?php
/**
 * Admin page handler.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Admin;

use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriodResolver;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriods;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\SalesReportBuilder;
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
	 * Default admin view.
	 *
	 * @var string
	 */
	private const DEFAULT_VIEW = 'report';

	/**
	 * Request parameter name for admin view.
	 *
	 * @var string
	 */
	private const VIEW_PARAM = 'view';

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
	 * Sales report builder.
	 *
	 * @var SalesReportBuilder
	 */
	private SalesReportBuilder $sales_report_builder;

	/**
	 * Admin page hooks.
	 *
	 * @var array<int, string>
	 */
	private array $admin_page_hooks = array();

	/**
	 * Constructor.
	 *
	 * @param   SalesReportRenderer  $sales_report_renderer Sales report renderer.
	 * @param   ReportPeriodResolver $period_resolver       Report period resolver.
	 * @param   SalesReportBuilder   $sales_report_builder  Sales report builder.
	 */
	public function __construct(
		SalesReportRenderer $sales_report_renderer,
		ReportPeriodResolver $period_resolver,
		SalesReportBuilder $sales_report_builder,
	) {
		$this->sales_report_renderer = $sales_report_renderer;
		$this->period_resolver       = $period_resolver;
		$this->sales_report_builder  = $sales_report_builder;
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
		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_assets' )
		);
	}

	/**
	 * Register admin menu.
	 *
	 * @return  void
	 */
	public function register_admin_menu(): void {
		$this->add_admin_page_hook(
			add_menu_page(
				__( 'かんたん報告書', 'welcart-simple-report-sales' ),
				__( 'かんたん報告書', 'welcart-simple-report-sales' ),
				self::CAPABILITY,
				self::MENU_PARENT_SLUG,
				array( $this, 'render_sales_report_page' ),
				'dashicons-media-spreadsheet',
				56
			)
		);

		$this->add_admin_page_hook(
			add_submenu_page(
				self::MENU_PARENT_SLUG,
				__( '売上報告書', 'welcart-simple-report-sales' ),
				__( '売上報告書', 'welcart-simple-report-sales' ),
				self::CAPABILITY,
				self::MENU_SALES_SLUG,
				array( $this, 'render_sales_report_page' )
			)
		);
	}

	/**
	 * Add admin page hook suffix.
	 *
	 * @param   bool|string $hook_suffix    Admin page hook suffix.
	 * @return  void
	 */
	private function add_admin_page_hook( bool|string $hook_suffix ): void {
		if ( ! is_string( $hook_suffix ) ) {
			return;
		}

		$this->admin_page_hooks[] = $hook_suffix;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param   string $hook_suffix    Current page hook suffix.
	 * @return  void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, $this->admin_page_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'wsrs-admin',
			plugins_url( 'assets/css/admin.css', WSRS_PLUGIN_FILE ),
			array(),
			WSRS_VERSION
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

		$current_view = $this->resolve_current_view();

		echo '<div class="wrap">';
		echo '<h1 class="wsrs-no-print">' . esc_html__( '売上報告書', 'welcart-simple-report-sales' ) . '</h1>';
		echo '<div class="wsrs-admin-layout">';

		$this->render_inner_navigation( $current_view );

		echo '<div class="wsrs-admin-main">';

		if ( self::DEFAULT_VIEW === $current_view ) {
			$this->render_report_generation_view();
		} else {
			/**
			 * Fires when rendering an extended sales report admin view.
			 *
			 * @param   string  $current_view   Current admin view.
			 */
			do_action( 'wsrs_render_sales_report_admin_view', $current_view );
		}

		echo '</div>';
		echo '</div>';
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
	 * @param   array<string, string> $period Report period.
	 * @return  void
	 */
	private function render_period_form( array $period ): void {

		$current_period = $period['period'] ?? ReportPeriods::PREVIOUS_MONTH;
		$start_date     = $period['start_date'] ?? '';
		$end_date       = $period['end_date'] ?? '';

		echo '<form method="get" class="wsrs-period-form wsrs-no-print">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::MENU_SALES_SLUG ) . '" />';
		echo '<input type="hidden" name="' . esc_attr( self::VIEW_PARAM ) . '" value="' . esc_attr( self::DEFAULT_VIEW ) . '" />';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		echo '<fieldset>';
		echo '<legend class="wsrs-period-form__legend">' . esc_html__( '期間', 'welcart-simple-report-sales' ) . '</legend>';
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
		echo '<label class="wsrs-period-form__radio">';
		echo '<input type="radio" name="period" value="' . esc_attr( ReportPeriods::CUSTOM ) . '" ' . checked( $current_period, ReportPeriods::CUSTOM, false ) . ' />';
		echo ' ' . esc_html__( '期間指定', 'welcart-simple-report-sales' );
		echo '</label>';
		echo '<input type="date" name="start_date" value="' . esc_attr( $start_date ) . '" />';
		echo ' <span aria-hidden="true">～</span> ';
		echo '<input type="date" name="end_date" value="' . esc_attr( $end_date ) . '" />';
		echo '</fieldset>';
		echo '<p class="wsrs-period-form__actions">';
		echo '<button type="submit" class="button button-secondary">';
		echo esc_html__( '表示', 'welcart-simple-report-sales' );
		echo '</button>';
		echo '</p>';
		echo '</form>';
	}

	/**
	 * Render period radio.
	 *
	 * @param   string $value          Radio value.
	 * @param   string $label          Radio label.
	 * @param   string $current_period Current period.
	 * @return  void
	 */
	private function render_period_radio( string $value, string $label, string $current_period ): void {
		echo '<label class="wsrs-period-form__radio">';
		echo '<input type="radio" name="period" value="' . esc_attr( $value ) . '" ' . checked( $current_period, $value, false ) . ' />';
		echo ' ' . esc_html( $label );
		echo '</label>';
	}

	/**
	 * Render inner navigation.
	 *
	 * @param   string $current_view   Current view.
	 * @return  void
	 */
	private function render_inner_navigation( string $current_view ): void {
		$menu_items = $this->get_inner_navigation_items();

		echo '<nav class="wsrs-admin-nav wsrs-no-print">';

		foreach ( $menu_items as $view => $item ) {
			$label = isset( $item['label'] ) ? (string) $item['label'] : '';
			$url   = isset( $item['url'] ) ? (string) $item['url'] : '';

			$class_names = array( 'wsrs-admin-nav__item' );

			if ( $current_view === $view ) {
				$class_names[] = 'is-active';
			}

			echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( implode( ' ', $class_names ) ) . '">';
			echo esc_html( $label );
			echo '</a>';
		}

		echo '</nav>';
	}

	/**
	 * Get inner navigation items.
	 *
	 * @return  array<string, array<string, string>>
	 */
	private function get_inner_navigation_items(): array {
		$items = array(
			self::DEFAULT_VIEW => array(
				'label' => __( '報告書生成', 'welcart-simple-report-sales' ),
				'url'   => add_query_arg(
					array(
						'page'           => self::MENU_SALES_SLUG,
						self::VIEW_PARAM => self::DEFAULT_VIEW,
					),
					admin_url( 'admin.php' )
				),
			),
		);

		/**
		 * Filters sales report admin inner navigation items.
		 *
		 * @param   array<string, array<string, string>>    $items  Inner navigation items.
		 */
		return apply_filters(
			'wsrs_sales_report_admin_menu_items',
			$items
		);
	}

	/**
	 * Resolve current admin view.
	 *
	 * @return  string
	 */
	private function resolve_current_view(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$view = isset( $_GET[ self::VIEW_PARAM ] )
			? sanitize_key( wp_unslash( (string) $_GET[ self::VIEW_PARAM ] ) )
			: self::DEFAULT_VIEW;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$allowed_views = array_keys( $this->get_inner_navigation_items() );

		if ( ! in_array( $view, $allowed_views, true ) ) {
			return self::DEFAULT_VIEW;
		}

		return $view;
	}

	/**
	 * Render report generation view.
	 *
	 * @return void
	 */
	private function render_report_generation_view(): void {
		$request     = $this->get_verified_request();
		$period      = $this->period_resolver->resolve( $request );
		$view_data   = $this->sales_report_builder->build( $period );
		$report_html = $this->sales_report_renderer->render_default_sales_report( $view_data );

		$this->render_period_form( $period );

		echo '<div class="wsrs-print-actions wsrs-no-print">';
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
	}
}
