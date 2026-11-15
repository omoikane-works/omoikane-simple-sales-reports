<?php
/**
 * Activation and database upgrade tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit;

use OmoikaneWorks\SimpleSalesReports\Activation;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateKeys;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database\FakeWpdb;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the activation lifecycle.
 */
#[BackupGlobals( true )]
final class ActivationTest extends TestCase {

	/**
	 * Database fixture.
	 *
	 * @var FakeWpdb
	 */
	private FakeWpdb $wpdb;

	/**
	 * Set up a fresh database and option state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->wpdb              = new FakeWpdb();
		$this->wpdb->record_call = ossr_test_record_call( ... );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['wpdb']              = $this->wpdb;
		$GLOBALS['ossr_test_calls']   = array();
		$GLOBALS['ossr_test_options'] = array();
	}

	/**
	 * Installed versions that must not be migrated.
	 *
	 * @return array<string, array{string}>
	 */
	public static function current_or_newer_versions(): array {
		return array(
			'current' => array( '1.2.0' ),
			'newer'   => array( '1.3.0' ),
		);
	}

	/**
	 * A current or newer installation only reads its schema version.
	 *
	 * @param string $version Installed schema version.
	 * @return void
	 */
	#[DataProvider( 'current_or_newer_versions' )]
	public function test_skips_upgrade_for_current_or_newer_schema( string $version ): void {
		$GLOBALS['ossr_test_options']['ossr_db_version'] = $version;

		Activation::maybe_upgrade();

		$this->assertSame( array( array( 'get_option', 'ossr_db_version', '1.0.0' ) ), $GLOBALS['ossr_test_calls'] );
		$this->assertSame( $version, $GLOBALS['ossr_test_options']['ossr_db_version'] );
	}

	/**
	 * Versions that require a migration.
	 *
	 * @return array<string, array{?string}>
	 */
	public static function legacy_versions(): array {
		return array(
			'previous' => array( '1.1.0' ),
			'older'    => array( '1.0.5' ),
			'missing'  => array( null ),
		);
	}

	/**
	 * Save the new schema version after creating and checking the table.
	 *
	 * @param string|null $version Installed version, or null for a missing option.
	 * @return void
	 */
	#[DataProvider( 'legacy_versions' )]
	public function test_advances_schema_version_after_confirming_tables_and_settings_column( ?string $version ): void {
		if ( null !== $version ) {
			$GLOBALS['ossr_test_options']['ossr_db_version'] = $version;
		}
		$this->mock_schema_checks();

		Activation::maybe_upgrade();

		$calls = $GLOBALS['ossr_test_calls'];
		$this->assertSame( array( 'get_option', 'dbDelta', 'dbDelta', 'get_var', 'get_var', 'update_option' ), array_column( $calls, 0 ) );
		$this->assertSame( array( 'get_option', 'ossr_db_version', '1.0.0' ), $calls[0] );
		$this->assertStringStartsWith( 'CREATE TABLE wp_ossr_configs (', $calls[1][1] );
		$this->assertStringStartsWith( 'CREATE TABLE wp_ossr_templates (', $calls[2][1] );
		$this->assertSame( "SHOW TABLES LIKE 'wp\\_ossr\\_configs'", $calls[3][1] );
		$this->assertSame( "SHOW COLUMNS FROM 'wp_ossr_templates' LIKE 'settings'", $calls[4][1] );
		$this->assertSame( array( 'update_option', 'ossr_db_version', '1.2.0', false ), $calls[5] );
		$this->assertSame( '1.2.0', $GLOBALS['ossr_test_options']['ossr_db_version'] );
	}

	/**
	 * Schema checks that must succeed before recording the new version.
	 *
	 * @return array<string, array{bool, bool}>
	 */
	public static function failed_schema_checks(): array {
		return array(
			'missing config table'    => array( false, true ),
			'missing settings column' => array( true, false ),
		);
	}

	/**
	 * Retry the migration if either schema check fails.
	 *
	 * @param bool $config_table    Whether the config table exists.
	 * @param bool $settings_column Whether the settings column exists.
	 * @return void
	 */
	#[DataProvider( 'failed_schema_checks' )]
	public function test_retries_upgrade_when_schema_could_not_be_confirmed( bool $config_table, bool $settings_column ): void {
		$GLOBALS['ossr_test_options']['ossr_db_version'] = '1.1.0';
		$this->mock_schema_checks( $config_table, $settings_column );

		Activation::maybe_upgrade();

		$this->assertSame( '1.1.0', $GLOBALS['ossr_test_options']['ossr_db_version'] );
		$this->assertNotContains( 'update_option', array_column( $GLOBALS['ossr_test_calls'], 0 ) );

		$GLOBALS['ossr_test_calls'] = array();
		$this->mock_schema_checks();

		Activation::maybe_upgrade();

		$this->assertSame( array( 'get_option', 'dbDelta', 'dbDelta', 'get_var', 'get_var', 'update_option' ), array_column( $GLOBALS['ossr_test_calls'], 0 ) );
		$this->assertSame( '1.2.0', $GLOBALS['ossr_test_options']['ossr_db_version'] );
	}

	/**
	 * Set the results of the schema and seed lookups.
	 *
	 * @param bool $config_table    Whether the config table exists.
	 * @param bool $settings_column Whether the settings column exists.
	 * @return void
	 */
	private function mock_schema_checks( bool $config_table = true, bool $settings_column = true ): void {
		$this->wpdb->record_call = function ( string $call, string $argument ) use ( $config_table, $settings_column ): void {
			if ( 'get_var' === $call ) {
				$this->wpdb->set_var(
					match ( $argument ) {
						"SHOW TABLES LIKE 'wp\\_ossr\\_configs'" => $config_table ? 'wp_ossr_configs' : null,
						"SHOW TABLES LIKE 'wp\\_ossr\\_templates'" => 'wp_ossr_templates',
						"SHOW COLUMNS FROM 'wp_ossr_templates' LIKE 'settings'" => $settings_column ? 'settings' : null,
						default => '0',
					}
				);
			}
			ossr_test_record_call( $call, $argument );
		};
	}

	/**
	 * Activation with supported dependencies migrates and seeds before saving the plugin version.
	 *
	 * @return void
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_activate_upgrades_schema_creates_template_table_and_seeds(): void {
		$this->define_activation_constants();
		define( 'USCES_VERSION', '2.11.10' );
		$this->mock_schema_checks();

		Activation::activate();

		$calls = $GLOBALS['ossr_test_calls'];
		$this->assertSame(
			array( 'get_option', 'dbDelta', 'dbDelta', 'get_var', 'get_var', 'update_option', 'get_var', 'get_var', 'insert', 'get_var', 'insert', 'update_option', 'update_option' ),
			array_column( $calls, 0 )
		);
		$this->assertStringStartsWith( 'CREATE TABLE wp_ossr_templates (', $calls[2][1] );
		$this->assertSame( array( 'insert', 'wp_ossr_templates' ), $calls[8] );
		$this->assertSame( array( TemplateKeys::DEFAULT_SALES_REPORT, TemplateKeys::DEFAULT_SALES_REPORT ), array_column( $this->wpdb->get_inserted_rows(), 'template_key' ) );
		$catalog_hash = $GLOBALS['ossr_test_options']['ossr_template_catalog_hash'];
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $catalog_hash );
		$this->assertSame( array( 'update_option', 'ossr_template_catalog_hash', $catalog_hash, true ), $calls[11] );
		$this->assertSame( array( 'update_option', 'ossr_version', OSSR_VERSION, false ), $calls[12] );
		$this->assertSame(
			array(
				'ossr_db_version'            => '1.2.0',
				'ossr_template_catalog_hash' => $catalog_hash,
				'ossr_version'               => OSSR_VERSION,
			),
			$GLOBALS['ossr_test_options']
		);
	}

	/**
	 * Activation deactivates the plugin and aborts if Welcart is missing or too old.
	 *
	 * @param string|null $version Welcart version, or null when Welcart is inactive.
	 * @param string      $message Expected error message.
	 * @return void
	 */
	#[DataProvider( 'unsupported_welcart_versions' )]
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_activate_rejects_unsupported_welcart( ?string $version, string $message ): void {
		$this->define_activation_constants();
		if ( null !== $version ) {
			define( 'USCES_VERSION', $version );
		}

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( $message );

		try {
			Activation::activate();
		} finally {
			$this->assertSame( array( array( 'deactivate_plugins', OSSR_PLUGIN_BASENAME ) ), $GLOBALS['ossr_test_calls'] );
		}
	}

	/**
	 * Welcart states that prevent activation.
	 *
	 * @return array<string, array{?string, string}>
	 */
	public static function unsupported_welcart_versions(): array {
		return array(
			'inactive' => array( null, 'requires Welcart to be activated.' ),
			'too old'  => array( '2.11.9', 'Unsupported Welcart version.' ),
		);
	}

	/**
	 * Activation checks the PHP requirement before attempting any database work.
	 *
	 * @return void
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_activate_rejects_unsupported_php(): void {
		$this->define_activation_constants( '99.0' );
		define( 'USCES_VERSION', '2.11.10' );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Unsupported PHP version.' );

		try {
			Activation::activate();
		} finally {
			$this->assertSame( array( array( 'deactivate_plugins', OSSR_PLUGIN_BASENAME ) ), $GLOBALS['ossr_test_calls'] );
		}
	}

	/**
	 * Define the plugin entry point constants needed by Activation.
	 *
	 * @param string $minimum_php_version PHP version required for activation.
	 * @return void
	 */
	private function define_activation_constants( string $minimum_php_version = '8.2' ): void {
		define( 'OSSR_MINIMUM_PHP_VERSION', $minimum_php_version );
		define( 'OSSR_MINIMUM_USCES_VERSION', '2.11.10' );
		define( 'OSSR_PLUGIN_BASENAME', 'omoikane-simple-sales-reports/omoikane-simple-sales-reports.php' );
		define( 'OSSR_VERSION', '9.9.9' );
	}
}
