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
	 * OpenAI model URL
	 *
	 * @var string
	 */
	protected $model_url = 'https://api.openai.com/v1/models';

	/**
	 * Sanitize the API key, showing an error message if needed.
	 *
	 * @param array $new_settings Incoming settings, if any.
	 * @param array $settings     Current settings, if any.
	 * @return array
	 */
	public function sanitize_api_key_settings( array $new_settings = [], array $settings = [] ): array {
		$authenticated = $this->authenticate_credentials( $new_settings[ static::ID ]['api_key'] ?? '' );

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];

		if ( is_wp_error( $authenticated ) ) {
			$new_settings[ static::ID ]['authenticated'] = false;
			$error_message                               = $authenticated->get_error_message();

			// For response code 429, credentials are valid but rate limit is reached.
			if ( 429 === (int) $authenticated->get_error_code() ) {
				$new_settings[ static::ID ]['authenticated'] = true;
				$error_message                               = str_replace( 'plan and billing details', '<a href="https://platform.openai.com/account/billing/overview" target="_blank" rel="noopener">plan and billing details</a>', $error_message );
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
			$new_settings[ static::ID ]['authenticated'] = true;
		}

		$new_settings[ static::ID ]['api_key'] = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );

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
		$response = $request->get( $this->model_url );

		return ! is_wp_error( $response ) ? true : $response;
	}

	/**
	 * Get available post types to use in settings.
	 *
	 * @return array
	 */
	public function get_post_types_for_settings(): array {
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
	public function get_post_statuses_for_settings(): array {
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
	public function get_taxonomies_for_settings(): array {
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
	 * The list of supported taxonomies.
	 *
	 * @param \Classifai\Features\Feature $feature Feature to check.
	 * @return array
	 */
	public function get_supported_taxonomies( \Classifai\Features\Feature $feature ): array {
		$provider   = $feature->get_feature_provider_instance();
		$settings   = $feature->get_settings( $provider::ID );
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
