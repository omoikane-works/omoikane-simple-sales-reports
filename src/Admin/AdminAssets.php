<?php
/**
 * Admin assets.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Admin;

use OmoikaneWorks\SimpleSalesReports\Plugin;

/**
 * Admin assets.
 */
final class AdminAssets {

	/**
	 * Vite manifest entry key.
	 *
	 * @var string
	 */
	private const ENTRY_KEY = 'assets/admin/src/main.ts';

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	private const SCRIPT_HANDLE = 'ossr-admin';

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private const REST_NAMESPACE = 'ossr/v1';

	/**
	 * Admin views that are handled by the Vue.js app.
	 *
	 * @var array<string>
	 */
	private const OSSR_ADMIN_VUE_VIEWS = array(
		'templates',
		'config',
	);

	/**
	 * Enqueue admin assets.
	 *
	 * @param   string $hook_suffix Admin page hook suffix.
	 * @return  void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! $this->is_target_page( $hook_suffix ) ) {
			return;
		}

		$entry = $this->get_manifest_entry();

		if ( null === $entry ) {
			return;
		}

		$this->enqueue_script( $entry );
		$this->enqueue_styles( $entry );
		$this->localize_script();
	}

	/**
	 * Check whether current admin page is target page.
	 *
	 * @param   string $hook_suffix    Admin page hook suffix.
	 * @return  bool
	 */
	private function is_target_page( string $hook_suffix ): bool {
		unset( $hook_suffix );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] )
			? sanitize_key( wp_unslash( $_GET['page'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( Plugin::OSSR_ADMIN_PAGE_SLUG !== $page ) {
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current_view = isset( $_GET[ Plugin::OSSR_ADMIN_VIEW_PARAM ] ) && is_string( $_GET[ Plugin::OSSR_ADMIN_VIEW_PARAM ] )
			? sanitize_key( wp_unslash( $_GET[ Plugin::OSSR_ADMIN_VIEW_PARAM ] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return in_array( $current_view, self::OSSR_ADMIN_VUE_VIEWS, true );
	}

	/**
	 * Get Vite manifest entry.
	 *
	 * @return  array<string, mixed>|null
	 */
	private function get_manifest_entry(): ?array {
		$manifest_path = OSSR_PLUGIN_DIR . 'assets/admin/dist/.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$manifest_json = file_get_contents( $manifest_path );

		if ( false === $manifest_json ) {
			return null;
		}

		$manifest = json_decode( $manifest_json, true );

		if ( ! is_array( $manifest ) ) {
			return null;
		}

		if ( ! isset( $manifest[ self::ENTRY_KEY ] ) ) {
			return null;
		}

		$entry = $manifest[ self::ENTRY_KEY ];

		if ( ! is_array( $entry ) ) {
			return null;
		}

		if ( empty( $entry['file'] ) || ! is_string( $entry['file'] ) ) {
			return null;
		}

		return $entry;
	}

	/**
	 * Enqueue script.
	 *
	 * @param   array<string, mixed> $entry  Manifest entry.
	 * @return  void
	 */
	private function enqueue_script( array $entry ): void {
		$dist_url = OSSR_PLUGIN_URL . 'assets/admin/dist/';

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$dist_url . $entry['file'],
			array(),
			OSSR_VERSION,
			true
		);

		wp_script_add_data( self::SCRIPT_HANDLE, 'type', 'module' );
	}

	/**
	 * Enqueue styles.
	 *
	 * @param   array<string, mixed> $entry  Manifest entry.
	 * @return  void
	 */
	private function enqueue_styles( array $entry ): void {
		if ( empty( $entry['css'] ) || ! is_array( $entry['css'] ) ) {
			return;
		}

		$dist_url = OSSR_PLUGIN_URL . 'assets/admin/dist/';

		foreach ( $entry['css'] as $index => $css_file ) {
			if ( ! is_string( $css_file ) ) {
				continue;
			}

			wp_enqueue_style(
				self::SCRIPT_HANDLE . '-' . (string) $index,
				$dist_url . $css_file,
				array(),
				OSSR_VERSION
			);
		}
	}

	/**
	 * Localize script.
	 *
	 * @return  void
	 */
	private function localize_script(): void {
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'ossrRestConfig',
			array(
				'restUrl' => esc_url_raw( rest_url( self::REST_NAMESPACE ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'ossrI18n',
			$this->get_i18n_messages()
		);
	}

	/**
	 * Get i18n messages.
	 *
	 * @return  array<string, string>
	 */
	private function get_i18n_messages(): array {
		$i18n_path = OSSR_PLUGIN_DIR . 'resources/i18n.php';

		if ( ! file_exists( $i18n_path ) ) {
			return array();
		}

		$i18n = require $i18n_path;

		if ( ! is_array( $i18n ) ) {
			return array();
		}

		$messages = array();

		foreach ( $i18n as $key => $message ) {
			if ( is_string( $key ) && is_string( $message ) ) {
				$messages[ $key ] = $message;
			}
		}

		return $messages;
	}
}
