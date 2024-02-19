<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Watson\NLU;
use Classifai\Providers\OpenAI\Embeddings;

use function Classifai\get_post_statuses_for_language_settings;
use function Classifai\get_post_types_for_language_settings;

/**
 * Class Classification
 */
class Classification extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_classification';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Classification', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			NLU::ID        => __( 'IBM Watson NLU', 'classifai' ),
			Embeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables the automatic content classification of posts.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings      = $this->get_settings();
		$post_statuses = get_post_statuses_for_language_settings();

		add_settings_field(
			'post_statuses',
			esc_html__( 'Post statuses', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_statuses',
				'options'        => $post_statuses,
				'default_values' => $settings['post_statuses'],
				'description'    => __( 'Choose which post statuses are allowed to use this feature.', 'classifai' ),
			]
		);

		$post_types        = get_post_types_for_language_settings();
		$post_type_options = array();

		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

		add_settings_field(
			'post_types',
			esc_html__( 'Post types', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_types',
				'options'        => $post_type_options,
				'default_values' => $settings['post_types'],
				'description'    => __( 'Choose which post types are allowed to use this feature.', 'classifai' ),
			]
		);
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'post_statuses' => [
				'publish' => 1,
			],
			'post_types'    => [
				'post' => 1,
			],
			'provider'      => NLU::ID,
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

		$new_settings['post_statuses'] = isset( $new_settings['post_statuses'] ) ? array_map( 'sanitize_text_field', $new_settings['post_statuses'] ) : $settings['post_statuses'];
		$new_settings['post_types']    = isset( $new_settings['post_types'] ) ? array_map( 'sanitize_text_field', $new_settings['post_types'] ) : $settings['post_types'];

		return $new_settings;
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? NLU::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( NLU::ID === $provider_instance::ID ) {
			/** @var NLU $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'classify' ],
				[ ...$args ]
			);
		} elseif ( Embeddings::ID === $provider_instance::ID ) {
			/** @var Embeddings $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'generate_embeddings_for_post' ],
				[ ...$args ]
			);
		}

		return apply_filters(
			'classifai_' . static::ID . '_run',
			$result,
			$provider_instance,
			$args,
			$this
		);
	}

	/**
	 * Generates feature setting data required for migration from
	 * ClassifAI < 3.0.0 to 3.0.0
	 *
	 * @return array
	 */
	public function migrate_settings() {
		$old_settings = get_option( 'classifai_watson_nlu', array() );
		$new_settings = array();

		if ( isset( $old_settings['authenticated'] ) && $old_settings['authenticated'] ) {
			$new_settings['provider'] = 'ibm_watson_nlu';

			// Status
			if ( isset( $old_settings['enable_content_classification'] ) ) {
				$new_settings['status'] = $old_settings['enable_content_classification'];
			}

			// Post types
			if ( isset( $old_settings['post_types'] ) ) {
				if ( is_array( $old_settings['post_types'] ) ) {
					foreach ( $old_settings['post_types'] as $post_type => $value ) {
						if ( 1 === $value ) {
							$new_settings['post_types'][ $post_type ] = $post_type;
							continue;
						} elseif ( is_null( $value ) ) {
							$new_settings['post_types'][ $post_type ] = '0';
							continue;
						}
						$new_settings['post_types'][ $post_type ] = $value;
					}
				}

				unset( $new_settings['post_types']['attachment'] );
			}

			// Post statuses
			if ( isset( $old_settings['post_statuses'] ) ) {
				if ( is_array( $old_settings['post_statuses'] ) ) {
					foreach ( $old_settings['post_statuses'] as $post_status => $value ) {
						if ( 1 === $value ) {
							$new_settings['post_statuses'][ $post_status ] = $post_status;
							continue;
						} elseif ( is_null( $value ) ) {
							$new_settings['post_statuses'][ $post_status ] = '0';
							continue;
						}
						$new_settings['post_statuses'][ $post_status ] = $value;
					}
				}
			}

			// Roles
			if ( isset( $old_settings['content_classification_roles'] ) ) {
				$new_settings['roles'] = $old_settings['content_classification_roles'];
			}

			// Users
			if ( isset( $old_settings['users'] ) ) {
				$new_settings['users'] = $old_settings['users'];
			}

			// Provider.
			if ( isset( $old_settings['credentials'] ) && isset( $old_settings['credentials']['watson_url'] ) ) {
				$new_settings['ibm_watson_nlu']['endpoint_url'] = $old_settings['credentials']['watson_url'];
			}

			if ( isset( $old_settings['credentials'] ) && isset( $old_settings['credentials']['watson_username'] ) ) {
				$new_settings['ibm_watson_nlu']['username'] = $old_settings['credentials']['watson_username'];
			}

			if ( isset( $old_settings['credentials'] ) && isset( $old_settings['credentials']['watson_password'] ) ) {
				$new_settings['ibm_watson_nlu']['password'] = $old_settings['credentials']['watson_password'];
			}

			if ( isset( $old_settings['classification_mode'] ) ) {
				$new_settings['ibm_watson_nlu']['classification_mode'] = $old_settings['classification_mode'];
			}

			if ( isset( $old_settings['classification_method'] ) ) {
				$new_settings['ibm_watson_nlu']['classification_method'] = $old_settings['classification_method'];
			}

			if ( isset( $old_settings['features'] ) ) {
				foreach ( $old_settings['features'] as $feature => $value ) {
					$new_settings['ibm_watson_nlu'][ $feature ] = $value;
				}
			}

			if ( isset( $old_settings['authenticated'] ) ) {
				$new_settings['ibm_watson_nlu']['authenticated'] = $old_settings['authenticated'];
			}

			if ( isset( $old_settings['content_classification_user_based_opt_out'] ) ) {
				$new_settings['user_based_opt_out'] = $old_settings['content_classification_user_based_opt_out'];
			}

			if ( isset( $old_settings['content_classification_users'] ) ) {
				$new_settings['users'] = $old_settings['content_classification_users'];
			}
		} else {
			$old_settings = get_option( 'classifai_openai_embeddings', array() );

			if ( isset( $old_settings['enable_classification'] ) ) {
				$new_settings['status'] = $old_settings['enable_classification'];
			}

			$new_settings['provider'] = 'openai_embeddings';

			if ( isset( $old_settings['api_key'] ) ) {
				$new_settings['openai_embeddings']['api_key'] = $old_settings['api_key'];
			}

			if ( isset( $old_settings['number'] ) ) {
				$new_settings['openai_embeddings']['number_of_terms'] = $old_settings['number'];
			}

			if ( isset( $old_settings['number'] ) ) {
				$new_settings['openai_embeddings']['number_of_terms'] = $old_settings['number'];
			}

			if ( isset( $old_settings['taxonomies'] ) ) {
				$new_settings['openai_embeddings']['taxonomies'] = $old_settings['taxonomies'];
			}

			if ( isset( $old_settings['authenticated'] ) ) {
				$new_settings['openai_embeddings']['authenticated'] = $old_settings['authenticated'];
			}

			if ( isset( $old_settings['post_statuses'] ) ) {
				$new_settings['post_statuses'] = $old_settings['post_statuses'];
			}

			if ( isset( $old_settings['post_types'] ) ) {
				$new_settings['post_types'] = $old_settings['post_types'];
			}

			if ( isset( $old_settings['classification_roles'] ) ) {
				$new_settings['roles'] = $old_settings['classification_roles'];
			}

			if ( isset( $old_settings['classification_users'] ) ) {
				$new_settings['users'] = $old_settings['classification_users'];
			}

			if ( isset( $old_settings['classification_user_based_opt_out'] ) ) {
				$new_settings['user_based_opt_out'] = $old_settings['classification_user_based_opt_out'];
			}
		}

		return $new_settings;
	}
}
