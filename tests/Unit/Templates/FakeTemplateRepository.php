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
}
