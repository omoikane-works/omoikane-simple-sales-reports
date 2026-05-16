<?php
/**
 * Template repository.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Templates;

use OmoikaneWorks\WelcartSimpleReportSales\Database\TemplateTable;

defined( 'ABSPATH' ) || exit;

/**
 * Handles template queries.
 */
final class TemplateRepository {

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
}
