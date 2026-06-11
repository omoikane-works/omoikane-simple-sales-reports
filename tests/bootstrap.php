<?php
/**
 * PHPUnit bootstrap.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/tests/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( __DIR__ ) . '/tests/wp-content' );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAYA' );
}

if ( ! defined( 'OSSR_PLUGIN_DIR' ) ) {
	define( 'OSSR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'sanitize_key' ) ) {

	/**
	 * Sanitize key.
	 *
	 * @param   string $key    Key.
	 * @return  string
	 */
	function sanitize_key( string $key ): string {
		return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key ) ?? '' );
	}

}

if ( ! function_exists( 'wp_unslash' ) ) {

	/**
	 * Remove slashes.
	 *
	 * @param   mixed $value  Value.
	 * @return  mixed
	 */
	function wp_unslash( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}

}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Strip all HTML tags.
	 *
	 * @param   string $text   Text.
	 * @return  string
	 */
	function wp_strip_all_tags( string $text ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Sanitize text field.
	 *
	 * @param   string $value  Value.
	 * @return  string
	 */
	function sanitize_text_field( string $value ): string {
		return trim( wp_unslash( wp_strip_all_tags( $value ) ) );
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * Get WordPress timezone.
	 *
	 * @return  DateTimeZone
	 */
	function wp_timezone(): DateTimeZone {
		return new DateTimeZone( 'Asia/Tokyo' );
	}
}

if ( ! function_exists( 'current_datetime' ) ) {
	/**
	 * Get current datetime.
	 *
	 * @return  DateTimeImmutable
	 */
	function current_datetime(): DateTimeImmutable {
		$current_datetime = $GLOBALS['ossr_test_current_datetime'] ?? '2026-05-18 12:00:00';

		return new DateTimeImmutable( (string) $current_datetime, wp_timezone() );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Get current time.
	 *
	 * @param   string $type   Time type.
	 * @return  string
	 */
	function current_time( string $type ): string {
		unset( $type );

		return '2026-05-24 12:34:56';
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * Format date.
	 *
	 * @param   string $format     Date format.
	 * @param   int    $timestamp  Timestamp.
	 * @return  string
	 */
	function wp_date( string $format, int $timestamp ): string {
		return ( new DateTimeImmutable( '@' . $timestamp ) )
			->setTimezone( wp_timezone() )
			->format( $format );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translate text.
	 *
	 * @param   string $text   Text.
	 * @param   string $domain Domain.
	 * @return  string
	 */
	function __( string $text, string $domain ): string {
		unset( $domain );

		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Translate and escape text.
	 *
	 * @param   string $text   Text.
	 * @param   string $domain Text domain.
	 * @return  string
	 */
	function esc_html__( string $text, string $domain = 'default' ): string {
		unset( $domain );

		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	/**
	 * Get blog info.
	 *
	 * @param   string $show   Blog info key.
	 * @return  string
	 */
	function get_bloginfo( string $show = '' ): string {
		if ( 'name' === $show ) {
			return 'Example Store';
		}

		if ( 'charset' === $show ) {
			return 'UTF-8';
		}

		return '';
	}
}

if ( ! function_exists( 'home_url' ) ) {
	/**
	 * Get home url.
	 *
	 * @return  string
	 */
	function home_url(): string {
		return 'https://example.test';
	}
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	/**
	 * Generate uuid.
	 *
	 * @return  string
	 */
	function wp_generate_uuid4(): string {
		return '00000000-0000-4000-8000-000000000000';
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Convert a value to non-negative integer.
	 *
	 * @param   mixed $maybeint   Maybe integer.
	 * @return  int
	 */
	function absint( mixed $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

require __DIR__ . '/Support/WP_REST_Response.php';
require __DIR__ . '/Support/WP_Error.php';
require __DIR__ . '/Support/WP_REST_Request.php';

require dirname( __DIR__ ) . '/vendor/autoload.php';
