<?php
/**
 * OpenAI Speech to Text integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\AudioTranscriptsGeneration;
use Classifai\Providers\Provider;
use WP_Error;

use function Classifai\is_attachment;
use function Classifai\is_remote_url;
use function Classifai\is_local_path;

class SpeechToText extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	/**
	 * ID of the current provider.
	 *
	 * @var string
	 */
	const ID = 'openai_whisper';

	/**
	 * OpenAI Audio API URL
	 *
	 * @var string
	 */
	protected $audio_url = 'https://api.openai.com/v1/audio/';

	/**
	 * Supported file formats
	 *
	 * @var array
	 */
	public $file_formats = [
		'mp3',
		'mp4',
		'mpeg',
		'wav',
		'ogg',
	];

	/**
	 * Maximum file size our model supports
	 *
	 * @var int
	 */
	public $max_file_size = 25 * MB_IN_BYTES;

	/**
	 * OpenAI Speech to Text constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Get the model name.
	 *
	 * @return string
	 */
	public function get_model(): string {
		$settings = $this->feature_instance->get_settings();
		$model    = $settings[ static::ID ]['model'] ?? 'gpt-4o-mini-transcribe';

		/**
		 * Filter the model name.
		 *
		 * Useful if you want to change the model for certain use cases.
		 *
		 * @since 3.4.0
		 * @hook classifai_openai_speech_to_text_model
		 *
		 * @param string $model The current model to use.
		 *
		 * @return string The model to use.
		 */
		return apply_filters( 'classifai_openai_speech_to_text_model', $model );
	}

	/**
	 * Builds the API url.
	 *
	 * @param string $path Path to append to API URL.
	 * @return string
	 */
	public function get_api_url( string $path = '' ): string {
		/**
		 * Filter the API URL.
		 *
		 * @since 3.4.0
		 * @hook classifai_openai_speech_to_text_api_url
		 *
		 * @param string $url The default API URL.
		 *
		 * @return string The API URL.
		 */
		return apply_filters( 'classifai_openai_speech_to_text_api_url', sprintf( '%s%s', trailingslashit( $this->audio_url ), $path ) );
	}

	/**
	 * Register settings for this provider.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			static::ID . '_api_key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $settings['api_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					sprintf(
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
					),
			]
		);

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Get the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'api_key'       => '',
			'model'         => 'gpt-4o-mini-transcribe',
			'authenticated' => false,
		];

		return $common_settings;
	}

	/**
	 * Sanitize the settings for this provider.
	 *
	 * @param array $new_settings New settings.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings                                    = $this->feature_instance->get_settings();
		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( in_array( $new_settings[ static::ID ]['model'], [ 'whisper-1', 'gpt-4o-mini-transcribe', 'gpt-4o-transcribe' ], true ) ) {
			$new_settings[ static::ID ]['model'] = sanitize_text_field( $new_settings[ static::ID ]['model'] );
		} else {
			$new_settings[ static::ID ]['model'] = 'gpt-4o-mini-transcribe';
		}

		return $new_settings;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param string $audio_resource Attachment ID, URL or system file path to the audio resource.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( string $audio_resource, string $route_to_call = '', array $args = [] ) {
		$return = '';

		switch ( $route_to_call ) {
			case 'transcript':
				if ( is_attachment( $audio_resource ) ) {
					return $this->feature_instance->transcribe_from_attachment( $audio_resource, $args );
				} elseif ( is_remote_url( $audio_resource ) ) {
					return $this->feature_instance->transcribe_from_path( $audio_resource );
				} elseif ( is_local_path( $audio_resource ) ) {
					return $this->transcribe_audio( $audio_resource, $args );
				}
				break;
			default:
				break;
		}

		return $return;
	}

	/**
	 * Run the audio transcription process.
	 *
	 * @param string $file_path File system path.
	 * @param array  $args      Optional arguments passed in.
	 * @return WP_Error|bool
	 */
	public function transcribe_audio( string $file_path = '', array $args = [] ) {
		$feature  = new AudioTranscriptsGeneration();
		$settings = $feature->get_settings( static::ID );

		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Audio Transcripts Generation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings['api_key'] ?? '', $feature->get_option_name() );

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 2.2.0
		 * @hook classifai_whisper_transcribe_request_body
		 *
		 * @param array  $body      Request body that will be sent to OpenAI.
		 * @param string $file_path Path of the attachment we are transcribing.
		 * @param array  $args      Additional args.
		 *
		 * @return array Request body.
		 */
		$body = apply_filters(
			'classifai_whisper_transcribe_request_body',
			[
				'file'            => $file_path,
				'model'           => $this->get_model(),
				'response_format' => 'json',
				'temperature'     => 0,
			],
			$file_path,
			$args
		);

		// Make our API request.
		$response = $request->post_form(
			$this->get_api_url( 'transcriptions' ),
			$body
		);

		set_transient( 'classifai_openai_whisper_latest_response', $response, DAY_IN_SECONDS * 30 );

		// Extract out the text response, if it exists.
		if ( ! is_wp_error( $response ) && isset( $response['text'] ) ) {
			$response = $response['text'];
		}

		return $response;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings   = $this->feature_instance->get_settings();
		$debug_info = [];

		if ( $this->feature_instance instanceof AudioTranscriptsGeneration ) {
			$debug_info[ __( 'Latest response', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_openai_whisper_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
