<?php
/**
 * Azure Personalizer
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use Classifai\Blocks;
use WP_Error;

class Personalizer extends Provider {

	/**
	 * @var string URL fragment to the Rank API endpoint
	 */
	protected $rank_endpoint = '/personalizer/v1.0/rank';

	/**
	 * @var string URL fragment to the Reward API endpoint
	 */
	protected $reward_endpoint = '/personalizer/v1.0/events/{eventId}/reward';

	/**
	 * @var string URL fragment to the get status API endpoint
	 */
	protected $status_endpoint = '/personalizer/v1.0/status';

	/**
	 * Personalizer constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'Microsoft Azure',
			'Personalizer',
			'personalizer',
			$service
		);
	}

	/**
	 * Resets settings for the Personalizer provider.
	 */
	public function reset_settings() {
		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Default settings for Personalizer
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return [
			'authenticated' => false,
			'url'           => '',
			'api_key'       => '',
		];
	}

	/**
	 * Can the functionality be initialized?
	 *
	 * @return bool
	 */
	public function can_register() {
		$options = get_option( $this->get_option_name() );
		if ( isset( $options['authenticated'] ) && false === $options['authenticated'] ) {
			return false;
		}
		if ( empty( $options ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register the functionality.
	 */
	public function register() {
		// Setup Blocks
		Blocks\setup();
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		$default_settings = $this->get_default_settings();
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
				'description'   => __( 'Azure Cognitive Service Personalizer Endpoint, e.g., <code>https://REGION.api.cognitive.microsoft.com</code> or <code>https://EXAMPLE.cognitiveservices.azure.com</code>. This can look different based on your setting choices in Azure.', 'classifai' ),
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
				'description'   => __( 'Azure Cognitive Service Personalizer Key.', 'classifai' ),
			]
		);
	}

	/**
	 * Sanitization
	 *
	 * @param array $settings The settings being saved.
	 *
	 * @return array|mixed
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = [];
		if ( ! empty( $settings['url'] ) && ! empty( $settings['api_key'] ) ) {
			$auth_check = $this->authenticate_credentials( $settings['url'], $settings['api_key'] );
			if ( is_wp_error( $auth_check ) ) {
				$settings_errors['classifai-registration-credentials-error'] = $auth_check->get_error_message();
				$new_settings['authenticated'] = false;
			} else {
				$new_settings['authenticated'] = true;
			}
			$new_settings['url']     = esc_url_raw( $settings['url'] );
			$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] );
		} else {
			$new_settings['authenticated'] = false;
			$new_settings['url']           = '';
			$new_settings['api_key']       = '';

			$settings_errors['classifai-registration-credentials-empty'] = __( 'Please enter your credentials', 'classifai' );
		}

		if ( ! empty( $settings_errors ) ) {

			$registered_settings_errors = wp_list_pluck( get_settings_errors( $this->get_option_name() ), 'code' );

			foreach ( $settings_errors as $code => $message ) {

				if ( ! in_array( $code, $registered_settings_errors, true ) ) {
					add_settings_error(
						$this->get_option_name(),
						$code,
						esc_html( $message ),
						'error'
					);
				}
			}
		}

		return $new_settings;
	}

	/**
	 * Get Recent posts based on given arguments
	 *
	 * @param array $attributes The block attributes.
	 * @return object WP_Query object.
	 */
	protected function get_recent_actions( $attributes ) {
		$query_args = array(
			'posts_per_page'      => 50, // we have maximum 50 actions limit
			'post_status'         => 'publish',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'post_type'           => $attributes['contentPostType'],
		);

		/**
		 * Filters Recommended content post arguments.
		 *
		 * @since 1.8.0
		 * @hook classifai_recommended_content_post_args
		 *
		 * @param {array} $query_args Array of query args to get posts
		 * @param {array} $attributes The block attributes.
		 *
		 * @return {array} Array of query args to get posts
		 */
		$query_args = apply_filters(
			'classifai_recommended_content_post_args',
			$query_args,
			$attributes
		);

		$actions = array();
		$posts   = get_posts( $query_args );
		foreach ( $posts as $post ) {
			array_push(
				$actions,
				array(
					'id'       => $post->ID,
					'features' => array( $this->get_post_features( $post->ID ) ),
				)
			);
		}

		return $actions;
	}

	/**
	 * Get Recommended content Id from Azure personalizer.
	 *
	 * @param array $attributes The block attributes.
	 * @return Object
	 */
	public function get_recommended_content( $attributes ) {
		$actions = $this->get_recent_actions( $attributes );

		// TODO: Add contextFeatures.
		$rank_request = array(
			'contextFeatures' => [],
			'actions'         => $actions,
			'deferActivation' => false,
		);

		$response = $this->personalizer_get_ranked_action( $rank_request );

		if ( is_wp_error( $response ) ) {
			return sprintf( __( 'Failed to contact Azure Cognitive Service Personalizer: %s', 'classifai' ) . $response->get_error_message() );
		}

		return $response;
	}

	/**
	 * Renders the `classifai/recommended-content-block` block on server.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return string Returns the post content with recommended content added.
	 */
	public function render_recommended_content( $attributes ) {
		$content = $this->get_recommended_content( $attributes );

		if ( empty( $content ) || empty( $content->rewardActionId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			return $content;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$post_id   = $content->rewardActionId;
		$post      = get_post( $post_id );
		$post_link = esc_url( get_permalink( $post ) );
		$title     = get_the_title( $post );

		if ( ! $title ) {
			$title = __( '(no title)', 'classifai' );
		}

		$markup = '<li>';

		if ( $attributes['displayFeaturedImage'] && has_post_thumbnail( $post ) ) {
			$image_classes = 'wp-block-classifai-recommended-content__featured-image';

			$featured_image = get_the_post_thumbnail( $post );
			if ( $attributes['addLinkToFeaturedImage'] ) {
				$featured_image = sprintf(
					'<a href="%1$s" aria-label="%2$s">%3$s</a>',
					esc_url( $post_link ),
					esc_attr( $title ),
					$featured_image
				);
			}
			$markup .= sprintf(
				'<div class="%1$s">%2$s</div>',
				esc_attr( $image_classes ),
				$featured_image
			);
		}

		$markup .= sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $post_link ),
			esc_html( $title )
		);

		if ( isset( $attributes['displayAuthor'] ) && $attributes['displayAuthor'] ) {
			$author_display_name = get_the_author_meta( 'display_name', $post->post_author );

			/* translators: byline. %s: current author. */
			$byline = sprintf( __( 'by %s', 'classifai' ), $author_display_name );

			if ( ! empty( $author_display_name ) ) {
				$markup .= sprintf(
					'<div class="wp-block-classifai-recommended-content__post-author">%1$s</div>',
					esc_html( $byline )
				);
			}
		}

		if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
			$markup .= sprintf(
				'<time datetime="%1$s" class="wp-block-classifai-recommended-content__post-date">%2$s</time>',
				esc_attr( get_the_date( 'c', $post ) ),
				esc_html( get_the_date( '', $post ) )
			);
		}

		if ( isset( $attributes['displayPostExcept'] ) && $attributes['displayPostExcept'] ) {
			$trimmed_excerpt = get_the_excerpt( $post );

			if ( post_password_required( $post ) ) {
				$trimmed_excerpt = __( 'This content is password protected.', 'classifai' );
			}

			$markup .= sprintf(
				'<div class="wp-block-classifai-recommended-content__post-excerpt">%1$s</div>',
				esc_html( $trimmed_excerpt )
			);
		}

		$markup .= "</li>\n";

		$class = 'wp-block-classifai-recommended-content';

		if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
			$class .= ' has-dates';
		}

		if ( isset( $attributes['displayAuthor'] ) && $attributes['displayAuthor'] ) {
			$class .= ' has-author';
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $class ) );

		return sprintf(
			'<ul %1$s>%2$s</ul>',
			$wrapper_attributes,
			$markup
		);
	}

	/**
	 * Get Features for specific post.
	 *
	 * @param int $post_id Post Object.
	 * @return array
	 */
	protected function get_post_features( $post_id ) {
		$features = array(
			'title'   => $this->get_string_words( get_the_title( $post_id ) ),
			'excerpt' => $this->get_string_words( get_the_excerpt( $post_id ) ),
			'_URL'    => get_the_permalink( $post_id ),
		);

		$post_type = get_post_type( $post_id );

		// Get post type taxonomies.
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
			// Get the terms related to post.
			$terms = get_the_terms( $post_id, $taxonomy_slug );

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$features[ $taxonomy->label ][ $term->name ] = 1;
				}
			}
		}

		return $features;
	}

	/**
	 * Get array of words from string. Words as key of array.
	 *
	 * @param string $string String to get words from.
	 * @return array
	 */
	protected function get_string_words( $string ) {
		$str_array = preg_split( '/\s+/', $string );
		$words     = array();
		foreach ( $str_array as $str ) {
			$words[ $str ] = 1;
		}

		return $words;
	}

	/**
	 * Get Ranked action by sending request to Azure personalizer.
	 *
	 * @param array $rank_request Prepared Request data.
	 * @return object|string
	 */
	protected function personalizer_get_ranked_action( $rank_request ) {
		$settings = $this->get_settings();
		$result   = wp_remote_post(
			trailingslashit( $settings['url'] ) . $this->rank_endpoint,
			[
				'timeout'  => 30,
				'blocking' => true,
				'headers'  => [
					'Ocp-Apim-Subscription-Key' => $settings['api_key'],
					'Content-Type'              => 'application/json',
				],
				'body'     => json_encode( $rank_request ),
			]
		);

		if ( ! is_wp_error( $result ) ) {
			$response = json_decode( wp_remote_retrieve_body( $result ) );
			set_transient( 'classifai_azure_personalizer_rank_response', $response, DAY_IN_SECONDS * 30 );
			if ( ! empty( $response->error ) ) {
				return new WP_Error( 'error', $response->error->message );
			}
			return $response;
		}
		return $result;
	}

	/**
	 * Authenticates our credentials.
	 *
	 * @param string $url     Endpoint URL.
	 * @param string $api_key Api Key.
	 *
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( $url, $api_key ) {
		$rtn    = false;
		$result = wp_remote_get(
			trailingslashit( $url ) . $this->status_endpoint,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $api_key,
				],
			]
		);

		if ( ! is_wp_error( $result ) ) {
			$response = json_decode( wp_remote_retrieve_body( $result ) );
			set_transient( 'classifai_azure_personalizer_status_response', $response, DAY_IN_SECONDS * 30 );
			if ( ! empty( $response->error ) ) {
				$rtn = new WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		}

		return $rtn;
	}

	/**
	 * Provides debug information related to the provider.
	 *
	 * @param null|array $settings Settings array. If empty, settings will be retrieved.
	 * @return array Keyed array of debug information.
	 * @since 1.4.0
	 */
	public function get_provider_debug_information( $settings = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->sanitize_settings( $this->get_settings() );
		}

		$authenticated = 1 === intval( $settings['authenticated'] ?? 0 );

		return [
			__( 'Authenticated', 'classifai' )  => $authenticated ? __( 'Yes', 'classifai' ) : __( 'No', 'classifai' ),
			__( 'API URL', 'classifai' )        => $settings['url'] ?? '',
			__( 'Service Status', 'classifai' ) => $this->get_formatted_latest_response( get_transient( 'classifai_azure_personalizer_status_response' ) ),
		];
	}

	/**
	 * Format the result of most recent request.
	 *
	 * @param mixed $data Response data to format.
	 *
	 * @return string
	 */
	private function get_formatted_latest_response( $data ) {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}
}
