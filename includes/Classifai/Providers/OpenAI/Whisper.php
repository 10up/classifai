<?php
/**
 * OpenAI Whisper (speech to text) integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\Whisper\Transcribe;
use WP_Error;

class Whisper extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

	/**
	 * OpenAI Whisper constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'OpenAI Whisper',
			'Whisper',
			'openai_whisper',
			$service
		);

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'OpenAI Whisper', 'classifai' ),
			'fields'   => array( 'api-key' ),
			'features' => array(
				'enable_transcripts' => __( 'Generate transcripts from audio files', 'classifai' ),
			),
		);
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$settings = $this->get_settings();

		if ( empty( $settings ) || ( isset( $settings['authenticated'] ) && false === $settings['authenticated'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register what we need for the plugin.
	 *
	 * This only fires if can_register returns true.
	 */
	public function register() {
		add_action( 'add_attachment', [ $this, 'transcribe_audio' ] );
	}

	/**
	 * Start the audio transcription process.
	 *
	 * @param int $attachment_id Attachment ID to process.
	 * @return WP_Error|bool
	 */
	public function transcribe_audio( $attachment_id = 0 ) {
		$settings = $this->get_settings();

		// Check if the current user has permission.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if ( empty( $roles ) || ! empty( array_diff( $user_roles, $roles ) ) ) {
			return new WP_Error( 'invalid', esc_html__( 'User role does not have permission.', 'classifai' ) );
		}

		// Ensure feature is turned on.
		if ( ! isset( $settings['enable_transcripts'] ) || 1 !== (int) $settings['enable_transcripts'] ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Transcripts are not enabled.', 'classifai' ) );
		}

		$transcribe = new Transcribe( intval( $attachment_id ), $settings );

		return $transcribe->process();
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		$default_settings = $this->get_default_settings();

		$this->setup_api_fields( $default_settings['api_key'] );

		add_settings_field(
			'enable-transcripts',
			esc_html__( 'Generate transcripts from audio files', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_transcripts',
				'input_type'    => 'checkbox',
				'default_value' => $default_settings['enable_transcripts'],
				'description'   => __( 'Automatically generate transcripts for supported audio files.', 'classifai' ),
			]
		);

		$roles = get_editable_roles() ?? [];
		$roles = array_combine( array_keys( $roles ), array_column( $roles, 'name' ) );

		add_settings_field(
			'roles',
			esc_html__( 'Allowed roles', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'      => 'roles',
				'options'        => $roles,
				'default_values' => $default_settings['roles'],
				'description'    => __( 'Choose which roles are allowed to generate transcripts.', 'classifai' ),
			]
		);
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $settings Array of settings about to be saved.
	 *
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();
		$new_settings = array_merge(
			$new_settings,
			$this->sanitize_api_key_settings( $new_settings, $settings )
		);

		if ( empty( $settings['enable_transcripts'] ) || 1 !== (int) $settings['enable_transcripts'] ) {
			$new_settings['enable_transcripts'] = 'no';
		} else {
			$new_settings['enable_transcripts'] = '1';
		}

		if ( isset( $settings['roles'] ) && is_array( $settings['roles'] ) ) {
			$new_settings['roles'] = array_map( 'sanitize_text_field', $settings['roles'] );
		} else {
			$new_settings['roles'] = array_keys( get_editable_roles() ?? [] );
		}

		return $new_settings;
	}

	/**
	 * Resets settings for the provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings for Whisper.
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return [
			'authenticated'      => false,
			'api_key'            => '',
			'enable_transcripts' => false,
			'roles'              => array_keys( get_editable_roles() ?? [] ),
		];
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param array|null $settings Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be retrieved.
	 * @return string|array
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated     = 1 === intval( $settings['authenticated'] ?? 0 );
		$enable_transcript = 1 === intval( $settings['enable_transcript'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )        => $authenticated ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Generate transcripts', 'classifai' ) => $enable_transcript ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'Allowed roles', 'classifai' )        => implode( ', ', $settings['roles'] ?? [] ),
			__( 'Latest response', 'classifai' )      => $this->get_formatted_latest_response( 'classifai_openai_whisper_latest_response' ),
		];
	}

}
