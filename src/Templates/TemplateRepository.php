<?php
/**
 * Template repository.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Templates;

use OmoikaneWorks\SimpleSalesReports\Database\TemplateTable;

defined( 'ABSPATH' ) || exit;

/**
 * Handles template queries.
 */
final class TemplateRepository implements TemplateRepositoryInterface {

	/**
	 * Find default sales report template.
	 *
	 * @return  array<string, mixed>|null
	 */
	public function find_default_sales_report_template(): ?array {
		return $this->find_by_key( TemplateKeys::DEFAULT_SALES_REPORT );
	}

	/**
	 * Find template by key.
	 *
	 * @param   string $template_key   Template key.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_key( string $template_key ): ?array {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
					id,
					template_key,
					name,
					type,
					content,
					content_hash,
					version,
					is_system,
					is_default,
					is_active,
					created_at,
					updated_at
				FROM %i
				WHERE template_key = %s
				AND is_active = 1
				ORDER BY id DESC
				LIMIT 1',
				$table_name,
				$template_key
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return $this->normalize_row( $row );
	}

	/**
	 * Find active default template by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<string, mixed>|null
	 */
	public function find_active_default_by_type( string $type = TemplateTypes::SALES_REPORT ): ?array {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
					id,
					template_key,
					name,
					type,
					content,
					content_hash,
					version,
					is_system,
					is_default,
					is_active,
					created_at,
					updated_at
				FROM %i
				WHERE type = %s
				AND is_default = 1
				AND is_active = 1
				ORDER BY id DESC
				LIMIT 1',
				$table_name,
				$type
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return $this->normalize_row( $row );
	}

	/**
	 * Find template by ID.
	 *
	 * @param   int $id     Template ID.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_id( int $id ): ?array {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
					id,
					template_key,
					name,
					type,
					content,
					content_hash,
					version,
					is_system,
					is_default,
					is_active,
					created_at,
					updated_at
				FROM %i
				WHERE id = %d
				AND is_active = 1
				LIMIT 1',
				$table_name,
				$id
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return $this->normalize_row( $row );
	}

	/**
	 * Find selectable by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<int, array<string, mixed>>
	 */
	public function find_selectable_by_type( string $type ): array {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT
					id,
					template_key,
					name,
					type,
					content,
					content_hash,
					version,
					is_system,
					is_default,
					is_active,
					created_at,
					updated_at
				FROM %i
				WHERE type = %s
				AND is_active = 1
				ORDER BY is_system DESC, id DESC',
				$table_name,
				$type
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					array( $this, 'normalize_row' ),
					$rows
				)
			)
		);
	}

	/**
	 * Normalize database row.
	 *
	 * @param   array<string, mixed> $row    Database row.
	 * @return  array<string, mixed>
	 */
	private function normalize_row( array $row ): array {
		return array(
			'id'           => isset( $row['id'] ) ? (int) $row['id'] : 0,
			'template_key' => isset( $row['template_key'] ) ? (string) $row['template_key'] : '',
			'name'         => isset( $row['name'] ) ? (string) $row['name'] : '',
			'type'         => isset( $row['type'] ) ? (string) $row['type'] : '',
			'content'      => isset( $row['content'] ) ? (string) $row['content'] : '',
			'content_hash' => isset( $row['content_hash'] ) ? (string) $row['content_hash'] : '',
			'version'      => isset( $row['version'] ) ? (string) $row['version'] : '',
			'is_system'    => ! empty( $row['is_system'] ),
			'is_default'   => ! empty( $row['is_default'] ),
			'is_active'    => ! empty( $row['is_active'] ),
			'created_at'   => isset( $row['created_at'] ) ? (string) $row['created_at'] : '',
			'updated_at'   => isset( $row['updated_at'] ) ? (string) $row['updated_at'] : '',
		);
	}

	/**
	 * Check name exists.
	 *
	 * @param   string   $name       Name.
	 * @param   int|null $exclude_id Exclude id.
	 * @return  bool
	 */
	public function name_exists( string $name, ?int $exclude_id = null ): bool {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		if ( null !== $exclude_id ) {
			$query = $wpdb->prepare(
				'SELECT EXISTS(SELECT 1 FROM %i WHERE name = %s AND is_active = 1 AND id <> %d)',
				$table_name,
				$name,
				$exclude_id
			);
		} else {
			$query = $wpdb->prepare(
				'SELECT EXISTS(SELECT 1 FROM %i WHERE name = %s AND is_active = 1)',
				$table_name,
				$name,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_var( $query );

		return ( '1' === (string) $result );
	}

	/**
	 * Insert data.
	 *
	 * @param   array<string, mixed> $data   Data.
	 * @return  int
	 * @phpstan-param   array{
	 *  template_key: string,
	 *  name: string,
	 *  content: string,
	 *  content_hash: string,
	 *  version: string
	 * }    $data
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();
		$now        = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table_name,
			array(
				'template_key' => $data['template_key'],
				'name'         => $data['name'],
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => $data['content'],
				'content_hash' => $data['content_hash'],
				'version'      => $data['version'],
				'is_system'    => 0,
				'is_default'   => 0,
				'is_active'    => 1,
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
			)
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update data.
	 *
	 * @param   int                  $id     ID.
	 * @param   array<string, mixed> $data   Data.
	 * @return  bool
	 * @phpstan-param   array{
	 *  name: string,
	 *  content: string,
	 *  content_hash: string
	 * }    $data
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table_name,
			array(
				'name'         => $data['name'],
				'content'      => $data['content'],
				'content_hash' => $data['content_hash'],
				'updated_at'   => $now,
			),
			array(
				'id'        => $id,
				'is_active' => 1,
			),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d', '%d' )
		);

		return ( false !== $result ) ? true : false;
	}

	/**
	 * Deactivate template.
	 *
	 * @param   int $id ID.
	 * @return  bool
	 */
	public function deactivate( int $id ): bool {
		global $wpdb;

		$table_name = TemplateTable::get_table_name();

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table_name,
			array(
				'is_active'  => 0,
				'updated_at' => $now,
			),
			array(
				'id'        => $id,
				'is_active' => 1,
			),
			array( '%d', '%s' ),
			array( '%d', '%d' ),
		);

		return ( false !== $result ) ? true : false;
	}
}
