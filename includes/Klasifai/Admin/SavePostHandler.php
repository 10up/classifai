<?php

namespace Klasifai\Admin;

/**
 * Classifies Posts based on the current Klasifai configuration.
 */
class SavePostHandler {

	/**
	 * Lazy loaded classifier object
	 */
	public $classifier;

	/**
	 * Enables the classification on save post behaviour.
	 */
	public function register() {
		add_action( 'save_post', [ $this, 'did_save_post' ] );
		add_action( 'admin_notices', [ $this, 'show_error_if' ] );
	}

	/**
	 * Save Post handler only runs on admin or REST requests
	 */
	public function can_register() {
		if ( is_admin() ) {
			return true;
		} else if ( $this->is_rest_route() ) {
			return true;
		} else if ( defined( 'PHPUNIT_RUNNER' ) && PHPUNIT_RUNNER ) {
			return false;
		} else if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		} else {
			return false;
		}
	}

	/**
	 * If current post type support is enabled in Klasifai settings, it
	 * is tagged using the IBM Watson classification result.
	 *
	 * Skips classification if running under the Gutenberg Metabox
	 * compatibility request. The classification is performed during the REST
	 * lifecyle when using Gutenberg.
	 *
	 * @param int $post_id The post that was saved
	 */
	function did_save_post( $post_id ) {
		if ( ! empty( $_GET['classic-editor'] ) ) {
			return;
		}

		$supported   = \Klasifai\get_supported_post_types();
		$post_type   = get_post_type( $post_id );
		$post_status = get_post_status( $post_id );

		if ( $post_status === 'publish' && in_array( $post_type, $supported ) ) {
			$this->classify( $post_id );
		}
	}

	/**
	 * Classifies the post specified with the PostClassifier object.
	 * Existing terms relationships are removed before classification.
	 *
	 * @param int $post_id the post to classify & link
	 */
	function classify( $post_id ) {
		//error_log( 'classify: ' . $post_id );
		$classifier = $this->get_classifier();

		if ( \Klasifai\get_feature_enabled( 'category' ) ) {
			wp_delete_object_term_relationships( $post_id, \Klasifai\get_feature_taxonomy( 'category' ) );
		}

		if ( \Klasifai\get_feature_enabled( 'keyword' ) ) {
			wp_delete_object_term_relationships( $post_id, \Klasifai\get_feature_taxonomy( 'keyword' ) );
		}

		if ( \Klasifai\get_feature_enabled( 'concept' ) ) {
			wp_delete_object_term_relationships( $post_id, \Klasifai\get_feature_taxonomy( 'concept' ) );
		}

		if ( \Klasifai\get_feature_enabled( 'entity' ) ) {
			wp_delete_object_term_relationships( $post_id, \Klasifai\get_feature_taxonomy( 'entity' ) );
		}

		$output = $classifier->classify_and_link( $post_id );

		if ( is_wp_error( $output ) ) {
			update_post_meta( $post_id, '_klasifai_error', [
				'code'    => $output->get_error_code(),
				'message' => $output->get_error_message(),
			] );
		}

		return $output;
	}

	/**
	 * Lazy initializes the Post Classifier object
	 */
	function get_classifier() {
		if ( is_null( $this->classifier ) ) {
			$this->classifier = new \Klasifai\PostClassifier();
		}

		return $this->classifier;
	}

	/**
	 * Outputs an Admin Notice with the error message if NLU
	 * classification had failed earlier.
	 */
	function show_error_if() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		$post_id = $post->ID;

		if ( empty( $post_id ) ) {
			return;
		}

		$error = get_post_meta( $post_id, '_klasifai_error', true );

		if ( ! empty( $error ) ) {
			delete_post_meta( $post_id, '_klasifai_error' );

			$code    = ! empty( $error['code'] ) ? $error['code'] : 500;
			$message = ! empty( $error['message'] ) ? $error['message'] : 'Unknown NLU API error';

		?>
		<div class="notice notice-error is-dismissible">
			<p>
				Error: Failed to classify content with the IBM Watson NLU API.
			</p>
			<p>
				<?php echo esc_html( $code ); ?>
				-
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
		}
	}

	function is_rest_route() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		} else if ( ! empty( $_GET['rest_route'] ) ) {
			return true;
		} else {
			return false;
		}
	}

}
