<?php
/**
 * Fake WP_Error.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Fake WP_Error
	 */
	class WP_Error {

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private string $message;

		/**
		 * Error code.
		 *
		 * @var string
		 */
		private string $code;

		/**
		 * Error data.
		 *
		 * @var mixed
		 */
		private mixed $data;

		/**
		 * Constructor.
		 *
		 * @param   string $code       Error code.
		 * @param   string $message    Error message.
		 * @param   mixed  $data       Error data.
		 */
		public function __construct( string $code, string $message, mixed $data ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Get error code.
		 *
		 * @return  string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * Get error message.
		 *
		 * @return  string
		 */
		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * Get error data.
		 *
		 * @return  mixed
		 */
		public function get_error_data(): mixed {
			return $this->data;
		}
	}
}
