<?php
/**
 * class-rocketslide-image-processor.php
 *
 * BULLETPROOF IMAGE UPLOAD & AUTOMATIC 540x960 WebP CONVERSION ENGINE
 * ============================================================
 *
 * Handles server-side processing of every uploaded reel image:
 *   1. Accepts direct file upload ($_FILES) or WP Media Library attachment ID.
 *   2. Accurately calculates center 9:16 crop boundaries for ANY source resolution.
 *   3. Uses WordPress Image Editor with GD/Imagick fallback.
 *   4. Crops & resizes to 540x960 px without dimension errors.
 *   5. Converts to high-efficiency WebP (80% quality) or JPEG (85% quality).
 *   6. Saves to wp-content/uploads/rocketslide/.
 *
 * @package RocketSlide_Landing_Page
 * @since   3.7.0
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
	 * Process, crop, and convert any image to 540x960 WebP (or JPEG fallback).
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

		$unique_id     = uniqid( 'img_' );
		$suffix        = time() . '_' . wp_generate_password( 6, false, false );
		$webp_filename = 'reel_' . $suffix . '.webp';
		$webp_path     = rocketslide_uploads_dir() . $webp_filename;
		$webp_url      = rocketslide_uploads_url() . $webp_filename;
		$jpg_filename  = 'reel_' . $suffix . '.jpg';
		$jpg_path      = rocketslide_uploads_dir() . $jpg_filename;
		$jpg_url       = rocketslide_uploads_url() . $jpg_filename;

		// Inspect original image dimensions
		$image_size = @getimagesize( $source_path );
		$orig_w     = $image_size ? $image_size[0] : 0;
		$orig_h     = $image_size ? $image_size[1] : 0;

		$target_w     = self::TARGET_WIDTH;  // 540
		$target_h     = self::TARGET_HEIGHT; // 960
		$target_ratio = $target_w / $target_h; // 0.5625

		// -------------------------------------------------------------
		// Method 1: WordPress Image Editor with Exact 9:16 Center Crop
		// -------------------------------------------------------------
		$editor = wp_get_image_editor( $source_path );
		if ( ! is_wp_error( $editor ) ) {
			$size  = $editor->get_size();
			$src_w = ! empty( $size['width'] ) ? $size['width'] : $orig_w;
			$src_h = ! empty( $size['height'] ) ? $size['height'] : $orig_h;

			if ( $src_w > 0 && $src_h > 0 ) {
				$src_ratio = $src_w / $src_h;

				if ( $src_ratio > $target_ratio ) {
					// Source is wider -> crop sides to keep center
					$crop_w = round( $src_h * $target_ratio );
					$crop_h = $src_h;
					$crop_x = max( 0, round( ( $src_w - $crop_w ) / 2 ) );
					$crop_y = 0;
				} else {
					// Source is taller or 9:16 -> crop top/bottom
					$crop_w = $src_w;
					$crop_h = round( $src_w / $target_ratio );
					$crop_x = 0;
					$crop_y = max( 0, round( ( $src_h - $crop_h ) / 2 ) );
				}

				// Crop and scale to exact target dimensions (540x960)
				$crop_result = $editor->crop( $crop_x, $crop_y, $crop_w, $crop_h, $target_w, $target_h );
				if ( ! is_wp_error( $crop_result ) ) {
					// Attempt saving WebP
					$editor->set_quality( self::WEBP_QUALITY );
					$saved_webp = $editor->save( $webp_path, 'image/webp' );

					if ( ! is_wp_error( $saved_webp ) && file_exists( $webp_path ) ) {
						return array(
							'id'     => $unique_id,
							'url'    => $webp_url,
							'path'   => $webp_path,
							'format' => 'webp',
						);
					}

					// Fallback to JPEG if WebP save failed
					$editor->set_quality( self::JPEG_FALLBACK_QUALITY );
					$saved_jpg = $editor->save( $jpg_path, 'image/jpeg' );

					if ( ! is_wp_error( $saved_jpg ) && file_exists( $jpg_path ) ) {
						return array(
							'id'     => $unique_id,
							'url'    => $jpg_url,
							'path'   => $jpg_path,
							'format' => 'jpeg',
						);
					}
				}
			}
		}

		// -------------------------------------------------------------
		// Method 2: Pure PHP GD Direct Fallback (Guaranteed to work)
		// -------------------------------------------------------------
		if ( function_exists( 'imagecreatetruecolor' ) && $orig_w > 0 && $orig_h > 0 ) {
			$img_type = $image_size ? $image_size[2] : 0;
			$src_gd   = null;

			switch ( $img_type ) {
				case IMAGETYPE_JPEG:
					$src_gd = @imagecreatefromjpeg( $source_path );
					break;
				case IMAGETYPE_PNG:
					$src_gd = @imagecreatefrompng( $source_path );
					break;
				case IMAGETYPE_WEBP:
					if ( function_exists( 'imagecreatefromwebp' ) ) {
						$src_gd = @imagecreatefromwebp( $source_path );
					}
					break;
				case IMAGETYPE_GIF:
					$src_gd = @imagecreatefromgif( $source_path );
					break;
			}

			if ( $src_gd ) {
				$src_ratio = $orig_w / $orig_h;

				if ( $src_ratio > $target_ratio ) {
					$crop_w = round( $orig_h * $target_ratio );
					$crop_h = $orig_h;
					$crop_x = max( 0, round( ( $orig_w - $crop_w ) / 2 ) );
					$crop_y = 0;
				} else {
					$crop_w = $orig_w;
					$crop_h = round( $orig_w / $target_ratio );
					$crop_x = 0;
					$crop_y = max( 0, round( ( $orig_h - $crop_h ) / 2 ) );
				}

				$dst_gd = imagecreatetruecolor( $target_w, $target_h );

				// Preserve PNG transparency if needed
				imagealphablending( $dst_gd, false );
				imagesavealpha( $dst_gd, true );

				imagecopyresampled(
					$dst_gd,
					$src_gd,
					0,
					0,
					$crop_x,
					$crop_y,
					$target_w,
					$target_h,
					$crop_w,
					$crop_h
				);

				// Attempt saving WebP via GD
				if ( function_exists( 'imagewebp' ) && @imagewebp( $dst_gd, $webp_path, self::WEBP_QUALITY ) ) {
					imagedestroy( $src_gd );
					imagedestroy( $dst_gd );
					return array(
						'id'     => $unique_id,
						'url'    => $webp_url,
						'path'   => $webp_path,
						'format' => 'webp',
					);
				}

				// Fallback JPEG via GD
				if ( @imagejpeg( $dst_gd, $jpg_path, self::JPEG_FALLBACK_QUALITY ) ) {
					imagedestroy( $src_gd );
					imagedestroy( $dst_gd );
					return array(
						'id'     => $unique_id,
						'url'    => $jpg_url,
						'path'   => $jpg_path,
						'format' => 'jpeg',
					);
				}

				imagedestroy( $src_gd );
				imagedestroy( $dst_gd );
			}
		}

		return new WP_Error( 'rocketslide_image_crop_failed', 'Image crop processing failed. Please try a different image format.' );
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
