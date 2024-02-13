<?php
/**
 * Azure OpenAI integration
 */

namespace Classifai\Providers\Azure;

use Classifai\Features\ContentResizing;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Provider;
use Classifai\Normalizer;
use WP_Error;

use function Classifai\get_default_prompt;

class OpenAI extends Provider {

	/**
	 * Provider ID
	 *
	 * @var string
	 */
	const ID = 'azure_openai';

	/**
	 * Chat completion URL fragment.
	 *
	 * @var string
	 */
	protected $chat_completion_url = 'openai/deployments/{deployment-id}/chat/completions';

	/**
	 * Completion URL fragment.
	 *
	 * @var string
	 */
	protected $completion_url = 'openai/deployments/{deployment-id}/completions';

	/**
	 * Chat completion API version.
	 *
	 * @var string
	 */
	protected $chat_completion_api_version = '2023-05-15';

	/**
	 * Completion API version.
	 *
	 * @var string
	 */
	protected $completion_api_version = '2023-05-15';

	/**
	 * GeminiAPI constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Render the provider fields.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_endpoint_url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'endpoint_url',
				'input_type'    => 'text',
				'default_value' => $settings['endpoint_url'],
				'description'   => __( 'Supported protocol and hostname endpoints, e.g., <code>https://EXAMPLE.openai.azure.com</code>.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		add_settings_field(
			static::ID . '_deployment',
			esc_html__( 'Deployment name', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'deployment',
				'input_type'    => 'text',
				'default_value' => $settings['deployment'],
				'description'   => __( 'Custom name you chose for your deployment when you deployed a model.', 'classifai' ),
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
			]
		);

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'endpoint_url'  => '',
			'api_key'       => '',
			'deployment'    => '',
			'authenticated' => false,
		];

		return $common_settings;
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * @param array $new_settings The settings array.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings = $this->feature_instance->get_settings();

		if (
			! empty( $new_settings[ static::ID ]['endpoint_url'] ) &&
			! empty( $new_settings[ static::ID ]['api_key'] ) &&
			! empty( $new_settings[ static::ID ]['deployment'] )
		) {
			$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];
			$new_settings[ static::ID ]['endpoint_url']  = esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ?? $settings[ static::ID ]['endpoint_url'] );
			$new_settings[ static::ID ]['api_key']       = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );
			$new_settings[ static::ID ]['deployment']    = sanitize_text_field( $new_settings[ static::ID ]['deployment'] ?? $settings[ static::ID ]['deployment'] );

			$is_authenticated   = $new_settings[ static::ID ]['authenticated'];
			$is_endpoint_same   = $new_settings[ static::ID ]['endpoint_url'] === $settings[ static::ID ]['endpoint_url'];
			$is_api_key_same    = $new_settings[ static::ID ]['api_key'] === $settings[ static::ID ]['api_key'];
			$is_deployment_same = $new_settings[ static::ID ]['deployment'] === $settings[ static::ID ]['deployment'];

			if ( ! ( $is_authenticated && $is_endpoint_same && $is_api_key_same && $is_deployment_same ) ) {
				$auth_check = $this->authenticate_credentials(
					$new_settings[ static::ID ]['endpoint_url'],
					$new_settings[ static::ID ]['api_key'],
					$new_settings[ static::ID ]['deployment']
				);

				if ( is_wp_error( $auth_check ) ) {
					$new_settings[ static::ID ]['authenticated'] = false;
					$error_message                               = $auth_check->get_error_message();

					// Add an error message.
					add_settings_error(
						'api_key',
						'classifai-auth',
						$error_message,
						'error'
					);
				} else {
					$new_settings[ static::ID ]['authenticated'] = true;
				}
			}
		} else {
			$new_settings[ static::ID ]['endpoint_url'] = $settings[ static::ID ]['endpoint_url'];
			$new_settings[ static::ID ]['api_key']      = $settings[ static::ID ]['api_key'];
			$new_settings[ static::ID ]['deployment']   = $settings[ static::ID ]['deployment'];
		}

		return $new_settings;
	}

	/**
	 * Build and return the API endpoint based on settings.
	 *
	 * @param \Classifai\Features\Feature $feature Feature instance
	 * @return string
	 */
	protected function prep_api_url( \Classifai\Features\Feature $feature = null ): string {
		$settings   = $feature->get_settings( static::ID );
		$endpoint   = $settings['endpoint_url'] ?? '';
		$deployment = $settings['deployment'] ?? '';

		if ( ! $endpoint ) {
			return '';
		}

		if ( $feature instanceof ExcerptGeneration && ! empty( $deployment ) ) {
			$endpoint = trailingslashit( $endpoint ) . $this->chat_completion_url;
			$endpoint = str_replace( '{deployment-id}', $deployment, $endpoint );
			$endpoint = add_query_arg( 'api-version', $this->chat_completion_api_version, $endpoint );
		}

		return $endpoint;
	}

	/**
	 * Authenticates our credentials.
	 *
	 * @param string $url Endpoint URL.
	 * @param string $api_key Api Key.
	 * @param string $deployment Deployment name.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $url, string $api_key, string $deployment ) {
		$rtn = false;

		$endpoint = trailingslashit( $url ) . $this->completion_url;
		$endpoint = str_replace( '{deployment-id}', $deployment, $endpoint );
		$endpoint = add_query_arg( 'api-version', $this->completion_api_version, $endpoint );

		$request = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'api-key'      => $api_key,
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'prompt'     => 'Once upon a time',
						'max_tokens' => 5,
					]
				),
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				$rtn = new WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		}

		return $rtn;
	}
}
