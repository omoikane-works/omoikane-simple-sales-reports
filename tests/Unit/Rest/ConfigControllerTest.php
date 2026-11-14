<?php
/**
 * Branding REST controller tests.
 *
 * @package SimpleSalesReports
 */

declare(strict_types=1);

namespace OmoikaneWorks\SimpleSalesReports\Tests\Unit\Rest;

use OmoikaneWorks\SimpleSalesReports\Configs\BrandingImageService;
use OmoikaneWorks\SimpleSalesReports\Configs\ConfigRepositoryInterface;
use OmoikaneWorks\SimpleSalesReports\Configs\ConfigService;
use OmoikaneWorks\SimpleSalesReports\Rest\ConfigController;
use OmoikaneWorks\SimpleSalesReports\Tests\Unit\Configs\FakeConfigRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- REST image fixtures.

/**
 * Exercises the branding endpoints and image validation with real GD.
 */
final class ConfigControllerTest extends TestCase {

	/**
	 * Controller under test.
	 *
	 * @var ConfigController
	 */
	private ConfigController $controller;

	/**
	 * Config store under test.
	 *
	 * @var FakeConfigRepository
	 */
	private FakeConfigRepository $configs;

	/**
	 * Create a controller with an independent Config store.
	 */
	protected function setUp(): void {
		$this->configs    = new FakeConfigRepository();
		$this->controller = new ConfigController( new BrandingImageService( new ConfigService( $this->configs ) ) );
	}

	/**
	 * Require administrative capability.
	 */
	public function test_permissions(): void {
		$this->assertFalse( $this->controller->check_permissions() );
		$GLOBALS['ossr_test_manage_options'] = true;
		try {
			$this->assertTrue( $this->controller->check_permissions() );
		} finally {
			unset( $GLOBALS['ossr_test_manage_options'] );
		}
	}

	/**
	 * Manage both image slots independently.
	 */
	public function test_get_put_replace_delete_and_slot_independence(): void {
		$logo    = $this->request( 'logo' );
		$stamp   = $this->request( 'stamp' );
		$initial = $this->controller->get_image( $logo );
		$this->assertInstanceOf( WP_REST_Response::class, $initial );
		$this->assertSame( array( 'image' => null ), $initial->get_data() );
		$this->assertSame( 'no-store', $initial->get_headers()['Cache-Control'] );

		$first_logo = $this->png( 2, 1 );
		$stamp_data = $this->png( 3, 1 );
		$next_logo  = $this->png( 4, 1 );
		$this->assert_image( $this->upload( $first_logo ), $first_logo );
		$this->assert_image( $this->upload( $stamp_data, 'stamp', array( 'mime' => 'image/png' ) ), $stamp_data );
		$this->assert_image( $this->upload( $next_logo ), $next_logo );

		$result = $this->controller->get_image( $logo );
		$this->assert_image( $result, $next_logo );
		$this->assertSame( 'no-store', $result->get_headers()['Cache-Control'] );
		$this->assert_image( $this->controller->get_image( $stamp ), $stamp_data );
		$this->assertSame( $result->get_data()['image'], json_decode( $this->configs->get( 'branding.logo' ), true ) );
		$this->assertSame( base64_encode( $stamp_data ), json_decode( $this->configs->get( 'branding.stamp' ), true )['data'] );

		// JSON fields cannot override the path segment used to select the storage key.
		$logo->set_json_params( array( 'slot' => 'stamp' ) );
		for ( $i = 0; $i < 2; ++$i ) {
			$result = $this->controller->delete_image( $logo );
			$this->assertInstanceOf( WP_REST_Response::class, $result );
			$this->assertSame( 204, $result->get_status() );
		}
		$this->assertSame( array( 'image' => null ), $this->controller->get_image( $logo )->get_data() );
		$this->assert_image( $this->controller->get_image( $stamp ), $stamp_data );
		$this->assertNull( $this->configs->get( 'branding.logo' ) );

		$result = $this->controller->delete_image( $stamp );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( 204, $result->get_status() );
		$this->assertSame( array( 'image' => null ), $this->controller->get_image( $stamp )->get_data() );
		$this->assertNull( $this->configs->get( 'branding.stamp' ) );
	}

	/**
	 * Verify that GD decodes each supported image type.
	 *
	 * @param string $encoder GD encoder.
	 * @param string $mime    Expected MIME type.
	 */
	#[DataProvider( 'formats' )]
	public function test_supported_formats( string $encoder, string $mime ): void {
		if ( ! function_exists( $encoder ) ) {
			$this->markTestSkipped( 'GD cannot generate this format.' );
		}

		$image = imagecreatetruecolor( 3, 2 );
		$this->assertInstanceOf( \GdImage::class, $image );
		ob_start();
		$success = $encoder( $image );
		$bytes   = ob_get_clean();
		$this->assertTrue( $success );
		$this->assertIsString( $bytes );
		$this->assert_image( $this->upload( $bytes ), $bytes, $mime );
	}

	/**
	 * Provide the GD encoders and MIME types.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function formats(): array {
		return array(
			'PNG'  => array( 'imagepng', 'image/png' ),
			'JPEG' => array( 'imagejpeg', 'image/jpeg' ),
			'WebP' => array( 'imagewebp', 'image/webp' ),
		);
	}

	/**
	 * Enforce the decoded byte limit.
	 */
	public function test_file_size_limit_includes_exactly_one_mib(): void {
		$png = $this->png( 1, 1 );
		foreach ( array( 1048576, 1048577 ) as $size ) {
			$text  = "comment\0" . str_repeat( 'x', $size - strlen( $png ) - 12 - 8 );
			$bytes = substr( $png, 0, -12 ) . $this->png_chunk( 'tEXt', $text ) . substr( $png, -12 );
			$this->assertSame( $size, strlen( $bytes ) );
			if ( 1048576 === $size ) {
				$this->assert_image( $this->upload( $bytes ), $bytes );
			} else {
				$this->assert_error( $this->upload( $bytes ), 'ossr_image_too_large', 413 );
			}
		}
	}

	/**
	 * Enforce width, height, and area limits.
	 *
	 * @param int  $width  Width.
	 * @param int  $height Height.
	 * @param bool $valid  Whether the dimensions meet the limits.
	 */
	#[DataProvider( 'dimensions' )]
	public function test_dimension_limits( int $width, int $height, bool $valid ): void {
		$png    = $this->png( $width, $height );
		$result = $this->upload( $png );
		if ( $valid ) {
			$this->assert_image( $result, $png );
		} else {
			$this->assert_error( $result, 'ossr_image_dimensions', 400 );
		}
	}

	/**
	 * Provide supported and oversized dimensions.
	 *
	 * @return array<string, array{int, int, bool}>
	 */
	public static function dimensions(): array {
		return array(
			'maximum width and area'  => array( 4096, 1024, true ),
			'maximum height and area' => array( 1024, 4096, true ),
			'width exceeds limit'     => array( 4097, 1, false ),
			'height exceeds limit'    => array( 1, 4097, false ),
			'area exceeds limit'      => array( 2049, 2048, false ),
		);
	}

	/**
	 * Preserve the original when the new value is invalid.
	 */
	public function test_failed_validation_leaves_original_image(): void {
		$png = $this->png( 1, 1 );
		$this->assert_image( $this->upload( $png ), $png );
		$this->assert_error( $this->upload( $png, 'logo', array( 'mime' => 'image/jpeg' ) ), 'ossr_image_mime_mismatch', 400 );
		$this->assert_error( $this->upload( '<svg/>' ), 'ossr_invalid_image', 400 );
		$this->assert_error( $this->upload( base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true ) ), 'ossr_invalid_image', 400 );
		$this->assert_error( $this->upload( substr( $png, 0, -3 ) ), 'ossr_invalid_image', 400 );
		$broken = substr( $png, 0, 33 ) . $this->png_chunk( 'IDAT', 'broken zlib stream' ) . substr( $png, -12 );
		$this->assert_error( $this->upload( $broken ), 'ossr_invalid_image', 400 );
		$this->assert_image( $this->controller->get_image( $this->request( 'logo' ) ), $png );
	}

	/**
	 * Reject an invalid PUT body and leave the stored image intact.
	 *
	 * @param string $json JSON body.
	 * @param string $code Expected error code.
	 */
	#[DataProvider( 'invalid_json_bodies' )]
	public function test_invalid_json_body_leaves_original_image( string $json, string $code ): void {
		$png = $this->png( 1, 1 );
		$this->assert_image( $this->upload( $png, 'stamp' ), $png );
		$original = $this->configs->get( 'branding.stamp' );

		$request = $this->request( 'stamp' );
		$request->set_json_params( json_decode( $json, true, 512, JSON_THROW_ON_ERROR ) );
		$this->assert_error( $this->controller->put_image( $request ), $code, 400 );
		$this->assertSame( $original, $this->configs->get( 'branding.stamp' ) );
		$this->assert_image( $this->controller->get_image( $request ), $png );
	}

	/**
	 * Provide JSON value types and invalid image fields.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function invalid_json_bodies(): array {
		$invalid_request = 'ossr_invalid_image_request';
		return array(
			'null body'       => array( 'null', $invalid_request ),
			'string body'     => array( '"image"', $invalid_request ),
			'number body'     => array( '42', $invalid_request ),
			'boolean body'    => array( 'true', $invalid_request ),
			'array body'      => array( '[{"data":"AA=="}]', $invalid_request ),
			'empty object'    => array( '{}', $invalid_request ),
			'missing data'    => array( '{"mime":"image/png"}', $invalid_request ),
			'array data'      => array( '{"data":["AA=="]}', $invalid_request ),
			'number data'     => array( '{"data":42}', $invalid_request ),
			'null data'       => array( '{"data":null}', $invalid_request ),
			'null MIME'       => array( '{"data":"AA==","mime":null}', $invalid_request ),
			'non-string MIME' => array( '{"data":"AA==","mime":42}', $invalid_request ),
			'invalid base64'  => array( '{"data":"bad!*"}', 'ossr_invalid_image' ),
		);
	}

	/**
	 * Report repository failures and retain the original image.
	 */
	public function test_failed_storage_leaves_original_image(): void {
		$png      = $this->png( 1, 1 );
		$stored   = array(
			'mime' => 'image/png',
			'data' => base64_encode( $png ),
		);
		$database = $this->createMock( ConfigRepositoryInterface::class );
		$database->expects( $this->once() )->method( 'set' )->with( 'branding.logo', $this->isType( 'string' ) )->willReturn( false );
		$database->expects( $this->once() )->method( 'delete' )->with( 'branding.logo' )->willReturn( false );
		$database->method( 'get' )->with( 'branding.logo' )->willReturn( wp_json_encode( $stored ) );

		$this->controller = new ConfigController( new BrandingImageService( new ConfigService( $database ) ) );
		$this->assert_error( $this->upload( $this->png( 2, 1 ) ), 'ossr_image_storage_failed', 500 );
		$this->assert_error( $this->controller->delete_image( $this->request( 'logo' ) ), 'ossr_image_storage_failed', 500 );
		$this->assert_image( $this->controller->get_image( $this->request( 'logo' ) ), $png );
	}

	/**
	 * Reject APNG chunk types regardless of chunk order.
	 */
	public function test_png_animation_chunks_are_rejected_on_either_side_of_image_data(): void {
		$png = $this->png( 1, 1 );
		foreach ( array( 'acTL', 'fcTL', 'fdAT' ) as $type ) {
			$chunk = $this->png_chunk( $type, pack( 'NN', 1, 0 ) );
			foreach ( array( 33, strlen( $png ) - 12 ) as $offset ) {
				$bytes = substr( $png, 0, $offset ) . $chunk . substr( $png, $offset );
				$this->assert_error( $this->upload( $bytes ), 'ossr_animated_image', 400 );
			}
		}

		$chunk = $this->png_chunk( 'tEXt', "comment\0acTL fcTL fdAT" );
		$bytes = substr( $png, 0, 33 ) . $chunk . substr( $png, 33 );
		$this->assert_image( $this->upload( $bytes ), $bytes );
	}

	/**
	 * Reject WebP animation declarations and malformed containers.
	 */
	public function test_webp_animation_flags_and_chunks_are_rejected(): void {
		// One-pixel static VP8 image chunk.
		$frame = base64_decode( 'VlA4IBgAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v02aAA=', true );
		$chunk = static fn ( string $type, string $data ): string => $type . pack( 'V', strlen( $data ) ) . $data . str_repeat( "\0", strlen( $data ) % 2 );
		$webp  = static fn ( string $chunks ): string => 'RIFF' . pack( 'V', strlen( $chunks ) + 4 ) . 'WEBP' . $chunks;

		$static_header = $chunk( 'VP8X', str_repeat( "\0", 10 ) );
		$this->assert_error( $this->upload( $webp( $chunk( 'VP8X', "\x02" . str_repeat( "\0", 9 ) ) . $frame ) ), 'ossr_animated_image', 400 );
		foreach ( array( 'ANIM', 'ANMF' ) as $type ) {
			$bytes = $webp( $static_header . $frame . $chunk( $type, str_repeat( "\0", 6 ) ) );
			$this->assert_error( $this->upload( $bytes ), 'ossr_animated_image', 400 );
		}

		$wrong_canvas = $chunk( 'VP8X', str_repeat( "\0", 4 ) . "\x01" . str_repeat( "\0", 5 ) );
		$this->assert_error( $this->upload( $webp( $wrong_canvas . $frame ) ), 'ossr_invalid_image', 400 );
		$this->assert_error( $this->upload( $webp( $static_header . $frame ) . 'ANIM' ), 'ossr_invalid_image', 400 );
	}

	/**
	 * Build a predictable grayscale PNG with valid CRCs.
	 *
	 * @param int $width  Width.
	 * @param int $height Height.
	 * @return string
	 */
	private function png( int $width, int $height ): string {
		return "\x89PNG\r\n\x1a\n"
			. $this->png_chunk( 'IHDR', pack( 'NNCCCCC', $width, $height, 8, 0, 0, 0, 0 ) )
			. $this->png_chunk( 'IDAT', gzcompress( str_repeat( "\0", ( $width + 1 ) * $height ) ) )
			. $this->png_chunk( 'IEND', '' );
	}

	/**
	 * Encode a PNG chunk.
	 *
	 * @param string $type Chunk type.
	 * @param string $data Chunk payload.
	 * @return string
	 */
	private function png_chunk( string $type, string $data ): string {
		return pack( 'N', strlen( $data ) ) . $type . $data . pack( 'N', crc32( $type . $data ) );
	}

	/**
	 * Construct a request matched to a branding slot.
	 *
	 * @param string $slot Branding slot.
	 * @return WP_REST_Request
	 */
	private function request( string $slot ): WP_REST_Request {
		$request = new WP_REST_Request();
		$request->set_url_params( array( 'slot' => $slot ) );
		return $request;
	}

	/**
	 * Send an image to the PUT handler.
	 *
	 * @param string               $bytes Image content.
	 * @param string               $slot  Branding slot.
	 * @param array<string, mixed> $body  Additional parameters or overrides.
	 * @return WP_REST_Response|WP_Error
	 */
	private function upload( string $bytes, string $slot = 'logo', array $body = array() ): WP_REST_Response|WP_Error {
		$request = $this->request( $slot );
		$request->set_json_params( $body + array( 'data' => base64_encode( $bytes ) ) );
		return $this->controller->put_image( $request );
	}

	/**
	 * Check the image response.
	 *
	 * @param WP_REST_Response|WP_Error $result Handler result.
	 * @param string                    $bytes Expected bytes.
	 * @param string                    $mime  Expected MIME type.
	 */
	private function assert_image( WP_REST_Response|WP_Error $result, string $bytes, string $mime = 'image/png' ): void {
		$this->assertInstanceOf( WP_REST_Response::class, $result, $result instanceof WP_Error ? $result->get_error_message() : '' );
		$this->assertSame( 200, $result->get_status() );
		$this->assertSame( $mime, $result->get_data()['image']['mime'] );
		$this->assertSame( base64_encode( $bytes ), $result->get_data()['image']['data'] );
	}

	/**
	 * Check the error response.
	 *
	 * @param WP_REST_Response|WP_Error $result Handler result.
	 * @param string                    $code   Expected WP_Error code.
	 * @param int                       $status Expected HTTP status.
	 */
	private function assert_error( WP_REST_Response|WP_Error $result, string $code, int $status ): void {
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( $status, $result->get_error_data()['status'] );
	}
}
