<?php

namespace Classifai\Providers\Watson;

use Classifai\Providers\Watson\PostClassifier;

/**
 * Classifies Posts based on the current Watson configuration.
 */
class SavePostHandler {

	/**
	 * @var PostClassifier $classifier Lazy loaded classifier object
	 */
	public $classifier;

	/**
	 * Enables the classification on save post behaviour.
	 */
	public function register() {
		add_filter( 'removable_query_args', [ $this, 'classifai_removable_query_args' ] );
		add_filter( 'default_post_metadata', [ $this, 'default_post_metadata' ], 10, 3 );
		add_action( 'save_post', [ $this, 'did_save_post' ] );
		add_action( 'admin_notices', [ $this, 'show_error_if' ] );
		add_action( 'admin_post_classifai_classify_post', array( $this, 'classifai_classify_post' ) );
	}

	/**
	 * Check to see if we can register this class.
	 *
	 * @return bool
	 */
	public function can_register(): bool {

		$should_register = false;
		if ( $this->is_configured() && ( is_admin() || $this->is_rest_route() ) ) {
			$should_register = true;
		}

		/**
		 * Filter whether ClassifAI should register this class.
		 *
		 * @since 1.8.0
		 * @hook classifai_should_register_save_post_handler
		 *
		 * @param  {bool} $should_register Whether the class should be registered.
		 * @return {bool} Whether the class should be registered.
		 */
		$should_register = apply_filters( 'classifai_should_register_save_post_handler', $should_register );

		return $should_register;
	}

	/**
	 * Check if ClassifAI is properly configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return ! empty( get_option( 'classifai_configured' ) ) && ! empty( get_option( 'classifai_watson_nlu' )['credentials']['watson_url'] );
	}

	/**
	 * Sets the default value for the _classifai_process_content meta key.
	 *
	 * @param mixed  $value     The value get_metadata() should return - a single metadata value,
	 *                          or an array of values.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @return mixed
	 */
	public function default_post_metadata( $value, int $object_id, string $meta_key ) {
		if ( '_classifai_process_content' === $meta_key ) {
			if ( 'automatic_classification' === get_classification_mode() ) {
				return 'yes';
			} else {
				return 'no';
			}
		}

		return $value;
	}

	/**
	 * If current post type support is enabled in ClassifAI settings, it
	 * is tagged using the IBM Watson classification result.
	 *
	 * Skips classification if running under the Gutenberg Metabox
	 * compatibility request. The classification is performed during the REST
	 * lifecycle when using Gutenberg.
	 *
	 * @param int $post_id The post that was saved
	 */
	public function did_save_post( int $post_id ) {
		if ( ! empty( $_GET['classic-editor'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$supported     = get_supported_post_types();
		$post_type     = get_post_type( $post_id );
		$post_status   = get_post_status( $post_id );
		$post_statuses = get_supported_post_statuses();

		/**
		 * Filter post statuses for post type or ID.
		 *
		 * @since 1.7.1
		 * @hook classifai_post_statuses_for_post_type_or_id
		 *
		 * @param {array} $post_statuses Array of post statuses to be classified with language processing.
		 * @param {string} $post_type The post type.
		 * @param {int} $post_id The post ID.
		 *
		 * @return {array} Array of post statuses.
		 */
		$post_statuses = apply_filters( 'classifai_post_statuses_for_post_type_or_id', $post_statuses, $post_type, $post_id );

		// Process posts in allowed post statuses, supported items and only if features are enabled
		if ( in_array( $post_status, $post_statuses, true ) && in_array( $post_type, $supported, true ) ) {
			// Check if processing content on save is disabled.
			$classifai_process_content = get_post_meta( $post_id, '_classifai_process_content', true );
			if ( 'no' === $classifai_process_content ) {
				return;
			}
			$this->classify( $post_id );
		}
	}

	/**
	 * Classifies the post specified with the PostClassifier object.
	 * Existing terms relationships are removed before classification.
	 *
	 * @param int  $post_id the post to classify & link.
	 * @param bool $link_terms Whether to link the terms to the post.
	 * @return object|bool
	 */
	public function classify( int $post_id, bool $link_terms = true ) {
		/**
		 * Filter whether ClassifAI should classify a post.
		 *
		 * Default is true, return false to skip classifying a post.
		 *
		 * @since 1.2.0
		 * @hook classifai_should_classify_post
		 *
		 * @param {bool} $should_classify Whether the post should be classified. Default `true`, return `false` to skip
		 *                                classification for this post.
		 * @param {int}  $post_id         The ID of the post to be considered for classification.
		 *
		 * @return {bool} Whether the post should be classified.
		 */
		$classifai_should_classify_post = apply_filters( 'classifai_should_classify_post', true, $post_id );
		if ( ! $classifai_should_classify_post ) {
			return false;
		}

		$classifier = $this->get_classifier();

		if ( $link_terms ) {
			if ( get_feature_enabled( 'category' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'category' ) );
			}

			if ( get_feature_enabled( 'keyword' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'keyword' ) );
			}

			if ( get_feature_enabled( 'concept' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'concept' ) );
			}

			if ( get_feature_enabled( 'entity' ) ) {
				wp_delete_object_term_relationships( $post_id, get_feature_taxonomy( 'entity' ) );
			}
		}

		$output = $classifier->classify_and_link( $post_id, [], $link_terms );

		if ( is_wp_error( $output ) ) {
			update_post_meta(
				$post_id,
				'_classifai_error',
				wp_json_encode(
					[
						'code'    => $output->get_error_code(),
						'message' => $output->get_error_message(),
					]
				)
			);
		} else {
			// If there is no error, clear any existing error states.
			delete_post_meta( $post_id, '_classifai_error' );
		}

		return $output;
	}

	/**
	 * Lazy initializes the Post Classifier object.
	 *
	 * @return PostClassifier
	 */
	public function get_classifier(): PostClassifier {
		if ( is_null( $this->classifier ) ) {
			$this->classifier = new PostClassifier();
		}

		return $this->classifier;
	}

	/**
	 * Outputs an Admin Notice with the error message if NLU
	 * classification had failed earlier.
	 */
	public function show_error_if() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		$post_id = $post->ID;

		if ( empty( $post_id ) ) {
			return;
		}

		$error = get_post_meta( $post_id, '_classifai_error', true );

		if ( ! empty( $error ) ) {
			delete_post_meta( $post_id, '_classifai_error' );
			$error   = (array) json_decode( $error );
			$code    = ! empty( $error['code'] ) ? $error['code'] : 500;
			$message = ! empty( $error['message'] ) ? $error['message'] : 'Unknown NLU API error';

			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php esc_html_e( 'Error: Failed to classify content with the IBM Watson NLU API.', 'classifai' ); ?>
				</p>
				<p>
					<?php echo esc_html( $code ); ?>
					-
					<?php echo esc_html( $message ); ?>
				</p>
			</div>
			<?php
		}

		// Display classify post success message for manually classified post.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$classified = isset( $_GET['classifai_classify'] ) ? intval( wp_unslash( $_GET['classifai_classify'] ) ) : 0;
		if ( 1 === $classified ) {
			$post_type       = get_post_type_object( get_post_type( $post ) );
			$post_type_label = esc_html__( 'Post', 'classifai' );
			if ( $post_type ) {
				$post_type_label = $post_type->labels->singular_name;
			}
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					// translators: %s is post type label.
					printf( esc_html__( '%s classified successfully.', 'classifai' ), esc_html( $post_type_label ) );
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * We need to determine if we're doing a REST call.
	 *
	 * @return bool
	 */
	public function is_rest_route(): bool {

		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		/**
		 * Filter the REST bases. Supports custom post types with a custom REST base.
		 *
		 * @since 1.5.0
		 * @hook classifai_rest_bases
		 *
		 * @param {array} rest_bases Array of REST bases.
		 *
		 * @return {array} The filtered array of REST bases.
		 */
		$rest_bases = apply_filters( 'classifai_rest_bases', array( 'posts', 'pages' ) );

		foreach ( $rest_bases as $rest_base ) {
			if ( false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'wp-json/wp/v2/' . $rest_base ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Classify post manually.
	 */
	public function classifai_classify_post() {
		if ( ! empty( $_GET['classifai_classify_post_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['classifai_classify_post_nonce'] ) ), 'classifai_classify_post_action' ) ) {
			$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
			if ( $post_id ) {
				$result     = $this->classify( $post_id );
				$classified = array();
				if ( ! is_wp_error( $result ) ) {
					$classified = array( 'classifai_classify' => 1 );
				}
				wp_safe_redirect( esc_url_raw( add_query_arg( $classified, get_edit_post_link( $post_id, 'edit' ) ) ) );
				exit();
			}
		} else {
			wp_die( esc_html__( 'You don\'t have permission to perform this operation.', 'classifai' ) );
		}
	}

	/**
	 * Add "classifai_classify" in list of query variable names to remove.
	 *
	 * @param [] $removable_query_args An array of query variable names to remove from a URL.
	 * @return []
	 */
	public function classifai_removable_query_args( array $removable_query_args ): array {
		$removable_query_args[] = 'classifai_classify';
		return $removable_query_args;
	}
}
