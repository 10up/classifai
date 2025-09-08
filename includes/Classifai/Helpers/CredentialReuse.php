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
 * @since 3.6.0
 */
class CredentialReuse {

	/**
	 * Provider groups that share the same API key.
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	private static $provider_groups = [
		'openai' => [
			'openai_chatgpt',
			'openai_embeddings',
			'openai_moderation',
			'openai_dalle',
			'openai_speech_to_text',
			'openai_text_to_speech',
		],
		'azure'  => [
			'azure_openai',
			'azure_ai_vision',
			'azure_speech',
		],
		'ollama' => [
			'ollama',
			'ollama_embeddings',
			'ollama_multimodal',
		],
	];

	/**
	 * Get all configured providers across all features.
	 *
	 * @since 3.6.0
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
	 * Get the provider group for a given provider ID.
	 *
	 * @since 3.6.0
	 *
	 * @param string $provider_id The provider ID.
	 * @return string|null The provider group name or null if not found.
	 */
	private static function get_provider_group( string $provider_id ): ?string {
		foreach ( self::$provider_groups as $group_name => $providers ) {
			if ( in_array( $provider_id, $providers, true ) ) {
				return $group_name;
			}
		}
		return null;
	}

	/**
	 * Get all providers in the same group as the given provider.
	 *
	 * @since 3.6.0
	 *
	 * @param string $provider_id The provider ID.
	 * @return array Array of provider IDs in the same group.
	 */
	private static function get_providers_in_same_group( string $provider_id ): array {
		$group = self::get_provider_group( $provider_id );
		if ( ! $group ) {
			return [ $provider_id ];
		}
		return self::$provider_groups[ $group ];
	}

	/**
	 * Check if a provider is compatible with a feature.
	 *
	 * @since 3.6.0
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
	 * Check if any provider in the same group is compatible with a feature.
	 *
	 * @since 3.6.0
	 *
	 * @param string $provider_id The provider ID to check.
	 * @param string $feature_id  The feature ID to check against.
	 * @return bool True if any provider in the same group is compatible.
	 */
	private static function is_provider_group_compatible( string $provider_id, string $feature_id ): bool {
		$providers_in_group = self::get_providers_in_same_group( $provider_id );

		foreach ( $providers_in_group as $group_provider_id ) {
			if ( self::is_provider_compatible( $group_provider_id, $feature_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the best matching provider ID for a feature from a provider group.
	 *
	 * @since 3.6.0
	 *
	 * @param string $source_provider_id The source provider ID.
	 * @param string $feature_id         The target feature ID.
	 * @return string|null The best matching provider ID or null if none found.
	 */
	private static function get_best_matching_provider( string $source_provider_id, string $feature_id ): ?string {
		$providers_in_group = self::get_providers_in_same_group( $source_provider_id );

		// First, try to find an exact match
		if ( self::is_provider_compatible( $source_provider_id, $feature_id ) ) {
			return $source_provider_id;
		}

		// Then, try to find any compatible provider in the same group
		foreach ( $providers_in_group as $group_provider_id ) {
			if ( self::is_provider_compatible( $group_provider_id, $feature_id ) ) {
				return $group_provider_id;
			}
		}

		return null;
	}

	/**
	 * Get reusable credentials for a feature.
	 *
	 * @since 3.6.0
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

			// Check if provider is directly compatible with the target feature.
			if ( self::is_provider_compatible( $provider_id, $feature_id ) ) {
				$reusable[ $provider_id ] = $provider_data;
				continue;
			}

			// Check if any provider in the same group is compatible.
			if ( self::is_provider_group_compatible( $provider_id, $feature_id ) ) {
				$best_match = self::get_best_matching_provider( $provider_id, $feature_id );
				if ( $best_match ) {
					// Create a modified provider data entry with the best matching provider ID
					$reusable[ $best_match ] = [
						'feature_id'         => $provider_data['feature_id'],
						'feature_label'      => $provider_data['feature_label'],
						'credentials'        => $provider_data['credentials'],
						'source_provider_id' => $provider_id,
					];
				}
			}
		}

		/**
		 * Filter the reusable credentials for a feature.
		 *
		 * @since 3.6.0
		 * @hook classifai_reusable_credentials
		 *
		 * @param array  $reusable   Array of reusable credentials.
		 * @param string $feature_id The feature ID.
		 *
		 * @return array Filtered reusable credentials.
		 */
		return apply_filters( 'classifai_reusable_credentials', $reusable, $feature_id );
	}

	/**
	 * Copy credentials from one feature to another.
	 *
	 * @since 3.6.0
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

		// Find the source provider credentials
		$source_credentials = null;

		// First, try to find the exact provider
		if ( ! empty( $source_settings[ $provider_id ] ) ) {
			$source_credentials = $source_settings[ $provider_id ];
		} else {
			// If not found, look for any provider in the same group
			$providers_in_group = self::get_providers_in_same_group( $provider_id );
			foreach ( $providers_in_group as $group_provider_id ) {
				if ( ! empty( $source_settings[ $group_provider_id ] ) ) {
					$source_credentials = $source_settings[ $group_provider_id ];
					break;
				}
			}
		}

		if ( ! $source_credentials ) {
			return false;
		}

		// Copy the provider credentials to the target provider
		$target_settings[ $provider_id ] = $source_credentials;
		$target_settings['provider']     = $provider_id;

		// Update the target feature settings.
		update_option( $features[ $target_feature_id ]->get_option_name(), $target_settings );

		/**
		 * Fires after credentials are copied between features.
		 *
		 * @since 3.6.0
		 * @hook classifai_credentials_copied
		 *
		 * @param string $source_feature_id Source feature ID.
		 * @param string $target_feature_id Target feature ID.
		 * @param string $provider_id       Provider ID that was copied.
		 */
		do_action( 'classifai_credentials_copied', $source_feature_id, $target_feature_id, $provider_id );

		return true;
	}

	/**
	 * Get all feature instances.
	 *
	 * @since 3.6.0
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
	 * @since 3.6.0
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

	/**
	 * Get provider groups for external use.
	 *
	 * @since 3.6.0
	 *
	 * @return array Array of provider groups.
	 */
	public static function get_provider_groups(): array {
		return self::$provider_groups;
	}
}
