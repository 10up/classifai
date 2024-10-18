<?php
/**
 * IBM Watson NLU
 */

namespace Classifai\Providers\Watson;

use Classifai\Providers\Provider;
use Classifai\Taxonomy\TaxonomyFactory;
use Classifai\Features\Classification;
use Classifai\Features\Feature;
use Classifai\Providers\Watson\PostClassifier;
use WP_Error;

use function Classifai\get_classification_feature_taxonomy;

class NLU extends Provider {

	const ID = 'ibm_watson_nlu';

	/**
	 * @var $taxonomy_factory TaxonomyFactory Watson taxonomy factory
	 */
	public $taxonomy_factory;

	/**
	 * NLU features that are supported by this provider
	 *
	 * @var array
	 */
	public $nlu_features = [];

	/**
	 * Watson NLU constructor.
	 *
	 * @param \Classifai\Features\Feature $feature Feature instance (Optional, only required in admin).
	 */
	public function __construct( $feature = null ) {
		$this->feature_instance = $feature;

		$this->nlu_features = [
			'category' => [
				'feature'           => __( 'Category', 'classifai' ),
				'threshold'         => __( 'Category Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Category Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_CATEGORY_THRESHOLD,
				'taxonomy_default'  => WATSON_CATEGORY_TAXONOMY,
			],
			'keyword'  => [
				'feature'           => __( 'Keyword', 'classifai' ),
				'threshold'         => __( 'Keyword Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Keyword Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_KEYWORD_THRESHOLD,
				'taxonomy_default'  => WATSON_KEYWORD_TAXONOMY,
			],
			'entity'   => [
				'feature'           => __( 'Entity', 'classifai' ),
				'threshold'         => __( 'Entity Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Entity Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_ENTITY_THRESHOLD,
				'taxonomy_default'  => WATSON_ENTITY_TAXONOMY,
			],
			'concept'  => [
				'feature'           => __( 'Concept', 'classifai' ),
				'threshold'         => __( 'Concept Threshold (%)', 'classifai' ),
				'taxonomy'          => __( 'Concept Taxonomy', 'classifai' ),
				'threshold_default' => WATSON_CONCEPT_THRESHOLD,
				'taxonomy_default'  => WATSON_CONCEPT_TAXONOMY,
			],
		];
	}

	/**
	 * Renders settings fields for this provider.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_endpoint_url',
			esc_html__( 'API URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'endpoint_url',
				'default_value' => $settings['endpoint_url'],
				'input_type'    => 'text',
				'large'         => true,
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_username',
			esc_html__( 'API Username', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'username',
				'default_value' => $settings['username'],
				'input_type'    => 'text',
				'large'         => true,
				'class'         => 'classifai-provider-field ' . ( $this->use_username_password() ? 'hide-username' : '' ) . ' provider-scope-' . static::ID, // Important to add this.
			]
		);

		add_settings_field(
			static::ID . '_password',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'password',
				'default_value' => $settings['password'],
				'input_type'    => 'password',
				'large'         => true,
				'class'         => 'classifai-provider-field provider-scope-' . static::ID, // Important to add this.
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					sprintf(
						wp_kses(
							/* translators: %1$s is the link to register for an IBM Cloud account, %2$s is the link to setup the NLU service */
							__( 'Don\'t have an IBM Cloud account yet? <a title="Register for an IBM Cloud account" href="%1$s">Register for one</a> and set up a <a href="%2$s">Natural Language Understanding</a> Resource to get your API key.', 'classifai' ),
							[
								'a' => [
									'href'  => [],
									'title' => [],
								],
							]
						),
						esc_url( 'https://cloud.ibm.com/registration' ),
						esc_url( 'https://cloud.ibm.com/catalog/services/natural-language-understanding' )
					),
			]
		);

		add_settings_field(
			static::ID . '_toggle',
			'',
			function ( $args = [] ) {
				printf(
					'<a id="classifai-waston-cred-toggle" href="#" class="%s">%s</a>',
					$args['class'] ? esc_attr( $args['class'] ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$this->use_username_password() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						? esc_html__( 'Use a username/password instead?', 'classifai' )
						: esc_html__( 'Use an API Key instead?', 'classifai' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			},
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'class' => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Modify the default settings for the classification feature.
	 *
	 * @param array   $settings Current settings.
	 * @param Feature $feature_instance The feature instance.
	 * @return array
	 */
	public function modify_default_feature_settings( array $settings, $feature_instance ): array {
		remove_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10 );

		if ( $feature_instance->get_settings( 'provider' ) !== static::ID ) {
			return $settings;
		}

		add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10, 2 );

		return array_merge(
			$settings,
			[
				'category'           => true,
				'category_threshold' => WATSON_CATEGORY_THRESHOLD,
				'category_taxonomy'  => WATSON_CATEGORY_TAXONOMY,

				'keyword'            => true,
				'keyword_threshold'  => WATSON_KEYWORD_THRESHOLD,
				'keyword_taxonomy'   => WATSON_KEYWORD_TAXONOMY,

				'concept'            => false,
				'concept_threshold'  => WATSON_CONCEPT_THRESHOLD,
				'concept_taxonomy'   => WATSON_CONCEPT_TAXONOMY,

				'entity'             => false,
				'entity_threshold'   => WATSON_ENTITY_THRESHOLD,
				'entity_taxonomy'    => WATSON_ENTITY_TAXONOMY,
			]
		);
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'endpoint_url' => '',
			'apikey'       => '',
			'username'     => 'apikey',
			'password'     => '',
		];

		return $common_settings;
	}

	/**
	 * Register what we need for the plugin.
	 */
	public function register() {
		add_filter( 'classifai_feature_classification_get_default_settings', [ $this, 'modify_default_feature_settings' ], 10, 2 );

		$feature = new Classification();

		if (
			$feature->is_feature_enabled() &&
			$feature->get_feature_provider_instance()::ID === static::ID
		) {

			$this->taxonomy_factory = new TaxonomyFactory();
			$this->taxonomy_factory->build_all();

			add_action( 'wp_ajax_get_post_classifier_preview_data', array( $this, 'get_post_classifier_preview_data' ) );
		}
	}

	/**
	 * Returns classifier data for previewing.
	 */
	public function get_post_classifier_preview_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'classifai-previewer-action' ) ) {
			wp_send_json_error( esc_html__( 'Failed nonce check.', 'classifai' ) );
		}

		$post_id    = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		$classifier = new Classifier();
		$normalizer = new \Classifai\Normalizer();

		$text_to_classify        = $normalizer->normalize( $post_id );
		$body                    = $classifier->get_body( $text_to_classify );
		$request_options['body'] = $body;
		$request                 = $classifier->get_request();

		$classified_data = $request->post( $classifier->get_endpoint(), $request_options );
		$classified_data = $this->filter_classify_preview_data( $classified_data );

		wp_send_json_success( $classified_data );
	}

	/**
	 * Filter classifier preview based on the feature settings.
	 *
	 * @param array|WP_Error $classified_data The classified data.
	 * @return array
	 */
	public function filter_classify_preview_data( $classified_data ): array {
		if ( is_wp_error( $classified_data ) ) {
			return $classified_data;
		}

		$classify_existing_terms = 'existing_terms' === get_classification_method();
		if ( ! $classify_existing_terms ) {
			return $classified_data;
		}

		$features = [
			'category' => 'categories',
			'concept'  => 'concepts',
			'entity'   => 'entities',
			'keyword'  => 'keywords',
		];
		foreach ( $features as $key => $feature ) {
			$taxonomy = get_classification_feature_taxonomy( $key );
			if ( ! $taxonomy ) {
				continue;
			}

			if ( ! isset( $classified_data[ $feature ] ) || empty( $classified_data[ $feature ] ) ) {
				continue;
			}

			// Handle categories feature.
			if ( 'categories' === $feature ) {
				$classified_data[ $feature ] = array_filter(
					$classified_data[ $feature ],
					function ( $item ) use ( $taxonomy ) {
						$keep  = false;
						$parts = explode( '/', $item['label'] );
						$parts = array_filter( $parts );
						if ( ! empty( $parts ) ) {
							foreach ( $parts as $part ) {
								$term = get_term_by( 'name', $part, $taxonomy );
								if ( ! empty( $term ) ) {
									$keep = true;
									break;
								}
							}
						}
						return $keep;
					}
				);
				// Reset array keys.
				$classified_data[ $feature ] = array_values( $classified_data[ $feature ] );
				continue;
			}

			$classified_data[ $feature ] = array_filter(
				$classified_data[ $feature ],
				function ( $item ) use ( $taxonomy, $key ) {
					$name = $item['text'];
					if ( 'keyword' === $key ) {
						$name = preg_replace( '#^[a-z]+ ([A-Z].*)$#', '$1', $name );
					} elseif ( 'entity' === $key ) {
						if ( ! empty( $item['disambiguation'] ) && ! empty( $item['disambiguation']['name'] ) ) {
							$name = $item['disambiguation']['name'];
						}
					}
					$term = get_term_by( 'name', $name, $taxonomy );
					return ! empty( $term );
				}
			);
			// Reset array keys.
			$classified_data[ $feature ] = array_values( $classified_data[ $feature ] );
		}

		return $classified_data;
	}

	/**
	 * Check if a username/password is used instead of API key.
	 *
	 * @return bool
	 */
	protected function use_username_password(): bool {
		$feature  = new Classification();
		$settings = $feature->get_settings( static::ID );

		if ( empty( $settings['username'] ) ) {
			return false;
		}

		return 'apikey' === $settings['username'];
	}

	/**
	 * Helper to ensure the authentication works.
	 *
	 * @param array $settings The list of settings to be saved
	 * @return bool|WP_Error
	 */
	protected function nlu_authentication_check( array $settings ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $settings[ static::ID ]['username'] )
			|| empty( $settings[ static::ID ]['password'] )
			|| empty( $settings[ static::ID ]['endpoint_url'] )
		) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your credentials.', 'classifai' ) );
		}

		$request           = new APIRequest();
		$request->username = $settings[ static::ID ]['username'];
		$request->password = $settings[ static::ID ]['password'];
		$base_url          = trailingslashit( $settings[ static::ID ]['endpoint_url'] ) . 'v1/analyze';
		$url               = esc_url( add_query_arg( [ 'version' => WATSON_NLU_VERSION ], $base_url ) );
		$options           = [
			'body' => wp_json_encode(
				[
					'text'     => 'Lorem ipsum dolor sit amet.',
					'language' => 'en',
					'features' => [
						'keywords' => [
							'emotion' => false,
							'limit'   => 1,
						],
					],
				]
			),
		];

		$response = $request->post( $url, $options );

		if ( ! is_wp_error( $response ) ) {
			update_option( 'classifai_configured', true );
			return true;
		} else {
			delete_option( 'classifai_configured' );
			return $response;
		}
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $new_settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings      = $this->feature_instance->get_settings();
		$authenticated = $this->nlu_authentication_check( $new_settings );

		if ( is_wp_error( $authenticated ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			add_settings_error(
				'classifai-credentials',
				'classifai-auth',
				$authenticated->get_error_message(),
				'error'
			);
		} else {
			$new_settings[ static::ID ]['authenticated'] = true;
		}

		$new_settings[ static::ID ]['endpoint_url'] = esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ?? $settings[ static::ID ]['endpoint_url'] );
		$new_settings[ static::ID ]['username']     = sanitize_text_field( $new_settings[ static::ID ]['username'] ?? $settings[ static::ID ]['username'] );
		$new_settings[ static::ID ]['password']     = sanitize_text_field( $new_settings[ static::ID ]['password'] ?? $settings[ static::ID ]['password'] );

		return $new_settings;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id The Post Id we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to run classification.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'classify':
				$return = $this->classify( $post_id );
				break;
		}

		return $return;
	}

	/**
	 * Classifies the post specified with the PostClassifier object.
	 *
	 * Existing terms relationships are removed during classification.
	 *
	 * @param int $post_id the post to classify & link
	 * @return array|WP_Error
	 */
	public function classify( int $post_id ) {
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
		$should_classify = apply_filters( 'classifai_should_classify_post', true, $post_id );
		if ( ! $should_classify ) {
			return new WP_Error( 'invalid', esc_html__( 'Classification is disabled for this item.', 'classifai' ) );
		}

		$classifier = new PostClassifier();

		$output = $classifier->classify( $post_id );

		return $output;
	}

	/**
	 * Links the Watson NLU response output to taxonomy terms.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $terms The classification results from Watson NLU.
	 * @param bool  $link Whether to link the terms or not.
	 * @return array|WP_Error
	 */
	public function link( int $post_id, array $terms, bool $link = true ) {
		if ( empty( $terms ) ) {
			return new WP_Error( 'invalid', esc_html__( 'No terms to link.', 'classifai' ) );
		}

		$classifier = new PostClassifier();

		$output = $classifier->link( $post_id, $terms, [], $link );

		return $output;
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param array|WP_Error $data Response data to format.
	 * @return string
	 */
	protected function get_formatted_latest_response( $data ): string {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		$formatted_data = array_intersect_key(
			$data,
			[
				'usage'    => 1,
				'language' => 1,
			]
		);

		foreach ( array_diff_key( $data, $formatted_data ) as $key => $value ) {
			$formatted_data[ $key ] = count( $value );
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $formatted_data ) );
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings   = $this->feature_instance->get_settings();
		$debug_info = [];

		if ( $this->feature_instance instanceof Classification ) {
			foreach ( $this->nlu_features as $slug => $feature ) {
				$debug_info[ $feature['feature'] . ' (status)' ]    = Feature::get_debug_value_text( $settings[ $slug ], 1 );
				$debug_info[ $feature['feature'] . ' (threshold)' ] = Feature::get_debug_value_text( $settings[ $slug . '_threshold' ], 1 );
				$debug_info[ $feature['feature'] . ' (taxonomy)' ]  = Feature::get_debug_value_text( $settings[ $slug . '_taxonomy' ], 1 );
			}

			$debug_info[ __( 'Latest response', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_watson_nlu_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
