<?php
/**
 * Tests for template placement settings.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates;

use InvalidArgumentException;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for template settings validation and normalization.
 */
final class TemplateSettingsTest extends TestCase {

	/**
	 * Fill defaults and canonicalize numeric values and property order.
	 *
	 * @return void
	 */
	public function test_normalizes_placements_and_applies_defaults(): void {
		$this->assertSame( TemplateSettings::defaults(), TemplateSettings::normalize( json_decode( '{}' ) ) );

		$settings = TemplateSettings::normalize(
			json_decode( '{"branding":{"stamp":{"width_mm":0.1},"logo":{"width_mm":1000.0,"y_mm":1000,"x_mm":-1000.0,"enabled":true}}}' )
		);
		$this->assertSame(
			array(
				'branding' => array(
					'logo'  => array(
						'enabled'  => true,
						'x_mm'     => -1000,
						'y_mm'     => 1000,
						'width_mm' => 1000,
					),
					'stamp' => array(
						'enabled'  => false,
						'x_mm'     => 0,
						'y_mm'     => 0,
						'width_mm' => 0.1,
					),
				),
			),
			$settings
		);
	}

	/**
	 * Invalid settings shapes and values.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function invalid_settings(): array {
		return array(
			'root list'       => array( '[]', 'settings' ),
			'root null'       => array( 'null', 'settings' ),
			'unknown root'    => array( '{"image":"data"}', 'settings' ),
			'branding list'   => array( '{"branding":[]}', 'settings.branding' ),
			'branding null'   => array( '{"branding":null}', 'settings.branding' ),
			'unknown slot'    => array( '{"branding":{"seal":{}}}', 'settings.branding' ),
			'slot list'       => array( '{"branding":{"stamp":[]}}', 'settings.branding.stamp' ),
			'slot null'       => array( '{"branding":{"stamp":null}}', 'settings.branding.stamp' ),
			'unknown field'   => array( '{"branding":{"logo":{"data":"image"}}}', 'settings.branding.logo' ),
			'coerced bool'    => array( '{"branding":{"logo":{"enabled":1}}}', 'settings.branding.logo.enabled' ),
			'null field'      => array( '{"branding":{"logo":{"enabled":null}}}', 'settings.branding.logo.enabled' ),
			'numeric string'  => array( '{"branding":{"logo":{"x_mm":"0"}}}', 'settings.branding.logo.x_mm' ),
			'boolean width'   => array( '{"branding":{"logo":{"width_mm":true}}}', 'settings.branding.logo.width_mm' ),
			'nonfinite'       => array( '{"branding":{"logo":{"y_mm":1e400}}}', 'settings.branding.logo.y_mm' ),
			'negative bound'  => array( '{"branding":{"logo":{"x_mm":-1000.01}}}', 'settings.branding.logo.x_mm' ),
			'positive bound'  => array( '{"branding":{"logo":{"y_mm":1000.01}}}', 'settings.branding.logo.y_mm' ),
			'disabled width'  => array( '{"branding":{"stamp":{"enabled":false,"width_mm":0.099}}}', 'settings.branding.stamp.width_mm' ),
			'width too large' => array( '{"branding":{"logo":{"width_mm":1000.01}}}', 'settings.branding.logo.width_mm' ),
		);
	}

	/**
	 * Reject invalid input with the relevant settings path.
	 *
	 * @param string $json Invalid input.
	 * @param string $field Expected field path.
	 * @return void
	 */
	#[DataProvider( 'invalid_settings' )]
	public function test_rejects_invalid_settings( string $json, string $field ): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( $field . ' ' );

		TemplateSettings::normalize( json_decode( $json, false, 512, JSON_THROW_ON_ERROR ) );
	}
}
