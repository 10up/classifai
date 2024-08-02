<?php
/**
 * Powers the Recommended Content feature using Amazon Personalize.
 *
 * @package Classifai\Providers\AWS
 */

namespace Classifai\Providers\AWS;

use Classifai\Providers\Provider;
use Classifai\Features\RecommendedContent;
use Aws\Sdk;
use WP_Error;

class AmazonPersonalize extends Provider {

	const ID = 'aws_personalize';

	/**
	 * AmazonPersonalize constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;

		do_action( 'classifai_' . static::ID . '_init', $this );
	}

	/**
	 * Render the provider fields.
	 */
	public function render_provider_fields() {
		$settings = $this->feature_instance->get_settings( static::ID );

		add_settings_field(
			'access_key_id',
			esc_html__( 'Access key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'access_key_id',
				'input_type'    => 'text',
				'default_value' => $settings['access_key_id'],
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					sprintf(
						wp_kses(
							/* translators: %1$s is replaced with the AWS documentation URL */
							__( 'Enter the AWS access key. Please follow the steps given <a title="AWS documentation" href="%1$s">here</a> to generate AWS credentials.', 'classifai' ),
							[
								'a' => [
									'href'  => [],
									'title' => [],
								],
							]
						),
						esc_url( 'https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html#Using_CreateAccessKey' )
					),
			]
		);

		add_settings_field(
			'secret_access_key',
			esc_html__( 'Secret access key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'secret_access_key',
				'input_type'    => 'password',
				'default_value' => $settings['secret_access_key'],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID,
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					esc_html__( 'Enter the AWS secret access key.', 'classifai' ),
			]
		);

		add_settings_field(
			'aws_region',
			esc_html__( 'Region', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'aws_region',
				'input_type'    => 'text',
				'default_value' => $settings['aws_region'],
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
				'description'   => $this->feature_instance->is_configured_with_provider( static::ID ) ?
					'' :
					wp_kses(
						__( 'Enter the AWS Region. eg: <code>us-east-1</code>', 'classifai' ),
						[
							'code' => [],
						]
					),
			]
		);

		add_settings_field(
			'event_tracker_id',
			esc_html__( 'Event Tracker ID', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'event_tracker_id',
				'input_type'    => 'text',
				'default_value' => $settings['event_tracker_id'], // TODO: could make an API request to get all event trackers and populate a dropdown. Or make an API request to get the dataset and automatically get the tracker ID from the chosen dataset.
				'placeholder'   => '4282ac5f-1681-53fg-8g35-1acf2gae4125',
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
				'description'   => esc_html__( 'Enter the event tracker ID associated with your dataset', 'classifai' ),
			]
		);

		add_settings_field(
			'campaign_arn',
			esc_html__( 'Campaign ARN', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => 'campaign_arn',
				'input_type'    => 'text',
				'default_value' => $settings['campaign_arn'],
				'placeholder'   => 'arn:aws:personalize:us-east-1:12345:campaign/name',
				'class'         => 'large-text classifai-provider-field hidden provider-scope-' . static::ID,
				'description'   => esc_html__( 'Enter the Amazon Resource Name (ARN) of the campaign to use for generating the personalized ranking', 'classifai' ),
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
			'access_key_id'     => '',
			'secret_access_key' => '',
			'aws_region'        => '',
			'event_tracker_id'  => '',
			'campaign_arn'      => '',
			'authenticated'     => false,
		];

		switch ( $this->feature_instance::ID ) {
			case RecommendedContent::ID:
				return $common_settings;
		}

		return [];
	}

	/**
	 * Sanitization callback for settings.
	 *
	 * @param array $new_settings The settings being saved.
	 * @return array
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings               = $this->feature_instance->get_settings();
		$is_credentials_changed = false;

		$new_settings[ static::ID ]['authenticated'] = $settings[ static::ID ]['authenticated'];

		if (
			! empty( $new_settings[ static::ID ]['access_key_id'] ) &&
			! empty( $new_settings[ static::ID ]['secret_access_key'] ) &&
			! empty( $new_settings[ static::ID ]['aws_region'] )
		) {
			$new_access_key_id     = sanitize_text_field( $new_settings[ static::ID ]['access_key_id'] );
			$new_secret_access_key = sanitize_text_field( $new_settings[ static::ID ]['secret_access_key'] );
			$new_aws_region        = sanitize_text_field( $new_settings[ static::ID ]['aws_region'] );

			if (
				$new_access_key_id !== $settings[ static::ID ]['access_key_id'] ||
				$new_secret_access_key !== $settings[ static::ID ]['secret_access_key'] ||
				$new_aws_region !== $settings[ static::ID ]['aws_region']
			) {
				$is_credentials_changed = true;
			}

			if ( $is_credentials_changed || ! $new_settings[ static::ID ]['authenticated'] ) {
				$new_settings[ static::ID ]['access_key_id']     = $new_access_key_id;
				$new_settings[ static::ID ]['secret_access_key'] = $new_secret_access_key;
				$new_settings[ static::ID ]['aws_region']        = $new_aws_region;

				$connected = $this->check_connection(
					[
						'access_key_id'     => $new_access_key_id,
						'secret_access_key' => $new_secret_access_key,
						'aws_region'        => $new_aws_region,
					]
				);

				if ( $connected ) {
					$new_settings[ static::ID ]['authenticated'] = true;
				} else {
					$new_settings[ static::ID ]['authenticated'] = false;
				}
			}
		} else {
			$new_settings[ static::ID ]['access_key_id']     = $settings[ static::ID ]['access_key_id'];
			$new_settings[ static::ID ]['secret_access_key'] = $settings[ static::ID ]['secret_access_key'];
			$new_settings[ static::ID ]['aws_region']        = $settings[ static::ID ]['aws_region'];

			add_settings_error(
				$this->feature_instance->get_option_name(),
				'classifai-aws-personalize-auth-empty',
				esc_html__( 'One or more credentials required to connect to the Amazon Personalize service is empty.', 'classifai' ),
				'error'
			);
		}

		$new_settings[ static::ID ]['event_tracker_id'] = sanitize_text_field( $new_settings[ static::ID ]['event_tracker_id'] ?? $settings[ static::ID ]['event_tracker_id'] );

		$new_settings[ static::ID ]['campaign_arn'] = sanitize_text_field( $new_settings[ static::ID ]['campaign_arn'] ?? $settings[ static::ID ]['campaign_arn'] );

		return $new_settings;
	}

	/**
	 * Check the connection to the Amazon Personalize service.
	 *
	 * @param array $args Overridable args.
	 * @return bool
	 */
	public function check_connection( array $args = [] ): bool {
		$settings = $this->feature_instance->get_settings( static::ID );

		$default = [
			'access_key_id'     => $settings[ static::ID ]['access_key_id'] ?? '',
			'secret_access_key' => $settings[ static::ID ]['secret_access_key'] ?? '',
			'aws_region'        => $settings[ static::ID ]['aws_region'] ?? 'us-east-1',
		];

		$default = wp_parse_args( $args, $default );

		// Return if credentials don't exist.
		if ( empty( $default['access_key_id'] ) || empty( $default['secret_access_key'] ) ) {
			return false;
		}

		try {
			/**
			 * Filters the return value of the check connection function.
			 *
			 * Returning a non-false value from the filter will short-circuit the request
			 * and return early with that value.
			 *
			 * This filter is useful for E2E tests.
			 *
			 * @since x.x.x
			 * @hook classifai_aws_personalize_pre_check_connection
			 *
			 * @param {bool} $pre The value of pre connect to service. Default false. Non-false value will short-circuit the request.
			 *
			 * @return {bool} The filtered value of connect to service.
			 */
			$pre = apply_filters( 'classifai_' . self::ID . '_pre_check_connection', false );

			if ( false !== $pre ) {
				return (bool) $pre;
			}

			$client  = $this->get_client( 'personalize', $args );
			$schemas = $client->listSchemas( [ 'maxResults' => 1 ] );

			return $schemas && isset( $schemas['schemas'] );
		} catch ( \Exception $e ) {
			add_settings_error(
				$this->feature_instance->get_option_name(),
				'aws-personalize-auth-failed',
				sprintf(
					/* translators: %s is replaced with the error message */
					esc_html__( 'Connection to Amazon Personalize failed. Error: %s', 'classifai' ),
					$e->getMessage()
				),
				'error'
			);

			return false;
		}
	}

	/**
	 * Returns proper AWS client.
	 *
	 * @param string $client_type Client type.
	 * @param array  $aws_config AWS configuration array.
	 * @return \Aws\AwsClient|null
	 */
	public function get_client( string $client_type = '', array $aws_config = [] ) {
		$settings = $this->feature_instance->get_settings( static::ID );

		$default = [
			'access_key_id'     => $settings['access_key_id'] ?? '',
			'secret_access_key' => $settings['secret_access_key'] ?? '',
			'aws_region'        => $settings['aws_region'] ?? 'us-east-1',
		];

		$default = wp_parse_args( $aws_config, $default );

		// Return if credentials don't exist.
		if ( empty( $default['access_key_id'] ) || empty( $default['secret_access_key'] ) ) {
			return null;
		}

		// Set the AWS SDK configuration.
		$config = [
			'region'      => $default['aws_region'] ?? 'us-east-1',
			'version'     => 'latest',
			'ua_append'   => [ 'request-source/classifai' ],
			'credentials' => [
				'key'    => $default['access_key_id'],
				'secret' => $default['secret_access_key'],
			],
		];

		$sdk = new Sdk( $config );

		switch ( $client_type ) {
			case 'personalize':
				return $sdk->createPersonalize();
			case 'personalize-events':
				return $sdk->createPersonalizeEvents();
			case 'personalize-runtime':
				return $sdk->createPersonalizeRuntime();
		}

		return null;
	}

	/**
	 * Renders the markup for the Recommended Content block.
	 *
	 * @param array $attributes The block attributes.
	 * @return string.
	 */
	public function render_recommended_content( array $attributes ): string {
		/**
		 * Filter the recommended content block attributes
		 *
		 * @since 2.0.0
		 * @hook classifai_recommended_block_attributes
		 *
		 * @param {array} $attributes Attributes of blocks.
		 *
		 * @return {string} The filtered attributes.
		 */
		$attributes = apply_filters( 'classifai_recommended_block_attributes', $attributes );

		$recommended_ids = $this->get_recommended_items( $attributes );

		if ( empty( $recommended_ids ) ) {
			return __( 'No results found.', 'classifai' );
		}

		$number_of_posts = isset( $attributes['numberOfItems'] ) ? absint( $attributes['numberOfItems'] ) : 3;
		$recommended_ids = array_slice( $recommended_ids, 0, $number_of_posts );

		$markup = '';
		$args   = [
			'post__in'               => $recommended_ids,
			'post_type'              => $attributes['contentPostType'],
			'posts_per_page'         => $number_of_posts,
			'orderby'                => 'post__in',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		];

		$recommended_posts = get_posts( $args );

		foreach ( $recommended_posts as $post ) {
			$post_link = get_permalink( $post );
			$title     = get_the_title( $post );

			if ( ! $title ) {
				$title = __( '(no title)', 'classifai' );
			}

			$markup .= '<li>';

			if ( $attributes['displayFeaturedImage'] && has_post_thumbnail( $post ) ) {
				$image_classes = 'wp-block-classifai-recommended-content__featured-image';

				$featured_image = get_the_post_thumbnail( $post );

				if ( $attributes['addLinkToFeaturedImage'] ) {
					$featured_image = sprintf(
						'<a href="%1$s" aria-label="%2$s" class="classifai-send-reward" data-eventid="%3$s">%4$s</a>',
						esc_url( $post_link ),
						esc_attr( $title ),
						esc_attr( $post->ID ),
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
				'<a href="%1$s" class="classifai-send-reward" data-eventid="%2$s">%3$s</a>',
				esc_url( $post_link ),
				esc_attr( $post->ID ),
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
		 *
		 * @return {string} The filtered markup.
		 */
		return apply_filters( 'classifai_recommended_block_markup', $final_markup, $attributes );
	}

	/**
	 * Get recommended items from Amazon Personalize.
	 *
	 * @param array $attributes The block attributes.
	 * @return array
	 */
	public function get_recommended_items( array $attributes ): array {
		$items = $this->get_recent_items( $attributes );

		if ( empty( $items ) ) {
			return $items;
		}

		// If items are less than or equal to number we want to display, avoid API call.
		$number_of_posts = isset( $attributes['numberOfItems'] ) ? absint( $attributes['numberOfItems'] ) : 3;
		if ( count( $items ) <= $number_of_posts ) {
			return $items;
		}

		// Get our AWS client.
		$client = $this->get_client( 'personalize-runtime' );

		if ( ! $client ) {
			return $items;
		}

		$settings = $this->feature_instance->get_settings( static::ID );

		// Convert the post IDs to strings as the API expects that.
		$items = array_map(
			function ( $item ) {
				return (string) $item;
			},
			$items
		);

		try {
			$result = $client->getPersonalizedRanking(
				[
					'campaignArn' => $settings['campaign_arn'] ?? '',
					'inputList'   => $items,
					'userId'      => '1', // TODO: We need a user ID that can be tracked across page views.
				]
			);

			// Pull the post IDs out of the personalized ranking response.
			if ( ! empty( $result['personalizedRanking'] ) ) {
				$items = array_map(
					function ( $item ) {
						return (int) $item['itemId'];
					},
					$result['personalizedRanking']
				);
			}
		} catch ( \Exception $e ) {
			return $items;
		}

		return $items;
	}

	/**
	 * Get recent items based on given arguments.
	 *
	 * These will then be re-ranked by Amazon Personalize.
	 *
	 * @param array $attributes The block attributes.
	 * @return array
	 */
	public function get_recent_items( array $attributes ): array {
		$post_type      = $attributes['contentPostType'];
		$key_attributes = [
			'terms'    => $attributes['taxQuery'] ?? [],
			'excluded' => $attributes['excludeId'] ?? 0,
		];
		$transient_key  = 'classifai_actions_' . $post_type . md5( maybe_serialize( $key_attributes ) );
		$items          = get_transient( $transient_key );

		if ( false !== $items && ! empty( $items ) ) {
			return $items;
		}

		$query_args = [
			'posts_per_page'      => 100,
			'post_status'         => 'publish',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'post_type'           => $post_type,
		];

		// Exclude item on which we are displaying the block.
		if ( ! empty( $attributes['excludeId'] ) ) {
			$query_args['post__not_in'] = [ absint( $attributes['excludeId'] ) ];
		}

		// Handle taxonomy filters.
		if ( isset( $attributes['taxQuery'] ) && ! empty( $attributes['taxQuery'] ) ) {
			foreach ( $attributes['taxQuery'] as $taxonomy => $terms ) {
				if ( ! empty( $terms ) ) {
					$query_args['tax_query'][] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $terms,
					];
				}
			}

			if ( isset( $query_args['tax_query'] ) && count( $query_args['tax_query'] ) > 1 ) {
				$query_args['tax_query']['relation'] = 'AND';
			}
		}

		/**
		 * Filters recommended content post arguments.
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

		$items = [];
		$query = new \WP_Query( $query_args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$items[] = get_the_ID();
			}
		}

		wp_reset_postdata();

		if ( ! empty( $content ) ) {
			set_transient( $transient_key, $items, 6 * \HOUR_IN_SECONDS );
		}

		return $items;
	}

	/**
	 * Send a tracking event to Amazon Personalize.
	 *
	 * @param int   $post_id The post ID to track.
	 * @param array $args Additional event arguments.
	 * @return bool|WP_Error
	 */
	public function track_event( $post_id, array $args = [] ) {
		$settings = $this->feature_instance->get_settings( static::ID );
		$client   = $this->get_client( 'personalize-events' );

		if ( ! $client ) {
			return new WP_Error( 'client_not_found', esc_html__( 'Client not found.', 'classifai' ) );
		}

		$id     = uniqid(); // TODO: Is this the best way to generate a session ID?
		$params = [
			'eventList'  => [
				[
					'eventType' => $args['event']['type'] ?? 'click',
					'itemId'    => (string) $post_id,
					'sentAt'    => time(),
				],
			],
			'sessionId'  => $id,
			'trackingId' => $settings['event_tracker_id'] ?? '',
			'userId'     => $id, // TODO: We need a user ID that can be tracked across page views.
		];

		if ( isset( $args['event']['id'] ) ) {
			$params['eventList'][0]['eventId'] = (string) $args['event']['id'];
		}

		try {
			$client->putEvents( $params );
		} catch ( \Exception $e ) {
			return new WP_Error( 'event_failed', esc_html__( 'Event tracking failed.', 'classifai' ) );
		}

		return true;
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
				$return = $this->track_event( $post_id, $args );
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
			$debug_info[ __( 'Authenticated', 'classifai' ) ] = $provider_settings['authenticated'];
		}

		return apply_filters(
			'classifai_' . self::ID . '_debug_information',
			$debug_info,
			$settings,
			$this->feature_instance
		);
	}
}
