<?php
/**
 * Sales report renderer.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Reports;

use Mustache_Engine;
use OmoikaneWorks\WelcartSimpleReportSales\Templates\TemplateRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Renders sales report templates.
 */
final class SalesReportRenderer {

	/**
	 * Template repository.
	 *
	 * @var TemplateRepository
	 */
	private TemplateRepository $template_repository;

	/**
	 * Constructor.
	 *
	 * @param   TemplateRepository $template_repository    Template repository.
	 */
	public function __construct( TemplateRepository $template_repository ) {
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

		$mustache = new Mustache_Engine(
			array(
				'entity_flags' => ENT_QUOTES,
				'charset'      => get_bloginfo( 'charset' ),
			)
		);

		return $mustache->render( $content, $view_data );
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
}
