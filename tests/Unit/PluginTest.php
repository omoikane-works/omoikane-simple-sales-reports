<?php
/**
 * Plugin bootstrap tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit;

use OmoikaneWorks\SimpleSalesReports\Admin\AdminAssets;
use OmoikaneWorks\SimpleSalesReports\Admin\AdminPage;
use OmoikaneWorks\SimpleSalesReports\Plugin;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database\FakeWpdb;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the plugin boot sequence.
 */
final class PluginTest extends TestCase {

	/**
	 * Boot migrates the database and wires the admin and REST hooks once.
	 *
	 * @return void
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_boot_upgrades_schema_and_registers_hooks_once(): void {
		$database              = new FakeWpdb();
		$database->record_call = ossr_test_record_call( ... );
		$database->set_var( 'wp_ossr_configs' );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['wpdb']              = $database;
		$GLOBALS['ossr_test_options'] = array( 'ossr_db_version' => '1.0.0' );
		$GLOBALS['ossr_test_calls']   = array();

		$plugin = Plugin::instance();
		$plugin->boot();

		$calls = $GLOBALS['ossr_test_calls'];
		$this->assertSame( array( 'get_option', 'dbDelta', 'get_var', 'update_option' ), array_column( array_slice( $calls, 0, 4 ), 0 ) );
		$this->assertSame( '1.1.0', $GLOBALS['ossr_test_options']['ossr_db_version'] );

		$hooks         = array_slice( $calls, 4 );
		$registrations = array();
		$rest_init     = null;
		foreach ( $hooks as $registration ) {
			list( $operation, $hook, $callback ) = $registration;
			if ( $callback instanceof \Closure ) {
				$rest_init       = $callback;
				$registrations[] = array( $operation, $hook, \Closure::class );
				continue;
			}
			$registrations[] = array( $operation, $hook, $callback[0]::class, $callback[1] );
		}

		$this->assertEqualsCanonicalizing(
			array(
				array( 'add_action', 'admin_menu', AdminPage::class, 'register_admin_menu' ),
				array( 'add_action', 'admin_enqueue_scripts', AdminPage::class, 'enqueue_admin_assets' ),
				array( 'add_action', 'admin_enqueue_scripts', AdminAssets::class, 'enqueue' ),
				array( 'add_action', 'ossr_render_sales_report_admin_view', Plugin::class, 'render_extended_admin_view' ),
				array( 'add_filter', 'ossr_sales_report_admin_menu_items', Plugin::class, 'add_admin_menu_items' ),
				array( 'add_action', 'rest_api_init', \Closure::class ),
			),
			$registrations
		);

		Plugin::instance()->boot();
		$this->assertSame( $calls, $GLOBALS['ossr_test_calls'] );

		$this->assertInstanceOf( \Closure::class, $rest_init );
		$rest_init();

		$registrations = array_slice( $GLOBALS['ossr_test_calls'], count( $calls ) );
		$this->assertSame( array_fill( 0, 4, 'register_rest_route' ), array_column( $registrations, 0 ) );
		$this->assertSame( array_fill( 0, 4, 'ossr/v1' ), array_column( $registrations, 1 ) );
		$this->assertEqualsCanonicalizing(
			array( '/templates', '/templates/(?P<id>\d+)', '/templates/(?P<id>\d+)/duplicate', '/config/branding/(?P<slot>logo|stamp)' ),
			array_column( $registrations, 2 )
		);

		$branding = $registrations[3][3];
		$this->assertSame( array( 'GET', 'PUT', 'DELETE' ), array_column( $branding, 'methods' ) );
		foreach ( $branding as $endpoint ) {
			$this->assertSame( 'check_permissions', $endpoint['permission_callback'][1] );
		}
	}
}
