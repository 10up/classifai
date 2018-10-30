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
	}

	/**
	 * Always enabled by default. TODO: capabilities
	 */
	public function can_register() {
		return true;
	}

	/**
	 * If current post type support is enabled in Klasifai settings, it
	 * is tagged using the IBM Watson classification result.
	 *
	 * @param int $post_id The post that was saved
	 */
	function did_save_post( $post_id ) {
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
		//error_log( var_export( $output, true ) );
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

}
