<?php

namespace Classifai\Features;

use Classifai\Services\ImageProcessing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared async (Action Scheduler) processing logic for Image Processing features.
 *
 * Used by the Feature classes that act on uploaded images (descriptive text,
 * image tags, OCR and smart cropping). When a Feature's `processing_mode` is set
 * to `automatic_async`, the upload hook enqueues an Action Scheduler job instead
 * of running the provider synchronously during the upload request. The job hook
 * and status meta key are shared, so the constants live on the
 * {@see ImageProcessing} service (traits cannot declare constants on PHP 7.4).
 */
trait AsyncImageProcessing {

	/**
	 * Build the Action Scheduler job arguments for an attachment.
	 *
	 * The values are spread positionally into {@see handle_async_image_job()} by
	 * Action Scheduler, so insertion order matters. `feature` + `type` +
	 * `attachment_id` make the args unique per feature, which lets the shared
	 * job hook de-dupe per feature via `as_has_scheduled_action()`.
	 *
	 * @param int    $attachment_id Attachment ID being processed.
	 * @param string $type          Run type (e.g. `descriptive_text`, `tags`, `ocr`, `crop`).
	 * @return array
	 */
	public function get_async_job_args( int $attachment_id, string $type ): array {
		return array(
			'attachment_id'   => $attachment_id,
			'feature'         => static::ID,
			'type'            => $type,
			'calling_user_id' => get_current_user_id(),
		);
	}

	/**
	 * Enqueue an async image processing job.
	 *
	 * @param int    $attachment_id Attachment ID being processed.
	 * @param string $type          Run type passed through to `run()`.
	 * @return bool True if a job was scheduled (or already pending); false if
	 *              Action Scheduler is unavailable so the caller should fall
	 *              back to synchronous processing.
	 */
	public function enqueue_async_image_job( int $attachment_id, string $type ): bool {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return false;
		}

		$args = $this->get_async_job_args( $attachment_id, $type );

		if ( function_exists( 'as_has_scheduled_action' ) && \as_has_scheduled_action( ImageProcessing::ASYNC_JOB_HOOK, $args, 'classifai' ) ) {
			return true;
		}

		\as_enqueue_async_action( ImageProcessing::ASYNC_JOB_HOOK, $args, 'classifai' );

		$this->set_async_status(
			$attachment_id,
			array(
				'status' => 'scheduled',
				'type'   => $type,
			)
		);

		return true;
	}

	/**
	 * Handle an async image processing job.
	 *
	 * Registered against the shared job hook by every image Feature, so it self
	 * filters on the `feature` arg. Reuses the Feature's existing `run()` and
	 * `save()` methods so behavior matches the synchronous and REST paths.
	 *
	 * @param int    $attachment_id   Attachment ID being processed.
	 * @param string $feature         Feature ID the job was scheduled for.
	 * @param string $type            Run type passed through to `run()`.
	 * @param int    $calling_user_id User who triggered the upload, for context.
	 */
	public function handle_async_image_job( $attachment_id = 0, $feature = '', $type = '', $calling_user_id = 0 ) {
		// Ignore jobs scheduled for a different feature (the hook is shared).
		if ( static::ID !== $feature ) {
			return;
		}

		$attachment_id = (int) $attachment_id;

		// Attachment was deleted before the job ran.
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			return;
		}

		// Restore the uploading user's context before any access checks, since
		// Action Scheduler runs jobs without a logged-in user.
		$original_user_id = get_current_user_id();

		if ( $calling_user_id ) {
			wp_set_current_user( (int) $calling_user_id );
		}

		// Feature was disabled between enqueue and run.
		if ( ! $this->is_feature_enabled() ) {
			$this->clear_async_status( $attachment_id );

			if ( $calling_user_id ) {
				wp_set_current_user( $original_user_id );
			}

			return;
		}

		$this->set_async_status(
			$attachment_id,
			array(
				'status' => 'running',
				'type'   => $type,
			)
		);

		// Smart cropping needs the current attachment metadata and persists an
		// updated copy; the other features only read the attachment.
		if ( 'crop' === $type ) {
			$result = $this->run( $attachment_id, $type, wp_get_attachment_metadata( $attachment_id ) );
		} else {
			$result = $this->run( $attachment_id, $type );
		}

		if ( ! is_wp_error( $result ) && ! empty( $result ) ) {
			if ( 'crop' === $type ) {
				$meta = $this->save( $result, $attachment_id );
				wp_update_attachment_metadata( $attachment_id, $meta );
			} else {
				$this->save( $result, $attachment_id );
			}

			$this->clear_async_status( $attachment_id );
		} else {
			$this->set_async_status(
				$attachment_id,
				array(
					'status'  => 'error',
					'type'    => $type,
					'message' => is_wp_error( $result )
						? $result->get_error_message()
						: __( 'No result was returned.', 'classifai' ),
				)
			);
		}

		if ( $calling_user_id ) {
			wp_set_current_user( $original_user_id );
		}
	}

	/**
	 * Store the async processing status for this feature on an attachment.
	 *
	 * Status is kept in a single object meta keyed by Feature ID so several
	 * image features can report progress on the same attachment at once.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $data          Status data (`status`, `type`, optional `message`).
	 */
	public function set_async_status( int $attachment_id, array $data ) {
		$statuses = get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true );

		if ( ! is_array( $statuses ) ) {
			$statuses = array();
		}

		$statuses[ static::ID ] = $data;

		update_post_meta( $attachment_id, ImageProcessing::STATUS_META, $statuses );
	}

	/**
	 * Clear this feature's async processing status on an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function clear_async_status( int $attachment_id ) {
		$statuses = get_post_meta( $attachment_id, ImageProcessing::STATUS_META, true );

		if ( ! is_array( $statuses ) || ! isset( $statuses[ static::ID ] ) ) {
			return;
		}

		unset( $statuses[ static::ID ] );

		if ( empty( $statuses ) ) {
			delete_post_meta( $attachment_id, ImageProcessing::STATUS_META );
		} else {
			update_post_meta( $attachment_id, ImageProcessing::STATUS_META, $statuses );
		}
	}

	/**
	 * Register the async status meta for attachments.
	 *
	 * Exposed in the REST API for parity with other async features (the media
	 * modal itself polls a dedicated AJAX endpoint).
	 */
	public function register_async_status_meta() {
		register_meta(
			'post',
			ImageProcessing::STATUS_META,
			array(
				'object_subtype' => 'attachment',
				'type'           => 'object',
				'single'         => true,
				'auth_callback'  => '__return_true',
				'show_in_rest'   => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => array(
							'type'       => 'object',
							'properties' => array(
								'status'  => array( 'type' => 'string' ),
								'type'    => array( 'type' => 'string' ),
								'message' => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Sanitize the processing mode setting against the allowed values.
	 *
	 * @param mixed  $value   Submitted value.
	 * @param string $current Current stored value to fall back to.
	 * @return string
	 */
	public function sanitize_processing_mode( $value, string $current ): string {
		$value = sanitize_text_field( $value ?? $current );

		if ( ! in_array( $value, array( 'automatic', 'automatic_async', 'manual' ), true ) ) {
			return $current;
		}

		return $value;
	}
}
