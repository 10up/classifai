<?php
/**
 * OpenAI Whisper (speech to text) integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\AudioTranscriptsGeneration;
use Classifai\Providers\Provider;
use WP_Error;

class Whisper extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	/**
	 * ID of the current provider.
	 *
	 * @var string
	 */
	const ID = 'openai_whisper';

	/**
	 * OpenAI Whisper URL
	 *
	 * @var string
	 */
	protected $whisper_url = 'https://api.openai.com/v1/audio/';

	/**
	 * OpenAI Whisper model
	 *
	 * @var string
	 */
	protected $whisper_model = 'whisper-1';

	/**
	 * Supported file formats
	 *
	 * @var array
	 */
	public $file_formats = [
		'mp3',
		'mp4',
		'mpeg',
		'mpga',
		'm4a',
		'wav',
		'webm',
	];

	/**
	 * Maximum file size our model supports
	 *
	 * @var int
	 */
	public $max_file_size = 25 * MB_IN_BYTES;

	/**
	 * OpenAI Whisper constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		parent::__construct(
			'OpenAI Whisper',
			'Whisper',
			'openai_whisper'
		);

		$this->feature_instance = $feature_instance;
	}

	/**
	 * Register any needed hooks.
	 */
	public function register() {
	}

	/**
	 * Builds the API url.
	 *
	 * @param string $path Path to append to API URL.
	 * @return string
	 */
	public function get_api_url( string $path = '' ): string {
		return sprintf( '%s%s', trailingslashit( $this->whisper_url ), $path );
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
				'description'   => sprintf(
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

		return $new_settings;
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id The Post ID we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( int $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid attachment ID is required to generate a transcript.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'transcript':
				$return = $this->transcribe_audio( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Start the audio transcription process.
	 *
	 * @param int   $attachment_id Attachment ID to process.
	 * @param array $args Optional arguments passed in.
	 * @return WP_Error|bool
	 */
	public function transcribe_audio( int $attachment_id = 0, array $args = [] ) {
		if ( $attachment_id && ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'no_permission', esc_html__( 'User does not have permission to edit this attachment.', 'classifai' ) );
		}

		$feature = new AudioTranscriptsGeneration();

		if ( ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Transcript generation is disabled. Please check your settings.', 'classifai' ) );
		}

		if ( ! $feature->should_process( $attachment_id ) ) {
			return new WP_Error( 'process_error', esc_html__( 'Attachment does not meet processing requirements. Ensure the file type and size meet requirements.', 'classifai' ) );
		}

		$settings = $feature->get_settings();

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );

		/**
		 * Filter the request body before sending to Whisper.
		 *
		 * @since 2.2.0
		 * @hook classifai_whisper_transcribe_request_body
		 *
		 * @param {array} $body Request body that will be sent to Whisper.
		 * @param {int} $attachment_id ID of attachment we are transcribing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_whisper_transcribe_request_body',
			[
				'file'            => get_attached_file( $attachment_id ) ?? '',
				'model'           => $this->whisper_model,
				'response_format' => 'json',
				'temperature'     => 0,
			],
			$attachment_id
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
