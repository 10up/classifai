<?php
/**
 * Provides Text to Speech synthesis feature using Microsoft Azure Speech to Text.
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use WP_Http;

use function Classifai\get_post_types_for_language_settings;
use function Classifai\get_asset_info;

class TextToSpeech extends Provider {

	/**
	 * Name of the feature that is displayed to the end user.
	 *
	 * @var string
	 */
	const FEATURE_NAME = 'Text to Speech';

	/**
	 * Azure's Speech to Text endpoint path.
	 *
	 * @var string
	 */
	const API_PATH = 'cognitiveservices/v1';

	/**
	 * Meta key to store data indicating whether Speech to Text is enabled for
	 * the current post.
	 *
	 * @var string
	 */
	const SYNTHESIZE_SPEECH_KEY = '_classifai_synthesize_speech';

	/**
	 * Meta key to get/set the ID of the speech audio file.
	 *
	 * @var string
	 */
	const AUDIO_ID_KEY = '_classifai_post_audio_id';

	/**
	 * Meta key to get/set the timestamp indicating when the speech was generated.
	 * Used for cache-busting as the audio filename remains static for a given post.
	 *
	 * @var string
	 */
	const AUDIO_TIMESTAMP_KEY = '_classifai_post_audio_timestamp';

	/**
	 * Watson NLU constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'Microsoft Azure',
			self::FEATURE_NAME,
			'azure_text_to_speech',
			$service
		);
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function enqueue_editor_assets() {
		global $post;
		wp_enqueue_script(
			'classifai-editor', // Handle.
			CLASSIFAI_PLUGIN_URL . 'dist/editor.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-edit-post' ),
			CLASSIFAI_PLUGIN_VERSION,
			true
		);

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'classifai-gutenberg-plugin',
			CLASSIFAI_PLUGIN_URL . 'dist/gutenberg-plugin.js',
			array( 'lodash', 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-plugins' ),
			CLASSIFAI_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'classifai-gutenberg-plugin',
			'classifaiTextToSpeechData',
			[
				'supportedPostTypes' => self::get_supported_post_types(),
				'noPermissions'      => ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ),
			]
		);
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$options = $this->get_settings();

		if ( empty( $options ) || ( isset( $options['authenticated'] ) && false === $options['authenticated'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register the actions needed.
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_filter( 'rest_api_init', [ $this, 'add_synthesize_speech_meta_to_rest_api' ] );
		add_filter( 'the_content', [ $this, 'render_post_audio_controls' ] );
	}

	/**
	 * Resets settings for the Personalizer provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Set up the fields for each section.
	 */
	public function setup_fields_sections() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		$default_settings = $this->get_default_settings();
		$voices_options   = $this->get_voices_select_options();

		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'url',
				'input_type'    => 'text',
				'default_value' => $default_settings['url'],
				'description'   => __( 'Supported protocol and hostname endpoints, e.g., <code>https://REGION.api.cognitive.microsoft.com</code> or <code>https://EXAMPLE.cognitiveservices.azure.com</code>. This can look different based on your setting choices in Azure.', 'classifai' ),
			]
		);

		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'api_key',
				'input_type'    => 'password',
				'default_value' => $default_settings['api_key'],
			]
		);

		add_settings_field(
			'post-types',
			esc_html__( 'Post Types', 'classifai' ),
			[ $this, 'render_post_types_checkboxes' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'option_index' => 'post_types',
			]
		);

		if ( ! empty( $voices_options ) ) {
			add_settings_field(
				'voice',
				esc_html__( 'Voice', 'classifai' ),
				[ $this, 'render_select' ],
				$this->get_option_name(),
				$this->get_option_name(),
				[
					'label_for'     => 'voice',
					'options'       => $voices_options,
					'default_value' => $default_settings['voice'],
				]
			);
		}
	}

	/**
	 * Sanitization callback for settings.
	 *
	 * @param array $settings The settings being saved.
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();

		if ( ! empty( $settings['url'] ) && ! empty( $settings['api_key'] ) ) {
			$new_settings['url']     = trailingslashit( esc_url_raw( $settings['url'] ) );
			$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] );

			$is_connected = $this->connect_to_service(
				array(
					'url'     => $new_settings['url'],
					'api_key' => $new_settings['api_key'],
				)
			);

			if ( is_wp_error( $is_connected ) ) {
				$new_settings['authenticated'] = false;
			} else {
				$new_settings['authenticated'] = true;
			}
		} else {
			$new_settings['url']           = '';
			$new_settings['api_key']       = '';
			$new_settings['authenticated'] = false;

			add_settings_error(
				$this->get_option_name(),
				'classifai-azure-text-to-speech-auth-empty',
				esc_html__( 'One or more credentials required to connect to the Azure Text to Speech service is empty.', 'classifai' ),
				'error'
			);
		}

		// Sanitize the post type checkboxes
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		foreach ( $post_types as $post_type ) {
			if ( isset( $settings['post_types'][ $post_type->name ] ) ) {
				$new_settings['post_types'][ $post_type->name ] = $settings['post_types'][ $post_type->name ];
			} else {
				$new_settings['post_types'][ $post_type->name ] = null;
			}
		}

		if ( isset( $settings['voice'] ) && ! empty( $settings['voice'] ) ) {
			$new_settings['voice'] = sanitize_text_field( $settings['voice'] );
		} else {
			$new_settings['voice'] = '';
		}

		return $new_settings;
	}

	/**
	 * Connects to Azure's Text to Speech service.
	 *
	 * @param array $args Overridable args.
	 * @return boolean
	 */
	public function connect_to_service( array $args = array() ) {
		$options = $this->get_settings();

		$default = array(
			'url'     => isset( $options['url'] ) ? $options['url'] : '',
			'api_key' => isset( $options['api_key'] ) ? $options['api_key'] : '',
		);

		$default = array_merge( $default, $args );

		$voices_transient = get_transient( 'classifai-azure-speech-to-text-voices' );

		// Return if transient exists.
		if ( false !== $voices_transient ) {
			return false;
		}

		// Return if credentials don't exist.
		if ( empty( $default['url'] ) || empty( $default['api_key'] ) ) {
			return false;
		}

		// Create request arguments.
		$request_params = array(
			'headers' => array(
				'Ocp-Apim-Subscription-Key' => $default['api_key'],
				'Content-Type'              => 'application/json',
			),
		);

		// Create request URL.
		$request_url = sprintf(
			'%1$scognitiveservices/voices/list',
			$default['url']
		);

		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$response = vip_safe_wp_remote_get(
				$request_url,
				'',
				3,
				1,
				20,
				$request_params
			);
		} else {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- use of `vip_safe_wp_remote_get` is done when available.
			$response = wp_remote_get(
				$request_url,
				$request_params
			);
		}

		if ( is_wp_error( $response ) ) {
			add_settings_error(
				$this->get_option_name(),
				'azure-text-to-request-failed',
				esc_html__( 'HTTP request failed.', 'classifai' ),
				'error'
			);
			return false;
		}

		$http_code = wp_remote_retrieve_response_code( $response );

		// Return and render error if HTTP response status code is other than 200.
		if ( WP_Http::OK !== $http_code ) {
			add_settings_error(
				$this->get_option_name(),
				'azure-text-to-speech-auth-failed',
				esc_html__( 'Connection to Azure Text to Speech failed.', 'classifai' ),
				'error'
			);

			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$voices        = json_decode( $response_body );

		set_transient( 'classifai-azure-speech-to-text-voices', $voices, MONTH_IN_SECONDS );

		return true;
	}

	/**
	 * Returns HTML select dropdown options for voices.
	 *
	 * @return array
	 */
	public function get_voices_select_options() {
		$voices_transient = get_transient( 'classifai-azure-speech-to-text-voices' );
		$options          = array();

		if ( false === $voices_transient ) {
			return $options;
		}

		foreach ( $voices_transient as $voice ) {
			// phpcs is disabled because it throws error for camel case.
			// phpcs:disable
			$options[ "{$voice->ShortName}|{$voice->Gender}" ] = sprintf(
				'%1$s (%2$s/%3$s)',
				esc_html( $voice->LocaleName ),
				esc_html( $voice->DisplayName ),
				esc_html( $voice->Gender )
			);
			// phpcs:enable
		}

		return $options;
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param null|array $settings   Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be retrieved.
	 * @return array Keyed array of debug information.
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated = 1 === intval( $settings['authenticated'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' ) => $authenticated ? __( 'Yes', 'classifai' ) : __( 'No', 'classifai' ),
			__( 'API URL', 'classifai' )       => $settings['url'] ?? '',
		];
	}

	/**
	 * Returns the default settings.
	 */
	private function get_default_settings() {
		return [
			'url'           => '',
			'api_key'       => '',
			'voice'         => '',
			'authenticated' => false,
		];
	}

	/**
	 * Add `classifai_synthesize_speech` to rest API for view/edit.
	 */
	public function add_synthesize_speech_meta_to_rest_api() {
		$settings             = $this->get_settings();
		$supported_post_types = $settings['post_types'] ?? array();

		$supported_post_types = array_keys(
			array_filter(
				$supported_post_types,
				function( $post_type ) {
					return ! is_null( $post_type );
				}
			)
		);

		register_rest_field(
			$supported_post_types,
			'classifai_synthesize_speech',
			array(
				'get_callback'    => function( $object ) {
					$process_content = get_post_meta( $object['id'], self::SYNTHESIZE_SPEECH_KEY, true );
					return ( 'no' === $process_content ) ? 'no' : 'yes';
				},
				'update_callback' => function ( $value, $object ) {
					$value = ( 'no' === $value ) ? 'no' : 'yes';
					return update_post_meta( $object->ID, self::SYNTHESIZE_SPEECH_KEY, $value );
				},
				'schema'          => [
					'type'    => 'string',
					'context' => [ 'view', 'edit' ],
				],
			)
		);

		register_rest_field(
			$supported_post_types,
			'classifai_post_audio_id',
			array(
				'get_callback' => function( $object ) {
					$post_audio_id = get_post_meta( $object['id'], self::AUDIO_ID_KEY, true );
					return (int) $post_audio_id;
				},
				'schema'       => [
					'type'    => 'integer',
					'context' => [ 'view', 'edit' ],
				],
			)
		);
	}

	/**
	 * Adds audio controls to the post that has speech sythesis enabled.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function render_post_audio_controls( $content ) {
		global $post;

		if ( ! $post ) {
			return $content;
		}

		/**
		 * Filter to disable Text to Speech synthesis for a post by post ID.
		 *
		 * @param boolean 'is_disabled' Boolean to toggle the service. By default - false.
		 * @param boolean 'post_id'     Post ID.
		 */
		if ( apply_filters( 'classifai_disable_post_to_audio_block', false, $post->ID ) ) {
			return $content;
		}

		wp_enqueue_script(
			'classifai-post-audio-player-js',
			CLASSIFAI_PLUGIN_URL . '/dist/post-audio-controls.js',
			get_asset_info( 'post-audio-controls', 'dependencies' ),
			get_asset_info( 'post-audio-controls', 'version' ),
			true
		);

		wp_enqueue_style(
			'classifai-post-audio-player-css',
			CLASSIFAI_PLUGIN_URL . '/dist/post-audio-controls.css',
			array(),
			get_asset_info( 'post-audio-controls', 'version' ),
			'all'
		);

		$audio_attachment_id = (int) get_post_meta( $post->ID, self::AUDIO_ID_KEY, true );

		if ( ! $audio_attachment_id ) {
			return $content;
		}

		$audio_timestamp      = (int) get_post_meta( $post->ID, self::AUDIO_TIMESTAMP_KEY, true );
		$audio_attachment_url = sprintf(
			'%1$s?ver=%2$s',
			wp_get_attachment_url( $audio_attachment_id ),
			filter_var( $audio_timestamp, FILTER_SANITIZE_NUMBER_INT )
		);

		ob_start();

		?>
			<div>
				<div class='classifai-listen-to-post-wrapper'>
					<div class="class-post-audio-controls">
						<span class="dashicons dashicons-controls-play"></span>
						<span class="dashicons dashicons-controls-pause"></span>
					</div>
					<div class='classifai-post-audio-heading'>
						<?php
							$list_to_post_text = apply_filters( 'classifai_listen_to_this_post_text', esc_html__( 'Listen to this post', 'classifai' ) );
							echo esc_html( $list_to_post_text );
						?>
					</div>
				</div>
				<audio id="classifai-post-audio-player" src="<?php echo esc_url( $audio_attachment_url ); ?>"></audio>
			</div>
		<?php

		return ob_get_clean() . $content;
	}

	/**
	 * Render the post types checkbox array.
	 *
	 * @param array $args Settings for the input
	 *
	 * @return void
	 */
	public function render_post_types_checkboxes( $args ) {
		echo '<ul>';
		$post_types = get_post_types_for_language_settings();
		foreach ( $post_types as $post_type ) {
			$args = [
				'label_for'    => $post_type->name,
				'option_index' => 'post_types',
				'input_type'   => 'checkbox',
			];

			echo '<li>';
			$this->render_checkbox_group( $args );
			echo '<label for="classifai-settings-' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->label ) . '</label>';
			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Generic text input field callback
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_checkbox_group( array $args = array() ) {
		$setting_index = $this->get_settings( $args['option_index'] );
		$type          = $args['input_type'] ?? 'text';
		$value         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';

		// Check for a default value
		$value = ( empty( $value ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $value;
		$attrs = '';
		$class = '';

		switch ( $type ) {
			case 'checkbox':
				$attrs = ' value="' . esc_attr( $args['label_for'] ) . '"' . checked( $args['label_for'], $value, false );
				break;
		}
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="classifai-settings-<?php echo esc_attr( $args['label_for'] ); ?>"
			class="<?php echo esc_attr( $class ); ?>"
			name="classifai_<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $args['option_index'] ); ?>][<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<br /><span class="description">' . wp_kses_post( $args['description'] ) . '</span>';
		}
	}

	/**
	 * Returns supported post types for Azure Text to Speech.
	 *
	 * @todo Move this to a more generic method during refactoring of the plugin.
	 * @return array
	 */
	public static function get_supported_post_types() {
		$settings             = \Classifai\get_plugin_settings( 'language_processing', self::FEATURE_NAME );
		$supported_post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array();

		return array_keys(
			array_filter(
				$supported_post_types,
				function( $post_type ) {
					return ! is_null( $post_type );
				}
			)
		);
	}
}
