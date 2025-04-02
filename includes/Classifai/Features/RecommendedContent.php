<?php

namespace Classifai\Features;

use Classifai\Services\Personalizer as PersonalizerService;
use Classifai\Providers\Azure\Personalizer as PersonalizerProvider;
use Classifai\Providers\OpenAI\Embeddings as OpenAIEmbeddings;

use function Classifai\get_asset_info;

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
			OpenAIEmbeddings::ID     => __( 'OpenAI Embeddings', 'classifai' ),
			PersonalizerProvider::ID => __( 'Microsoft Azure AI Personalizer', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_filter( 'pre_render_block', [ $this, 'pre_render_block' ], 10, 2 );
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables a recommended content block.', 'classifai' );
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider' => OpenAIEmbeddings::ID,
		];
	}

	/**
	 * Enqueue editor assets.
	 */
	public function enqueue_editor_assets() {
		$settings = $this->get_settings();

		if ( isset( $settings['provider'] ) && OpenAIEmbeddings::ID === $settings['provider'] ) {
			wp_enqueue_script(
				'classifai-plugin-classification-ibm-watson-js',
				CLASSIFAI_PLUGIN_URL . 'dist/recommended-content-block-variation.js',
				get_asset_info( 'recommended-content-block-variation', 'dependencies' ),
				get_asset_info( 'recommended-content-block-variation', 'version' ),
				true
			);
		}
	}

	/**
	 * Add filter to modify the Recommended Content block query vars.
	 *
	 * @param string $pre_render The pre-rendered block content.
	 * @param array  $block The block data.
	 * @return string
	 */
	public function pre_render_block( $pre_render, $block ) {
		// Add our filter if this is the recommended content block.
		// and we have the proper Provider selected.
		if (
			isset( $block['attrs']['namespace'] ) &&
			'classifai/recommended-content' === $block['attrs']['namespace']
		) {
			$settings = $this->get_settings();

			if ( isset( $settings['provider'] ) && OpenAIEmbeddings::ID === $settings['provider'] ) {
				add_filter( 'query_loop_block_query_vars', [ $this, 'modify_block_query_vars' ] );
			}
		}

		return $pre_render;
	}

	/**
	 * Modify the Recommended Content block query vars.
	 *
	 * @param array $query_vars The current query vars.
	 * @return array
	 */
	public function modify_block_query_vars( $query_vars ) {
		$post_id           = get_the_ID();
		$post__in          = [];
		$count             = 0;
		$provider_instance = $this->get_feature_provider_instance();
		$cache_key         = 'classifai_recommended_content_' . $post_id;

		switch ( $provider_instance::ID ) {
			case OpenAIEmbeddings::ID:
				// Use cached results if they exist.
				$post__in = wp_cache_get( $cache_key );

				if ( is_array( $post__in ) ) {
					break;
				} else {
					$post__in = [];
				}

				// Get embeddings for the current post.
				/** @var OpenAIEmbeddings $provider_instance */
				$embeddings = $provider_instance->generate_embeddings_for_post( $post_id, false, $this );

				if ( ! empty( $embeddings ) && ! is_wp_error( $embeddings ) ) {
					// Get the posts that are similar to the current post.
					/** @var OpenAIEmbeddings $provider_instance */
					$results = $provider_instance->get_posts( $embeddings );

					if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
						// Loop through the results and add them to the post__in array.
						foreach ( $results as $result ) {
							if ( $count >= $query_vars['posts_per_page'] ) {
								break;
							}

							if (
								$result['post_id'] === $post_id ||
								in_array( $result['post_id'], $post__in, true )
							) {
								continue;
							}

							$post__in[] = $result['post_id'];

							++$count;
						}
					}
				}

				break;
		}

		// If we have no matches, don't modify the query.
		if ( empty( $post__in ) ) {
			return $query_vars;
		}

		// Cache the results.
		wp_cache_set( $cache_key, $post__in, '', 60 * MINUTE_IN_SECONDS );

		// Add the post IDs we want to our query.
		$query_vars = array_merge(
			$query_vars,
			[
				'posts_per_page' => count( $post__in ),
				'post__in'       => array_unique( $post__in ),
			]
		);

		return $query_vars;
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
