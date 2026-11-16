<?php
/**
 * Sales report renderer.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Reports;

use Mustache_Engine;
use OmoikaneWorks\SimpleSalesReports\Configs\BrandingImageService;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateRepositoryInterface;
use OmoikaneWorks\SimpleSalesReports\Templates\TemplateSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders sales report templates.
 *
 * @phpstan-import-type Settings from TemplateSettings
 */
final class SalesReportRenderer {

	/**
	 * Constructor.
	 *
	 * @param TemplateRepositoryInterface $template_repository Template repository.
	 * @param BrandingImageService        $branding_images     Branding image service.
	 */
	public function __construct(
		private readonly TemplateRepositoryInterface $template_repository,
		private readonly BrandingImageService $branding_images,
	) {}

	/**
	 * Render default sales report.
	 *
	 * @param   array<string, mixed> $view_data  View data.
	 * @return  string
	 */
	public function render_default_sales_report( array $view_data ): string {
		return $this->render_report( $view_data, null );
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
				'The sales report template was not found. Please reactivate the plugin.',
				'omoikane-simple-sales-reports'
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
		return $this->render_report( $view_data, $template_id );
	}

	/**
	 * Resolve the template, then render its body with its placement settings.
	 *
	 * @param array<string, mixed> $view_data   View data.
	 * @param int|null             $template_id Requested template ID.
	 * @return string
	 */
	private function render_report( array $view_data, ?int $template_id ): string {
		$template = null === $template_id ? null : $this->template_repository->find_by_id( $template_id );
		if ( '' === (string) ( $template['content'] ?? '' ) ) {
			$template = $this->template_repository->find_default_sales_report_template();
		}

		$content = (string) ( $template['content'] ?? '' );
		if ( '' === $content ) {
			return $this->render_template_not_found_message();
		}

		$branding = $this->build_branding_view_data( $template['settings'] ?? TemplateSettings::defaults() );
		unset( $view_data['branding'] );
		if ( array() !== $branding ) {
			$view_data['branding'] = $branding;
		}

		if ( is_array( $view_data['totals'] ?? null ) ) {
			$view_data['totals'] = $this->with_renderable_points( $view_data['totals'] );
		}
		if ( is_array( $view_data['orders'] ?? null ) ) {
			foreach ( $view_data['orders'] as $index => $order ) {
				if ( is_array( $order ) ) {
					$view_data['orders'][ $index ] = $this->with_renderable_points( $order );
				}
			}
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
	 * Allow the old scalar point tags to be interpolated at the paths shared with the current point sections.
	 *
	 * @param array<string, mixed> $row Totals or order row.
	 * @return array<string, mixed>
	 */
	private function with_renderable_points( array $row ): array {
		if ( ! is_array( $row['amounts'] ?? null ) ) {
			return $row;
		}

		foreach ( array( 'used_points', 'earned_points' ) as $key ) {
			$points = $row['amounts'][ $key ] ?? null;
			if ( is_array( $points ) && 2 === count( $points ) && is_int( $points['amount'] ?? null ) && is_string( $points['label'] ?? null ) ) {
				$row['amounts'][ $key ] = new ReportPointValue( $points['amount'], $points['label'] );
			}
		}

		return $row;
	}

	/**
	 * Build Mustache variables for enabled slots with valid images.
	 *
	 * @param array<string, mixed> $settings Normalized template settings.
	 * @phpstan-param Settings $settings
	 * @return array<string, array{src: string, x_mm: int|float, y_mm: int|float, width_mm: int|float}>
	 */
	private function build_branding_view_data( array $settings ): array {
		$branding = array();
		foreach ( $settings['branding'] as $slot => $placement ) {
			if ( ! $placement['enabled'] ) {
				continue;
			}

			$image = $this->branding_images->get_validated( $slot );
			if ( ! is_array( $image ) ) {
				continue;
			}

			$branding[ $slot ] = array(
				'src'      => 'data:' . $image['mime'] . ';base64,' . $image['data'],
				'x_mm'     => $placement['x_mm'],
				'y_mm'     => $placement['y_mm'],
				'width_mm' => $placement['width_mm'],
			);
		}

		return $branding;
	}
}
