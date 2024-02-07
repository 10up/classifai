<?php

namespace Classifai\Providers\Watson;

use Classifai\Providers\Watson\PostClassifier;
use Classifai\Features\Classification;

use function Classifai\get_classification_feature_enabled;
use function Classifai\get_classification_feature_taxonomy;

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
		add_action( 'save_post', [ $this, 'did_save_post' ] );
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

		$supported     = ( new Classification() )->get_supported_post_types();
		$post_type     = get_post_type( $post_id );
		$post_status   = get_post_status( $post_id );
		$post_statuses = ( new Classification() )->get_supported_post_statuses();

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
			if ( 'no' === get_post_meta( $post_id, '_classifai_process_content', true ) ) {
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
			if ( get_classification_feature_enabled( 'category' ) ) {
				wp_delete_object_term_relationships( $post_id, get_classification_feature_taxonomy( 'category' ) );
			}

			if ( get_classification_feature_enabled( 'keyword' ) ) {
				wp_delete_object_term_relationships( $post_id, get_classification_feature_taxonomy( 'keyword' ) );
			}

			if ( get_classification_feature_enabled( 'concept' ) ) {
				wp_delete_object_term_relationships( $post_id, get_classification_feature_taxonomy( 'concept' ) );
			}

			if ( get_classification_feature_enabled( 'entity' ) ) {
				wp_delete_object_term_relationships( $post_id, get_classification_feature_taxonomy( 'entity' ) );
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
}
