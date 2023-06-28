<?php
/**
 * OpenAI shared functionality
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\OpenAI\APIRequest;
use WP_Error;

use function Classifai\get_all_post_statuses;

trait OpenAI {

	/**
	 * OpenAI completions URL
	 *
	 * @var string
	 */
	protected $completions_url = 'https://api.openai.com/v1/completions';

	/**
	 * Add our OpenAI API settings field.
	 *
	 * @param string $default_api_key Default API key.
	 */
	protected function setup_api_fields( string $default_api_key = '' ) {
		$existing_settings = $this->get_settings();
		$description       = '';

		// Add the settings section.
		add_settings_section(
			$this->get_option_name(),
			$this->provider_service_name,
			function() {
				printf(
					wp_kses(
						/* translators: %1$s is replaced with the OpenAI sign up URL */
						__( 'Don\'t have an OpenAI account yet? <a title="Sign up for an OpenAI account" href="%1$s">Sign up for one</a> in order to get your API key.', 'classifai' ),
						[
							'a' => [
								'href'  => [],
								'title' => [],
							],
						]
					),
					esc_url( 'https://platform.openai.com/signup' )
				);
			},
			$this->get_option_name()
		);

		// Determine which other OpenAI provider to look for an API key in.
		if ( 'ChatGPT' === $this->provider_service_name ) {
			$settings              = \Classifai\get_plugin_settings( 'image_processing', 'DALL·E' );
			$provider_service_name = 'DALL·E';
		} elseif ( 'DALL·E' === $this->provider_service_name ) {
			$settings              = \Classifai\get_plugin_settings( 'language_processing', 'ChatGPT' );
			$provider_service_name = 'ChatGPT';
		} else {
			$settings              = [];
			$provider_service_name = '';
		}

		// If we already have a valid API key from OpenAI, use that as our default.
		if ( ! empty( $settings ) && ( isset( $settings['authenticated'] ) && isset( $settings['api_key'] ) && true === $settings['authenticated'] ) ) {
			$default_api_key = $settings['api_key'];

			if ( empty( $existing_settings ) || empty( $existing_settings['api_key'] ) ) {
				/* translators: %1$s: the provider service name */
				$description = sprintf( __( 'API key has been prefilled from your %1$s settings.', 'classifai' ), $provider_service_name );
			}
		}

		// Add our API Key setting.
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $default_api_key,
				'description'   => $description,
			]
		);
	}

	/**
	 * Sanitize the API key, showing an error message if needed.
	 *
	 * @param array $new_settings New settings being saved.
	 * @param array $old_settings Existing settings, if any.
	 * @return array
	 */
	protected function sanitize_api_key_settings( array $new_settings = [], array $old_settings = [] ) {
		$authenticated = $this->authenticate_credentials( $old_settings['api_key'] ?? '' );

		if ( is_wp_error( $authenticated ) ) {
			$new_settings['authenticated'] = false;
			$error_message                 = $authenticated->get_error_message();

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				$new_settings['authenticated'] = true;
				$error_message                 = str_replace( 'plan and billing details', '<a href="https://platform.openai.com/account/billing/overview" target="_blank" rel="noopener">plan and billing details</a>', $error_message );
			} else {
				$error_message = str_replace( 'https://platform.openai.com/account/api-keys', '<a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noopener">https://platform.openai.com/account/api-keys</a>', $error_message );
			}

			add_settings_error(
				'api_key',
				'classifai-auth',
				$error_message,
				'error'
			);
		} else {
			$new_settings['authenticated'] = true;
		}

		$new_settings['api_key'] = sanitize_text_field( $old_settings['api_key'] ?? '' );

		return $new_settings;
	}

	/**
	 * Authenticate our credentials.
	 *
	 * @param string $api_key Api Key.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $api_key = '' ) {
		// Check that we have credentials before hitting the API.
		if ( empty( $api_key ) ) {
			return new WP_Error( 'auth', esc_html__( 'Please enter your OpenAI API key.', 'classifai' ) );
		}

		// Make request to ensure credentials work.
		$request  = new APIRequest( $api_key );
		$response = $request->post(
			$this->completions_url,
			[
				'body' => wp_json_encode(
					[
						'model'      => 'ada',
						'prompt'     => 'hi',
						'max_tokens' => 1,
					]
				),
			]
		);

		return ! is_wp_error( $response ) ? true : $response;
	}

	/**
	 * Get available post types to use in settings.
	 *
	 * @return array
	 */
	public function get_post_types_for_settings() {
		$post_types     = [];
		$post_type_objs = get_post_types( [], 'objects' );
		$post_type_objs = array_filter( $post_type_objs, 'is_post_type_viewable' );
		unset( $post_type_objs['attachment'] );

		foreach ( $post_type_objs as $post_type ) {
			$post_types[ $post_type->name ] = $post_type->label;
		}

		/**
		 * Filter post types shown in settings.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_settings_post_types
		 *
		 * @param {array} $post_types Array of post types to show in settings.
		 * @param {object} $this Current instance of the class.
		 *
		 * @return {array} Array of post types.
		 */
		return apply_filters( 'classifai_openai_settings_post_types', $post_types, $this );
	}

	/**
	 * Get available post statuses to use in settings.
	 *
	 * @return array
	 */
	public function get_post_statuses_for_settings() {
		$post_statuses = get_all_post_statuses();

		/**
		 * Filter post statuses shown in settings.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_settings_post_statuses
		 *
		 * @param {array} $post_statuses Array of post statuses to show in settings.
		 * @param {object} $this Current instance of the class.
		 *
		 * @return {array} Array of post statuses.
		 */
		return apply_filters( 'classifai_openai_settings_post_statuses', $post_statuses, $this );
	}

	/**
	 * Get available taxonomies to use in settings.
	 *
	 * @return array
	 */
	public function get_taxonomies_for_settings() {
		$taxonomies = get_taxonomies( [], 'objects' );
		$taxonomies = array_filter( $taxonomies, 'is_taxonomy_viewable' );
		$supported  = [];

		foreach ( $taxonomies as $taxonomy ) {
			$supported[ $taxonomy->name ] = $taxonomy->labels->singular_name;
		}

		/**
		 * Filter taxonomies shown in settings.
		 *
		 * @since 2.2.0
		 * @hook classifai_openai_settings_taxonomies
		 *
		 * @param {array} $supported Array of supported taxonomies.
		 * @param {object} $this Current instance of the class.
		 *
		 * @return {array} Array of taxonomies.
		 */
		return apply_filters( 'classifai_openai_settings_taxonomies', $supported, $this );
	}

	/**
	 * The list of supported post types.
	 *
	 * return array
	 */
	public function get_supported_post_types() {
		$settings   = $this->get_settings();
		$post_types = [];

		if ( ! empty( $settings ) && isset( $settings['post_types'] ) ) {
			foreach ( $settings['post_types'] as $post_type => $enabled ) {
				if ( ! empty( $enabled ) ) {
					$post_types[] = $post_type;
				}
			}
		}

		return $post_types;
	}

	/**
	 * The list of supported post statuses.
	 *
	 * @return array
	 */
	public function get_supported_post_statuses() {
		$settings      = $this->get_settings();
		$post_statuses = [];

		if ( ! empty( $settings ) && isset( $settings['post_statuses'] ) ) {
			foreach ( $settings['post_statuses'] as $post_status => $enabled ) {
				if ( ! empty( $enabled ) ) {
					$post_statuses[] = $post_status;
				}
			}
		}

		return $post_statuses;
	}

	/**
	 * The list of supported taxonomies.
	 *
	 * @return array
	 */
	public function get_supported_taxonomies() {
		$settings   = $this->get_settings();
		$taxonomies = [];

		if ( ! empty( $settings ) && isset( $settings['taxonomies'] ) ) {
			foreach ( $settings['taxonomies'] as $taxonomy => $enabled ) {
				if ( ! empty( $enabled ) ) {
					$taxonomies[] = $taxonomy;
				}
			}
		}

		return $taxonomies;
	}

}
