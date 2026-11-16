<?php
/**
 * Fake WP_REST_Response.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Fake WP_REST_Response.
	 */
	class WP_REST_Response {

		/**
		 * Response data.
		 *
		 * @var mixed
		 */
		private mixed $data;

		/**
		 * HTTP status.
		 *
		 * @var int
		 */
		private int $status;

		/**
		 * HTTP headers.
		 *
		 * @var array<string, string>
		 */
		private array $headers = array();

		/**
		 * Set a response header.
		 *
		 * @param string $key   Header name.
		 * @param string $value Header value.
		 */
		public function header( string $key, string $value ): void {
			$this->headers[ $key ] = $value;
		}

		/**
		 * Get response headers.
		 *
		 * @return array<string, string>
		 */
		public function get_headers(): array {
			return $this->headers;
		}

		/**
		 * Constructor.
		 *
		 * @param   mixed $data   Response data.
		 * @param   int   $status HTTP status.
		 */
		public function __construct( mixed $data, int $status ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * Get response data.
		 *
		 * @return  mixed
		 */
		public function get_data(): mixed {
			return $this->data;
		}

		/**
		 * Get HTTP status.
		 *
		 * @return  int
		 */
		public function get_status(): int {
			return $this->status;
		}
	}
}
