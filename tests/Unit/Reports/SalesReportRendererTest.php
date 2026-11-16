<?php
/**
 * Sales report renderer tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Reports;

use OmoikaneWorks\SimpleSalesReports\Configs\BrandingImageService;
use OmoikaneWorks\SimpleSalesReports\Configs\ConfigRepositoryInterface;
use OmoikaneWorks\SimpleSalesReports\Configs\ConfigService;
use OmoikaneWorks\SimpleSalesReports\Reports\SalesReportRenderer;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSettings;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Configs\FakeConfigRepository;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates\FakeTemplateRepository;
use OmoikaneWorks\SimpleSalesReports\Tests\Support\CreatesTemplateRows;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SalesReportRenderer.
 */
final class SalesReportRendererTest extends TestCase {

	use CreatesTemplateRows;

	private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAMAAAACCAIAAAASFvFNAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAC0lEQVQImWNgwAQAABQAAWX1h1kAAAAASUVORK5CYII=';

	/**
	 * Test render default sales report renders template content.
	 *
	 * @return  void
	 */
	public function test_render_default_sales_report_renders_template_content(): void {
		$template    = $this->create_template_row(
			array(
				'id'      => 1,
				'name'    => 'Default Sales Report',
				'content' => '<h1>{{report.title}}</h1><p>{{store.name}}</p><p>{{totals.payment_total_label}}円</p>',
			)
		);
		$template_id = (int) $template['id'];

		$renderer = $this->create_renderer(
			new FakeTemplateRepository(
				array(
					$template_id => $template,
				)
			)
		);

		$result = $renderer->render_default_sales_report(
			array(
				'report' => array(
					'title' => '売上報告書',
				),
				'store'  => array(
					'name' => 'Example Store',
				),
				'totals' => array(
					'payment_total_label' => '10,200',
				),
			)
		);

		$this->assertSame(
			'<h1>売上報告書</h1><p>Example Store</p><p>10,200円</p>',
			$result
		);
	}

	/**
	 * Test render default sales report escapes template variables.
	 *
	 * @return void
	 */
	public function test_render_default_sales_report_escapes_template_variables(): void {
		$template    = $this->create_template_row(
			array(
				'id'      => 1,
				'name'    => 'Default Sales Report',
				'content' => '<p>{{store.name}}</p>',
			)
		);
		$template_id = (int) $template['id'];

		$renderer = $this->create_renderer(
			new FakeTemplateRepository(
				array(
					$template_id => $template,
				)
			)
		);

		$result = $renderer->render_default_sales_report(
			array(
				'store' => array(
					'name' => '<Example Store>',
				),
			)
		);
		$this->assertSame( '<p>&lt;Example Store&gt;</p>', $result );
	}

	/**
	 * Test render default sales report returns error message when template is missing.
	 *
	 * @return void
	 */
	public function test_render_default_sales_report_returns_error_message_when_template_is_missing(): void {
		$renderer = $this->create_renderer(
			new FakeTemplateRepository()
		);

		$result = $renderer->render_default_sales_report( array() );

		$this->assertSame(
			'<div class="notice notice-error"><p>The sales report template was not found. Please reactivate the plugin.</p></div>',
			$result
		);
	}

	/**
	 * Test render default sales report returns error message when template content is empty.
	 *
	 * @return void
	 */
	public function test_render_default_sales_report_returns_error_message_when_template_content_is_empty(): void {
		$template    = $this->create_template_row(
			array(
				'id'      => 1,
				'name'    => 'Empty Sales Report',
				'content' => '',
			)
		);
		$template_id = (int) $template['id'];

		$renderer = $this->create_renderer(
			new FakeTemplateRepository(
				array(
					$template_id => $template,
				)
			)
		);

		$result = $renderer->render_default_sales_report( array() );

		$this->assertSame(
			'<div class="notice notice-error"><p>The sales report template was not found. Please reactivate the plugin.</p></div>',
			$result
		);
	}

	/**
	 * Each slot can fail independently.
	 *
	 * @return array<string, array{bool, bool, array<string, string>, list<string>}>
	 */
	public static function branding_states(): array {
		$png  = '{"mime":"image/png","data":"' . self::PNG . '"}';
		$jpeg = '{"mime":"image/jpeg","data":"' . self::PNG . '"}';
		$both = array_fill_keys( array( 'branding.logo', 'branding.stamp' ), $png );

		return array(
			'both valid'          => array( true, true, $both, array( 'logo', 'stamp' ) ),
			'logo disabled'       => array( false, true, $both, array( 'stamp' ) ),
			'stamp disabled'      => array( true, false, $both, array( 'logo' ) ),
			'logo missing'        => array( true, true, array( 'branding.stamp' => $png ), array( 'stamp' ) ),
			'stamp MIME mismatch' => array(
				true,
				true,
				array(
					'branding.logo'  => $png,
					'branding.stamp' => $jpeg,
				),
				array( 'logo' ),
			),
			'logo corrupt bytes'  => array(
				true,
				true,
				array(
					'branding.logo'  => '{"mime":"image/png","data":"YmFk"}',
					'branding.stamp' => $png,
				),
				array( 'stamp' ),
			),
			'corrupt and missing' => array( true, true, array( 'branding.logo' => '{"mime":"image/png","data":"invalid!"}' ), array() ),
			'neither enabled'     => array( false, false, $both, array() ),
		);
	}

	/**
	 * Render only enabled, valid images using the selected template's coordinates.
	 *
	 * @param bool                  $logo    Whether the logo is enabled.
	 * @param bool                  $stamp   Whether the stamp is enabled.
	 * @param array<string, string> $configs Stored image records.
	 * @param string[]              $visible Expected slots.
	 */
	#[DataProvider( 'branding_states' )]
	public function test_renders_only_valid_enabled_images( bool $logo, bool $stamp, array $configs, array $visible ): void {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			$this->markTestSkipped( 'GD is required to render validated images.' );
		}

		$settings = TemplateSettings::defaults();

		$settings['branding']['logo']['enabled']  = $logo;
		$settings['branding']['logo']['x_mm']     = -5.25;
		$settings['branding']['logo']['y_mm']     = 1.5;
		$settings['branding']['stamp']['enabled'] = $stamp;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Load the actual bundled template.
		$content  = file_get_contents( OSSR_PLUGIN_DIR . 'templates/default-sales-report-1.1.0.mustache' );
		$template = $this->create_template_row(
			array(
				'content'  => $content,
				'settings' => $settings,
			)
		);

		$config_repository = $this->createMock( ConfigRepositoryInterface::class );
		$config_repository->expects( $this->exactly( (int) $logo + (int) $stamp ) )
			->method( 'get' )
			->willReturnCallback( static fn ( string $key ): ?string => $configs[ $key ] ?? null );
		$config_repository->expects( $this->never() )->method( 'set' );
		$config_repository->expects( $this->never() )->method( 'delete' );

		$renderer = new SalesReportRenderer(
			new FakeTemplateRepository( array( $template ) ),
			new BrandingImageService( new ConfigService( $config_repository ) )
		);
		$html     = $renderer->render_sales_report( array(), 1 );
		$this->assertStringContainsString( '注文明細', $html );
		$this->assertSame( count( $visible ), substr_count( $html, 'src="data:image/png;base64,' . self::PNG . '"' ) );

		if ( in_array( 'logo', $visible, true ) ) {
			$this->assertStringContainsString( 'alt="ロゴ"', $html );
			$this->assertStringContainsString( 'left: -5.25mm; top: 1.5mm; width: 30mm;', $html );
		} else {
			$this->assertStringNotContainsString( 'alt="ロゴ"', $html );
		}
		$this->assertSame( in_array( 'stamp', $visible, true ), str_contains( $html, 'alt="社印"' ) );
		$this->assertSame( array() !== $visible, str_contains( $html, '<div class="ossr-report__branding">' ) );
		$this->assertStringNotContainsString( 'overflow: hidden', $html );
	}

	/**
	 * A missing or empty selected template uses the default's body and placements together.
	 */
	public function test_fallback_uses_default_template_settings(): void {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			$this->markTestSkipped( 'GD is required to render validated images.' );
		}

		$settings = TemplateSettings::defaults();

		$settings['branding']['stamp']['enabled'] = true;
		$settings['branding']['stamp']['x_mm']    = 38;

		$templates = new FakeTemplateRepository(
			array(
				$this->create_template_row(
					array(
						'content'  => 'Default {{#branding.stamp}}{{x_mm}}mm{{/branding.stamp}}',
						'settings' => $settings,
					)
				),
				array(
					'id'      => 2,
					'content' => 'Old {{#branding.stamp}}stamp{{/branding.stamp}}',
				),
				array(
					'id'      => 3,
					'content' => '',
				),
			)
		);
		$config    = '{"mime":"image/png","data":"' . self::PNG . '"}';
		$renderer  = $this->create_renderer( $templates, array( 'branding.stamp' => $config ) );

		$this->assertSame( 'Old ', $renderer->render_sales_report( array(), 2 ) );
		$this->assertSame( 'Default 38mm', $renderer->render_sales_report( array(), 3 ) );
		$this->assertSame( 'Default 38mm', $renderer->render_sales_report( array(), 99 ) );
	}

	/**
	 * Create a renderer using the supplied fixtures.
	 *
	 * @param FakeTemplateRepository $templates Template rows.
	 * @param array<string, string>  $configs   Stored image records.
	 * @return SalesReportRenderer
	 */
	private function create_renderer( FakeTemplateRepository $templates, array $configs = array() ): SalesReportRenderer {
		return new SalesReportRenderer( $templates, new BrandingImageService( new ConfigService( new FakeConfigRepository( $configs ) ) ) );
	}
}
