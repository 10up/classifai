<?php

namespace Classifai\Features;

use Classifai\Services\LanguageProcessing;
use Classifai\Providers\Watson\NLU;
use Classifai\Providers\OpenAI\Embeddings;
use function Classifai\get_post_statuses_for_language_settings;
use function Classifai\get_post_types_for_language_settings;

/**
 * Class Classification
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
		$this->label = __( 'Classification', 'classifai' );

		// Contains all providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( LanguageProcessing::get_service_providers() );

		// Contains just the providers this feature supports.
		$this->supported_providers = [
			NLU::ID        => __( 'IBM Watson NLU', 'classifai' ),
			Embeddings::ID => __( 'OpenAI Embeddings', 'classifai' ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Enables the automatic content classification of posts.', 'classifai' );
	}

	/**
	 * Add any needed custom fields.
	 */
	public function add_custom_settings_fields() {
		$settings      = $this->get_settings();
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

		add_action( 'classifai_after_feature_settings_form', [ $this, 'render_previewer' ] );
	}

	/**
	 * Renders the previewer window for the feature.
	 *
	 * @param string $active_feature The ID of the current feature.
	 */
	public function render_previewer( string $active_feature = '' ) {
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
				// TODO: why this methods and not get_post_statuses_for_language_settings?
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
				<?php wp_nonce_field( 'classifai-previewer-action', 'classifai-previewer-nonce' ); ?>
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
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'post_statuses' => [],
			'post_types'    => [],
			'provider'      => NLU::ID,
		];
	}

	/**
	 * Sanitizes the default feature settings.
	 *
	 * @param array $new_settings Settings being saved.
	 * @return array
	 */
	public function sanitize_default_feature_settings( array $new_settings ): array {
		$settings = $this->get_settings();

		$new_settings['post_statuses'] = isset( $new_settings['post_statuses'] ) ? array_map( 'sanitize_text_field', $new_settings['post_statuses'] ) : $settings['post_statuses'];
		$new_settings['post_types']    = isset( $new_settings['post_types'] ) ? array_map( 'sanitize_text_field', $new_settings['post_types'] ) : $settings['post_types'];

		return $new_settings;
	}

	/**
	 * Runs the feature.
	 *
	 * @param mixed ...$args Arguments required by the feature depending on the provider selected.
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
		} elseif ( Embeddings::ID === $provider_instance::ID ) {
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
