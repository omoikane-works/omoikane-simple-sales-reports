<?php
/**
 * Creates template rows for tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Support;

use OmoikaneWorks\SimpleSalesReports\Templates\TemplateKeys;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateTypes;

/**
 * Create template rows for tests.
 */
trait CreatesTemplateRows {

	/**
	 * Create Template row.
	 *
	 * @param   array<string, mixed> $overrides  Overrides.
	 * @return  array<string, mixed>
	 */
	private function create_template_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'           => 1,
				'template_key' => TemplateKeys::DEFAULT_SALES_REPORT,
				'name'         => 'Default Sales Report',
				'type'         => TemplateTypes::SALES_REPORT,
				'content'      => '<h1>{{ report.title }}</h1>',
				'content_hash' => 'hash-default',
				'version'      => '1.0.0',
				'is_system'    => true,
				'is_default'   => true,
				'is_active'    => true,
				'created_at'   => '2026-05-01 10:00:00',
				'updated_at'   => '2026-05-01 10:00:00',
			),
			$overrides
		);
	}

	/**
	 * Create template rows indexed by ID.
	 *
	 * @param   array<int, array<string, mixed>> $templates  Templates.
	 * @return  array<int, array<string, mixed>>
	 */
	private function create_template_map( array $templates ): array {
		$template_map = array();

		foreach ( $templates as $template ) {
			if ( ! isset( $template['id'] ) ) {
				continue;
			}

			$template_map[ (int) $template['id'] ] = $template;
		}

		return $template_map;
	}
}
