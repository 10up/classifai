<?php
/**
 * Azure Computer vision
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;

class ComputerVision extends Provider {
	/**
	 * @var string URL fragment to the describe (caption) API endpoint.
	 */
	protected $describe_url = 'vision/v1.0/describe?maxCandidates=3';

	/**
	 * ComputerVision constructor.
	 *
	 * @param string $service The service this class belongs to.
	 */
	public function __construct( $service ) {
		parent::__construct(
			'Microsoft Azure',
			'Computer Vision',
			'computer_vision',
			$service
		);
	}

	/**
	 * Resets settings for the ComputerVision provider.
	 */
	public function reset_settings() {
		// TODO: Implement reset_settings() method.
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
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_alt_tags' ], 10, 2 );
	}

	/**
	 * Provides the max filesize for the ComputerVision service.
	 *
	 * @return int
	 *
	 * @since 1.4.0
	 */
	public function get_max_filesize() {
		/**
		 * Filters the ComputerVision maximum allowed filesize.
		 *
		 * @param int Default 4MB.
		 */
		return apply_filters( 'classifai_computervision_max_filesize', 4000000 ); // 4MB default.
	}

	/**
	 * Generate the alt tags for the image being uploaded.
	 *
	 * @param array $metadata      The metadata for the image
	 * @param int   $attachment_id Post ID for the attachment.
	 *
	 * @return mixed
	 */
	public function generate_alt_tags( $metadata, $attachment_id ) {
		$threshold = $this->get_settings( 'caption_threshold' );

		$image_url = $this->get_largest_acceptable_image_url(
			get_attached_file( $attachment_id ),
			wp_get_attachment_url( $attachment_id, 'full' ),
			$metadata['sizes']
		);

		if ( empty( $image_url ) ) {
			return $metadata;
		}

		$captions = $this->scan_image( $image_url );
		if ( ! is_wp_error( $captions ) && isset( $captions[0] ) ) {
			// Save the first caption as the alt text if it passes the threshold.
			if ( $captions[0]->confidence * 100 > $threshold ) {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $captions[0]->text );
			}
			// Save all the results for later.
			update_post_meta( $attachment_id, 'classifai_computer_vision_captions', $captions );
		}
		return $metadata;
	}

	/**
	 * Retrieves the URL of the largest version of an attachment image accepted by the ComputerVision service.
	 *
	 * @param string $full_image The path to the full-sized image source file.
	 * @param string $full_url   The URL of the full-sized image.
	 * @param array  $intermediate_sizes Intermediate size data from attachment meta.
	 * @return string|null The image URL, or null if no acceptable image found.
	 *
	 * @since 1.4.0
	 */
	public function get_largest_acceptable_image_url( $full_image, $full_url, $intermediate_sizes ) {
		$file_size = @filesize( $full_image ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( $file_size && $this->get_max_filesize() > $file_size ) {
			return $full_url;
		}

		$sizes = $intermediate_sizes['sizes'];

		// Sort the image sizes in order of total width + height, descending.
		$sort_sizes = function( $size_1, $size_2 ) {
			$size_1_total = $size_1['width'] + $size_1['height'];
			$size_2_total = $size_2['width'] + $size_2['height'];

			if ( $size_1_total === $size_2_total ) {
				return 0;
			}

			return $size_1_total > $size_2_total ? -1 : 1;
		};

		usort( $sizes, $sort_sizes );

		foreach ( $sizes as $size ) {
			$sized_file = str_replace( basename( $full_image ), $size['file'], $full_image );
			$file_size  = @filesize( $sized_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( $file_size && $this->get_max_filesize() > $file_size ) {
				return str_replace( basename( $full_url ), $size['file'], $full_url );
			}
		}

		return null;
	}

	/**
	 * Scan the image and return the captions.
	 *
	 * @param string $image_url Path to the uploaded image.
	 *
	 * @return bool|\WP_Error
	 */
	protected function scan_image( $image_url ) {
		$settings = get_option( $this->get_option_name() );
		$rtn      = false;

		$request = wp_remote_post(
			trailingslashit( $settings['url'] ) . $this->describe_url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $settings['api_key'],
					'Content-Type'              => 'application/json',
				],
				'body'    => '{"url":"' . $image_url . '"}',
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( isset( $response->error ) ) {
				$rtn = new \WP_Error( 'auth', $response->error->message );
			} else {
				if ( $response->description ) {
					return $response->description->captions;
				}
			}
		} else {
			$rtn = $request;
		}

		return $rtn;
	}

	/**
	 * Setup fields
	 */
	public function setup_fields_sections() {
		add_settings_section( $this->get_option_name(), $this->provider_service_name, '', $this->get_option_name() );
		add_settings_field(
			'url',
			esc_html__( 'Endpoint URL', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'   => 'url',
				'input_type'  => 'text',
				'description' => __( 'e.g. <code>https://REGION.api.cognitive.microsoft.com/</code>', 'classifai' ),
			]
		);
		add_settings_field(
			'api-key',
			esc_html__( 'API Key', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'  => 'api_key',
				'input_type' => 'password',
			]
		);
		add_settings_field(
			'caption-threshold',
			esc_html__( 'Caption Confidence Threshold', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'caption_threshold',
				'input_type'    => 'number',
				'default_value' => 75,
				'description'   => __( 'Minimum confidence score for automatically applied image captions, numeric value from 0-100. Recommended to be set to at least 75.', 'classifai' ),
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
		// TODO: Implement sanitize_settings() method.
		$new_settings = [];
		if ( ! empty( $settings['url'] ) && ! empty( $settings['api_key'] ) ) {
			$auth_check = $this->authenticate_credentials( $settings['url'], $settings['api_key'] );
			if ( is_wp_error( $auth_check ) ) {
				add_settings_error(
					$this->get_option_name(),
					'classifai-registration',
					$auth_check->get_error_message(),
					'error'
				);
				$new_settings['authenticated'] = false;
			} else {
				$new_settings['authenticated'] = true;
			}
			$new_settings['url']     = esc_url_raw( $settings['url'] );
			$new_settings['api_key'] = sanitize_text_field( $settings['api_key'] );
		} else {
			$new_settings['valid']   = false;
			$new_settings['url']     = '';
			$new_settings['api_key'] = '';
			add_settings_error(
				$this->get_option_name(),
				'classifai-registration',
				esc_html__( 'Please enter your credentials', 'classifai' ),
				'error'
			);
		}

		if ( is_numeric( $settings['caption_threshold'] ) && (int) $settings['caption_threshold'] >= 0 && (int) $settings['caption_threshold'] <= 100 ) {
			$new_settings['caption_threshold'] = absint( $settings['caption_threshold'] );
		} else {
			$new_settings['caption_threshold'] = 75;
		}

		return $new_settings;
	}

	/**
	 * Authenitcates our credentials.
	 *
	 * @param string $url     Endpoint URL.
	 * @param string $api_key Api Key.
	 *
	 * @return bool|\WP_Error
	 */
	protected function authenticate_credentials( $url, $api_key ) {
		$rtn     = false;
		$request = wp_remote_post(
			trailingslashit( $url ) . $this->describe_url,
			[
				'headers' => [
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Content-Type'              => 'application/json',
				],
				'body'    => '{"url":"https://classifaiplugin.com/wp-content/themes/classifai-theme/assets/img/header.png"}',
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $response->error ) ) {
				$rtn = new \WP_Error( 'auth', $response->error->message );
			} else {
				$rtn = true;
			}
		}

		return $rtn;
	}
}
