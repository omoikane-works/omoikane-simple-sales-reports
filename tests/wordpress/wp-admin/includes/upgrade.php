<?php
/**
 * Test fixture for the WordPress schema upgrade include.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- WordPress API name.

/**
 * Capture the SQL supplied to WordPress.
 *
 * @param string $sql Schema definition.
 * @return array<string, string>
 */
function dbDelta( string $sql ): array {
	ossr_test_record_call( 'dbDelta', $sql );

	return array();
}
