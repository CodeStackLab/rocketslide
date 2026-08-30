<?php
/**
 * class-rocketslide-image-processor.php
 *
 * IMAGE UPLOAD & AUTOMATIC 540x960 WebP CONVERSION ENGINE
 * ============================================================
 *
 * Handles server-side processing of every uploaded reel image:
 *   1. Accepts direct file upload ($_FILES) or WP Media Library attachment ID.
 *   2. Uses WordPress wp_get_image_editor() (GD or Imagick).
 *   3. Crops & resizes to 540x960 px (9:16 vertical format).
 *   4. Converts to WebP (75% quality) or falls back to JPEG (85% quality).
 *   5. Saves to wp-content/uploads/rocketslide/.
 *
 * @package RocketSlide_Landing_Page
 * @since   2.0.0
 */

// Block direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RocketSlide_Image_Processor {

	/** Target width for 9:16 vertical resolution */
	const TARGET_WIDTH  = 540;

	/** Target height for 9:16 vertical resolution */
	const TARGET_HEIGHT = 960;

	/** WebP compression quality */
	const WEBP_QUALITY  = 80;

	/** JPEG fallback quality */
	const JPEG_FALLBACK_QUALITY = 85;

	/**
	 * Ensure the upload directory exists.
	 */
	public static function ensure_upload_dir() {
		$dir = rocketslide_uploads_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );

			$htaccess = $dir . '.htaccess';
			if ( ! file_exists( $htaccess ) ) {
				@file_put_contents( $htaccess, "Options -Indexes\n" );
			}
		}
	}

	/**
	 * Process an uploaded $_FILES['image_file'] array.
	 *
	 * @param  array $file_array $_FILES['image_file']
	 * @return array|WP_Error
	 */
	public static function process_uploaded_file( $file_array ) {
		if ( empty( $file_array ) || ! isset( $file_array['tmp_name'] ) || empty( $file_array['tmp_name'] ) ) {
			return new WP_Error( 'rocketslide_no_file', 'No image file was uploaded.' );
		}

		if ( isset( $file_array['error'] ) && $file_array['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'rocketslide_upload_error', 'File upload error code: ' . $file_array['error'] );
		}

		$tmp_path  = $file_array['tmp_name'];
		$orig_name = isset( $file_array['name'] ) ? $file_array['name'] : 'image.jpg';

		// Verify it is a valid image using getimagesize
		$image_info = @getimagesize( $tmp_path );
		if ( ! $image_info ) {
			return new WP_Error( 'rocketslide_invalid_image', 'Uploaded file is not a valid image.' );
		}

		return self::process_image( $tmp_path, $orig_name );
	}

	/**
	 * Process from WP Media Library Attachment ID.
	 *
	 * @param  int $attachment_id
	 * @return array|WP_Error
	 */
	public static function process_from_attachment_id( $attachment_id ) {
		$attachment_path = get_attached_file( (int) $attachment_id );

		if ( ! $attachment_path || ! file_exists( $attachment_path ) ) {
			return new WP_Error(
				'rocketslide_attachment_not_found',
				sprintf( 'Attachment ID %d could not be found on server disk.', $attachment_id )
			);
		}

		return self::process_image( $attachment_path, basename( $attachment_path ) );
	}

	/**
	 * Alias for process_from_attachment_id
	 */
	public static function process_media_attachment( $attachment_id ) {
		return self::process_from_attachment_id( $attachment_id );
	}

	/**
	 * Process and crop image to 540x960 WebP.
	 *
	 * @param  string $source_path Absolute path to image
	 * @param  string $orig_name   Original filename for reference
	 * @return array|WP_Error
	 */
	public static function process_image( $source_path, $orig_name = '' ) {
		if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
			return new WP_Error( 'rocketslide_file_missing', 'Source image file not found.' );
		}

		self::ensure_upload_dir();

		// Open with WordPress image editor (GD or Imagick)
		$editor = wp_get_image_editor( $source_path );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		// Resize & hard crop to 540x960 (9:16 ratio)
		$resize_result = $editor->resize( self::TARGET_WIDTH, self::TARGET_HEIGHT, true );
		if ( is_wp_error( $resize_result ) ) {
			return $resize_result;
		}

		$unique_id = uniqid( 'img_' );
		$suffix    = time() . '_' . wp_generate_password( 6, false, false );

		// Attempt 1: WebP format
		$webp_filename = 'reel_' . $suffix . '.webp';
		$webp_path     = rocketslide_uploads_dir() . $webp_filename;
		$webp_url      = rocketslide_uploads_url() . $webp_filename;

		$editor->set_quality( self::WEBP_QUALITY );
		$saved = $editor->save( $webp_path, 'image/webp' );

		if ( ! is_wp_error( $saved ) && file_exists( $webp_path ) ) {
			return array(
				'id'     => $unique_id,
				'url'    => $webp_url,
				'path'   => $webp_path,
				'format' => 'webp',
			);
		}

		// Attempt 2: Fallback to JPEG if WebP is unsupported on host
		$jpg_filename = 'reel_' . $suffix . '.jpg';
		$jpg_path     = rocketslide_uploads_dir() . $jpg_filename;
		$jpg_url      = rocketslide_uploads_url() . $jpg_filename;

		$editor->set_quality( self::JPEG_FALLBACK_QUALITY );
		$saved_jpg = $editor->save( $jpg_path, 'image/jpeg' );

		if ( is_wp_error( $saved_jpg ) ) {
			return $saved_jpg;
		}

		return array(
			'id'     => $unique_id,
			'url'    => $jpg_url,
			'path'   => $jpg_path,
			'format' => 'jpeg',
		);
	}

	/**
	 * Delete a physically stored RocketSlide image file.
	 *
	 * @param  string $file_path Absolute path to the file
	 * @return bool
	 */
	public static function delete_image( $file_path ) {
		if ( empty( $file_path ) ) {
			return false;
		}

		$upload_dir = rocketslide_uploads_dir();
		if ( 0 !== strpos( realpath( dirname( $file_path ) ), realpath( $upload_dir ) ) ) {
			return false; // Prevent deletion outside rocketslide uploads folder
		}

		if ( file_exists( $file_path ) ) {
			return (bool) @unlink( $file_path );
		}

		return true;
	}
}
