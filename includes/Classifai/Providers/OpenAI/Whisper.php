<?php
/**
 * OpenAI Whisper (speech to text) integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Providers\OpenAI\Tokenizer;
use Classifai\Watson\Normalizer;
use function Classifai\get_asset_info;
use WP_Error;

class Whisper extends Provider {

	use \Classifai\Providers\OpenAI\OpenAI;

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
	protected $chatgpt_model = 'whisper-1';

	/**
	 * Supported file formats
	 *
	 * @var array
	 */
	protected $file_formats = [
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
	protected $max_file_size = 25 * MB_IN_BYTES;

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
		$settings = $this->get_settings();

		// Check if the current user has permission.
		$roles      = $settings['roles'] ?? [];
		$user_roles = wp_get_current_user()->roles ?? [];

		if (
			( ! empty( $roles ) && empty( array_diff( $user_roles, $roles ) ) )
			&& ( isset( $settings['enable_transcripts'] ) && 1 === (int) $settings['enable_transcripts'] )
		) {
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		}
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( $hook_suffix = '' ) {
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
