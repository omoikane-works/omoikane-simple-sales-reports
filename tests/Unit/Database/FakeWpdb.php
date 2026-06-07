<?php
/**
 * Fake wpdb for unit tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Database;

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
	 * Value returned by get_var()
	 *
	 * @var mixed
	 */
	private mixed $var = null;

	/**
	 * Inserted rows.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $inserted_rows = array();

	/**
	 * Insert ID.
	 *
	 * @var int
	 */
	public int $insert_id = 0;

	/**
	 * Updated rows.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $updated_rows = array();

	/**
	 * Updated where clauses.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $updated_where_clauses = array();

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

	/**
	 * Set var.
	 *
	 * @param   mixed $value    Value.
	 * @return  void
	 */
	public function set_var( mixed $value ): void {
		$this->var = $value;
	}

	/**
	 * Get var.
	 *
	 * @param   string $query  Query.
	 * @return  mixed
	 */
	public function get_var( string $query ): mixed {
		$this->last_query = $query;

		return $this->var;
	}

	/**
	 * Insert row.
	 *
	 * @param   string               $table  Table name.
	 * @param   array<string, mixed> $data   Data.
	 * @param   array<int, string>   $format Format.
	 * @return  int|false
	 */
	public function insert( string $table, array $data, array $format = array() ): int|bool {
		unset( $format );

		$this->last_query      = 'INSERT INTO ' . $table;
		$this->inserted_rows[] = $data;
		$this->insert_id       = count( $this->inserted_rows );

		return 1;
	}

	/**
	 * Get inserted rows.
	 *
	 * @return  array<int, array<string, mixed>>
	 */
	public function get_inserted_rows(): array {
		return $this->inserted_rows;
	}

	/**
	 * Update row.
	 *
	 * @param   string               $table          Table name.
	 * @param   array<string, mixed> $data           Data.
	 * @param   array<string, mixed> $where          Where.
	 * @param   array<int, string>   $format         Format.
	 * @param   array<int, string>   $where_format   Where format.
	 * @return  int|false
	 */
	public function update(
		string $table,
		array $data,
		array $where,
		array $format = array(),
		array $where_format = array()
	): int|bool {
		unset( $format, $where_format );

		$this->last_query              = 'UPDATE ' . $table;
		$this->updated_rows[]          = $data;
		$this->updated_where_clauses[] = $where;

		return 1;
	}

	/**
	 * Get updated rows.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_updated_rows(): array {
		return $this->updated_rows;
	}

	/**
	 * Get updated where clauses.
	 *
	 * @return array>int, array<string, mixed>>
	 */
	public function get_updated_where_clauses(): array {
		return $this->updated_where_clauses;
	}
}
