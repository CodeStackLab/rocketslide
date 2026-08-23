<?php
/**
 * class-reelflow-image-processor.php
 *
 * IMAGE UPLOAD & AUTOMATIC WebP CONVERSION ENGINE
 * ================================================
 *
 * Handles server-side processing of every uploaded reel image:
 *   1. Receives a local filesystem path (from file upload or WP Media Library).
 *   2. Uses WordPress's wp_get_image_editor() abstraction to support either
 *      PHP GD or PHP Imagick, whichever is installed on the server.
 *   3. Crops & resizes the image to exactly 540 × 960 px (9:16 ratio).
 *   4. Converts to WebP at 75% quality.
 *   5. Saves the optimised image to wp-content/uploads/reelflow/.
 *   6. Falls back gracefully to JPEG at 85% quality if the server does not
 *      support WebP output (very old GD builds without webp support).
 *
 * @package ReelFlow_Landing_Page
 * @since   2.0.0
 */

// Block direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReelFlow_Image_Processor {

	/** @const int   Target width for 9:16 vertical resolution */
	const TARGET_WIDTH  = 540;

	/** @const int   Target height for 9:16 vertical resolution */
	const TARGET_HEIGHT = 960;

	/** @const int   WebP compression quality (0–100) */
	const WEBP_QUALITY  = 75;

	/** @const int   JPEG fallback quality (used only when WebP is unavailable) */
	const JPEG_FALLBACK_QUALITY = 85;

	/**
	 * Ensure the upload directory exists.
	 * Called on plugin activation and on every AJAX upload request.
	 */
	public static function ensure_upload_dir() {
		$dir = reelflow_uploads_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
	}

	/**
	 * Main entry point — process and optimise a single source image.
	 *
	 * Steps performed:
	 *  1. Validate the source file exists.
	 *  2. Open with WP Image Editor (GD or Imagick, whichever is available).
	 *  3. Resize + hard-crop to 540 × 960 px.
	 *  4. Save as WebP (75% quality) → or fallback to JPEG (85% quality).
	 *  5. Return an array with the public URL and the local file path.
	 *
	 * @param  string       $source_path  Absolute path to the source image file.
	 * @return array|WP_Error
	 *         Success: array( 'url' => string, 'path' => string, 'format' => 'webp'|'jpeg' )
	 *         Failure: WP_Error object with descriptive message.
	 */
	public static function process_image( $source_path ) {
		// — — — Step 1: Validate source file — — —
		if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
			return new WP_Error(
				'reelflow_file_not_found',
				sprintf( 'Source image not found: %s', esc_html( $source_path ) )
			);
		}

		// Confirm the file is actually an image (basic MIME check via WP)
		$mime = wp_check_filetype( $source_path );
		if ( ! $mime['type'] || 0 !== strpos( $mime['type'], 'image/' ) ) {
			return new WP_Error( 'reelflow_not_image', 'The uploaded file is not a valid image.' );
		}

		// — — — Step 2: Open with WP Image Editor — — —
		$editor = wp_get_image_editor( $source_path );
		if ( is_wp_error( $editor ) ) {
			return $editor; // Propagate the error (e.g. "No editor could be selected")
		}

		// — — — Step 3: Resize & hard-crop to 540 × 960 — — —
		//
		// The third parameter `true` tells WP to CROP (not just scale).
		// This guarantees the output is exactly 540 × 960 regardless of the
		// source image's original aspect ratio.
		$result = $editor->resize( self::TARGET_WIDTH, self::TARGET_HEIGHT, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Ensure the upload directory is ready
		self::ensure_upload_dir();

		// — — — Step 4 & 5: Try WebP first, then JPEG fallback — — —
		$unique_suffix = time() . '_' . wp_generate_password( 8, false, false );

		// --- Attempt 1: Save as WebP ---
		$webp_filename = 'reel_' . $unique_suffix . '.webp';
		$webp_path     = reelflow_uploads_dir() . $webp_filename;
		$webp_url      = reelflow_uploads_url() . $webp_filename;

		$editor->set_quality( self::WEBP_QUALITY );
		$saved = $editor->save( $webp_path, 'image/webp' );

		if ( ! is_wp_error( $saved ) && file_exists( $webp_path ) ) {
			return array(
				'url'    => $webp_url,
				'path'   => $webp_path,
				'format' => 'webp',
			);
		}

		// --- Attempt 2: Fallback to JPEG if WebP not supported ---
		$jpg_filename = 'reel_' . $unique_suffix . '.jpg';
		$jpg_path     = reelflow_uploads_dir() . $jpg_filename;
		$jpg_url      = reelflow_uploads_url() . $jpg_filename;

		$editor->set_quality( self::JPEG_FALLBACK_QUALITY );
		$saved_jpg = $editor->save( $jpg_path, 'image/jpeg' );

		if ( is_wp_error( $saved_jpg ) ) {
			return $saved_jpg;
		}

		return array(
			'url'    => $jpg_url,
			'path'   => $jpg_path,
			'format' => 'jpeg', // Flagged so admin UI can show correct badge
		);
	}

	/**
	 * Resolve a WP Media Library attachment ID to its local filesystem path,
	 * then pass it through process_image().
	 *
	 * @param  int          $attachment_id  WP Attachment post ID.
	 * @return array|WP_Error
	 */
	public static function process_from_attachment_id( $attachment_id ) {
		$attachment_path = get_attached_file( (int) $attachment_id );

		if ( ! $attachment_path ) {
			return new WP_Error(
				'reelflow_attachment_not_found',
				sprintf( 'Attachment ID %d could not be resolved to a local file.', $attachment_id )
			);
		}

		return self::process_image( $attachment_path );
	}

	/**
	 * Delete a physically stored ReelFlow image from the uploads/reelflow/ directory.
	 *
	 * @param  string  $file_path  Absolute path to the image file.
	 * @return bool    TRUE if the file was deleted (or didn't exist), FALSE on failure.
	 */
	public static function delete_image( $file_path ) {
		if ( empty( $file_path ) ) {
			return false;
		}

		// Security: ensure the file is actually inside the ReelFlow upload directory
		$upload_dir = reelflow_uploads_dir();
		if ( 0 !== strpos( realpath( dirname( $file_path ) ), realpath( $upload_dir ) ) ) {
			return false; // Refuse to delete files outside our designated folder
		}

		if ( file_exists( $file_path ) ) {
			return (bool) @unlink( $file_path );
		}

		return true; // File already gone — consider it a success
	}
}
