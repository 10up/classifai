<?php

namespace Classifai\Features;

use Classifai\Services\ContentRecommendation as ContentRecommendationService;
use Classifai\Providers\OpenAI\Embeddings as OpenAIEmbeddings;

use function Classifai\get_asset_info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		$this->provider_instances = $this->get_provider_instances( ContentRecommendationService::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			OpenAIEmbeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
		];
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {
		$settings = $this->get_settings();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_feature_setting_assets' ] );

		if ( isset( $settings['provider'] ) && OpenAIEmbeddings::ID === $settings['provider'] ) {
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
			add_filter( 'pre_render_block', [ $this, 'pre_render_block' ], 10, 2 );
			add_filter( 'rest_post_query', [ $this, 'modify_block_query_vars_rest' ], 10, 2 );
		}
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
			'provider'         => OpenAIEmbeddings::ID,
			'default_template' => 'title-date',
		];
	}

	/**
	 * Enqueues feature-level assets.
	 */
	public function enqueue_feature_setting_assets() {
		wp_enqueue_script(
			'classifai-plugin-recommended-content-feature-fields',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-recommended-content-feature-fields.js',
			get_asset_info( 'classifai-plugin-recommended-content-feature-fields', 'dependencies' ),
			get_asset_info( 'classifai-plugin-recommended-content-feature-fields', 'version' ),
			true
		);
	}

	/**
	 * Enqueue editor assets.
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'classifai-recommended-content-block-variation',
			CLASSIFAI_PLUGIN_URL . 'dist/recommended-content-block-variation.js',
			get_asset_info( 'recommended-content-block-variation', 'dependencies' ),
			get_asset_info( 'recommended-content-block-variation', 'version' ),
			true
		);

		$settings = $this->get_settings();
		$data     = [
			'default_template' => $settings['default_template'],
		];

		wp_add_inline_script(
			'classifai-recommended-content-block-variation',
			sprintf(
				'const classifaiRecommendedContentSettings = %s',
				wp_json_encode( $data )
			)
		);
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
		if (
			isset( $block['attrs']['namespace'] ) &&
			'classifai/recommended-content' === $block['attrs']['namespace']
		) {
			add_filter( 'query_loop_block_query_vars', [ $this, 'modify_block_query_vars_front_end' ] );
		}

		return $pre_render;
	}

	/**
	 * Modify the Recommended Content block query vars.
	 *
	 * This is applied to the front-end rendering of the block.
	 *
	 * @param array $query_vars The current query vars.
	 * @return array
	 */
	public function modify_block_query_vars_front_end( $query_vars ) {
		if ( is_admin() ) {
			return $query_vars;
		}

		$query_vars = $this->modify_block_query_vars( $query_vars );

		return $query_vars;
	}

	/**
	 * Modify the Recommended Content block query vars.
	 *
	 * This is applied to the REST request the block makes
	 * in the editor.
	 *
	 * @param array            $query_vars The current query vars.
	 * @param \WP_REST_Request $request The REST request.
	 * @return array
	 */
	public function modify_block_query_vars_rest( $query_vars, $request ) {
		if ( ! (bool) $request->get_param( 'useAI' ) ) {
			return $query_vars;
		}

		// Get the post ID from the request.
		$referer = $request->get_header( 'referer' );
		$post_id = 0;

		if ( $referer ) {
			$query = wp_parse_url( $referer, PHP_URL_QUERY );

			if ( $query ) {
				parse_str( $query, $parts );

				if ( isset( $parts['post'] ) ) {
					$post_id = (int) $parts['post'];
				}
			}
		}

		$query_vars = $this->modify_block_query_vars( $query_vars, $post_id );

		return $query_vars;
	}

	/**
	 * Modify the Recommended Content block query vars.
	 *
	 * @param array $query_vars The current query vars.
	 * @param int   $post_id The post ID.
	 * @return array
	 */
	public function modify_block_query_vars( array $query_vars = [], int $post_id = 0 ) {
		$post_id           = ! $post_id ? get_the_ID() : $post_id;
		$post__in          = [];
		$count             = 0;
		$provider_instance = $this->get_feature_provider_instance();

		switch ( $provider_instance::ID ) {
			case OpenAIEmbeddings::ID:
				// Get embeddings for the current post.
				/** @var OpenAIEmbeddings $provider_instance */
				$embeddings = $provider_instance->generate_embeddings_for_post( $post_id, false, $this );

				if ( ! empty( $embeddings ) && ! is_wp_error( $embeddings ) ) {
					// Get the posts that are similar to the current post.
					/** @var OpenAIEmbeddings $provider_instance */
					$results = $provider_instance->get_posts( $embeddings, $post_id );

					if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
						// Loop through the results and add them to the post__in array.
						foreach ( $results as $result ) {
							// If we have reached the max number of posts, break out of the loop.
							if ( $count >= $query_vars['posts_per_page'] ) {
								break;
							}

							// Skip the current item or items we've already added.
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

		// If we have no matches, remove the current post from the query
		// but otherwise keep the query as is.
		if ( empty( $post__in ) ) {
			$query_vars['post__not_in'] = [ $post_id ];
			return $query_vars;
		}

		$post__in   = array_unique( $post__in );
		$post_count = count( $post__in );

		// Backfill if we have less than the requested number of posts.
		if ( $post_count < $query_vars['posts_per_page'] ) {
			$new_query_vars = $query_vars;

			// Run a query using the current query vars to get more posts.
			$backfill_query = new \WP_Query(
				array_merge(
					$new_query_vars,
					[
						'posts_per_page' => (int) $query_vars['posts_per_page'] - $post_count,
						'post__not_in'   => [ $post_id ],
						'fields'         => 'ids',
					]
				)
			);

			// Add the backfilled posts to the post__in array.
			$post__in   = array_merge( $post__in, $backfill_query->posts );
			$post_count = count( $post__in );
		}

		// Add the post IDs we want to our query.
		$query_vars = array_merge(
			$query_vars,
			[
				'posts_per_page' => $post_count,
				'post__in'       => $post__in,
				'orderby'        => 'post__in',
			]
		);

		return $query_vars;
	}

	/**
	 * Get status of embeddings generation process.
	 *
	 * @return bool
	 */
	public function is_embeddings_generation_in_progress(): bool {
		$is_in_progress    = false;
		$provider_instance = $this->get_feature_provider_instance();

		if ( $provider_instance && method_exists( $provider_instance, 'is_embeddings_generation_in_progress' ) ) {
			$is_in_progress = $provider_instance->is_embeddings_generation_in_progress();
		}

		return $is_in_progress;
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
