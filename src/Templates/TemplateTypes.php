<?php
/**
 * Template types.
 *
 * @package WelcartSimpleReportSales
 */

declare(strict_types=1);

namespace OmoikaneWorks\WelcartSimpleReportSales\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Defines template types.
 */
final class TemplateTypes {

	/**
	 * Sales report template type.
	 *
	 * @var string
	 */
	public const SALES_REPORT = 'sales_report';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
