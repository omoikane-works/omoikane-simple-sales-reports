<?php
/**
 * Sales report renderer.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Reports;

use Mustache_Engine;
use OmoikaneWorks\WelcartSimpleReportSales\Templates\TemplateRepositoryInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Renders sales report templates.
 */
final class SalesReportRenderer {

	/**
	 * Template repository.
	 *
	 * @var TemplateRepositoryInterface
	 */
	private TemplateRepositoryInterface $template_repository;

	/**
	 * Constructor.
	 *
	 * @param   TemplateRepositoryInterface $template_repository    Template repository.
	 */
	public function __construct( TemplateRepositoryInterface $template_repository ) {
		$this->template_repository = $template_repository;
	}

	/**
	 * Render default sales report.
	 *
	 * @param   array<string, mixed> $view_data  View data.
	 * @return  string
	 */
	public function render_default_sales_report( array $view_data ): string {
		$template = $this->template_repository->find_default_sales_report_template();

		if ( null === $template ) {
			return $this->render_template_not_found_message();
		}

		$content = isset( $template['content'] ) ? (string) $template['content'] : '';

		if ( '' === $content ) {
			return $this->render_template_not_found_message();
		}

		return $this->render_content( $content, $view_data );
	}

	/**
	 * Render template not found message.
	 *
	 * @return  string
	 */
	private function render_template_not_found_message(): string {
		return sprintf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__(
				'売上報告書テンプレートが見つかりません。プラグインを再有効化してください。',
				'welcart-simple-report-sales'
			)
		);
	}

	/**
	 * Render sales report.
	 *
	 * @param   array<string, mixed> $view_data      View data.
	 * @param   int                  $template_id    Template ID.
	 * @return  string
	 */
	public function render_sales_report( array $view_data, int $template_id ): string {
		$template = $this->template_repository->find_by_id( $template_id );

		if ( null === $template ) {
			return $this->render_default_sales_report( $view_data );
		}

		$content = isset( $template['content'] )
			? (string) $template['content']
			: '';

		if ( '' === $content ) {
			return $this->render_default_sales_report( $view_data );
		}

		return $this->render_content( $content, $view_data );
	}

	/**
	 * Render template content.
	 *
	 * @param   string               $content    Template content.
	 * @param   array<string, mixed> $view_data  View data.
	 * @return  string
	 */
	private function render_content( string $content, array $view_data ): string {
		$mustache = new Mustache_Engine(
			array(
				'entity_flags' => ENT_QUOTES,
				'charset'      => get_bloginfo( 'charset' ),
			)
		);

		return $mustache->render( $content, $view_data );
	}
}
