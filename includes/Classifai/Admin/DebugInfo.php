<?php
/**
 * DebugInfo class
 *
 * @since 1.4.0
 * @package Classifai
 */

namespace Classifai\Admin;

/**
 * Adds information useful for debugging to the Site Health screen introduced to core in 5.2.
 *
 * @since 1.4.0
 */
class DebugInfo {

	/**
	 * Checks whether this class's register method should run.
	 *
	 * @return bool
	 * @since 1.4.0
	 */
	public function can_register() {
		return is_admin();
	}

	/**
	 * Adds WP hook callbacks.
	 *
	 * @since 1.4.0
	 */
	public function register() {
		add_filter( 'debug_information', [ $this, 'add_classifai_debug_information' ] );
	}

	/**
	 * Modifies debug information displayed on the WP Site Health screen.
	 *
	 * @see WP_Debug_Data::debug_data
	 * @filter debug_information
	 *
	 * @param array $information The full array of site debug information.
	 * @return array Filtered debug information.
	 *
	 * @since 1.4.0
	 */
	public function add_classifai_debug_information( $information ) {
		$plugin_data = get_plugin_data( CLASSIFAI_PLUGIN );

		/**
		 * Filters debug information displayed on the Site Health screen for the ClassifAI plugin.
		 *
		 * @since 1.4.0
		 * @hook classifai_debug_information
		 * @see {@link https://developer.wordpress.org/reference/hooks/debug_information/|debug_information}
		 *
		 * @param {array} 'debug_info' Array of associative arrays corresponding to lines shown on the Site Health screen. Each array
		 *              requires a `label` and a `value` field. Other accepted fields are `debug` and `private`.
		 *
		 * @return {array} Filtered array of debug information.
		 */
		$fields = apply_filters(
			'classifai_debug_information',
			[
				[
					'label' => __( 'Version', 'classifai' ),
					'value' => $plugin_data['Version'],
				],
			],
			$information
		);

		if ( ! is_array( $fields ) ) {
			$fields = [];
		}

		$validate_field = function( $field ) {
			if ( ! is_array( $field ) ) {
				return false;
			}

			return isset( $field['label'] ) && isset( $field['value'] );
		};
		$fields         = array_filter( $fields, $validate_field );

		$text_domain = $plugin_data['TextDomain'];
		$label       = $plugin_data['Name'];

		$information[ $text_domain ] = compact( 'label', 'fields' );

		return $information;
	}
}
