<?php
/**
 * OpenAI Moderation integration
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\Provider;
use Classifai\Features\Moderation as ModerationFeature;
use WP_Error;

class Moderation extends Provider {

	use OpenAI;

	const ID = 'openai_moderation';

	/**
	 * OpenAI Moderation URL
	 *
	 * @var string
	 */
	protected $moderation_url = 'https://api.openai.com/v1/moderations';

	/**
	 * OpenAI Moderation model
	 *
	 * @var string
	 */
	protected $model = 'omni-moderation-latest';

	/**
	 * OpenAI Moderation constructor.
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
		/**
		 * Filter the model name.
		 *
		 * Useful if you want to use a different model, like
		 * text-moderation-latest.
		 *
		 * @since x.x.x
		 * @hook classifai_openai_moderation_model
		 *
		 * @param {string} $model The default model to use.
		 *
		 * @return {string} The model to use.
		 */
		return apply_filters( 'classifai_openai_moderation_model', $this->model );
	}

	/**
	 * Register what we need for the provider.
	 */
	public function register() {
	}

	/**
	 * Render the provider fields.
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
	 * Returns the default settings for this provider.
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
	 * @param array $new_settings The settings array.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings         = $this->feature_instance->get_settings();
		$api_key_settings = $this->sanitize_api_key_settings( $new_settings, $settings );

		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		return $new_settings;
	}

	/**
	 * Sanitize the API key.
	 *
	 * @param array $new_settings The settings array.
	 * @return string
	 */
	public function sanitize_api_key( array $new_settings ): string {
		$settings = $this->feature_instance->get_settings();
		return sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] ?? '' );
	}

	/**
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $item_id The item ID we're processing.
	 * @param string $route_to_call The route we are processing.
	 * @param array  $args Optional arguments to pass to the route.
	 * @return array|WP_Error
	 */
	public function rest_endpoint_callback( $item_id = 0, string $route_to_call = '', array $args = [] ) {
		$route_to_call = strtolower( $route_to_call );
		$return        = [];

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'comment':
				$return = $this->moderate_comment( $item_id );
				break;
			case 'post':
				$return = [];
				break;
		}

		return $return;
	}

	/**
	 * Send comment to remote service for moderation.
	 *
	 * @param int $comment_id Attachment ID to process.
	 * @return array|WP_Error
	 */
	public function moderate_comment( int $comment_id = 0 ) {
		// Ensure we have a valid comment.
		if ( ! $comment_id || ! get_comment( $comment_id ) ) {
			return new WP_Error( 'valid_id_required', esc_html__( 'A valid comment ID is required to run moderation.', 'classifai' ) );
		}

		// Ensure the current user has proper permissions.
		if (
			! current_user_can( 'moderate_comments' ) ||
			! current_user_can( 'edit_comment', $comment_id )
		) {
			return new WP_Error( 'permission_denied', esc_html__( 'You do not have permission to moderate comments.', 'classifai' ) );
		}

		$feature  = new ModerationFeature();
		$settings = $feature->get_settings();

		// Ensure the feature is enabled and the user has access.
		if (
			! $feature->is_feature_enabled() ||
			! in_array( 'comments', $feature->get_moderation_content_settings(), true )
		) {
			return new WP_Error( 'not_enabled', esc_html__( 'Moderation is disabled or OpenAI authentication failed. Please check your settings.', 'classifai' ) );
		}

		$request = new APIRequest( $settings[ static::ID ]['api_key'] ?? '', $feature->get_option_name() );
		$comment = get_comment( $comment_id );

		/**
		 * Filter the request body before sending to OpenAI.
		 *
		 * @since 3.0.0
		 * @hook classifai_openai_moderation_request_body
		 *
		 * @param {array} $body Request body that will be sent to OpenAI.
		 * @param {int} $comment_id ID of comment we are moderating.
		 *
		 * @return {array} Request body.
		 */
		$body = apply_filters(
			'classifai_openai_moderation_request_body',
			[
				'input' => $comment->comment_content,
				'model' => $this->get_model(),
			],
			$comment_id
		);

		// Make our API request.
		$response = $request->post(
			$this->moderation_url,
			[
				'body' => wp_json_encode( $body ),
			]
		);

		set_transient( 'classifai_openai_moderation_latest_response', $response, DAY_IN_SECONDS * 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $response['results'][0];
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information() {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof ModerationFeature ) {
			$debug_info[ __( 'Content to Moderate', 'classifai' ) ] = implode( ', ', $provider_settings['content_types'] ?? [] );
			$debug_info[ __( 'Latest response', 'classifai' ) ]     = $this->get_formatted_latest_response( get_transient( 'classifai_openai_moderation_latest_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
