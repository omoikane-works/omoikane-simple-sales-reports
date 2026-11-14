<?php
/**
 * HTTP method constants used during template REST route registration.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

/**
 * Fake WP_REST_Server.
 */
class WP_REST_Server {

	public const READABLE  = 'GET';
	public const CREATABLE = 'POST';
	public const EDITABLE  = 'POST, PUT, PATCH';
	public const DELETABLE = 'DELETE';
}
