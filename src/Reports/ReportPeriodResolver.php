<?php
/**
 * Report period resolver.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves report period from request parameters.
 */
final class ReportPeriodResolver {

	/**
	 * Resolve report period.
	 *
	 * @param   array<string, mixed> $request    Request parameters.
	 * @return  array<string, string>
	 */
	public function resolve( array $request ): array {
		$period = isset( $request['period'] )
			? sanitize_key( (string) wp_unslash( $request['period'] ) )
			: ReportPeriods::PREVIOUS_MONTH;

		if ( ! in_array( $period, $this->get_allowed_periods(), true ) ) {
			$period = ReportPeriods::PREVIOUS_MONTH;
		}

		if ( ReportPeriods::CURRENT_MONTH === $period ) {
			return $this->resolve_current_month();
		}

		if ( ReportPeriods::CUSTOM === $period ) {
			return $this->resolve_custom( $request );
		}

		return $this->resolve_previous_month();
	}

	/**
	 * Get allowed periods.
	 *
	 * @return  string[]
	 */
	private function get_allowed_periods(): array {
		return array(
			ReportPeriods::CURRENT_MONTH,
			ReportPeriods::PREVIOUS_MONTH,
			ReportPeriods::CUSTOM,
		);
	}

	/**
	 * Resolve current month.
	 *
	 * @return  array<string, string>
	 */
	private function resolve_current_month(): array {
		$current = current_datetime();

		$start_date = $current->modify( 'first day of this month' )->format( 'Y-m-d' );
		$end_date   = $current->modify( 'last day of this month' )->format( 'Y-m-d' );

		return $this->build_result( ReportPeriods::CURRENT_MONTH, $start_date, $end_date );
	}

	/**
	 * Resolve previous month.
	 *
	 * @return  array<string, string>
	 */
	private function resolve_previous_month(): array {
		$current = current_datetime();

		$start_date = $current->modify( 'first day of previous month' )->format( 'Y-m-d' );
		$end_date   = $current->modify( 'last day of previous month' )->format( 'Y-m-d' );

		return $this->build_result( ReportPeriods::PREVIOUS_MONTH, $start_date, $end_date );
	}

	/**
	 * Resolve custom date range.
	 *
	 * @param   array<string, mixed> $request    Request parameters.
	 * @return  array<string, string>
	 */
	private function resolve_custom( array $request ): array {
		$start_date = isset( $request['start_date'] )
			? sanitize_text_field( wp_unslash( (string) $request['start_date'] ) )
			: '';
		$end_date   = isset( $request['end_date'] )
			? sanitize_text_field( wp_unslash( (string) $request['end_date'] ) )
			: '';

		if ( ! $this->is_valid_date( $start_date ) || ! $this->is_valid_date( $end_date ) ) {
			return $this->resolve_previous_month();
		}

		if ( $start_date > $end_date ) {
			return $this->resolve_previous_month();
		}

		return $this->build_result( ReportPeriods::CUSTOM, $start_date, $end_date );
	}

	/**
	 * Check whether date string is valid.
	 *
	 * @param   string $date   Date string.
	 * @return  bool
	 */
	private function is_valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}

		$date_time = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );

		return $date_time instanceof \DateTimeImmutable && $date_time->format( 'Y-m-d' ) === $date;
	}

	/**
	 * Build result.
	 *
	 * @param   string $period      Period.
	 * @param   string $start_date  Start date.
	 * @param   string $end_date    End date.
	 * @return  array<string, string>
	 */
	private function build_result(
		string $period,
		string $start_date,
		string $end_date,
	): array {
		return array(
			'period'       => $period,
			'start_date'   => $start_date,
			'end_date'     => $end_date,
			'period_label' => $this->build_period_label( $start_date, $end_date ),
		);
	}

	/**
	 * Build period label.
	 *
	 * @param   string $start_date Start date.
	 * @param   string $end_date   End date.
	 * @return  string
	 */
	private function build_period_label( string $start_date, string $end_date ): string {
		$start = \DateTimeImmutable::createFromFormat( '!Y-m-d', $start_date, wp_timezone() );
		$end   = \DateTimeImmutable::createFromFormat( '!Y-m-d', $end_date, wp_timezone() );

		if ( ! $start instanceof \DateTimeImmutable || ! $end instanceof \DateTimeImmutable ) {
			return '';
		}

		return sprintf(
			/* translators: 1: start date, 2: end date */
			__( '%1$s ～ %2$s', 'omoikane-simple-sales-reports' ),
			wp_date( __( 'Y年n月j日', 'omoikane-simple-sales-reports' ), $start->getTimestamp() ),
			wp_date( __( 'Y年n月j日', 'omoikane-simple-sales-reports' ), $end->getTimestamp() )
		);
	}
}
