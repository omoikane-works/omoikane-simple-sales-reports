<?php
/**
 * Template placement settings.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Templates;

use InvalidArgumentException;
use RuntimeException;
use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Validates and normalizes the JSON settings for a template.
 *
 * @phpstan-type Placement array{enabled: bool, x_mm: int|float, y_mm: int|float, width_mm: int|float}
 * @phpstan-type Settings array{branding: array{logo: Placement, stamp: Placement}}
 */
final class TemplateSettings {

	/**
	 * Default placements, relative to the template anchor and expressed in mm.
	 *
	 * @var Settings
	 */
	private const DEFAULTS = array(
		'branding' => array(
			'logo'  => array(
				'enabled'  => false,
				'x_mm'     => 0,
				'y_mm'     => 0,
				'width_mm' => 30,
			),
			'stamp' => array(
				'enabled'  => false,
				'x_mm'     => 0,
				'y_mm'     => 0,
				'width_mm' => 18,
			),
		),
	);

	/**
	 * Return the placements for an unconfigured template.
	 *
	 * @return Settings
	 */
	public static function defaults(): array {
		return self::DEFAULTS;
	}

	/**
	 * Encode a normalized settings object for the settings column.
	 *
	 * @param array<string, mixed> $settings Normalized settings.
	 * @phpstan-param Settings $settings
	 * @return string|null SQL NULL represents the default settings.
	 * @throws RuntimeException If encoding failed.
	 */
	public static function encode( array $settings ): ?string {
		if ( self::DEFAULTS === $settings ) {
			return null;
		}

		$json = wp_json_encode( $settings );
		if ( false === $json ) {
			throw new RuntimeException( 'Failed to encode template settings.' );
		}

		return $json;
	}

	/**
	 * Validate a decoded JSON object and fill missing fields with defaults.
	 *
	 * @param mixed $settings Decoded JSON settings.
	 * @return Settings
	 * @throws InvalidArgumentException If a key or value is invalid.
	 */
	public static function normalize( mixed $settings ): array {
		$root = self::object_properties( $settings, 'settings', array( 'branding' ) );
		if ( ! array_key_exists( 'branding', $root ) ) {
			return self::defaults();
		}

		$branding = self::object_properties( $root['branding'], 'settings.branding', array( 'logo', 'stamp' ) );

		return array(
			'branding' => array(
				'logo'  => self::normalize_placement( $branding, 'logo' ),
				'stamp' => self::normalize_placement( $branding, 'stamp' ),
			),
		);
	}

	/**
	 * Extract only properties defined by the contract.
	 *
	 * @param mixed    $value        Decoded JSON value.
	 * @param string   $path         Field path for validation errors.
	 * @param string[] $allowed_keys Allowed property names.
	 * @return array<array-key, mixed>
	 * @throws InvalidArgumentException If the value is not an object or has an unknown key.
	 */
	private static function object_properties( mixed $value, string $path, array $allowed_keys ): array {
		if ( ! $value instanceof stdClass ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The path contains only fixed field names.
			throw new InvalidArgumentException( sprintf( '%s must be an object.', $path ) );
		}

		$properties = get_object_vars( $value );
		if ( array_diff_key( $properties, array_flip( $allowed_keys ) ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The path contains only fixed field names.
			throw new InvalidArgumentException( sprintf( '%s contains an unknown field.', $path ) );
		}

		return $properties;
	}

	/**
	 * Normalize one branding slot in a fixed field order.
	 *
	 * @param array<array-key, mixed> $branding Branding settings.
	 * @param 'logo'|'stamp'          $slot     Slot name.
	 * @return Placement
	 * @throws InvalidArgumentException If the enabled flag is invalid.
	 */
	private static function normalize_placement( array $branding, string $slot ): array {
		$defaults = self::DEFAULTS['branding'][ $slot ];
		if ( ! array_key_exists( $slot, $branding ) ) {
			return $defaults;
		}

		$path   = 'settings.branding.' . $slot;
		$values = self::object_properties( $branding[ $slot ], $path, array_keys( $defaults ) ) + $defaults;
		if ( ! is_bool( $values['enabled'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The path contains only fixed field names.
			throw new InvalidArgumentException( sprintf( '%s.enabled must be a boolean.', $path ) );
		}

		return array(
			'enabled'  => $values['enabled'],
			'x_mm'     => self::normalize_mm( $values['x_mm'], $path . '.x_mm', -1000 ),
			'y_mm'     => self::normalize_mm( $values['y_mm'], $path . '.y_mm', -1000 ),
			'width_mm' => self::normalize_mm( $values['width_mm'], $path . '.width_mm', 0.1 ),
		);
	}

	/**
	 * Validate a JSON number and canonicalize integral values.
	 *
	 * @param mixed     $value Decoded JSON value.
	 * @param string    $path  Field path for validation errors.
	 * @param int|float $min   Inclusive lower bound in mm.
	 * @return int|float
	 * @throws InvalidArgumentException If the value is not a finite number in range.
	 */
	private static function normalize_mm( mixed $value, string $path, int|float $min ): int|float {
		if ( ( ! is_int( $value ) && ! is_float( $value ) ) || ! is_finite( $value ) || $min > $value || 1000 < $value ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The path and limit come from the settings definition.
			throw new InvalidArgumentException( sprintf( '%s must be a number from %s to 1000 mm.', $path, $min ) );
		}

		return is_float( $value ) && floor( $value ) === $value ? (int) $value : $value;
	}
}
