<?php
/**
 *  Abstract class that defines the providers for a service.
 */

namespace Classifai\Providers;

use Classifai\Providers\CredentialResolver;

abstract class Provider {

	/**
	 * @var string The ID of the provider.
	 *
	 * To be set in the subclass.
	 */
	const ID = '';

	/**
	 * Feature instance.
	 *
	 * @var \Classifai\Features\Feature
	 */
	protected $feature_instance = null;

	/**
	 * Prefix for the system prompt message.
	 *
	 * @var string
	 */
	protected $system_prompt = 'You will be provided with content delimited by triple quotes.';

	/**
	 * Prefix for the WooCommerce system prompt message.
	 *
	 * @var string
	 */
	protected $system_prompt_woo = 'You are an expert in e-commerce and WooCommerce SEO.';

	/**
	 * Format the result of most recent request.
	 *
	 * @param array|\WP_Error $data Response data to format.
	 *
	 * @return string
	 */
	protected function get_formatted_latest_response( $data ): string {
		if ( ! $data ) {
			return __( 'N/A', 'classifai' );
		}

		if ( is_wp_error( $data ) ) {
			return $data->get_error_message();
		}

		return preg_replace( '/,"/', ', "', wp_json_encode( $data ) );
	}

	/**
	 * Get the product content.
	 *
	 * This is a helper function to get the product content in JSON format.
	 *
	 * @param int $product_id The product ID.
	 * @return string
	 */
	public function get_product_content( int $product_id ): string {
		$product = function_exists( 'wc_get_product' ) ? \wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			return '';
		}

		$product_data = [
			'title'       => $product->get_name(),
			'type'        => $product->get_type(),
			'sku'         => $product->get_sku(),
			'categories'  => function_exists( 'wc_get_product_category_list' ) ? wp_strip_all_tags( \wc_get_product_category_list( $product_id ) ) : null,
			'tags'        => function_exists( 'wc_get_product_tag_list' ) ? wp_strip_all_tags( \wc_get_product_tag_list( $product_id ) ) : null,
			'attributes'  => [],
			'price'       => $product->get_price(),
			'stock'       => $product->is_in_stock() ? 'In Stock' : 'Out of Stock',
			'short_desc'  => wp_strip_all_tags( $product->get_short_description() ),
			'description' => wp_strip_all_tags( $product->get_description() ),
		];

		// Fetch attributes.
		foreach ( $product->get_attributes() as $attribute_name => $attribute ) {
			if ( $attribute->is_taxonomy() ) {
				$terms                        = function_exists( 'wc_get_product_terms' ) ? \wc_get_product_terms( $product_id, $attribute_name, [ 'fields' => 'names' ] ) : [];
				$product_data['attributes'][] = $attribute_name . ': ' . implode( ', ', $terms );
			} else {
				$options                      = is_array( $attribute->get_options() ) ? $attribute->get_options() : [];
				$product_data['attributes'][] = $attribute_name . ': ' . implode( ', ', $options );
			}
		}

		return wp_json_encode( $product_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT );
	}

	/**
	 * Get the request messages.
	 *
	 * This is a helper function to get the request messages based on the post type.
	 *
	 * @param int    $post_id         The post ID.
	 * @param string $prompt          The prompt message.
	 * @param string $message_content The message content.
	 * @return array
	 */
	public function get_request_messages( int $post_id, string $prompt, string $message_content = '' ): array {
		$messages = [];

		// WooCommerce Product Handling.
		if (
			'product' === get_post_type( $post_id ) &&
			function_exists( 'wc_get_product' ) &&
			\wc_get_product( $post_id )
		) {
			$messages = [
				[
					'role'    => 'system',
					'content' => $this->system_prompt_woo . ' ' . $prompt,
				],
				[
					'role'    => 'user',
					'content' => sprintf( 'Product data: """%s"""', $message_content ),
				],
			];
		} else {
			// Fallback for regular WordPress posts, or when WooCommerce is not active.
			$messages = [
				[
					'role'    => 'system',
					'content' => $this->system_prompt . ' ' . $prompt,
				],
				[
					'role'    => 'user',
					'content' => '"""' . $message_content . '"""',
				],
			];
		}

		return $messages;
	}

	/**
	 * Adds an API key field.
	 *
	 * @param array $args API key field arguments.
	 */
	public function add_api_key_field( array $args = [] ) {
		$default_settings = $this->feature_instance->get_settings();
		$default_settings = $default_settings[ static::ID ];
		$id               = $args['id'] ?? 'api_key';

		add_settings_field(
			$id,
			$args['label'] ?? esc_html__( 'API key', 'classifai' ),
			[ $this->feature_instance, 'render_input' ],
			$this->feature_instance->get_option_name(),
			$this->feature_instance->get_option_name() . '_section',
			[
				'option_index'  => static::ID,
				'label_for'     => $id,
				'input_type'    => 'password',
				'default_value' => $default_settings[ $id ],
				'class'         => 'classifai-provider-field hidden provider-scope-' . static::ID, // Important to add this.
			]
		);
	}

	/**
	 * Get the credentials for the Provider.
	 *
	 * Will return either the Feature-level credentials,
	 * if they are being overridden, or the global credentials.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public function get_credentials(): array {
		/**
		 * Filter the credentials for the Provider.
		 *
		 * @since x.x.x
		 * @hook classifai_provider_credentials
		 *
		 * @param array $credentials The credentials for the Provider.
		 * @param string $provider_id The ID of the Provider.
		 * @param string|null $feature_id The ID of the Feature, or null if not set.
		 * @param array $feature_provider_settings The Provider specific Feature settings.
		 * @return array The filtered credentials.
		 */
		return apply_filters(
			'classifai_provider_credentials',
			CredentialResolver::resolve(
				static::ID,
				$this->feature_instance->get_settings( static::ID ) ?? []
			),
			static::ID,
			$this->feature_instance::ID ?? null,
			$this->feature_instance->get_settings( static::ID ) ?? []
		);
	}

	/**
	 * Get a credential field value for the Provider.
	 *
	 * @param string $credential_key The credential field name.
	 * @return mixed The credential value, or null if not set.
	 */
	public function get_credential( string $credential_key ) {
		$credentials = $this->get_credentials();
		return $credentials[ $credential_key ] ?? null;
	}
}
