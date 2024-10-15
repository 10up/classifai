<?php
/**
 * Chrome AI integration
 */

namespace Classifai\Providers\Browser;

use Classifai\Features\ContentResizing;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Provider;
use Classifai\Normalizer;
use WP_Error;

use function Classifai\get_default_prompt;
use function Classifai\sanitize_number_of_responses_field;

class ChromeAI extends Provider {

	/**
	 * Provider ID
	 *
	 * @var string
	 */
	const ID = 'chrome_ai';

	/**
	 * ChromeAI constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Register what we need for the plugin.
	 */
	public function register() {
		// TODO: find a better hook for this.
		add_action( 'wp_print_scripts', [ $this, 'output_excerpt_script' ] );
	}

	/**
	 * Render the provider fields.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				add_settings_field(
					static::ID . '_number_of_suggestions',
					esc_html__( 'Number of suggestions', 'classifai' ),
					[ $this->feature_instance, 'render_input' ],
					$this->feature_instance->get_option_name(),
					$this->feature_instance->get_option_name() . '_section',
					[
						'option_index'  => static::ID,
						'label_for'     => 'number_of_suggestions',
						'input_type'    => 'number',
						'min'           => 1,
						'step'          => 1,
						'default_value' => $settings['number_of_suggestions'],
						'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
						'description'   => esc_html__( 'Number of suggestions that will be generated in one request.', 'classifai' ),
					]
				);
				break;
		}

		do_action( 'classifai_' . static::ID . '_render_provider_fields', $this );
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'authenticated' => true,
		];

		/**
		 * Default values for feature specific settings.
		 */
		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				$common_settings['number_of_suggestions'] = 1;
				break;
		}

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

		switch ( $this->feature_instance::ID ) {
			case ContentResizing::ID:
			case TitleGeneration::ID:
				$new_settings[ static::ID ]['number_of_suggestions'] = sanitize_number_of_responses_field( 'number_of_suggestions', $new_settings[ static::ID ], $settings[ static::ID ] );
				break;
		}

		return $new_settings;
	}

	/**
	 * Output the excerpt script.
	 *
	 * This will make a request to the Chrome AI API to generate an excerpt.
	 */
	public function output_excerpt_script() {
		// TODO: ensure this only loads on single admin content.
		?>
		<script type="text/javascript">
			async function classifaiChromeAITextGeneration( prompt = '', content = '' ) {
				let result = '';

				if ( ! window.ai ) {
					return result;
				}

				const supportsTextGeneration = await window.ai.languageModel.capabilities();

				if (
					supportsTextGeneration &&
					supportsTextGeneration.available === 'readily'
				) {
					const session = await window.ai.languageModel.create( {
						initialPrompts: [
							{
								role: 'system',
								content: prompt,
							},
						]
					} );
					result = await session.prompt( `"""${content}"""` );
				}

				return result;
			}
		</script>
		<?php
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id The Post ID we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id = 0, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate titles.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'excerpt':
				$return = $this->generate_excerpt( $post_id, $args );
				break;
		}

		return $return;
	}

	/**
	 * Generate an excerpt.
	 *
	 * @param int   $post_id The Post ID we're processing
	 * @param array $args    Arguments passed in.
	 * @return string|WP_Error
	 */
	public function generate_excerpt( int $post_id = 0, array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required to generate an excerpt.', 'classifai' ) );
		}

		$feature  = new ExcerptGeneration();
		$settings = $feature->get_settings();
		$args     = wp_parse_args(
			array_filter( $args ),
			[
				'content' => '',
				'title'   => get_the_title( $post_id ),
			]
		);

		// These checks (and the one above) happen in the REST permission_callback,
		// but we run them again here in case this method is called directly.
		if ( empty( $settings ) || ! $feature->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'Excerpt generation is disabled or authentication failed. Please check your settings.', 'classifai' ) );
		}

		$excerpt_length = absint( $settings['length'] ?? 55 );
		$excerpt_prompt = esc_textarea( get_default_prompt( $settings['generate_excerpt_prompt'] ) ?? $feature->prompt );

		// Replace our variables in the prompt.
		$prompt_search  = array( '{{WORDS}}', '{{TITLE}}' );
		$prompt_replace = array( $excerpt_length, $args['title'] );
		$prompt         = str_replace( $prompt_search, $prompt_replace, $excerpt_prompt );

		/**
		 * Filter the prompt we will send to Chrome AI.
		 *
		 * @since x.x.x
		 * @hook classifai_chrome_ai_excerpt_prompt
		 *
		 * @param {string} $prompt Prompt we are sending. Gets added before post content.
		 * @param {int} $post_id ID of post we are summarizing.
		 * @param {int} $excerpt_length Length of final excerpt.
		 *
		 * @return {string} Prompt.
		 */
		$prompt = apply_filters( 'classifai_chrome_ai_excerpt_prompt', $prompt, $post_id, $excerpt_length );

		/**
		 * Filter the request body before sending to Chrome AI.
		 *
		 * @since x.x.x
		 * @hook classifai_chrome_ai_excerpt_request_body
		 *
		 * @param {array} $body Request body that will be sent.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_chrome_ai_excerpt_request_body',
			[
				'prompt'  => 'You will be provided with content delimited by triple quotes. ' . $prompt,
				'content' => $this->get_content( $post_id, $excerpt_length, false, $args['content'] ),
				'func'    => 'classifaiChromeAITextGeneration',
			],
			$post_id
		);

		return $body;
	}

	/**
	 * Get our content.
	 *
	 * We don't trim content here as we don't know for sure which model
	 * someone is using.
	 *
	 * @param int    $post_id Post ID to get content from.
	 * @param int    $return_length Word length of returned content.
	 * @param bool   $use_title Whether to use the title or not.
	 * @param string $post_content The post content.
	 * @return string
	 */
	public function get_content( int $post_id = 0, int $return_length = 0, bool $use_title = true, string $post_content = '' ): string {
		$normalizer = new Normalizer();

		if ( empty( $post_content ) ) {
			$post         = get_post( $post_id );
			$post_content = apply_filters( 'the_content', $post->post_content );
		}

		$post_content = preg_replace( '#\[.+\](.+)\[/.+\]#', '$1', $post_content );

		// Add the title to the content, if needed, and normalize things.
		if ( $use_title ) {
			$content = $normalizer->normalize( $post_id, $post_content );
		} else {
			$content = $normalizer->normalize_content( $post_content, '', $post_id );
		}

		/**
		 * Filter content that will get sent to Chrome AI.
		 *
		 * @since x.x.x
		 * @hook classifai_chrome_ai_content
		 *
		 * @param {string} $content Content that will be sent.
		 * @param {int} $post_id ID of post we are summarizing.
		 *
		 * @return {string} Content.
		 */
		return apply_filters( 'classifai_chrome_ai_content', $content, $post_id );
	}
}
