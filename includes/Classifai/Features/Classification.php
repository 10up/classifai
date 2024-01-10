<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use \Classifai\Providers\Watson\NLU;
use \Classifai\Providers\OpenAI\Embeddings;
use function Classifai\get_post_statuses_for_language_settings;
use function Classifai\get_post_types_for_language_settings;

/**
 * Class TitleGeneration
 */
class Classification extends Feature {
	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_classification';

	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Every feature must set the `provider_instances` variable with the list of provider instances
		 * that are registered to a service.
		 */
		$service_providers        = LanguageProcessing::get_service_providers();
		$this->provider_instances = $this->get_provider_instances( $service_providers );
	}

	/**
	 * Returns the label of the feature.
	 *
	 * @return string
	 */
	public function get_label() {
		return apply_filters(
			'classifai_' . static::ID . '_label',
			__( 'Classification', 'classifai' )
		);
	}

	/**
	 * Returns the providers supported by the feature.
	 *
	 * @return array
	 */
	public function get_providers() {
		return apply_filters(
			'classifai_' . static::ID . '_providers',
			[
				NLU::ID        => __( 'IBM Watson NLU', 'classifai' ),
				Embeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
			]
		);
	}

	/**
	 * Sets up the fields and sections for the feature.
	 */
	public function setup_fields_sections() {
		$settings = $this->get_settings();

		/*
		 * These are the feature-level fields that are
		 * independent of the provider.
		 */
		add_settings_section(
			$this->get_option_name() . '_section',
			esc_html__( 'Feature settings', 'classifai' ),
			'__return_empty_string',
			$this->get_option_name()
		);

		add_settings_field(
			'status',
			esc_html__( 'Enable classification', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'status',
				'input_type'    => 'checkbox',
				'default_value' => $settings['status'],
				'description'   => __( 'Enables the automatic content classification of posts.', 'classifai' ),
			]
		);

		// Add user/role-based access fields.
		$this->add_access_control_fields();

		$post_statuses = get_post_statuses_for_language_settings();

		add_settings_field(
			'post_statuses',
			esc_html__( 'Post statuses', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_statuses',
				'options'        => $post_statuses,
				'default_values' => $settings['post_statuses'],
				'description'    => __( 'Choose which post statuses are allowed to use this feature.', 'classifai' ),
			]
		);

		$post_types        = get_post_types_for_language_settings();
		$post_type_options = array();

		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

		add_settings_field(
			'post_types',
			esc_html__( 'Post types', 'classifai' ),
			[ $this, 'render_checkbox_group' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'      => 'post_types',
				'options'        => $post_type_options,
				'default_values' => $settings['post_types'],
				'description'    => __( 'Choose which post types are allowed to use this feature.', 'classifai' ),
			]
		);

		add_settings_field(
			'provider',
			esc_html__( 'Select a provider', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name() . '_section',
			[
				'label_for'     => 'provider',
				'options'       => $this->get_providers(),
				'default_value' => $settings['provider'],
			]
		);

		/*
		 * The following renders the fields of all the providers
		 * that are registered to the feature.
		 */
		$this->render_provider_fields();
		add_action( 'classifai_after_feature_settings_form', [ $this, 'render_previewer' ] );
	}

	/**
	 * Renders the previewer window for the feature.
	 *
	 * @param string $active_feature The ID of the current feature.
	 *
	 * @return void
	 */
	public function render_previewer( $active_feature = '' ) {
		if ( static::ID !== $active_feature ) {
			return;
		}

		$settings = $this->get_settings();

		if ( ! $settings['status'] ) {
			return;
		}

		?>
		<div id="classifai-post-preview-app">
			<?php
				$supported_post_statuses = \Classifai\get_supported_post_statuses();
				$supported_post_types    = \Classifai\get_supported_post_types();

				$posts_to_preview = get_posts(
					array(
						'post_type'      => $supported_post_types,
						'post_status'    => $supported_post_statuses,
						'posts_per_page' => 10,
					)
				);

				$features = array(
					'category' => array(
						'name'    => esc_html__( 'Category', 'classifai' ),
						'enabled' => \Classifai\get_feature_enabled( 'category' ),
						'plural'  => 'categories',
					),
					'keyword'  => array(
						'name'    => esc_html__( 'Keyword', 'classifai' ),
						'enabled' => \Classifai\get_feature_enabled( 'keyword' ),
						'plural'  => 'keywords',
					),
					'entity'   => array(
						'name'    => esc_html__( 'Entity', 'classifai' ),
						'enabled' => \Classifai\get_feature_enabled( 'entity' ),
						'plural'  => 'entities',
					),
					'concept'  => array(
						'name'    => esc_html__( 'Concept', 'classifai' ),
						'enabled' => \Classifai\get_feature_enabled( 'concept' ),
						'plural'  => 'concepts',
					),
				);
			?>
			<h2><?php esc_html_e( 'Preview Language Processing', 'classifai' ); ?></h2>
			<div id="classifai-post-preview-controls">
				<select id="classifai-preview-post-selector">
					<?php foreach ( $posts_to_preview as $post ) : ?>
						<option value="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php wp_nonce_field( "classifai-previewer-action", "classifai-previewer-nonce" ); ?>
				<button type="button" class="button" id="get-classifier-preview-data-btn">
					<span><?php esc_html_e( 'Preview', 'classifai' ); ?></span>
				</button>
			</div>
			<div id="classifai-post-preview-wrapper">
				<?php foreach ( $features as $feature_slug => $feature ) : ?>
					<div class="tax-row tax-row--<?php echo esc_attr( $feature['plural'] ); ?> <?php echo esc_attr( $feature['enabled'] ) ? '' : 'tax-row--hide'; ?>">
						<div class="tax-type"><?php echo esc_html( $feature['name'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * The root-level keys are the setting keys that are independent of the provider.
	 * Provider specific settings should be nested under the provider key.
	 *
	 * @todo Add a filter hook to allow other plugins to add their own settings.
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		$provider_settings = $this->get_provider_default_settings();
		$feature_settings  = [
			'post_statuses' => [],
			'post_types'    => [],
			'provider'      => NLU::ID,
		];

		return apply_filters(
			'classifai_' . static::ID . '_get_default_settings',
			array_merge(
				parent::get_default_settings(),
				$feature_settings,
				$provider_settings
			)
		);
	}

	/**
	 * Sanitizes the settings before saving.
	 *
	 * @param array $new_settings The settings to be sanitized on save.
	 *
	 * @return array
	 */
	public function sanitize_settings( $new_settings ) {
		$settings = $this->get_settings();

		// Sanitization of the feature-level settings.
		$new_settings                  = parent::sanitize_settings( $new_settings );
		$new_settings['post_statuses'] = isset( $new_settings['post_statuses'] ) ? array_map( 'sanitize_text_field', $new_settings['post_statuses'] ) : $settings['roles'];
		$new_settings['post_types']    = isset( $new_settings['post_types'] ) ? array_map( 'sanitize_text_field', $new_settings['post_types'] ) : $settings['roles'];

		// Sanitization of the provider-level settings.
		$provider_instance = $this->get_feature_provider_instance( $new_settings['provider'] );
		$new_settings      = $provider_instance->sanitize_settings( $new_settings );

		return apply_filters(
			'classifai_' . static::ID . '_sanitize_settings',
			$new_settings,
			$settings
		);
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
	 *
	 * @return mixed
	 */
	public function run( ...$args ) {
		$settings          = $this->get_settings();
		$provider_id       = $settings['provider'] ?? NLU::ID;
		$provider_instance = $this->get_feature_provider_instance( $provider_id );
		$result            = '';

		if ( NLU::ID === $provider_instance::ID ) {
			/** @var NLU $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'classify' ],
				[ ...$args ]
			);
		} else if ( Embeddings::ID === $provider_instance::ID ) {
			/** @var Embeddings $provider_instance */
			$result = call_user_func_array(
				[ $provider_instance, 'generate_embeddings_for_post' ],
				[ ...$args ]
			);
		}

		return apply_filters(
			'classifai_' . static::ID . '_run',
			$result,
			$provider_instance,
			$args,
			$this
		);
	}
}
