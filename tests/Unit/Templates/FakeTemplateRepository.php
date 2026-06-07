<?php
/**
 * Sales report renderer test.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Templates;

use OmoikaneWorks\SimpleSalesReports\Templates\TemplateRepositoryInterface;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;

/**
 * Fake Template repository.
 */
final class FakeTemplateRepository implements TemplateRepositoryInterface {

	/**
	 * Templates.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $template;

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
	 * Deactivated id.
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
	public int $insert_result = 1;

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
	 * @param   array<string, mixed>|null $template   Template.
	 */
	public function __construct( ?array $template ) {
		$this->template = $template;
	}

	/**
	 * Find default sale report template.
	 *
	 * @return  array<string, mixed>|null
	 */
	public function find_default_sales_report_template(): ?array {
		return $this->template;
	}

	/**
	 * Find template by key.
	 *
	 * @param   string $template_key   Template key.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_key( string $template_key ): ?array {
		unset( $template_key );

		return $this->template;
	}

	/**
	 * Find active default template by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<string, mixed>|null
	 */
	public function find_active_default_by_type( string $type = TemplateTypes::SALES_REPORT ): ?array {
		unset( $type );

		return $this->template;
	}

	/**
	 * Find template by ID.
	 *
	 * @param   int $id     Template ID.
	 * @return  array<string, mixed>|null
	 */
	public function find_by_id( int $id ): ?array {
		unset( $id );

		return $this->template;
	}

	/**
	 * Find selectable by type.
	 *
	 * @param   string $type   Template type.
	 * @return  array<int, array<string, mixed>>
	 */
	public function find_selectable_by_type( string $type ): array {
		unset( $type );

		if ( null === $this->template ) {
			return array();
		}

		return array( $this->template );
	}

	/**
	 * Check name exists.
	 *
	 * @param   string   $name        Name.
	 * @param   int|null $exclude_id  Exclude id.
	 * @return  bool
	 */
	public function name_exists( string $name, ?int $exclude_id = null ): bool {
		unset( $name, $exclude_id );

		return $this->name_exists_result;
	}

	/**
	 * Insert data.
	 *
	 * @param   array<string, mixed> $data Data.
	 * @return  int
	 */
	public function insert( array $data ): int {
		$this->inserted_data = $data;

		return $this->insert_result;
	}

	/**
	 * Update data.
	 *
	 * @param   int                  $id   ID.
	 * @param   array<string, mixed> $data Data.
	 * @return  bool
	 */
	public function update( int $id, array $data ): bool {
		$this->updated_data = array(
			'id'   => $id,
			'data' => $data,
		);

		return $this->update_result;
	}

	/**
	 * Deactivate template.
	 *
	 * @param   int $id ID.
	 * @return  bool
	 */
	public function deactivate( int $id ): bool {
		$this->deactivated_id = $id;

		return $this->deactivate_result;
	}
}
