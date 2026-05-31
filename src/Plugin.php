<?php
/**
 * Main plugin class.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales;

use OmoikaneWorks\WelcartSimpleReportSales\Admin\AdminPage;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\OrderRepository;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\ReportPeriodResolver;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\SalesReportBuilder;
use OmoikaneWorks\WelcartSimpleReportSales\Reports\SalesReportRenderer;
use OmoikaneWorks\WelcartSimpleReportSales\Templates\TemplateRepository;

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

		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return  void
	 */
	public function register_hooks(): void {
		$period_resolver       = new ReportPeriodResolver();
		$template_repository   = new TemplateRepository();
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
