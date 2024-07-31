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
							/* translators: %1$s is replaced with the OpenAI sign up URL */
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

				$connected = $this->connect_to_service(
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

		return $new_settings;
	}

	/**
	 * Connects to the Amazon Personalize service.
	 *
	 * @param array $args Overridable args.
	 * @return array
	 */
	public function connect_to_service( array $args = array() ): array {
		$settings = $this->feature_instance->get_settings( static::ID );

		$default = [
			'access_key_id'     => $settings[ static::ID ]['access_key_id'] ?? '',
			'secret_access_key' => $settings[ static::ID ]['secret_access_key'] ?? '',
			'aws_region'        => $settings[ static::ID ]['aws_region'] ?? 'us-east-1',
		];

		$default = wp_parse_args( $args, $default );

		// Return if credentials don't exist.
		if ( empty( $default['access_key_id'] ) || empty( $default['secret_access_key'] ) ) {
			return [];
		}

		try {
			/**
			 * Filters the return value of the connect to services function.
			 *
			 * Returning a non-false value from the filter will short-circuit the request
			 * and return early with that value.
			 *
			 * This filter is useful for E2E tests.
			 *
			 * @since x.x.x
			 * @hook classifai_aws_personalize_pre_connect_to_service
			 *
			 * @param {bool} $pre The value of pre connect to service. Default false. Non-false value will short-circuit the request.
			 *
			 * @return {bool|mixed} The filtered value of connect to service.
			 */
			$pre = apply_filters( 'classifai_' . self::ID . '_pre_connect_to_service', false );

			if ( false !== $pre ) {
				return $pre;
			}

			$client  = $this->get_personalize_client( $args );
			$schemas = $client->listSchemas();

			return $schemas;
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

			return [];
		}
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

	/**
	 * Returns AWS Personalize client.
	 *
	 * @param array $aws_config AWS configuration array.
	 * @return \Aws\Personalize\PersonalizeClient|null
	 */
	public function get_personalize_client( array $aws_config = array() ) {
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

		return $sdk->createPersonalize();
	}
}
