<?php
/**
 * Helper class for credential reuse functionality.
 */

namespace Classifai\Helpers;

use Classifai\Services\ServicesManager;
use function Classifai\get_plugin;

/**
 * CredentialReuse class.
 *
 * Handles detection and reuse of service provider credentials across features.
 *
 * @since x.x.x
 */
class CredentialReuse {

	/**
	 * Get all configured providers across all features.
	 *
	 * @since x.x.x
	 *
	 * @return array Array of configured providers with their credentials.
	 */
	public static function get_configured_providers(): array {
		$configured_providers = [];
		$features             = self::get_all_features();

		foreach ( $features as $feature_id => $feature_instance ) {
			$settings    = $feature_instance->get_settings();
			$provider_id = $settings['provider'] ?? '';

			if ( empty( $provider_id ) || empty( $settings[ $provider_id ]['authenticated'] ) ) {
				continue;
			}

			$configured_providers[ $provider_id ] = [
				'feature_id'    => $feature_id,
				'feature_label' => $feature_instance->get_label(),
				'credentials'   => $settings[ $provider_id ],
			];
		}
		return $configured_providers;
	}

	/**
	 * Check if a provider is compatible with a feature.
	 *
	 * @since x.x.x
	 *
	 * @param string $provider_id The provider ID to check.
	 * @param string $feature_id  The feature ID to check against.
	 * @return bool True if compatible.
	 */
	public static function is_provider_compatible( string $provider_id, string $feature_id ): bool {
		$features = self::get_all_features();

		if ( ! isset( $features[ $feature_id ] ) ) {
			return false;
		}

		$feature_providers = $features[ $feature_id ]->get_providers();
		return array_key_exists( $provider_id, $feature_providers );
	}

	/**
	 * Get reusable credentials for a feature.
	 *
	 * @since x.x.x
	 *
	 * @param string $feature_id The feature ID to get credentials for.
	 * @return array Array of compatible providers with existing credentials.
	 */
	public static function get_reusable_credentials( string $feature_id ): array {
		$configured_providers = self::get_configured_providers();
		$reusable             = [];

		foreach ( $configured_providers as $provider_id => $provider_data ) {
			// Skip if this is the same feature.
			if ( $provider_data['feature_id'] === $feature_id ) {
				continue;
			}

			// Check if provider is compatible with the target feature.
			if ( self::is_provider_compatible( $provider_id, $feature_id ) ) {
				$reusable[ $provider_id ] = $provider_data;
			}
		}

		/**
		 * Filter the reusable credentials for a feature.
		 *
		 * @since x.x.x
		 * @hook classifai_reusable_credentials
		 *
		 * @param {array}  $reusable   Array of reusable credentials.
		 * @param {string} $feature_id The feature ID.
		 *
		 * @return {array} Filtered reusable credentials.
		 */
		return apply_filters( 'classifai_reusable_credentials', $reusable, $feature_id );
	}

	/**
	 * Copy credentials from one feature to another.
	 *
	 * @since x.x.x
	 *
	 * @param string $source_feature_id Source feature ID.
	 * @param string $target_feature_id Target feature ID.
	 * @param string $provider_id       Provider ID to copy.
	 * @return bool Success status.
	 */
	public static function copy_provider_credentials( string $source_feature_id, string $target_feature_id, string $provider_id ): bool {
		$features = self::get_all_features();

		if ( ! isset( $features[ $source_feature_id ] ) || ! isset( $features[ $target_feature_id ] ) ) {
			return false;
		}

		$source_settings = $features[ $source_feature_id ]->get_settings();
		$target_settings = $features[ $target_feature_id ]->get_settings();

		if ( empty( $source_settings[ $provider_id ] ) ) {
			return false;
		}

		// Copy the provider credentials.
		$target_settings[ $provider_id ] = $source_settings[ $provider_id ];
		$target_settings['provider']     = $provider_id;

		// Update the target feature settings.
		update_option( $features[ $target_feature_id ]->get_option_name(), $target_settings );

		/**
		 * Fires after credentials are copied between features.
		 *
		 * @since x.x.x
		 * @hook classifai_credentials_copied
		 *
		 * @param {string} $source_feature_id Source feature ID.
		 * @param {string} $target_feature_id Target feature ID.
		 * @param {string} $provider_id       Provider ID that was copied.
		 */
		do_action( 'classifai_credentials_copied', $source_feature_id, $target_feature_id, $provider_id );

		return true;
	}

	/**
	 * Get all feature instances.
	 *
	 * @since x.x.x
	 *
	 * @return array Array of feature instances.
	 */
	private static function get_all_features(): array {
		$services = get_plugin()->services;
		$features = [];

		if ( empty( $services['service_manager'] ) || ! $services['service_manager'] instanceof ServicesManager ) {
			return $features;
		}

		/** @var ServicesManager $service_manager */
		$service_manager = $services['service_manager'];

		foreach ( $service_manager->service_classes as $service ) {
			foreach ( $service->feature_classes as $feature ) {
				$features[ $feature::ID ] = $feature;
			}
		}

		return $features;
	}

	/**
	 * Get a user-friendly provider name.
	 *
	 * @since x.x.x
	 *
	 * @param string $provider_id The provider ID.
	 * @return string The formatted provider name.
	 */
	public static function get_provider_display_name( string $provider_id ): string {
		$provider_names = [
			'openai_chatgpt'        => __( 'OpenAI ChatGPT', 'classifai' ),
			'openai_dalle'          => __( 'OpenAI Images', 'classifai' ),
			'openai_embeddings'     => __( 'OpenAI Embeddings', 'classifai' ),
			'openai_moderation'     => __( 'OpenAI Moderation', 'classifai' ),
			'openai_speech_to_text' => __( 'OpenAI Speech to Text', 'classifai' ),
			'openai_text_to_speech' => __( 'OpenAI Text to Speech', 'classifai' ),
			'azure_openai'          => __( 'Azure OpenAI', 'classifai' ),
			'azure_ai_vision'       => __( 'Azure AI Vision', 'classifai' ),
			'azure_speech'          => __( 'Microsoft Azure AI Speech', 'classifai' ),
			'ms_azure_personalizer' => __( 'Microsoft Azure AI Personalizer', 'classifai' ),
			'aws_polly'             => __( 'Amazon Polly', 'classifai' ),
			'google_gemini_api'     => __( 'Google AI Gemini API', 'classifai' ),
			'ibm_watson_nlu'        => __( 'IBM Watson NLU', 'classifai' ),
			'ollama'                => __( 'Ollama', 'classifai' ),
			'ollama_embeddings'     => __( 'Ollama Embeddings', 'classifai' ),
			'ollama_multimodal'     => __( 'Ollama Multimodal', 'classifai' ),
			'xai_grok'              => __( 'xAI Grok', 'classifai' ),
		];

		return $provider_names[ $provider_id ] ?? ucwords( str_replace( '_', ' ', $provider_id ) );
	}
}
