<?php
/**
 * Main plugin class.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports;

use OmoikaneWorks\SimpleSalesReports\Admin\AdminPage;
use OmoikaneWorks\SimpleSalesReports\Admin\AdminAssets;
use OmoikaneWorks\SimpleSalesReports\Configs\BrandingImageService;
use OmoikaneWorks\SimpleSalesReports\Configs\ConfigRepository;
use OmoikaneWorks\SimpleSalesReports\Configs\ConfigService;
use OmoikaneWorks\SimpleSalesReports\Reports\OrderRepository;
use OmoikaneWorks\SimpleSalesReports\Reports\ReportPeriodResolver;
use OmoikaneWorks\SimpleSalesReports\Reports\SalesReportBuilder;
use OmoikaneWorks\SimpleSalesReports\Reports\SalesReportRenderer;
use OmoikaneWorks\SimpleSalesReports\Rest\ConfigController;
use OmoikaneWorks\SimpleSalesReports\Rest\TemplateController;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateRepository;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSeeder;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateService;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin bootstrap.
 */
final class Plugin {

	/**
	 * Admin page slug for OSSR.
	 *
	 * @var string
	 */
	public const OSSR_ADMIN_PAGE_SLUG = 'omoikane-simple-sales-reports';

	/**
	 * Request parameter for ossr admin view.
	 *
	 * @var string
	 */
	public const OSSR_ADMIN_VIEW_PARAM = 'view';

	/**
	 * Admin
	 *
	 * @var string
	 */
	public const OSSR_ADMIN_TEMPLATES_VIEW_SLUG = 'templates';

	/**
	 * Settings view slug.
	 *
	 * @var string
	 */
	public const OSSR_ADMIN_SETTINGS_VIEW_SLUG = 'config';

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

		// Run upgrade tasks if needed.
		Activation::maybe_upgrade();
		TemplateSeeder::maybe_seed();

		$this->register_hooks();

		add_filter(
			'ossr_sales_report_admin_menu_items',
			array( $this, 'add_admin_menu_items' )
		);

		add_action(
			'ossr_render_sales_report_admin_view',
			array( $this, 'render_extended_admin_view' )
		);

		$admin_assets = new AdminAssets();

		add_action(
			'admin_enqueue_scripts',
			array( $admin_assets, 'enqueue' )
		);
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return  void
	 */
	public function register_hooks(): void {
		$period_resolver       = new ReportPeriodResolver();
		$template_repository   = new TemplateRepository();
		$template_service      = new TemplateService( $template_repository );
		$sales_report_renderer = new SalesReportRenderer( $template_repository );
		$order_repository      = new OrderRepository();
		$sales_report_builder  = new SalesReportBuilder( $order_repository );

		$admin_page = new AdminPage(
			$sales_report_renderer,
			$period_resolver,
			$sales_report_builder,
			$template_repository,
		);
		$admin_page->register_hooks();

		add_action(
			'rest_api_init',
			static function () use ( $template_service ): void {
				$controller = new TemplateController( $template_service );
				$controller->register_routes();

				$configs = new ConfigService( new ConfigRepository() );
				( new ConfigController( new BrandingImageService( $configs ) ) )->register_routes();
			}
		);
	}

	/**
	 * Add admin menu items.
	 *
	 * @param  array<string, array<string, string>> $items Inner navigation items.
	 * @return array<string, array<string, string>>
	 */
	public function add_admin_menu_items( array $items ): array {
		$views = array(
			self::OSSR_ADMIN_TEMPLATES_VIEW_SLUG => __( 'Templates', 'omoikane-simple-sales-reports' ),
			self::OSSR_ADMIN_SETTINGS_VIEW_SLUG  => __( 'Settings', 'omoikane-simple-sales-reports' ),
		);

		foreach ( $views as $view => $label ) {
			$items[ $view ] = array(
				'label' => $label,
				'url'   => add_query_arg(
					array(
						'page'                      => self::OSSR_ADMIN_PAGE_SLUG,
						self::OSSR_ADMIN_VIEW_PARAM => $view,
					),
					admin_url( 'admin.php' ),
				),
			);
		}

		return $items;
	}

	/**
	 * Render extended admin view.
	 *
	 * @param  string $current_view Current admin view.
	 * @return void
	 */
	public function render_extended_admin_view( string $current_view ): void {
		if ( self::OSSR_ADMIN_TEMPLATES_VIEW_SLUG === $current_view ) {
			echo '<div id="ossr-admin"></div>';
		} elseif ( self::OSSR_ADMIN_SETTINGS_VIEW_SLUG === $current_view ) {
			echo '<div id="ossr-settings"></div>';
		}
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
