<?php
/**
 * Azure Cognitive Tools - Language
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use Classifai\Blocks;
use WP_Error;

class Language extends Provider {

	/**
	 * @var string URL fragment to perform the syncronous text analysis request
	 */
	protected $endpoint = '/language/:analyze-text?api-version=2022-10-01-preview';

	/**
	 * @var string URL fragment to access asyncronous text analysis features
	 */
	protected $async_endpoint = '/language/analyze-text/jobs?api-version=2022-05-15-preview';

	/**
	 * Language constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'Microsoft Azure',
			'Language',
			'Language',
			$service
		);
	}

	/**
	 * Resets settings for the Language provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$options = get_option( $this->get_option_name() );
		if ( empty( $options ) || ( isset( $options['authenticated'] ) && false === $options['authenticated'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Default settings for Language
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			'authenticated' => false,
			'url'           => '',
			'api_key'       => '',
		);
	}

	/**
	 * Setup fields.
	 */
	public function setup_fields_sections() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		$default_settings = $this->get_default_settings();
		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			array( $this, 'render_input' ),
			$this->get_option_name(),
			$this->get_option_name(),
			array(
				'label_for'     => 'url',
				'input_type'    => 'text',
				'default_value' => $default_settings['url'],
				'description'   => sprintf(
					wp_kses(
						// translators: 1 - link to create a Language resource.
						__( 'Azure Cognitive Service Language Endpoint, <a href="%1$s" target="_blank">create a Language resource</a> in the Azure portal to get your key and endpoint.', 'classifai' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					),
					esc_url( 'https://portal.azure.com/#create/Microsoft.CognitiveServicesLanguage' )
				),
			)
		);
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			array( $this, 'render_input' ),
			$this->get_option_name(),
			$this->get_option_name(),
			array(
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $default_settings['api_key'],
				'description'   => __( 'Azure Cognitive Service Language Key.', 'classifai' ),
			)
		);
	}

	/**
	 * Authenticates our credentials.
	 *
	 * @param string $url     Endpoint URL.
	 * @param string $api_key Api Key.
	 *
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( $url, $api_key ) {
		$rtn = false;
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$result = wp_remote_post(
			trailingslashit( $url ) . $this->endpoint,
			array(
				'headers' => array(
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				),
				'body'    => '{"kind":"LanguageDetection","parameters":{"modelVersion": "latest"},"analysisInput": {"documents": [{"id": "1","text": "Hello world"}]}}',
			)
		);

		if ( ! is_wp_error( $result ) ) {
			$response = json_decode( wp_remote_retrieve_body( $result ) );
			set_transient( 'classifai_azure_language_status_response', $response, DAY_IN_SECONDS * 30 );
			if ( ! empty( $response->error ) ) {
				$rtn = new WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		}

		return $rtn;
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings The settings being saved.
	 *
	 * @return array|mixed
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = array();
		if ( ! empty( $settings['url'] ) && ! empty( $settings['api_key'] ) ) {
			$auth_check = $this->authenticate_credentials( $settings['url'], $settings['api_key'] );
			if ( is_wp_error( $auth_check ) ) {
				$settings_errors['classifai-registration-credentials-error'] = $auth_check->get_error_message();
				$new_settings['authenticated']                               = false;
			} else {
				$new_settings['authenticated'] = true;
			}
			$new_settings['url']     = esc_url_raw( $settings['url'] );
			$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] );
		} else {
			$new_settings['authenticated'] = false;
			$new_settings['url']           = '';
			$new_settings['api_key']       = '';

			$settings_errors['classifai-registration-credentials-empty'] = __( 'Please enter your credentials', 'classifai' );
		}

		if ( ! empty( $settings_errors ) ) {

			$registered_settings_errors = wp_list_pluck( get_settings_errors( $this->get_option_name() ), 'code' );

			foreach ( $settings_errors as $code => $message ) {

				if ( ! in_array( $code, $registered_settings_errors, true ) ) {
					add_settings_error(
						$this->get_option_name(),
						$code,
						esc_html( $message ),
						'error'
					);
				}
			}
		}

		return $new_settings;
	}

	/**
	 * Register the functionality.
	 */
	public function register() {
		// Setup Blocks
		Blocks\setup();
	}


	/**
	 * Provides debug information related to the provider.
	 *
	 * @param null|array $settings Settings array. If empty, settings will be retrieved.
	 * @return array Keyed array of debug information.
	 * @since 1.4.0
	 */
	public function get_provider_debug_information( $settings = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated = 1 === intval( $settings['authenticated'] ?? 0 );

		return array(
			__( 'Authenticated', 'classifai' )  => $authenticated ? __( 'Yes', 'classifai' ) : __( 'No', 'classifai' ),
			__( 'API URL', 'classifai' )        => $settings['url'] ?? '',
			__( 'Service Status', 'classifai' ) => $this->get_formatted_latest_response( get_transient( 'classifai_azure_language_status_response' ) ),
		);
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param mixed $data Response data to format.
	 *
	 * @return string
	 */
	private function get_formatted_latest_response( $data ) {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}

	/**
	 * Perform API call to detect the language of the text
	 *
	 * @param string $text Text to analyze.
	 * @return object|WP_Error
	 */
	public function language_detection( string $text ) {
		$body = array(
			'kind'          => 'LanguageDetection',
			'parameters'    => array(
				'modelVersion' => 'latest',
			),
			'analysisInput' => array(
				array(
					'id'   => 1,
					'text' => $text,
				),
			),
		);

		return $this->sync_request( $body );
	}

	/**
	 * Perform KeyPhraseExtraction API call to get list of keywords
	 *
	 * @param string $text Text to aanlyze.
	 * @return object|WP_Error
	 */
	public function keyphrase_extraction( string $text ) {
		$body = array(
			'kind'          => 'KeyPhraseExtraction',
			'parameters'    => array(
				'modelVersion' => 'latest',
			),
			'analysisInput' => array(
				array(
					'id'   => 1,
					'text' => $text,
				),
			),
		);

		return $this->sync_request( $body );
	}

	/**
	 * Perform syncronous request to Azure Language processing
	 *
	 * @param string|array|object $body The request body. JSON string, associative array or object.
	 * @return object|WP_Error
	 */
	private function sync_request( $body ) {
		$settings = $this->get_settings();
		$rtn      = false;
		$url      = $settings['url'];

		if ( is_string( $body ) ) {
			// Validate the JSON string.
			if ( empty( json_decode( $body ) ) ) {
				return new WP_Error( 'error', 'The body is not a JSON string' );
			}
		} else {
			// JSONify the body.
			$body = wp_json_encode( $body );
		}

		$request = wp_remote_post(
			trailingslashit( $url ) . $this->endpoint,
			array(
				'headers' => array(
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				),
				'body'    => $body,
			)
		);

		if ( ! is_wp_error( $request ) ) {
			$response_body = json_decode( wp_remote_retrieve_body( $request ) );

			if ( 200 !== wp_remote_retrieve_response_code( $request ) && isset( $response_body->message ) ) {
				$rtn = new WP_Error( $response_body->code ?? 'error', $response_body->message, $body );
			} else {
				$rtn = $response_body;
			}
		} else {
			$rtn = $request;
		}

		return $rtn;
	}
}
