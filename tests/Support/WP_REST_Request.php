<?php
/**
 * Fake WP_REST_Request.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Fake WP_REST_Request.
	 */
	class WP_REST_Request implements \ArrayAccess {

		/**
		 * Request params.
		 *
		 * @var   array<string, mixed>
		 */
		private array $params = array();

		/**
		 * Matched URL parameters.
		 *
		 * @var array<string, string>
		 */
		private array $url_params = array();

		/**
		 * Decoded JSON body.
		 *
		 * @var mixed
		 */
		private mixed $json_params = null;

		/**
		 * Raw JSON request body.
		 *
		 * @var string
		 */
		private string $body = '';

		/**
		 * Get the request body.
		 *
		 * @return string
		 */
		public function get_body(): string {
			return $this->body;
		}

		/**
		 * Set a JSON request body using the WordPress parameter decoding convention.
		 *
		 * @param string $body Raw JSON.
		 * @return void
		 */
		public function set_body( string $body ): void {
			$this->body        = $body;
			$this->json_params = json_decode( $body, true );
		}

		/**
		 * Get the matched URL parameters.
		 *
		 * @return array<string, string>
		 */
		public function get_url_params(): array {
			return $this->url_params;
		}

		/**
		 * Set the matched URL parameters.
		 *
		 * @param array<string, string> $params URL parameters.
		 */
		public function set_url_params( array $params ): void {
			$this->url_params = $params;
		}

		/**
		 * Get the decoded JSON body.
		 *
		 * @return mixed
		 */
		public function get_json_params(): mixed {
			return $this->json_params;
		}

		/**
		 * Set the decoded JSON body as returned by WordPress.
		 *
		 * @param mixed $params Decoded JSON value.
		 */
		public function set_json_params( mixed $params ): void {
			$this->json_params = $params;
		}

		/**
		 * Get request parameter.
		 *
		 * @param   string $key    Parameter key.
		 * @return  string|null
		 */
		public function get_param( string $key ): ?string {
			return $this->params[ $key ] ?? null;
		}

		/**
		 * Set request parameter.
		 *
		 * @param   string $key    Parameter key.
		 * @param   mixed  $value  Parameter value.
		 * @return  void
		 */
		public function set_param( string $key, mixed $value ): void {
			$this->params[ $key ] = $value;
		}

		// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

		/**
		 * Whenever an offset exists.
		 *
		 * @param   mixed $offset Offset.
		 * @return  bool
		 */
		public function offsetExists( mixed $offset ): bool {
			return isset( $this->params[ (string) $offset ] );
		}

		/**
		 * Get an offset.
		 *
		 * @param   mixed $offset Offset.
		 * @return  mixed
		 */
		public function offsetGet( mixed $offset ): mixed {
			return $this->params[ (string) $offset ];
		}

		/**
		 * Set an offset.
		 *
		 * @param   mixed $offset Offset.
		 * @param   mixed $value  Value.
		 * @return  void
		 */
		public function offsetSet( mixed $offset, mixed $value ): void {
			$this->params[ (string) $offset ] = $value;
		}

		/**
		 * Unset an offset.
		 *
		 * @param   mixed $offset Offset.
		 * @return  void
		 */
		public function offsetUnset( mixed $offset ): void {
			unset( $this->params[ (string) $offset ] );
		}

		// phpcs:enable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	}
}
