<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\OpenAI\Embeddings as OpenAIEmbeddings;
use Classifai\Providers\Azure\Embeddings as AzureEmbeddings;
use WP_Error;
use WP_Query;

use function Classifai\is_elasticpress_installed;

/**
 * Class Smart404
 */
class Smart404 extends Feature {

	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_smart_404';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Smart 404', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			OpenAIEmbeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
			AzureEmbeddings::ID  => __( 'Azure OpenAI Embeddings', 'classifai' ),
		];
	}

	/**
	 * Setup any needed integrations.
	 *
	 * This will always fire even if the Feature is not enabled
	 * so we add our own check.
	 */
	public function setup() {
		// Ensure ElasticPress is installed before we proceed.
		if ( ! is_elasticpress_installed() ) {
			$warning_notice_func = function ( $current_feature ) {
				if ( self::ID !== $current_feature ) {
					return;
				}

				echo '<style>.classifai-nlu-sections .submit {display:none;}</style>';
				?>
				<h2 class="notice notice-error">
					<p><?php esc_html_e( 'The Smart 404 Feature requires the ElasticPress plugin to be installed and active prior to use.', 'classifai' ); ?></p>
				</h2>
				<?php
			};
			add_action( 'classifai_before_feature_settings_form', $warning_notice_func );
			add_action( 'classifai_before_onboarding_feature_settings_form', $warning_notice_func );
			return;
		}

		parent::setup();

		if ( $this->is_configured() && $this->is_enabled() ) {
			$integration = new Smart404EPIntegration( $this->get_feature_provider_instance() );
			$integration->init();
		}
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Adds a Smart 404 component that can be used to recommend content on a 404 page.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings = $this->get_settings();

		add_settings_field(
			'num',
			esc_html__( 'Number of posts to show', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'num',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $settings['num'],
				'description'   => __( 'Determines the maximum number of posts that will show on a 404 page. This can be overridden in the display functions.', 'classifai' ),
			]
		);

		add_settings_field(
			'num_search',
			esc_html__( 'Number of posts to search', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'num_search',
				'input_type'    => 'number',
				'min'           => 1,
				'step'          => 1,
				'default_value' => $settings['num_search'],
				'description'   => __( 'Determines the maximum number of posts Elasticsearch will use for the vector search. A higher number can give more accurate results but will be slower. This can be overridden in the display functions.', 'classifai' ),
			]
		);

		add_settings_field(
			'threshold',
			esc_html__( 'Threshold', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'threshold',
				'input_type'    => 'number',
				'min'           => 0,
				'step'          => 0.01,
				'default_value' => $settings['threshold'],
				'description'   => __( 'Set the minimum threshold we want for our results. Any result that falls below this number will be automatically removed.', 'classifai' ),
			]
		);

		add_settings_field(
			'rescore',
			esc_html__( 'Use rescore query', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'rescore',
				'input_type'    => 'checkbox',
				'default_value' => $settings['rescore'],
				'description'   => __( 'Will run a normal Elasticsearch query and then rescore those results using a vector query. Can give better results but often results in worse performance. This can be overridden in the display functions', 'classifai' ),
			]
		);

		add_settings_field(
			'fallback',
			esc_html__( 'Use fallback results', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'fallback',
				'input_type'    => 'checkbox',
				'default_value' => $settings['fallback'],
				'description'   => __( 'If no results are found in Elasticsearch, will fallback to displaying most recent results from WordPress. This can be overridden in the display functions', 'classifai' ),
			]
		);

		add_settings_field(
			'score_function',
			esc_html__( 'Score function', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'score_function',
				'options'       => [
					'cosine'      => __( 'Cosine', 'classifai' ),
					'dot_product' => __( 'Dot Product', 'classifai' ),
					'l1_norm'     => __( 'L1 Norm', 'classifai' ),
					'l2_norm'     => __( 'L2 Norm', 'classifai' ),
				],
				'default_value' => $settings['score_function'],
				'description'   => __( 'Choose which vector scoring function you want to use. You may need to adjust the threshold if you change this. This can be overridden in the display functions', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the default settings for the Feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider'       => OpenAIEmbeddings::ID,
			'num'            => 3,
			'num_search'     => 5000,
			'threshold'      => 2.35,
			'rescore'        => 0,
			'fallback'       => 1,
			'score_function' => 'cosine',
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings = $this->get_settings();

		$new_settings['num']        = absint( $new_settings['num'] ?? $settings['num'] );
		$new_settings['num_search'] = absint( $new_settings['num_search'] ?? $settings['num_search'] );
		$new_settings['threshold']  = floatval( $new_settings['threshold'] ?? $settings['threshold'] );

		if ( empty( $new_settings['rescore'] ) || 1 !== (int) $new_settings['rescore'] ) {
			$new_settings['rescore'] = 'no';
		} else {
			$new_settings['rescore'] = '1';
		}

		if ( empty( $new_settings['fallback'] ) || 1 !== (int) $new_settings['fallback'] ) {
			$new_settings['fallback'] = 'no';
		} else {
			$new_settings['fallback'] = '1';
		}

		if ( isset( $new_settings['score_function'] ) && in_array( $new_settings['score_function'], [ 'cosine', 'dot_product', 'l1_norm', 'l2_norm' ], true ) ) {
			$new_settings['score_function'] = sanitize_text_field( $new_settings['score_function'] );
		} else {
			$new_settings['score_function'] = 'cosine';
		}

		return $new_settings;
	}

	/**
	 * Run an exact k-NN search.
	 *
	 * @param string $query Query to search for.
	 * @param array  $args Arguments to pass to the search.
	 * @return array|WP_Error
	 */
	public function exact_knn_search( string $query, array $args = [] ) {
		// Ensure the Feature is enabled and configured before trying to use it.
		if ( ! is_elasticpress_installed() || ! $this->is_configured() || ! $this->is_enabled() ) {
			return new WP_Error( 'not_enabled', __( 'Feature is not enabled.', 'classifai' ) );
		}

		// Ensure we have a query.
		if ( empty( $query ) ) {
			return new WP_Error( 'no_query', __( 'No query provided.', 'classifai' ) );
		}

		$settings = $this->get_settings();

		// Parse the arguments, setting our defaults.
		$args = wp_parse_args(
			$args,
			[
				'index'          => 'post',
				'post_type'      => [ 'post' ],
				'num'            => $settings['num'] ?? 5,
				'num_candidates' => $settings['num_search'] ?? 5000,
				'rescore'        => $settings['rescore'] ?? '1',
				'fallback'       => $settings['fallback'] ?? '1',
				'score_function' => $settings['score_function'] ?? 'cosine',
			]
		);

		/**
		 * Filter the arguments before running the search.
		 *
		 * @hook classifai_smart_404_exact_knn_search_args
		 *
		 * @param array $args Arguments to pass to the search.
		 * @param string $query Query to search for.
		 */
		$args = apply_filters( 'classifai_smart_404_exact_knn_search_args', $args, $query );

		// Ensure our post types are set as an array.
		if ( ! is_array( $args['post_type'] ) ) {
			$args['post_type'] = [ $args['post_type'] ];
		}

		$integration = new Smart404EPIntegration( $this->get_feature_provider_instance() );

		// Run our search. Note that this will take our query and generate embeddings for it.
		if ( 'no' === $args['rescore'] || false === $args['rescore'] ) {
			$results = $integration->exact_knn_search( $query, $args );
		} else {
			$results = $integration->search_rescored_by_exact_knn( $query, $args );
		}

		// Ensure we have a good response.
		if ( is_wp_error( $results ) ) {
			// If we have fallback enabled, return those results.
			if ( 'no' !== $args['fallback'] && false !== $args['fallback'] ) {
				return $this->fallback_results( $args );
			}

			// translators: %s is the error message.
			return new WP_Error( 'error', sprintf( __( 'Error making request: %s.', 'classifai' ), $results->get_error_message() ) );
		}

		// Filter out any results that are below a certain score.
		$results = array_filter(
			$results,
			function ( $result ) use ( $settings ) {
				return (float) $result['score'] >= $settings['threshold'] ?? 2.35;
			}
		);

		// If we have no results after filtering and fallback is enabled, return those results.
		if ( empty( $results ) && ( 'no' !== $args['fallback'] && false !== $args['fallback'] ) ) {
			return $this->fallback_results( $args );
		}

		return $results;
	}

	/**
	 * Run a fallback WordPress query for most recent results.
	 *
	 * @param array $args Arguments to pass to the search.
	 * @return array|WP_Error
	 */
	public function fallback_results( array $args = [] ) {
		// Ensure the Feature is enabled and configured before trying to use it.
		if ( ! $this->is_configured() || ! $this->is_enabled() ) {
			return new WP_Error( 'not_enabled', __( 'Feature is not enabled.', 'classifai' ) );
		}

		$settings = $this->get_settings();

		// Parse the arguments, setting our defaults.
		$args = wp_parse_args(
			$args,
			[
				'num' => $settings['num'] ?? 5,
			]
		);

		// Run our query.
		$results = new WP_Query(
			[
				'post_type'      => 'post',
				'posts_per_page' => $args['num'],
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		// Ensure we have some results.
		if ( ! $results->have_posts() ) {
			return new WP_Error( 'no_results', __( 'No results found.', 'classifai' ) );
		}

		return $results->posts;
	}
}
