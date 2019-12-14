<?php
/**
 * IBM Watson NLU
 */

namespace Classifai\Providers\Watson;

use Classifai\Admin\SavePostHandler;
use Classifai\Providers\Provider;
use Classifai\Taxonomy\TaxonomyFactory;

class LanguageTranslator extends Provider {

	/**
	 * @var $taxonomy_factory TaxonomyFactory Watson taxonomy factory
	 */
	public $taxonomy_factory;

	/**
	 * @var $save_post_handler SavePostHandler Triggers a classification with Watson
	 */
	public $save_post_handler;

	/**
	 * @var $master_languages array A list of languages supported in the Language Translator service
	 */
	public $master_languages;

	/**
	 * @var $alternative_languages array A list of languages natively supported in the NLU service
	 */
	public $alternative_languages;

	/**
	 * Watson NLU constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'IBM Watson',
			'Language Translator',
			'watson_lt',
			$service
		);

	}

	/**
	 * Resets the settings for the NLU provider.
	 */
	public function reset_settings() {
		$settings = [];

		update_option( $this->get_option_name(), $settings );
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		// TODO: Implement can_register() method.
		return true;
	}

	/**
	 * Register what we need for the plugin.
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		$this->taxonomy_factory = new TaxonomyFactory();
		$this->taxonomy_factory->build_all();

		$this->save_post_handler = new SavePostHandler();

		if ( $this->save_post_handler->can_register() ) {
			$this->save_post_handler->register();
		}
	}

	/**
	 * Helper to get the settings and allow for settings default values.
	 *
	 * Overridden from parent to polyfill older settings storage schema.
	 *
	 * @param string|bool|mixed $index Optional. Name of the settings option index.
	 *
	 * @return array
	 */
	public function get_settings( $index = false ) {
		$defaults = [];
		$settings = get_option( $this->get_option_name(), [] );

		$settings = wp_parse_args( $settings, $defaults );

		if ( $index && isset( $settings[ $index ] ) ) {
			return $settings[ $index ];
		}

		return $settings;
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		// Create the Credentials Section.
		$this->do_credentials_section();
		// Create the Languages Section.
		$this->do_languages_section();
	}

	/**
	 * Helper method to create the credentials section
	 */
	protected function do_credentials_section() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		add_settings_field(
			'url',
			esc_html__( 'API URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'    => 'watson_url',
				'option_index' => 'credentials',
				'input_type'   => 'text',
			]
		);
		add_settings_field(
			'username',
			esc_html__( 'API Username', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'watson_username',
				'option_index'  => 'credentials',
				'input_type'    => 'text',
				'default_value' => 'apikey',
				'description'   => __( 'If your credentials do not include a username, it is typically apikey', 'classifai' ),
			]
		);
		add_settings_field(
			'password',
			esc_html__( 'API Key / Password', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'    => 'watson_password',
				'option_index' => 'credentials',
				'input_type'   => 'password',
			]
		);
	}

	/**
	 * Create the languages section
	 */
	protected function do_languages_section() {
		// Add the settings section.
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );

		add_settings_field(
			'language',
			esc_html__( 'Languages', 'classifai' ),
			[ $this, 'render_language_selectors' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'option_index' => 'languages',
				'languages'    => $this->get_languages_available(),
				'selected'     => $this->get_selected_languages(),
			]
		);
	}

	/**
	 * Retrieve languages available
	 *
	 * @return array
	 */
	protected function get_languages_available() {
		$nlu                         = new NLU( 'lt' );
		$this->master_languages      = $this->get_languages();
		$this->alternative_languages = $nlu->get_languages();

		return [
			'master'      => $this->master_languages,
			'alternative' => $this->alternative_languages,
		];
	}

	/**
	 * Get the user's choice of languages from the settings
	 *
	 * @return array
	 */
	protected function get_selected_languages() {
		$settings = $this->get_settings();

		return $settings['languages'];
	}

	/**
	 * Generic text input field callback
	 *
	 * @param array $args The args passed to add_settings_field.
	 */
	public function render_input( $args ) {
		$setting_index = $this->get_settings( $args['option_index'] );
		$type          = $args['input_type'] ?? 'text';
		$value         = ( isset( $setting_index[ $args['label_for'] ] ) ) ? $setting_index[ $args['label_for'] ] : '';

		// Check for a default value
		$value = ( empty( $value ) && isset( $args['default_value'] ) ) ? $args['default_value'] : $value;
		$attrs = '';
		$class = '';

		switch ( $type ) {
			case 'text':
			case 'password':
				$attrs = ' value="' . esc_attr( $value ) . '"';
				$class = 'regular-text';
				break;
			case 'number':
				$attrs = ' value="' . esc_attr( $value ) . '"';
				$class = 'small-text';
				break;
			case 'checkbox':
				$attrs = ' value="1"' . checked( '1', $value, false );
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
	 * Render language selectors
	 *
	 * @param array $args Settings for the input
	 */
	public function render_language_selectors( $args ) {
		?>
		<fieldset>
			<p>
				<label for="classifai-settings-language-master">Master Language</label><br/>
				<span class="description">
				<?php esc_html_e( 'Select the language that your content is primarily written in. You can override this setting on each post.', 'classifai' ); ?>
				</span><br/>
				<select name="classifai_<?php echo esc_attr( $this->option_name ); ?>[languages][master]">
					<?php
					foreach ( (array) $args['languages']['master']['languages'] as $lang ) {
						echo '<option value="' . esc_attr( $lang['language'] ) . '" ' . selected( $lang['language'], $args['selected']['master'], false ) . '>' . esc_html( $lang['name'] ) . '</option>';
					}
					?>
				</select><br/>
			</p>
			<p>
				<label for="classifai-settings-language-alternative">Alternative Classification Language</label><br/>
				<span class="description">
				<?php
				esc_html_e(
					'Select the language you wish your content is translated into before classification.
				This will most likely be a language that shares the most vocabulary and structure with your master language',
					'classifai'
				);
				?>
				</span><br/>
				<select name="classifai_<?php echo esc_attr( $this->option_name ); ?>[languages][alternative]">
					<?php
					foreach ( (array) $args['languages']['alternative'] as $code => $lang ) {
						echo '<option value="' . esc_attr( $code ) . '" ' . selected( $code, $args['selected']['alternative'], false ) . '>' . esc_html( $lang ) . '</option>';
					}
					?>
				</select><br/>
				<span class="description">
					<?php
					esc_html_e(
						'The support for features in each language varies. For broader support, choose English.
					You can find the latest support information in the documentation: ',
						'classifai'
					);
					?>
					<a href="https://cloud.ibm.com/docs/services/natural-language-understanding?topic=natural-language-understanding-language-support">IBM Watson NLU: Language Support</a>
				</span>
			</p>
		</fieldset>
		<?php
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
		if ( $this->authentication_check_failed( $settings ) ) {
			add_settings_error(
				'credentials',
				'classifai-auth',
				esc_html__( 'IBM Watson NLU Authentication Failed. Please check credentials.', 'classifai' ),
				'error'
			);
		}

		if ( isset( $settings['credentials']['watson_url'] ) ) {
			$new_settings['credentials']['watson_url'] = esc_url_raw( $settings['credentials']['watson_url'] );
		}

		if ( isset( $settings['credentials']['watson_username'] ) ) {
			$new_settings['credentials']['watson_username'] = sanitize_text_field( $settings['credentials']['watson_username'] );
		}

		if ( isset( $settings['credentials']['watson_password'] ) ) {
			$new_settings['credentials']['watson_password'] = sanitize_text_field( $settings['credentials']['watson_password'] );
		}

		// Sanitize language choices
		if ( isset( $settings['languages']['master'] ) ) {
			$new_settings['languages']['master'] = sanitize_text_field( $settings['languages']['master'] );
		}

		if ( isset( $settings['languages']['alternative'] ) ) {
			$new_settings['languages']['alternative'] = sanitize_text_field( $settings['languages']['alternative'] );
		}

		return $new_settings;
	}

	/**
	 * Get Language Translation service language support
	 *
	 * @return array
	 */
	public function get_languages() {
		$settings = $this->get_settings();

		$request           = new \Classifai\Watson\APIRequest();
		$request->username = $settings['credentials']['watson_username'];
		$request->password = $settings['credentials']['watson_password'];
		$base_url          = trailingslashit( $settings['credentials']['watson_url'] ) . 'v3/identifiable_languages';
		$url               = esc_url( add_query_arg( [ 'version' => WATSON_LT_VERSION ], $base_url ) );
		$response          = $request->get( $url );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_messages();
		}

		return $response;
	}

	/**
	 * Helper to ensure the authentication works.
	 *
	 * @param array $settings The list of settings to be saved
	 *
	 * @return bool
	 */
	protected function authentication_check_failed( $settings ) {

		// Check that we have credentials before hitting the API.
		if ( ! isset( $settings['credentials'] )
			 || empty( $settings['credentials']['watson_username'] )
			 || empty( $settings['credentials']['watson_password'] )
			 || empty( $settings['credentials']['watson_url'] )
		) {
			return true;
		}

		$request           = new \Classifai\Watson\APIRequest();
		$request->username = $settings['credentials']['watson_username'];
		$request->password = $settings['credentials']['watson_password'];
		$base_url          = trailingslashit( $settings['credentials']['watson_url'] ) . 'v3/identifiable_languages';
		$url               = esc_url( add_query_arg( [ 'version' => WATSON_LT_VERSION ], $base_url ) );
		$response          = $request->get( $url );

		$is_error = is_wp_error( $response );

		if ( ! $is_error ) {
			update_option( 'classifai_lt_configured', true );
		} else {
			delete_option( 'classifai_lt_configured' );
		}

		return $is_error;
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param array|null $settings   Settings array. If empty, settings will be retrieved.
	 * @param boolean    $configured Whether the provider is correctly configured. If null, the option will be
	 *                               retrieved.
	 *
	 * @return string|array
	 * @since 1.4.0
	 */
	public function get_provider_debug_information( $settings = null, $configured = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		if ( is_null( $configured ) ) {
			$configured = get_option( 'classifai_configured' );
		}

		$settings_post_types = $settings['post_types'] ?? [];
		$post_types          = array_filter(
			array_keys( $settings_post_types ),
			function ( $post_type ) use ( $settings_post_types ) {
				return 1 === intval( $settings_post_types[ $post_type ] );
			}
		);

		$credentials = $settings['credentials'] ?? [];

		return [
			__( 'Configured', 'classifai' )   => $configured ? __( 'yes', 'classifai' ) : __( 'no', 'classifai' ),
			__( 'API URL', 'classifai' )      => $credentials['watson_url'] ?? '',
			__( 'API username', 'classifai' ) => $credentials['watson_username'] ?? '',
			__( 'Languages', 'classifai' )    => preg_replace( '/,"/', ', "', wp_json_encode( $settings['languages'] ?? '' ) ),
		];
	}
}
