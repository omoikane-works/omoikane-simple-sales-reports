<?php
/**
 * Template repository interface.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Handles template queries.
 */
interface TemplateRepositoryInterface {

	/**
	 * Find default sales report template.
	 *
	 * @return  array<string, mixed>|null
	 */
	public function find_default_sales_report_template(): ?array;

	/**
	 * Find template by key.
	 *
	 * @param   string $template_key   Template key.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_key( string $template_key ): ?array;

	/**
	 * Find active default template by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<string, mixed>|null
	 */
	public function find_active_default_by_type( string $type ): ?array;

	/**
	 * Find template by ID.
	 *
	 * @param   int $id     Template ID.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_id( int $id ): ?array;

	/**
	 * Find selectable by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<int, array<string, mixed>>
	 */
	public function find_selectable_by_type( string $type ): array;

	/**
	 * Check name exists.
	 *
	 * @param   string   $name       Name.
	 * @param   int|null $exclude_id Exclude id.
	 * @return  bool
	 */
	public function name_exists( string $name, ?int $exclude_id ): bool;

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
	public function insert( array $data ): int;

	/**
	 * Update data.
	 *
	 * @param   int                  $id     ID.
	 * @param   array<string, mixed> $data   Data.
	 * @return  bool
	 * @phpstan-param   array{
	 *  name?: string,
	 *  content?: string,
	 *  content_hash?: string,
	 *  updated_at?: string
	 * }    $data
	 */
	public function update( int $id, array $data ): bool;

	/**
	 * Deactivate template.
	 *
	 * @param   int $id ID.
	 * @return  bool
	 */
	public function deactivate( int $id ): bool;
}
