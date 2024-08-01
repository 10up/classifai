<?php
/**
 * Azure AI Personalizer
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;
use Classifai\Blocks;
use Classifai\Features\RecommendedContent;
use WP_Error;
use WP_REST_Server;
use UAParser\Parser;

class Personalizer extends Provider {

	const ID = 'ms_azure_personalizer';

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
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;

		do_action( 'classifai_' . static::ID . '_init', $this );
	}

	/**
	 * Register the functionality.
	 */
	public function register() {
		add_action( 'classifai_before_feature_nav', [ $this, 'show_deprecation_message' ] );
	}

	/**
	 * Show a deprecation message for the provider.
	 *
	 * @param string $active_feature Feature currently shown.
	 */
	public function show_deprecation_message( string $active_feature ) {
		if ( 'feature_recommended_content' !== $active_feature || ( new RecommendedContent() )->get_settings( 'provider' ) !== static::ID ) {
			return;
		}
		?>

		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					wp_kses(
						// translators: 1 - link to Personalizer documentation; 2 - link to GitHub issue.
						__( '<a href="%1$s" target="_blank">As of September 2023</a>, new Personalizer resources can no longer be created in Azure. This is currently the only provider available for the Recommended Content feature and as such, this feature will not work unless you had previously created a Personalizer resource. The Azure AI Personalizer provider is deprecated and will be removed in a future release. We hope to replace this provider with another one in a coming release to continue to support this feature (see <a href="%2$s" target="_blank">issue#392</a>).', 'classifai' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					),
					'https://learn.microsoft.com/en-us/azure/ai-services/personalizer/',
					'https://github.com/10up/classifai/issues/392'
				)
				?>
			</p>
		</div>

		<?php
	}

	/**
	 * Render the provider fields.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			'endpoint_url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'endpoint_url',
				'input_type'    => 'text',
				'default_value' => $settings['endpoint_url'],
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					sprintf(
						wp_kses(
							// translators: 1 - link to create a Personalizer resource; 2 - link to GitHub issue.
							__( 'Azure AI Personalizer Endpoint; <a href="%1$s" target="_blank">create a Personalizer resource</a> in the Azure portal to get your key and endpoint. Note that <a href="%2$s" target="_blank">as of September 2023</a>, it is no longer possible to create this resource. Previously created Personalizer resources can still be used.', 'classifai' ),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
								),
							)
						),
						'https://portal.azure.com/#create/Microsoft.CognitiveServicesPersonalizer',
						'https://learn.microsoft.com/en-us/azure/ai-services/personalizer/'
					),
			]
		);

		add_settings_field(
			'api_key',
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
			]
		);
	}

	/**
	 * Returns the default settings for this provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'endpoint_url'  => '',
			'api_key'       => '',
			'authenticated' => false,
		];

		switch ( $this->feature_instance::ID ) {
			case RecommendedContent::ID:
				return $common_settings;
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

		$new_settings['endpoint_url'] = esc_url_raw( $new_settings[ static::ID ]['endpoint_url'] ?? $settings[ static::ID ]['endpoint_url'] );
		$new_settings['api_key']      = sanitize_text_field( $new_settings[ static::ID ]['api_key'] ?? $settings[ static::ID ]['api_key'] );

		if ( ! empty( $new_settings[ static::ID ]['endpoint_url'] ) && ! empty( $new_settings[ static::ID ]['api_key'] ) ) {
			$auth_check = $this->authenticate_credentials( $new_settings[ static::ID ]['endpoint_url'], $new_settings[ static::ID ]['api_key'] );

			if ( is_wp_error( $auth_check ) ) {
				$settings_errors['classifai-registration-credentials-error'] = $auth_check->get_error_message();
				$new_settings[ static::ID ]['authenticated']                 = false;
			} else {
				$new_settings[ static::ID ]['authenticated'] = true;
			}
		} else {
			$new_settings[ static::ID ]['authenticated'] = false;
			$new_settings[ static::ID ]['endpoint_url']  = '';
			$new_settings[ static::ID ]['api_key']       = '';

			$settings_errors['classifai-registration-credentials-empty'] = __( 'Please enter your credentials', 'classifai' );
		}

		if ( ! empty( $settings_errors ) ) {
			$registered_settings_errors = wp_list_pluck( get_settings_errors( $this->feature_instance->get_option_name() ), 'code' );

			foreach ( $settings_errors as $code => $message ) {

				if ( ! in_array( $code, $registered_settings_errors, true ) ) {
					add_settings_error(
						$this->feature_instance->get_option_name(),
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
	protected function get_recent_actions( array $attributes ): array {
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
	 * @return mixed
	 */
	public function get_recommended_content( array $attributes ) {
		$actions = $this->get_recent_actions( $attributes );

		if ( empty( $actions ) ) {
			return __( 'No results found.', 'classifai' );
		}

		// Exclude post/page on which we are displaying the content.
		if ( ! empty( $attributes['excludeId'] ) ) {
			$exclude = $attributes['excludeId'];
			$actions = array_filter(
				$actions,
				function ( $ele ) use ( $exclude ) {
					return $ele['id'] && absint( $ele['id'] ) !== absint( $exclude );
				}
			);
		}

		$action_ids = array_map(
			function ( $ele ) {
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
			error_log( __( 'Failed to contact Azure AI Personalizer: ', 'classifai' ) . $response->get_error_message() );
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
	 * @return string Returns the post content with recommended content added.
	 */
	public function render_recommended_content( array $attributes ): string {
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
				function ( $a, $b ) {
					return $a->probability - $b->probability;
				}
			);

			$recommended_ids = array_map(
				function ( $ele ) {
					return absint( $ele->id );
				},
				$ranking
			);
			$recommended_ids = array_filter(
				$recommended_ids,
				function ( $ele ) use ( $rewarded_post ) {
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
	protected function get_post_features( int $post_id ): array {
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
	 * @param string $text String of text to get words from.
	 * @return array
	 */
	protected function get_string_words( string $text ): array {
		$str_array = preg_split( '/\s+/', $text );
		$words     = array();
		foreach ( $str_array as $str ) {
			$words[ $str ] = 1;
		}

		return $words;
	}

	/**
	 * Get Ranked action by sending request to Azure AI Personalizer.
	 *
	 * @param array $rank_request Prepared Request data.
	 * @return object|string
	 */
	protected function personalizer_get_ranked_action( array $rank_request ) {
		$feature  = new RecommendedContent();
		$settings = $feature->get_settings( static::ID );
		$result   = wp_remote_post(
			trailingslashit( $settings['endpoint_url'] ) . $this->rank_endpoint,
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
	 * @return object|string
	 */
	public function personalizer_send_reward( string $event_id, int $reward ) {
		$feature  = new RecommendedContent();
		$settings = $feature->get_settings( static::ID );

		$reward_endpoint = str_replace( '{eventId}', sanitize_text_field( $event_id ), $this->reward_endpoint );
		$result          = wp_remote_post(
			trailingslashit( $settings['endpoint_url'] ) . $reward_endpoint,
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
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( string $url, string $api_key ) {
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
	 * Common entry point for all REST endpoints for this provider.
	 *
	 * @param int    $post_id       The post ID we're processing.
	 * @param string $route_to_call The name of the route we're going to be processing.
	 * @param array  $args          Optional arguments to pass to the route.
	 * @return array|string|WP_Error
	 */
	public function rest_endpoint_callback( $post_id, string $route_to_call = '', array $args = [] ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'post_id_required', esc_html__( 'A valid post ID is required.', 'classifai' ) );
		}

		$route_to_call = strtolower( $route_to_call );
		$return        = '';

		// Handle all of our routes.
		switch ( $route_to_call ) {
			case 'reward':
				$return = $this->personalizer_send_reward( $post_id, $args['rewarded'] );
				break;
		}

		return $return;
	}

	/**
	 * Returns the debug information for the provider settings.
	 *
	 * @return array
	 */
	public function get_debug_information(): array {
		$settings          = $this->feature_instance->get_settings();
		$provider_settings = $settings[ static::ID ];
		$debug_info        = [];

		if ( $this->feature_instance instanceof RecommendedContent ) {
			$debug_info[ __( 'API URL', 'classifai' ) ]         = $provider_settings['endpoint_url'];
			$debug_info[ __( 'Latest response', 'classifai' ) ] = $this->get_formatted_latest_response( get_transient( 'classifai_azure_personalizer_status_response' ) );
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
