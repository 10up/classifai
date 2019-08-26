<?php
/**
 * Azure Computer vision
 */

namespace Classifai\Providers\Azure;

use Classifai\Providers\Provider;

class ComputerVision extends Provider {

	/**
	 * @var string URL fragment to the analye API endpoint
	 */
	protected $analyze_url = '/vision/v1.0/analyze';

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
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'process_image' ], 10, 2 );
	}


	/**
	 * Process the image via Computer Vision based on the settings.
	 *
	 * @param array $metadata      The metadata for the image.
	 * @param int   $attachment_id Post ID for the attachment.
	 *
	 * @return mixed
	 */
	public function process_image( $metadata, $attachment_id ) {

		$settings = $this->get_settings();
		if (
			'no' !== $settings['enable_image_tagging'] ||
			'no' !== $settings['enable_image_captions']
		) {
			$image_url  = wp_get_attachment_image_url( $attachment_id );
			$image_scan = $this->scan_image( $image_url );
			if ( ! is_wp_error( $image_scan ) ) {
				// Check for captions
				if ( isset( $image_scan->description->captions ) ) {
					// Process the captions
					$this->generate_alt_tags( $image_scan->description->captions, $attachment_id );
				}
				// Check for tags
				if ( isset( $image_scan->tags ) ) {
					// Process the tags
					$this->generate_image_tags( $image_scan->tags, $attachment_id );
				}
			}
		}

		return $metadata;
	}

	/**
	 * Scan the image and return the captions.
	 *
	 * @param string $image_url Path to the uploaded image.
	 *
	 * @return bool|object|\WP_Error
	 */
	protected function scan_image( $image_url ) {
		$settings = $this->get_settings();
		$url      = $this->prep_api_url();

		$request = wp_remote_post(
			$url,
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
				return $response;
			}
		} else {
			$rtn = $request;
		}

		return $rtn;
	}

	/**
	 * Build and return the API endpoint based on settings.
	 *
	 * @return string
	 */
	protected function prep_api_url() {
		$settings     = $this->get_settings();
		$api_features = [];
		if ( 'no' !== $settings['enable_image_captions'] ) {
			$api_features[] = 'Description';
		}
		if ( 'no' !== $settings['enable_image_tagging'] ) {
			$api_features[] = 'Tags';
		}
		$endpoint = add_query_arg( 'visualFeatures', implode( ',', $api_features ), trailingslashit( $settings['url'] ) . $this->analyze_url );
		return $endpoint;
	}

	/**
	 * Generate the alt tags for the image being uploaded.
	 *
	 * @param array $captions      Captions returned from the API
	 * @param int   $attachment_id Post ID for the attachment.
	 */
	protected function generate_alt_tags( $captions, $attachment_id ) {
		/**
		 * Filter the captions returned from the API.
		 *
		 * @param array $captions. The caption data.
		 *
		 * @return array $captions The filtered caption data.
		 */
		$captions = apply_filters( 'classifai_computer_vision_captions', $captions );
		// If $captions isn't an array, don't save them.
		if ( is_array( $captions ) && ! empty( $captions ) ) {
			$threshold = $this->get_settings( 'caption_threshold' );
			// Save the first caption as the alt text if it passes the threshold.
			if ( $captions[0]->confidence * 100 > $threshold ) {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $captions[0]->text );
			}
			// Save all the results for later.
			update_post_meta( $attachment_id, 'classifai_computer_vision_captions', $captions );
		}
	}

	/**
	 * Generate the image tags for the image being uploaded.
	 *
	 * @param array $tags          Array ot tags returned from the API.
	 * @param int   $attachment_id Post ID for the attachment.
	 *
	 * @return mixed
	 */
	protected function generate_image_tags( $tags, $attachment_id ) {
		/**
		 * Filter the tags returned from the API.
		 *
		 * @param array $tags. The caption data.
		 *
		 * @return array $tags The filtered caption data.
		 */
		$tags = apply_filters( 'classifai_computer_vision_image_tags', $tags );
		// If $tags isn't an array, don't save them.
		if ( is_array( $tags ) && ! empty( $tags ) ) {
			$threshold = $this->get_settings( 'tag_threshold' );
			$taxonomy  = $this->get_settings( 'image_tag_taxonomy' );
			// Save the first caption as the alt text if it passes the threshold.
			$custom_tags = [];
			foreach ( $tags as $tag ) {
				if ( $tag->confidence * 100 > $threshold ) {
					$custom_tags[] = $tag->name;
					wp_add_object_terms( $attachment_id, $tag->name, $taxonomy );
				}
			}
			if ( ! empty( $custom_tags ) ) {
				wp_update_term_count_now( $custom_tags, $taxonomy );
			}

			// Save the tags for later
			update_post_meta( $attachment_id, 'classifai_computer_vision_image_tags', $tags );
		}
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
			'enable-image-captions',
			esc_html__( 'Automatically Caption Images', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_captions',
				'input_type'    => 'checkbox',
				'default_value' => true,
				'description'   => __( 'Uploaded images will be auto-captioned', 'classifai' ),
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
		add_settings_field(
			'enable-image-tagging',
			esc_html__( 'Automatically Tag Images', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'enable_image_tagging',
				'input_type'    => 'checkbox',
				'default_value' => true,
				'description'   => __( 'Uploaded images will be auto-tagged', 'classifai' ),
			]
		);
		add_settings_field(
			'image-tag-threshold',
			esc_html__( 'Tag Confidence Threshold', 'classifai' ),
			[ $this, 'render_input' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'     => 'tag_threshold',
				'input_type'    => 'number',
				'default_value' => 70,
				'description'   => __( 'Minimum confidence score for automatically applied image tags, numeric value from 0-100. Recommended to be set to at least 75.', 'classifai' ),
			]
		);

		// What taxonomy should we tag images with?
		$attachment_taxonomies = get_object_taxonomies( 'attachment', 'objects' );
		$options               = [];
		foreach ( $attachment_taxonomies as $name => $taxonomy ) {
			$options[ $name ] = $taxonomy->label;
		}
		add_settings_field(
			'image-tag-taxonomy',
			esc_html__( 'Tag Taxonomy', 'classifai' ),
			[ $this, 'render_select' ],
			$this->get_option_name(),
			$this->get_option_name(),
			[
				'label_for'   => 'image_tag_taxonomy',
				'options'     => $options,
				'description' => __( 'Minimum confidence score for automatically applied image tags, numeric value from 0-100. Recommended to be set to at least 75.', 'classifai' ),
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

		$caption_enabled                       = isset( $settings['enable_image_captions'] ) ? '1' : 'no';
		$new_settings['enable_image_captions'] = $caption_enabled;

		if ( is_numeric( $settings['caption_threshold'] ) && (int) $settings['caption_threshold'] >= 0 && (int) $settings['caption_threshold'] <= 100 ) {
			$new_settings['caption_threshold'] = absint( $settings['caption_threshold'] );
		} else {
			$new_settings['caption_threshold'] = 75;
		}

		$tag_enabled                          = isset( $settings['enable_image_tagging'] ) ? '1' : 'no';
		$new_settings['enable_image_tagging'] = $tag_enabled;

		if ( is_numeric( $settings['tag_threshold'] ) && (int) $settings['tag_threshold'] >= 0 && (int) $settings['tag_threshold'] <= 100 ) {
			$new_settings['tag_threshold'] = absint( $settings['tag_threshold'] );
		} else {
			$new_settings['tag_threshold'] = 75;
		}

		if ( isset( $settings['image_tag_taxonomy'] ) && taxonomy_exists( $settings['image_tag_taxonomy'] ) ) {
			$new_settings['image_tag_taxonomy'] = $settings['image_tag_taxonomy'];
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
			trailingslashit( $url ) . $this->analyze_url,
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
