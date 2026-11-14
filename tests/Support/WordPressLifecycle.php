<?php
/**
 * WordPress lifecycle API stubs for unit tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

/**
 * Record calls in order across the WordPress and database stubs.
 *
 * @param string $call    Recorded operation.
 * @param mixed  ...$args Operation arguments.
 * @return void
 */
function ossr_test_record_call( string $call, mixed ...$args ): void {
	$GLOBALS['ossr_test_calls'][] = array( $call, ...$args );
}

/**
 * Read an option from the test fixture.
 *
 * @param string $option  Option name.
 * @param mixed  $fallback Default for a missing option.
 * @return mixed
 */
function get_option( string $option, mixed $fallback = false ): mixed {
	ossr_test_record_call( 'get_option', $option, $fallback );

	return $GLOBALS['ossr_test_options'][ $option ] ?? $fallback;
}

/**
 * Record and store an option update.
 *
 * @param string           $option   Option name.
 * @param mixed            $value    New value.
 * @param bool|string|null $autoload Autoload setting.
 * @return bool
 */
function update_option( string $option, mixed $value, bool|string|null $autoload = null ): bool {
	ossr_test_record_call( 'update_option', $option, $value, $autoload );
	$GLOBALS['ossr_test_options'][ $option ] = $value;

	return true;
}

/**
 * Record an action registration.
 *
 * @param string   $hook       Hook name.
 * @param callable $callback   Hook callback.
 * @param int      $priority   Hook priority.
 * @param int      $args_count Accepted argument count.
 * @return bool
 */
function add_action( string $hook, callable $callback, int $priority = 10, int $args_count = 1 ): bool {
	ossr_test_record_call( 'add_action', $hook, $callback, $priority, $args_count );

	return true;
}

/**
 * Record a filter registration.
 *
 * @param string   $hook       Hook name.
 * @param callable $callback   Hook callback.
 * @param int      $priority   Hook priority.
 * @param int      $args_count Accepted argument count.
 * @return bool
 */
function add_filter( string $hook, callable $callback, int $priority = 10, int $args_count = 1 ): bool {
	ossr_test_record_call( 'add_filter', $hook, $callback, $priority, $args_count );

	return true;
}

/**
 * Record a REST route registration.
 *
 * @param string               $route_namespace REST namespace.
 * @param string               $route     Route pattern.
 * @param array<string, mixed> $args      Route arguments.
 * @return bool
 */
function register_rest_route( string $route_namespace, string $route, array $args ): bool {
	ossr_test_record_call( 'register_rest_route', $route_namespace, $route, $args );

	return true;
}

/**
 * Record plugin deactivation.
 *
 * @param string $plugin Plugin basename.
 * @return void
 */
function deactivate_plugins( string $plugin ): void {
	ossr_test_record_call( 'deactivate_plugins', $plugin );
}

/**
 * End execution of the activation path under test.
 *
 * @param string $message Error message.
 * @throws RuntimeException Simulates termination.
 */
function wp_die( string $message ): never {
	// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test fixture captures the supplied message.
	throw new RuntimeException( $message );
}

/**
 * Escape an activation message.
 *
 * @param string $message Error message.
 * @return string
 */
function esc_html( string $message ): string {
	return htmlspecialchars( $message, ENT_QUOTES, 'UTF-8' );
}

/**
 * Stand in for the message helper from the plugin entry point.
 *
 * @return string
 */
function ossr_get_unsupported_welcart_version_message(): string {
	return 'Unsupported Welcart version.';
}

/**
 * Stand in for the PHP version message helper from the plugin entry point.
 *
 * @return string
 */
function ossr_get_unsupported_php_version_message(): string {
	return 'Unsupported PHP version.';
}
