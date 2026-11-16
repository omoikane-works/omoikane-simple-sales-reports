<?php
/**
 * Branding config REST controller.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Rest;

use OmoikaneWorks\SimpleSalesReports\Configs\BrandingImageService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the two branding image slots.
 */
final class ConfigController {

	/**
	 * Constructor.
	 *
	 * @param BrandingImageService $images Branding image service.
	 */
	public function __construct( private readonly BrandingImageService $images ) {}

	/**
	 * Register the branding image route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'ossr/v1',
			'/config/branding/(?P<slot>logo|stamp)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_image' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'put_image' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_image' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			),
		);
	}

	/**
	 * Check access to the settings.
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the current image or null when the slot is empty.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_image( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$image = $this->images->get( $this->get_slot( $request ) );
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		$response = new WP_REST_Response( array( 'image' => $image ), 200 );
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * Store or replace an image.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function put_image( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$body = $request->get_json_params();
		if (
			// The WordPress PHPDoc says array, but get_json_params() returns the decoded JSON value.
			/* @phpstan-ignore function.alreadyNarrowedType (WordPress can return a scalar or null) */
			! is_array( $body ) ||
			! is_string( $body['data'] ?? null ) ||
			( array_key_exists( 'mime', $body ) && ! is_string( $body['mime'] ) )
		) {
			return new WP_Error(
				'ossr_invalid_image_request',
				__( 'Provide the image as base64 data and, optionally, its MIME type.', 'omoikane-simple-sales-reports' ),
				array( 'status' => 400 ),
			);
		}

		$image = $this->images->save( $this->get_slot( $request ), $body['data'], $body['mime'] ?? null );
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		return new WP_REST_Response( array( 'image' => $image ), 200 );
	}

	/**
	 * Delete the current image.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_image( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->images->delete( $this->get_slot( $request ) );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( null, 204 );
	}

	/**
	 * Use the matched path segment. WordPress matches routes case-insensitively.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return string
	 */
	private function get_slot( WP_REST_Request $request ): string {
		return strtolower( (string) $request->get_url_params()['slot'] );
	}
}
