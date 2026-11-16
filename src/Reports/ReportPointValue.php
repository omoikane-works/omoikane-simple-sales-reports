<?php
/**
 * Points value for Mustache rendering.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes amount and label in Mustache sections and renders the raw legacy count in scalar tags.
 */
final class ReportPointValue {

	/**
	 * Constructor.
	 *
	 * @param int    $amount Point count.
	 * @param string $label  Formatted point count.
	 */
	public function __construct(
		public readonly int $amount,
		public readonly string $label,
	) {}

	/**
	 * Render the raw count for a v1.0.3 scalar tag.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return (string) $this->amount;
	}
}
