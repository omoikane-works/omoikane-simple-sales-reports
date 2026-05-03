<?php
/**
 * Main plugin class.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales;

use OmoikaneWorks\WelcartSimpleReportSales\Admin\AdminPage;

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
		$admin_page = new AdminPage();
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
