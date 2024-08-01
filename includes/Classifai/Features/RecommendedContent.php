<?php

namespace Classifai\Features;

use Classifai\Services\Personalizer as PersonalizerService;
use Classifai\Providers\Azure\Personalizer as PersonalizerProvider;
use Classifai\Providers\AWS\AmazonPersonalize as PersonalizeProvider;
use Classifai\Blocks;

/**
 * Class RecommendedContent
 */
class RecommendedContent extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_recommended_content';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Recommended Content', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( PersonalizerService::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			PersonalizeProvider::ID  => __( 'Amazon AWS Personalize', 'classifai' ),
			PersonalizerProvider::ID => __( 'Microsoft Azure AI Personalizer', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 *
	 * This only runs if is_feature_enabled() returns true.
	 */
	public function feature_setup() {
		// Register the block.
		Blocks\setup();

		// AJAX callback for rendering recommended content.
		add_action( 'wp_ajax_classifai_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );
		add_action( 'wp_ajax_nopriv_classifai_render_recommended_content', [ $this, 'ajax_render_recommended_content' ] );

		add_action( 'save_post', [ $this, 'maybe_clear_transient' ] );
	}

	/**
	 * Render recommended content over AJAX.
	 */
	public function ajax_render_recommended_content() {
		check_ajax_referer( 'classifai-recommended-block', 'security' );

		if ( ! isset( $_POST['contentPostType'] ) || empty( $_POST['contentPostType'] ) ) {
			esc_html_e( 'No results found.', 'classifai' );
			exit();
		}

		$attributes = [
			'displayLayout'          => isset( $_POST['displayLayout'] ) ? sanitize_text_field( wp_unslash( $_POST['displayLayout'] ) ) : 'grid',
			'contentPostType'        => sanitize_text_field( wp_unslash( $_POST['contentPostType'] ) ),
			'excludeId'              => isset( $_POST['excludeId'] ) ? absint( $_POST['excludeId'] ) : 0,
			'displayPostExcerpt'     => isset( $_POST['displayPostExcerpt'] ) ? filter_var( wp_unslash( $_POST['displayPostExcerpt'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayAuthor'          => isset( $_POST['displayAuthor'] ) ? filter_var( wp_unslash( $_POST['displayAuthor'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayPostDate'        => isset( $_POST['displayPostDate'] ) ? filter_var( wp_unslash( $_POST['displayPostDate'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'displayFeaturedImage'   => isset( $_POST['displayFeaturedImage'] ) ? filter_var( wp_unslash( $_POST['displayFeaturedImage'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : true,
			'addLinkToFeaturedImage' => isset( $_POST['addLinkToFeaturedImage'] ) ? filter_var( wp_unslash( $_POST['addLinkToFeaturedImage'] ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : false,
			'columns'                => isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 3,
			'numberOfItems'          => isset( $_POST['numberOfItems'] ) ? absint( $_POST['numberOfItems'] ) : 3,
		];

		if ( isset( $_POST['taxQuery'] ) && ! empty( $_POST['taxQuery'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			foreach ( $_POST['taxQuery'] as $key => $value ) {
				$attributes['taxQuery'][ $key ] = array_map( 'absint', $value );
			}
		}

		$provider_instance = $this->get_feature_provider_instance();

		echo $provider_instance->render_recommended_content( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit();
	}

	/**
	 * Maybe clear transients for recent actions.
	 *
	 * @param int $post_id Post Id.
	 */
	public function maybe_clear_transient( int $post_id ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transients = $wpdb->get_col( $wpdb->prepare( "SELECT `option_name` FROM {$wpdb->options} WHERE  option_name LIKE %s", '_transient_classifai_actions_' . $post_type . '%' ) );

		// Delete all transients
		if ( ! empty( $transients ) ) {
			foreach ( $transients as $transient ) {
				delete_transient( str_replace( '_transient_', '', $transient ) );
			}
		}
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables the ability to generate recommended content data for the block.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => PersonalizerProvider::ID,
		];
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? PersonalizerProvider::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( PersonalizerProvider::ID === $provider_instance::ID ) {
			/** @var PersonalizerProvider $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'personalizer_send_reward' ],
				[ ...$args ]
			);
		}
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_personalizer', array() );
		$new_settings = $this->get_default_settings();

		if ( isset( $old_settings['enable_recommended_content'] ) ) {
			$new_settings['status'] = $old_settings['enable_recommended_content'];
		}

		$new_settings['provider'] = PersonalizerProvider::ID;

		if ( isset( $old_settings['url'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['endpoint_url'] = $old_settings['url'];
		}

		if ( isset( $old_settings['api_key'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['api_key'] = $old_settings['api_key'];
		}

		if ( isset( $old_settings['authenticated'] ) ) {
			$new_settings[ PersonalizerProvider::ID ]['authenticated'] = $old_settings['authenticated'];
		}

		if ( isset( $old_settings['recommended_content_roles'] ) ) {
			$new_settings['roles'] = $old_settings['recommended_content_roles'];
		}

		if ( isset( $old_settings['recommended_content_users'] ) ) {
			$new_settings['users'] = $old_settings['recommended_content_users'];
		}

		if ( isset( $old_settings['recommended_content_user_based_opt_out'] ) ) {
			$new_settings['user_based_opt_out'] = $old_settings['recommended_content_user_based_opt_out'];
		}

		return $new_settings;
	}
}
