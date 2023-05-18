<?php
/**
 * Azure Personalizer
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use Classifai\Blocks;
use WP_Error;
use UAParser\Parser;

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

		// Set the onboarding options.
		$this->onboarding_options = array(
			'title'    => __( 'Microsoft Azure Personalizer', 'classifai' ),
			'fields'   => array( 'url', 'api-key' ),
			'features' => array(
				'authenticated' => __( 'Recommended content block', 'classifai' ),
			),
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
	 * Register the functionality.
	 */
	public function register() {
		// Setup Blocks
		Blocks\setup();
	}

	/**
	 * Setup fields.
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
				'description'   => sprintf(
					wp_kses(
						// translators: 1 - link to create a Personalizer resource.
						__( 'Azure Cognitive Service Personalizer Endpoint, <a href="%1$s" target="_blank">create a Personalizer resource</a> in the Azure portal to get your key and endpoint.', 'classifai' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					),
					esc_url( 'https://portal.azure.com/#create/Microsoft.CognitiveServicesPersonalizer' )
				),
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
	 * Sanitize settings.
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
				$new_settings['authenticated']                               = false;
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
	 * Get Recent posts based on given arguments.
	 *
	 * @param array $attributes The block attributes.
	 * @return array recent actions based on block attributes.
	 */
	protected function get_recent_actions( $attributes ) {
		$post_type      = $attributes['contentPostType'];
		$key_attributes = array(
			'terms' => isset( $attributes['taxQuery'] ) ? $attributes['taxQuery'] : array(),
		);
		$transient_key  = 'classifai_actions_' . $post_type . md5( maybe_serialize( $key_attributes ) );
		$actions        = get_transient( $transient_key );
		if ( false === $actions ) {
			$query_args = array(
				'posts_per_page'      => 50, // we have maximum 50 actions limit
				'post_status'         => 'publish',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'post_type'           => $post_type,
			);

			// Handle Taxonomy filters.
			if ( isset( $attributes['taxQuery'] ) && ! empty( $attributes['taxQuery'] ) ) {
				foreach ( $attributes['taxQuery'] as $taxonomy => $terms ) {
					if ( ! empty( $terms ) ) {
						$query_args['tax_query'][] = array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $terms,
						);
					}
				}
				if ( isset( $query_args['tax_query'] ) && count( $query_args['tax_query'] ) > 1 ) {
					$query_args['tax_query']['relation'] = 'AND';
				}
			}

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
			$query   = new \WP_Query( $query_args );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$post_id = get_the_ID();
					array_push(
						$actions,
						array(
							'id'       => $post_id,
							'features' => array( $this->get_post_features( $post_id ) ),
						)
					);
				}
			}
			wp_reset_postdata();

			if ( ! empty( $actions ) ) {
				set_transient( $transient_key, $actions, 6 * \HOUR_IN_SECONDS );
			}
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

		if ( empty( $actions ) ) {
			return __( 'No results found.', 'classifai' );
		}

		// Exclude post/page on which we are displaying the content.
		if ( ! empty( $attributes['excludeId'] ) ) {
			$exclude = $attributes['excludeId'];
			$actions = array_filter(
				$actions,
				function( $ele ) use ( $exclude ) {
					return $ele['id'] && absint( $ele['id'] ) !== absint( $exclude );
				}
			);
		}

		$action_ids = array_map(
			function( $ele ) {
				return $ele['id'];
			},
			$actions
		);

		// If actions are less than or equal to number of items we have to display, avoid call personalizer service.
		$number_of_posts = isset( $attributes['numberOfItems'] ) ? absint( $attributes['numberOfItems'] ) : 3;
		if ( count( $action_ids ) <= $number_of_posts ) {
			return array(
				'response' => (object) array(),
				'actions'  => $action_ids,
			);
		}

		// TODO: Add Location contextFeatures.
		$rank_request = array(
			'contextFeatures' => [
				[
					'userAgent' => $this->get_user_agent_features(),
				],
				[
					'weekDay'   => ( wp_date( 'N' ) >= 6 ) ? 'weekend' : 'workweek',
					'timeOfDay' => wp_date( 'a' ),
				],
			],
			'actions'         => $actions,
			'deferActivation' => false,
		);

		$response = $this->personalizer_get_ranked_action( $rank_request );

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( __( 'Failed to contact Azure Cognitive Service Personalizer: ', 'classifai' ) . $response->get_error_message() );
			return array(
				'response' => (object) array(),
				'actions'  => $action_ids,
			);
		}

		return array(
			'response' => $response,
			'actions'  => $action_ids,
		);
	}

	/**
	 * Renders the `classifai/recommended-content-block` block on server.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return string Returns the post content with recommended content added.
	 */
	public function render_recommended_content( $attributes ) {
		/**
		 * Filter the recommended content block attributes
		 *
		 * @since 2.0.0
		 * @hook classifai_recommended_block_attributes
		 *
		 * @param {array}  $attributes   Attributes of blocks.
		 *
		 * @return {string} The filtered attributes.
		 */
		$attributes = apply_filters( 'classifai_recommended_block_attributes', $attributes );
		$content    = $this->get_recommended_content( $attributes );

		if ( ! is_array( $content ) || empty( $content ) ) {
			return $content;
		}

		$response = isset( $content['response'] ) ? $content['response'] : (object) array();
		$actions  = isset( $content['actions'] ) ? $content['actions'] : array();

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$event_id        = $response->eventId;
		$number_of_posts = isset( $attributes['numberOfItems'] ) ? absint( $attributes['numberOfItems'] ) : 3;

		if ( ! empty( $response ) && ! empty( $response->rewardActionId ) ) {
			$rewarded_post = absint( $response->rewardActionId );
			$ranking       = $response->ranking ? $response->ranking : array();

			// Sort ranking by probability.
			usort(
				$ranking,
				function( $a, $b ) {
					return $a->probability - $b->probability;
				}
			);

			$recommended_ids = array_map(
				function( $ele ) {
					return absint( $ele->id );
				},
				$ranking
			);
			$recommended_ids = array_filter(
				$recommended_ids,
				function( $ele ) use ( $rewarded_post ) {
					return $ele && absint( $ele ) !== absint( $rewarded_post );
				}
			);

			// Add rewarded post in recommended_posts.
			array_unshift( $recommended_ids, $rewarded_post );

			// Load posts from default query if needed.
			if ( count( $recommended_ids ) < $number_of_posts ) {
				$actions = array_slice( $actions, 0, $number_of_posts );
				foreach ( $actions as $action ) {
					if ( ! in_array( $action, $recommended_ids, true ) ) {
						array_push( $recommended_ids, $action );
					}
				}
			}

			$recommended_ids = array_slice( $recommended_ids, 0, $number_of_posts );
		} else {
			// No results from personalizer, use default query.
			if ( empty( $actions ) ) {
				return __( 'No results found.', 'classifai' );
			}

			$recommended_ids = array_slice( $actions, 0, $number_of_posts );
		}
		// phpcs:enable

		$markup = '';
		$args   = array(
			'post__in'               => $recommended_ids,
			'post_type'              => $attributes['contentPostType'],
			'posts_per_page'         => $number_of_posts,
			'orderby'                => 'post__in',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);

		$recommended_posts = get_posts( $args );
		foreach ( $recommended_posts as $post ) {
			$post_link = get_permalink( $post );
			$title     = get_the_title( $post );
			$rewarded  = ( absint( $post->ID ) === absint( $rewarded_post ) ) ? '1' : '0';

			if ( ! $title ) {
				$title = __( '(no title)', 'classifai' );
			}

			$markup .= '<li>';

			if ( $attributes['displayFeaturedImage'] && has_post_thumbnail( $post ) ) {
				$image_classes = 'wp-block-classifai-recommended-content__featured-image';

				$featured_image = get_the_post_thumbnail( $post );
				if ( $attributes['addLinkToFeaturedImage'] ) {
					$featured_image = sprintf(
						'<a href="%1$s" aria-label="%2$s" class="classifai-send-reward" data-eventid="%3$s" data-rewarded="%4$s">%5$s</a>',
						esc_url( $post_link ),
						esc_attr( $title ),
						esc_attr( $event_id ),
						$rewarded,
						$featured_image
					);
				}
				$markup .= sprintf(
					'<div class="%1$s">%2$s</div>',
					esc_attr( $image_classes ),
					$featured_image
				);
			}

			if ( ! empty( $event_id ) ) {
				$markup .= sprintf(
					'<a href="%1$s" class="classifai-send-reward" data-eventid="%2$s" data-rewarded="%3$s">%4$s</a>',
					esc_url( $post_link ),
					esc_attr( $event_id ),
					$rewarded,
					esc_html( $title )
				);
			} else {
				$markup .= sprintf( '<a href="%1$s">%2$s</a>', esc_url( $post_link ), esc_html( $title ) );
			}

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

			if ( isset( $attributes['displayPostExcerpt'] ) && $attributes['displayPostExcerpt'] ) {
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
		}

		$class = 'wp-block-classifai-recommended-content wp-block-classifai-recommended-content__list';

		if ( 'grid' === $attributes['displayLayout'] ) {
			$class .= ' is-grid';

			if ( isset( $attributes['columns'] ) && $attributes['columns'] ) {
				$class .= ' columns-' . $attributes['columns'];
			}
		}

		if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
			$class .= ' has-dates';
		}

		if ( isset( $attributes['displayAuthor'] ) && $attributes['displayAuthor'] ) {
			$class .= ' has-author';
		}

		$final_markup = sprintf(
			'<ul class="%1$s">%2$s</ul>',
			esc_attr( $class ),
			$markup
		);

		/**
		 * Filter the recommended content block markup
		 *
		 * @since 1.8.0
		 * @hook classifai_recommended_block_markup
		 *
		 * @param {string} $final_markup HTML Markup of recommended content block.
		 * @param {array}  $attributes   Attributes of blocks.
		 * @param {object} $response     Object of personalizer response.
		 * @param {array}  $actions      Selected actions(posts) to send in request to personalizer.
		 *
		 * @return {string} The filtered markup.
		 */
		return apply_filters( 'classifai_recommended_block_markup', $final_markup, $attributes, $response, $actions );
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
	 * Get user agent for personalizer contextFeatures.
	 */
	protected function get_user_agent_features() {
		// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// User Agent Parsing
		$parser    = Parser::create();
		$ua_result = $parser->parse( $user_agent );

		return array(
			'_ua'           => $ua_result->originalUserAgent, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'_DeviceFamily' => $ua_result->device->family,
			'_OSFamily'     => $ua_result->os->family,
			'DeviceBrand'   => $ua_result->device->brand,
			'DeviceModel'   => $ua_result->device->model,
		);
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
	 * Get Ranked action by sending request to Azure Personalizer.
	 *
	 * @param array $rank_request Prepared Request data.
	 * @return object|string
	 */
	protected function personalizer_get_ranked_action( $rank_request ) {
		$settings = $this->get_settings();
		$result   = wp_remote_post(
			trailingslashit( $settings['url'] ) . $this->rank_endpoint,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $settings['api_key'],
					'Content-Type'              => 'application/json',
				],
				'body'    => wp_json_encode( $rank_request ),
			]
		);

		if ( ! is_wp_error( $result ) ) {
			$response = json_decode( wp_remote_retrieve_body( $result ) );
			if ( ! empty( $response->error ) ) {
				return new WP_Error( 'error', $response->error->message );
			}
			return $response;
		}
		return $result;
	}

	/**
	 * Report reward to allocate to the top ranked action for the specified event.
	 *
	 * @param string $event_id Personalizer event ID.
	 * @param int    $reward   Reward value to send.
	 *
	 * @return object|string
	 */
	public function personalizer_send_reward( $event_id, $reward ) {
		$settings        = $this->get_settings();
		$reward_endpoint = str_replace( '{eventId}', sanitize_text_field( $event_id ), $this->reward_endpoint );
		$result          = wp_remote_post(
			trailingslashit( $settings['url'] ) . $reward_endpoint,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $settings['api_key'],
					'Content-Type'              => 'application/json',
				],
				'body'    => wp_json_encode( array( 'value' => $reward ) ),
			]
		);

		if ( ! is_wp_error( $result ) ) {
			$response = json_decode( wp_remote_retrieve_body( $result ) );
			if ( ! empty( $response->error ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( __( 'Error in send reward to personalizer: ', 'classifai' ) . $response->error->message );
				return new WP_Error( 'error', $response->error->message );
			}
			return true;
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
		$rtn = false;
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
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
}
