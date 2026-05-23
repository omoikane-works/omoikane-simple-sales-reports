<?php
/**
 * Fake wpdb for unit tests.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Tests\Unit\Database;

/**
 * Fake wpdb.
 */
final class FakeWpdb {

	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	public string $prefix = 'wp_';

	/**
	 * Row returned by get_row().
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $row = null;

	/**
	 * Rows returned by get_results().
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private ?array $results = null;

	/**
	 * Last query.
	 *
	 * @var string
	 */
	private string $last_query = '';

	/**
	 * Set row.
	 *
	 * @param   array<string, mixed>|null $row    Row.
	 * @return  void
	 */
	public function set_row( ?array $row ): void {
		$this->row = $row;
	}

	/**
	 * Set results.
	 *
	 * @param   array<int, array<string, mixed>> $results    Results.
	 * @return  void
	 */
	public function set_results( ?array $results ): void {
		$this->results = $results;
	}

	/**
	 * Prepare Query.
	 *
	 * @param   string $query   Query.
	 * @param   mixed  ...$args Arguments.
	 * @return  string
	 */
	public function prepare( string $query, mixed ...$args ): string {
		$prepared = $query;

		foreach ( $args as $arg ) {
			$value = $this->format_arg( $arg );

			$prepared = preg_replace( '/%[isdf]/', $value, $prepared, 1 ) ?? $prepared;
		}

		return $prepared;
	}

	/**
	 * Get row.
	 *
	 * @param   string $query  Query.
	 * @param   string $output Output type.
	 * @return  array<string, mixed>|null
	 */
	public function get_row( string $query, string $output ): ?array {
		unset( $output );

		$this->last_query = $query;

		return $this->row;
	}

	/**
	 * Get results.
	 *
	 * @param   string $query  Query.
	 * @param   string $output Output type.
	 * @return  array<int, array<string, mixed>>|null
	 */
	public function get_results( string $query, string $output ): ?array {
		unset( $output );

		$this->last_query = $query;

		return $this->results;
	}

	/**
	 * Get last query.
	 *
	 * @return  string
	 */
	public function get_last_query(): string {
		return $this->last_query;
	}

	/**
	 * Format prepare argument.
	 *
	 * @param   mixed $arg Argument.
	 * @return  string
	 */
	public function format_arg( mixed $arg ): string {
		if ( is_int( $arg ) || is_float( $arg ) ) {
			return (string) $arg;
		}

		return "'" . addslashes( (string) $arg ) . "'";
	}
}
