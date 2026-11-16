<?php
/**
 * Storage and validation for branding images.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Configs;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the logo and seal stored in Config.
 *
 * @phpstan-type Image array{mime: string, data: string}
 */
final class BrandingImageService {

	private const MAX_BYTES  = 1048576;
	private const MAX_BASE64 = 1398104; // 4 * ceil( MAX_BYTES / 3 ).
	private const MAX_SIDE   = 4096;
	private const MAX_PIXELS = 4194304;

	private const MIME_TYPES = array( 'image/png', 'image/jpeg', 'image/webp' );

	/**
	 * Constructor.
	 *
	 * @param ConfigService $configs Config service.
	 */
	public function __construct( private readonly ConfigService $configs ) {}

	/**
	 * Read an image.
	 *
	 * @param string $slot Branding slot.
	 * @return Image|WP_Error|null
	 */
	public function get( string $slot ): array|WP_Error|null {
		$value = $this->configs->get( 'branding.' . $slot );
		if ( null === $value ) {
			return null;
		}

		$image = json_decode( $value, true );
		if ( ! is_array( $image ) || ! in_array( $image['mime'] ?? null, self::MIME_TYPES, true ) || ! is_string( $image['data'] ?? null ) ) {
			return $this->storage_error();
		}

		return array(
			'mime' => $image['mime'],
			'data' => $image['data'],
		);
	}

	/**
	 * Read and validate the stored image for rendering.
	 *
	 * @param string $slot Branding slot.
	 * @return Image|WP_Error|null
	 */
	public function get_validated( string $slot ): array|WP_Error|null {
		$image = $this->get( $slot );
		if ( ! is_array( $image ) ) {
			return $image;
		}

		return $this->validate( $image['data'], $image['mime'] );
	}

	/**
	 * Validate and save an image.
	 *
	 * @param string      $slot Branding slot.
	 * @param string      $data Base64-encoded image.
	 * @param string|null $mime Optional declared MIME type.
	 * @return Image|WP_Error
	 */
	public function save( string $slot, string $data, ?string $mime ): array|WP_Error {
		$value = $this->validate( $data, $mime );
		if ( is_wp_error( $value ) ) {
			return $value;
		}

		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
		if ( false === $json || ! $this->configs->set( 'branding.' . $slot, $json ) ) {
			return $this->storage_error();
		}

		return $value;
	}

	/**
	 * Validate the image payload without accessing storage.
	 *
	 * @param string      $data Base64-encoded image.
	 * @param string|null $mime Optional declared MIME type.
	 * @return Image|WP_Error
	 */
	private function validate( string $data, ?string $mime ): array|WP_Error {
		if ( strlen( $data ) > self::MAX_BASE64 ) {
			return $this->size_error();
		}

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Decode and verify the image payload.
		$bytes = base64_decode( $data, true );
		if ( false === $bytes || '' === $bytes || base64_encode( $bytes ) !== $data ) {
			return $this->invalid_image();
		}

		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		if ( strlen( $bytes ) > self::MAX_BYTES ) {
			return $this->size_error();
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Malformed headers can emit a notice. The return value is checked.
		$dimensions = @getimagesizefromstring( $bytes );
		$detected   = $dimensions['mime'] ?? '';
		if ( false === $dimensions || ! in_array( $detected, self::MIME_TYPES, true ) ) {
			return $this->invalid_image();
		}

		if ( null !== $mime && $mime !== $detected ) {
			return new WP_Error(
				'ossr_image_mime_mismatch',
				__( 'The specified MIME type does not match the image.', 'omoikane-simple-sales-reports' ),
				array( 'status' => 400 ),
			);
		}

		if ( ! $this->dimensions_allowed( $dimensions[0], $dimensions[1] ) ) {
			return new WP_Error(
				'ossr_image_dimensions',
				__( 'The image must be at most 4,096 px per side and 4,194,304 pixels in total.', 'omoikane-simple-sales-reports' ),
				array( 'status' => 400 ),
			);
		}

		$static = match ( $detected ) {
			'image/png'  => $this->inspect_png( $bytes ),
			'image/webp' => $this->inspect_webp( $bytes, $dimensions[0], $dimensions[1] ),
			default      => true,
		};
		if ( is_wp_error( $static ) ) {
			return $static;
		}

		if ( ! function_exists( 'imagecreatefromstring' ) || ! $this->gd_supports( $detected ) ) {
			return new WP_Error(
				'ossr_image_decoder_unavailable',
				__( 'The server cannot decode this image format with GD.', 'omoikane-simple-sales-reports' ),
				array( 'status' => 503 ),
			);
		}

		// GD may return an image and emit a warning for a damaged input.
		$decode_warning = false;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Capture GD diagnostics without exposing them in the REST response.
		set_error_handler(
			static function () use ( &$decode_warning ): bool {
				$decode_warning = true;
				return true;
			},
			E_WARNING | E_NOTICE,
		);
		try {
			$image = imagecreatefromstring( $bytes );
		} finally {
			restore_error_handler();
		}

		if ( $decode_warning || false === $image || imagesx( $image ) !== $dimensions[0] || imagesy( $image ) !== $dimensions[1] ) {
			return $this->invalid_image();
		}

		return array(
			'mime' => $detected,
			'data' => $data,
		);
	}

	/**
	 * Check the decoder for the identified MIME type.
	 *
	 * @param string $mime Image MIME type.
	 * @return bool
	 */
	private function gd_supports( string $mime ): bool {
		$format = match ( $mime ) {
			'image/png'  => IMG_PNG,
			'image/jpeg' => IMG_JPG,
			'image/webp' => IMG_WEBP,
			default      => 0,
		};

		return 0 !== ( imagetypes() & $format );
	}

	/**
	 * Delete an image.
	 *
	 * @param string $slot Branding slot.
	 * @return bool|WP_Error
	 */
	public function delete( string $slot ): bool|WP_Error {
		return $this->configs->delete( 'branding.' . $slot ) ? true : $this->storage_error();
	}

	/**
	 * Check the dimensions before passing the image to GD.
	 *
	 * @param int $width  Image width.
	 * @param int $height Image height.
	 * @return bool
	 */
	private function dimensions_allowed( int $width, int $height ): bool {
		return $width > 0 && $height > 0 && $width <= self::MAX_SIDE && $height <= self::MAX_SIDE && $width * $height <= self::MAX_PIXELS;
	}

	/**
	 * Walk the PNG chunk boundaries through IEND.
	 *
	 * @param string $bytes PNG bytes.
	 * @return bool|WP_Error
	 */
	private function inspect_png( string $bytes ): bool|WP_Error {
		$end = strlen( $bytes );
		for ( $offset = 8; $end - $offset >= 12; $offset = $next ) {
			// Length and type precede the data. The CRC follows it.
			$size = ( unpack( 'N', $bytes, $offset )[1] ?? -1 );
			$type = substr( $bytes, $offset + 4, 4 );
			if ( $size < 0 || $size > $end - $offset - 12 ) {
				break;
			}
			$next = $offset + 12 + $size;
			if ( in_array( $type, array( 'acTL', 'fcTL', 'fdAT' ), true ) ) {
				return $this->animation_error();
			}
			if ( 'IEND' === $type ) {
				return 0 === $size && $next === $end ? true : $this->invalid_image();
			}
		}

		return $this->invalid_image();
	}

	/**
	 * Walk the padded RIFF chunks and check the encoded frame dimensions.
	 *
	 * @param string $bytes  WebP bytes.
	 * @param int    $width  Declared canvas width.
	 * @param int    $height Declared canvas height.
	 * @return bool|WP_Error
	 */
	private function inspect_webp( string $bytes, int $width, int $height ): bool|WP_Error {
		$end = strlen( $bytes );
		if ( ( unpack( 'V', $bytes, 4 )[1] ?? -1 ) !== $end - 8 ) {
			return $this->invalid_image();
		}

		$has_frame = false;
		for ( $offset = 12; $end - $offset >= 8; $offset = $next ) {
			$type = substr( $bytes, $offset, 4 );
			$size = ( unpack( 'V', $bytes, $offset + 4 )[1] ?? -1 );
			if ( $size < 0 || $size > $end - $offset - 8 ) {
				return $this->invalid_image();
			}
			$next = $offset + 8 + $size + ( $size % 2 );
			if ( $next > $end ) {
				return $this->invalid_image();
			}

			if ( 'VP8X' === $type ) {
				if ( 10 !== $size || 12 !== $offset ) {
					return $this->invalid_image();
				}
				if ( 0 !== ( ord( $bytes[ $offset + 8 ] ) & 0x02 ) ) {
					return $this->animation_error();
				}
			}

			if ( 'ANIM' === $type || 'ANMF' === $type ) {
				return $this->animation_error();
			}

			if ( 'VP8 ' === $type || 'VP8L' === $type ) {
				// Read the frame header without the VP8X canvas before GD allocates its pixels.
				$chunk_size = $next - $offset;
				$frame      = 'RIFF' . pack( 'V', $chunk_size + 4 ) . 'WEBP' . substr( $bytes, $offset, $chunk_size );
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Malformed frame headers can emit a notice. The return value is checked.
				$dimensions = @getimagesizefromstring( $frame );
				if ( $has_frame || false === $dimensions || $dimensions[0] !== $width || $dimensions[1] !== $height ) {
					return $this->invalid_image();
				}
				$has_frame = true;
			}
		}

		return $offset === $end && $has_frame ? true : $this->invalid_image();
	}

	/**
	 * Report invalid or unsupported image content.
	 *
	 * @return WP_Error
	 */
	private function invalid_image(): WP_Error {
		return new WP_Error(
			'ossr_invalid_image',
			__( 'Upload a valid PNG, JPEG, or WebP image.', 'omoikane-simple-sales-reports' ),
			array( 'status' => 400 ),
		);
	}

	/**
	 * Report an oversized file.
	 *
	 * @return WP_Error
	 */
	private function size_error(): WP_Error {
		return new WP_Error(
			'ossr_image_too_large',
			__( 'The image file must be 1 MiB or smaller.', 'omoikane-simple-sales-reports' ),
			array( 'status' => 413 ),
		);
	}

	/**
	 * Report an animation marker.
	 *
	 * @return WP_Error
	 */
	private function animation_error(): WP_Error {
		return new WP_Error(
			'ossr_animated_image',
			__( 'Animated images are not supported.', 'omoikane-simple-sales-reports' ),
			array( 'status' => 400 ),
		);
	}

	/**
	 * Report a failed Config operation.
	 *
	 * @return WP_Error
	 */
	private function storage_error(): WP_Error {
		return new WP_Error(
			'ossr_image_storage_failed',
			__( 'The branding image could not be accessed or saved.', 'omoikane-simple-sales-reports' ),
			array( 'status' => 500 ),
		);
	}
}
