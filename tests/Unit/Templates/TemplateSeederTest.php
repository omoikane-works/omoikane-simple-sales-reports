<?php
/**
 * Template seeder tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates;

use OmoikaneWorks\SimpleSalesReports\Templates\TemplateKeys;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSeeder;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database\FakeWpdb;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateSeeder.
 */
#[BackupGlobals( true )]
final class TemplateSeederTest extends TestCase {

	/**
	 * Catalog fingerprint from the v1.1.0 upgrade with the missing revision.
	 */
	private const CURRENT_CATALOG_HASH = 'dadb712675316700075d28abdfcd0e201fc5bb86688fb4c64e2af60046589820';

	/**
	 * Bulk lookup of the bundled key and version pairs.
	 */
	private const CATALOG_QUERY = "SELECT COUNT(DISTINCT template_key, version) FROM 'wp_ossr_templates' WHERE (template_key, version) IN (('default_sales_report', '1.0.0'), ('default_sales_report', '1.1.0'))";

	/**
	 * Fake wpdb.
	 *
	 * @var FakeWpdb
	 */
	private FakeWpdb $wpdb;

	/**
	 * Set up test.
	 *
	 * @return  void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->wpdb                   = new FakeWpdb();
		$GLOBALS['ossr_test_options'] = array();
		$GLOBALS['ossr_test_calls']   = array();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['wpdb'] = $this->wpdb;
	}

	/**
	 * Seed inserts the default template when its version is missing.
	 *
	 * @return  void
	 */
	public function test_seed_inserts_missing_default_template(): void {
		$this->wpdb->set_var( '0' );

		TemplateSeeder::seed();
		$inserted_rows = $this->wpdb->get_inserted_rows();
		$this->assertCount( 2, $inserted_rows );
		$row = $inserted_rows[0];

		$this->assertSame( TemplateKeys::DEFAULT_SALES_REPORT, $row['template_key'] );
		$this->assertSame( '標準 売上報告書', $row['name'] );
		$this->assertSame( TemplateTypes::SALES_REPORT, $row['type'] );
		$this->assertSame( '1.0.0', $row['version'] );
		$this->assertArrayNotHasKey( 'settings', $row );
		$this->assertNotSame( '', $row['content'] );
		$this->assertSame( hash( 'sha256', (string) $row['content'] ), $row['content_hash'] );
		$this->assertSame( 1, $row['is_system'] );
		$this->assertSame( 1, $row['is_default'] );
		$this->assertSame( 1, $row['is_active'] );
		$this->assertSame( '2026-05-24 12:34:56', $row['created_at'] );
		$this->assertSame( '2026-05-24 12:34:56', $row['updated_at'] );

		$new = $inserted_rows[1];
		$this->assertSame( TemplateKeys::DEFAULT_SALES_REPORT, $new['template_key'] );
		$this->assertSame( '1.1.0', $new['version'] );
		$this->assertStringContainsString( '{{#branding}}', $new['content'] );
		$this->assertSame( hash( 'sha256', $new['content'] ), $new['content_hash'] );
		$this->assertSame(
			'{"branding":{"logo":{"enabled":true,"x_mm":0,"y_mm":0,"width_mm":30},"stamp":{"enabled":true,"x_mm":38,"y_mm":0,"width_mm":18}}}',
			$new['settings']
		);
	}

	/**
	 * Existing template versions and whether they already satisfy the catalog.
	 *
	 * @return array<string, array{string, bool, bool}>
	 */
	public static function existing_template_versions(): array {
		return array(
			'same version and same body'      => array( '1.0.0', true, true ),
			'same version and different body' => array( '1.0.0', false, true ),
			'different version and same body' => array( '0.9.0', true, false ),
		);
	}

	/**
	 * Seed keeps stored versions intact and allows separate versions with the same body.
	 *
	 * @param string $version        Stored version.
	 * @param bool   $same_body      Whether the stored body matches the bundled file.
	 * @param bool   $already_seeded Whether the stored row satisfies the catalog.
	 * @return  void
	 */
	#[DataProvider( 'existing_template_versions' )]
	public function test_seed_matches_only_key_and_version( string $version, bool $same_body, bool $already_seeded ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Load the bundled template fixture.
		$bundled_body = file_get_contents( OSSR_PLUGIN_DIR . 'templates/default-sales-report-1.0.0.mustache' );
		$this->assertIsString( $bundled_body );
		$stored_body = $same_body ? $bundled_body : 'Previously stored template body';
		$stored_row  = array(
			'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
			'version'      => $version,
			'content'      => $stored_body,
			'content_hash' => hash( 'sha256', $stored_body ),
			'settings'     => '{"branding":{"logo":{"enabled":true}}}',
		);
		$this->wpdb->insert( 'wp_ossr_templates', $stored_row );
		$this->wpdb->record_call = function ( string $call, string $argument ) use ( $already_seeded ): void {
			ossr_test_record_call( $call, $argument );
			if ( 'get_var' === $call ) {
				$this->wpdb->set_var( $already_seeded && str_contains( $argument, "version = '1.0.0'" ) ? '1' : '0' );
			}
		};

		TemplateSeeder::seed();

		$this->assertSame(
			array( 'get_var', "SELECT EXISTS(SELECT 1 FROM 'wp_ossr_templates' WHERE template_key = 'default_sales_report' AND version = '1.0.0')" ),
			$GLOBALS['ossr_test_calls'][0]
		);
		$rows = $this->wpdb->get_inserted_rows();
		$this->assertCount( $already_seeded ? 2 : 3, $rows );
		$this->assertSame( '1.1.0', $rows[ count( $rows ) - 1 ]['version'] );
		$this->assertSame( $stored_row, $rows[0] );
		$this->assertSame( array(), $this->wpdb->get_updated_rows() );
		$this->assertArrayHasKey( 'ossr_template_catalog_hash', $GLOBALS['ossr_test_options'] );

		if ( ! $already_seeded ) {
			$this->assertSame( $stored_row['template_key'], $rows[1]['template_key'] );
			$this->assertSame( '1.0.0', $rows[1]['version'] );
			$this->assertSame( $stored_row['content_hash'], $rows[1]['content_hash'] );
		}
	}

	/**
	 * A failed lookup leaves registration pending and avoids a blind insert.
	 *
	 * @return void
	 */
	public function test_seed_leaves_catalog_pending_when_lookup_fails(): void {
		TemplateSeeder::seed();

		$this->assertSame( array(), $this->wpdb->get_inserted_rows() );
		$this->assertArrayNotHasKey( 'ossr_template_catalog_hash', $GLOBALS['ossr_test_options'] );
	}

	/**
	 * Catalog options present when a bundled revision is missing.
	 *
	 * @return array<string, array{string|null}>
	 */
	public static function initial_catalog_hashes(): array {
		return array(
			'matching hash' => array( self::CURRENT_CATALOG_HASH ),
			'old hash'      => array( 'previous-catalog' ),
			'no hash'       => array( null ),
		);
	}

	/**
	 * Resume a failed repair and skip inserts once all bundled revisions exist.
	 *
	 * @param string|null $previous_hash Catalog fingerprint before boot.
	 * @return void
	 */
	#[DataProvider( 'initial_catalog_hashes' )]
	public function test_maybe_seed_retries_failed_insert_and_preserves_existing_template( ?string $previous_hash ): void {
		$options = array(
			'ossr_sales_report_template_id' => 7,
		);
		if ( null !== $previous_hash ) {
			$options['ossr_template_catalog_hash'] = $previous_hash;
		}
		$GLOBALS['ossr_test_options'] = $options;

		$legacy = array(
			'id'           => 7,
			'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
			'version'      => '1.0.0',
			'content'      => 'Existing template body',
			'settings'     => '{"branding":{"logo":{"enabled":true}}}',
			'is_active'    => 1,
		);
		$this->wpdb->insert( 'wp_ossr_templates', $legacy );
		$this->wpdb->record_call = function ( string $call, string $argument ): void {
			ossr_test_record_call( $call, $argument );
			if ( 'get_var' === $call ) {
				$this->wpdb->set_var(
					match ( true ) {
						self::CATALOG_QUERY === $argument              => (string) count( $this->wpdb->get_inserted_rows() ),
						str_starts_with( $argument, 'SHOW TABLES' )    => 'wp_ossr_templates',
						str_contains( $argument, "version = '1.0.0'" ) => '1',
						str_contains( $argument, "version = '1.1.0'" ) => count( $this->wpdb->get_inserted_rows() ) > 1 ? '1' : '0',
						default                                       => null,
					}
				);
			}
		};
		$this->wpdb->set_insert_result( false );

		TemplateSeeder::maybe_seed();

		$failed_calls = array( 'get_option', 'get_var', 'get_var', 'get_var', 'insert' );
		if ( self::CURRENT_CATALOG_HASH === $previous_hash ) {
			array_splice( $failed_calls, 1, 0, 'get_var' );
			$this->assertSame( array( 'get_var', self::CATALOG_QUERY ), $GLOBALS['ossr_test_calls'][1] );
		}
		$this->assertSame( $failed_calls, array_column( $GLOBALS['ossr_test_calls'], 0 ) );
		$this->assertSame( $options, $GLOBALS['ossr_test_options'] );
		$this->assertSame( array( $legacy ), $this->wpdb->get_inserted_rows() );

		$this->wpdb->set_insert_result( 1 );
		TemplateSeeder::maybe_seed();

		$this->assertSame( self::CURRENT_CATALOG_HASH, $GLOBALS['ossr_test_options']['ossr_template_catalog_hash'] );
		$this->assertSame( 7, $GLOBALS['ossr_test_options']['ossr_sales_report_template_id'] );
		$rows = $this->wpdb->get_inserted_rows();
		$this->assertSame( array( '1.0.0', '1.1.0' ), array_column( $rows, 'version' ) );
		$this->assertSame( $legacy, $rows[0] );
		$this->assertSame( array(), $this->wpdb->get_updated_rows() );

		$GLOBALS['ossr_test_calls'] = array();
		TemplateSeeder::maybe_seed();

		$this->assertSame(
			array(
				array( 'get_option', 'ossr_template_catalog_hash', false ),
				array( 'get_var', self::CATALOG_QUERY ),
			),
			$GLOBALS['ossr_test_calls']
		);
		$this->assertSame( $rows, $this->wpdb->get_inserted_rows() );
	}

	/**
	 * A failed lookup must not be treated as a complete catalog.
	 *
	 * @return void
	 */
	public function test_maybe_seed_falls_through_when_catalog_lookup_fails(): void {
		$GLOBALS['ossr_test_options']['ossr_template_catalog_hash'] = self::CURRENT_CATALOG_HASH;
		$this->wpdb->record_call                                    = function ( string $call, string $argument ): void {
			ossr_test_record_call( $call, $argument );
			if ( 'get_var' === $call ) {
				$this->wpdb->set_var( str_starts_with( $argument, 'SHOW TABLES' ) ? 'wp_ossr_templates' : null );
			}
		};

		TemplateSeeder::maybe_seed();

		$this->assertSame( array( 'get_var', self::CATALOG_QUERY ), $GLOBALS['ossr_test_calls'][1] );
		$this->assertSame( array( 'get_option', 'get_var', 'get_var', 'get_var', 'get_var' ), array_column( $GLOBALS['ossr_test_calls'], 0 ) );
		$this->assertFalse( $this->wpdb->suppress_errors( false ) );
		$this->assertSame( array(), $this->wpdb->get_inserted_rows() );
	}

	/**
	 * Boot can recover from a failed table creation during activation.
	 *
	 * @return void
	 */
	public function test_maybe_seed_recreates_missing_template_table(): void {
		$GLOBALS['ossr_test_options']['ossr_template_catalog_hash'] = self::CURRENT_CATALOG_HASH;
		$this->wpdb->record_call                                    = function ( string $call, string $argument ): void {
			ossr_test_record_call( $call, $argument );
			if ( 'get_var' === $call && str_starts_with( $argument, 'SELECT EXISTS' ) ) {
				$this->wpdb->set_var( '0' );
			}
		};

		TemplateSeeder::maybe_seed();

		$this->assertSame( array( 'get_option', 'get_var', 'get_var', 'dbDelta', 'get_var', 'insert', 'get_var', 'insert', 'update_option' ), array_column( $GLOBALS['ossr_test_calls'], 0 ) );
		$this->assertSame( array( '1.0.0', '1.1.0' ), array_column( $this->wpdb->get_inserted_rows(), 'version' ) );
	}
}
