<?php
/**
 * Fake template repository.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates;

use OmoikaneWorks\SimpleSalesReports\Templates\TemplateRepositoryInterface;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;

/**
 * Fake template repository.
 */
final class FakeTemplateRepository implements TemplateRepositoryInterface {

	/**
	 * Templates.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $templates = array();

	/**
	 * Inserted data.
	 *
	 * @var array<string, mixed>|null
	 */
	public ?array $inserted_data = null;

	/**
	 * Updated data.
	 *
	 * @var array<string, mixed>|null
	 */
	public ?array $updated_data = null;

	/**
	 * Deactivated template ID.
	 *
	 * @var int|null
	 */
	public ?int $deactivated_id = null;

	/**
	 * Name exists result.
	 *
	 * @var bool
	 */
	public bool $name_exists_result = false;

	/**
	 * Insert result.
	 *
	 * @var int
	 */
	public int $insert_result = 2;

	/**
	 * Update result.
	 *
	 * @var bool
	 */
	public bool $update_result = true;

	/**
	 * Deactivate result.
	 *
	 * @var bool
	 */
	public bool $deactivate_result = true;

	/**
	 * Constructor.
	 *
	 * @param   array<int, array<string, mixed>> $templates   Templates.
	 */
	public function __construct( array $templates = array() ) {
		foreach ( $templates as $template ) {
			if ( ! isset( $template['id'] ) ) {
				continue;
			}

			$this->add_template( $template );
		}
	}

	/**
	 * Add template.
	 *
	 * @param   array<string, mixed> $template   Template.
	 * @return  void
	 */
	public function add_template( array $template ): void {
		$template_id = isset( $template['id'] ) ? (int) $template['id'] : count( $this->templates ) + 1;

		$this->templates[ $template_id ] = array_merge(
			array(
				'id'           => $template_id,
				'template_key' => 'custom_' . (string) $template_id,
				'name'         => 'Template ' . (string) $template_id,
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => '',
				'content_hash' => '',
				'version'      => '1.0.0',
				'is_system'    => false,
				'is_default'   => false,
				'is_active'    => true,
				'created_at'   => '2026-05-01 10:00:00',
				'updated_at'   => '2026-05-01 10:00:00',
			),
			$template,
			array( 'id' => $template_id )
		);
	}

	/**
	 * Find default sales report template.
	 *
	 * @return  array<string, mixed>|null
	 */
	public function find_default_sales_report_template(): ?array {
		return $this->find_active_default_by_type( TemplateTypes::SALES_REPORT );
	}

	/**
	 * Find template by key.
	 *
	 * @param   string $template_key   Template key.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_key( string $template_key ): ?array {
		foreach ( $this->templates as $template ) {
			$stored_template_key = (string) ( $template['template_key'] ?? '' );
			if ( $template_key === $stored_template_key ) {
				return $template;
			}
		}

		return null;
	}

	/**
	 * Find active default template by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<string, mixed>|null
	 */
	public function find_active_default_by_type( string $type ): ?array {
		foreach ( $this->templates as $template ) {
			$template_type = (string) ( $template['type'] ?? '' );
			if (
				$type === $template_type
				&& ! empty( $template['is_default'] )
				&& ! empty( $template['is_active'] )
			) {
				return $template;
			}
		}

		return null;
	}

	/**
	 * Find template by ID.
	 *
	 * @param   int $template_id     Template ID.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_id( int $template_id ): ?array {
		return $this->templates[ $template_id ] ?? null;
	}

	/**
	 * Find selectable by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<int, array<string, mixed>>
	 */
	public function find_selectable_by_type( string $type ): array {
		return array_values(
			array_filter(
				$this->templates,
				static function ( array $template ) use ( $type ): bool {
					$template_type = (string) ( $template['type'] ?? '' );
					return $type === $template_type && ! empty( $template['is_active'] );
				}
			)
		);
	}

	/**
	 * Check name exists.
	 *
	 * @param   string   $name        Name.
	 * @param   int|null $exclude_id  Exclude id.
	 * @return  bool
	 */
	public function name_exists( string $name, ?int $exclude_id = null ): bool {
		if ( $this->name_exists_result ) {
			return true;
		}

		foreach ( $this->templates as $template ) {
			$template_id   = isset( $template['id'] ) ? (int) $template['id'] : 0;
			$template_name = (string) ( $template['name'] ?? '' );

			if ( null !== $exclude_id && $exclude_id === $template_id ) {
				continue;
			}

			if ( $name === $template_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Insert template.
	 *
	 * @param   array<string, mixed> $data   Template data.
	 * @phpstan-param array{
	 *      template_key: string,
	 *      name: string,
	 *      content: string,
	 *      content_hash: string,
	 *      version: string
	 * } $data Template Data.
	 * @return  int
	 */
	public function insert( array $data ): int {
		$this->inserted_data = $data;

		if ( 0 >= $this->insert_result ) {
			return $this->insert_result;
		}

		$template_id = $this->insert_result;

		$this->add_template(
			array_merge(
				$data,
				array(
					'id' => $template_id,
				)
			)
		);

		return $template_id;
	}

	/**
	 * Update data.
	 *
	 * @param   int                  $template_id   Template ID.
	 * @param   array<string, mixed> $data          Template data.
	 * @return  bool
	 */
	public function update( int $template_id, array $data ): bool {
		$this->updated_data = array(
			'id'   => $template_id,
			'data' => $data,
		);

		if ( ! $this->update_result || ! isset( $this->templates[ $template_id ] ) ) {
			return false;
		}

		$this->templates[ $template_id ] = array_merge(
			$this->templates[ $template_id ],
			$data,
			array(
				'updated_at' => '2026-05-01 10:00:00',
			)
		);

		return true;
	}

	/**
	 * Deactivate template.
	 *
	 * @param   int $template_id Template ID.
	 * @return  bool
	 */
	public function deactivate( int $template_id ): bool {
		$this->deactivated_id = $template_id;

		if ( ! $this->deactivate_result || ! isset( $this->templates[ $template_id ] ) ) {
			return false;
		}

		$this->templates[ $template_id ]['is_active']  = false;
		$this->templates[ $template_id ]['updated_at'] = '2026-05-01 10:00:00';

		return true;
	}
}
