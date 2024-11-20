<?php

namespace Classifai;

use Classifai\Features\Classification;
use Classifai\Features\Smart404;
use Classifai\Features\Smart404EPIntegration;
use Classifai\Providers\Provider;
use Classifai\Admin\UserProfile;
use Classifai\Providers\Watson\NLU;
use Classifai\Services\Service;
use Classifai\Services\ServicesManager;
use WP_Error;

/**
 * Miscellaneous Helper functions to access different parts of the
 * ClassifAI plugin.
 */

/**
 * Returns the ClassifAI plugin's singleton instance
 *
 * @return Plugin
 */
function get_plugin() {
	return Plugin::get_instance();
}

/**
 * Overwrites the ClassifAI plugin's stored settings. Expected format is,
 *
 * [
 *     'post_types' => [ <list of post type names> ]
 *     'features' => [
 *         <feature_name> => <bool>
 *         <feature_threshold> => <int>
 *     ],
 *     'credentials' => [
 *         'watson_username' => <string>
 *         'watson_password' => <string>
 *     ]
 * ]
 *
 * @param array $settings The settings we're saving.
 */
function set_plugin_settings( array $settings ) {
	update_option( 'classifai_settings', $settings );
}

/**
 * Resets the plugin to factory defaults, keeping licensing information only.
 */
function reset_plugin_settings() {

	$options = get_option( 'classifai_settings' );
	if ( $options && isset( $options['registration'] ) ) {
		// This is a legacy option set, so let's update it to the new format.
		$new_settings = [
			'valid_license' => $options['valid_license'],
			'email'         => isset( $options['registration']['email'] ) ? $options['registration']['email'] : '',
			'license_key'   => isset( $options['registration']['license_key'] ) ? $options['registration']['license_key'] : '',
		];
		update_option( 'classifai_settings', $new_settings );
	}

	$services = get_plugin()->services;
	if ( ! isset( $services['service_manager'] ) || ! $services['service_manager']->service_classes ) {
		return;
	}

	$service_classes = $services['service_manager']->service_classes;
	foreach ( $service_classes as $service_class ) {
		if ( ! $service_class instanceof Service || empty( $service_class->provider_classes ) ) {
			continue;
		}

		foreach ( $service_class->provider_classes as $provider_class ) {
			if ( ! $provider_class instanceof Provider || ! method_exists( $provider_class, 'reset_settings' ) ) {
				continue;
			}

			$provider_class->reset_settings();
		}
	}
}

/**
 * Get post types we want to show in the language processing settings
 *
 * @since 1.6.0
 *
 * @return array
 */
function get_post_types_for_language_settings(): array {
	$post_types = get_post_types( [ 'public' => true ], 'objects' );
	$post_types = array_filter( $post_types, 'is_post_type_viewable' );

	// Remove the attachment post type
	unset( $post_types['attachment'] );

	/**
	 * Filter post types shown in language processing settings.
	 *
	 * @since 1.6.0
	 * @hook classifai_language_settings_post_types
	 *
	 * @param {array} $post_types Array of post types to show in language processing settings.
	 *
	 * @return {array} Array of post types.
	 */
	return apply_filters( 'classifai_language_settings_post_types', $post_types );
}

/**
 * Get post types we want to show in the language processing settings
 *
 * @since 1.7.1
 *
 * @return array
 */
function get_post_statuses_for_language_settings(): array {
	$post_statuses = get_all_post_statuses();

	/**
	 * Filter post statuses shown in language processing settings.
	 *
	 * @since 1.7.1
	 * @hook classifai_language_settings_post_statuses
	 *
	 * @param {array} $post_statuses Array of post statuses to show in language processing settings.
	 *
	 * @return {array} Array of post statuses.
	 */
	return apply_filters( 'classifai_language_settings_post_statuses', $post_statuses );
}

/**
 * Provides the max filesize for the Computer Vision service.
 *
 * @since 1.4.0
 *
 * @return int
 */
function computer_vision_max_filesize(): int {
	/**
	 * Filters the Computer Vision maximum allowed filesize.
	 *
	 * @since 1.5.0
	 * @hook classifai_computer_vision_max_filesize
	 *
	 * @param {int} file_size The maximum allowed filesize for Computer Vision in bytes. Default `4 * MB_IN_BYTES`.
	 *
	 * @return {int} Filtered filesize in bytes.
	 */
	return apply_filters( 'classifai_computer_vision_max_filesize', 4 * MB_IN_BYTES ); // 4MB default.
}

/**
 * Callback for sorting images by width plus height, descending.
 *
 * @since 1.5.0
 *
 * @param array $size_1 Associative array containing width and height values.
 * @param array $size_2 Associative array containing width and height values.
 * @return int Returns -1 if $size_1 is larger, 1 if $size_2 is larger, and 0 if they are equal.
 */
function sort_images_by_size_cb( array $size_1, array $size_2 ): int {
	$size_1_total = $size_1['width'] + $size_1['height'];
	$size_2_total = $size_2['width'] + $size_2['height'];

	if ( $size_1_total === $size_2_total ) {
		return 0;
	}

	return $size_1_total > $size_2_total ? -1 : 1;
}

/**
 * Retrieves the URL of the largest version of an attachment image lower than a specified max size.
 *
 * @since 1.4.0
 *
 * @param string $full_image The path to the full-sized image source file.
 * @param string $full_url   The URL of the full-sized image.
 * @param array  $sizes      Intermediate size data from attachment meta.
 * @param int    $max        The maximum acceptable size.
 * @return string|null The image URL, or null if no acceptable image found.
 */
function get_largest_acceptable_image_url( string $full_image, string $full_url, array $sizes, int $max = MB_IN_BYTES ) {
	$file_size = @filesize( $full_image ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( $file_size && $max >= $file_size ) {
		return $full_url;
	}

	usort( $sizes, __NAMESPACE__ . '\sort_images_by_size_cb' );

	foreach ( $sizes as $size ) {
		$sized_file = str_replace( basename( $full_image ), $size['file'], $full_image );
		$file_size  = @filesize( $sized_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $file_size && $max >= $file_size ) {
			return str_replace( basename( $full_url ), $size['file'], $full_url );
		}
	}

	return null;
}

/**
 * Retrieves the URL of the largest image that matches filesize and dimensions.
 *
 * This will check that the filesize of an image matches our requirements and
 * if so, will then check the dimensions match our requirements as well. If
 * neither match, will move on to the next largest image size.
 *
 * @param string $full_image The path to the full-sized image source file.
 * @param string $full_url   The URL of the full-sized image.
 * @param array  $metadata   Attachment metadata, including intermediate sizes.
 * @param array  $width      Array of minimimum and maximum width values. Default 0, 4200.
 * @param array  $height     Array of minimimum and maximum height values. Default 0, 4200.
 * @param int    $max_size   The maximum acceptable filesize. Default 1MB.
 * @return string|null The image URL, or null if no acceptable image found.
 */
function get_largest_size_and_dimensions_image_url( string $full_image, string $full_url, array $metadata, array $width = [ 0, 4200 ], array $height = [ 0, 4200 ], int $max_size = MB_IN_BYTES ) {
	// Check if the full size image meets our filesize and dimension requirements
	$file_size = @filesize( $full_image ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if (
		( $file_size && $max_size >= $file_size )
		&& ( $metadata['width'] >= $width[0] && $metadata['width'] <= $width[1] )
		&& ( $metadata['height'] >= $height[0] && $metadata['height'] <= $height[1] )
	) {
		return $full_url;
	}

	// If the full size doesn't match, run the same checks on our resized images
	if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
		usort( $metadata['sizes'], __NAMESPACE__ . '\sort_images_by_size_cb' );

		foreach ( $metadata['sizes'] as $size ) {
			$sized_file = str_replace( basename( $full_image ), $size['file'], $full_image );
			$file_size  = @filesize( $sized_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if (
				( $file_size && $max_size >= $file_size )
				&& ( $size['width'] >= $width[0] && $size['width'] <= $width[1] )
				&& ( $size['height'] >= $height[0] && $size['height'] <= $height[1] )
			) {
				return str_replace( basename( $full_url ), $size['file'], $full_url );
			}
		}
	}

	return null;
}

/**
 * Allows returning modified image URL for a given attachment.
 *
 * @param int $post_id Post ID.
 * @return mixed
 */
function get_modified_image_source_url( int $post_id ) {
	/**
	 * Filter to modify image source URL in order to allow scanning images,
	 * stored on third party storages that cannot be used by
	 * helper function `get_largest_acceptable_image_url()` to determine `filesize()` locally.
	 *
	 * Default is null, return filtered string to allow classifying image on external source.
	 *
	 * @since 1.6.0
	 * @hook classifai_generate_image_alt_tags_source_url
	 *
	 * @param {mixed} $image_url New image path for given attachment ID.
	 * @param {int}   $post_id   The ID of the attachment to be used in classification.
	 *
	 * @return {mixed} NULL or filtered URl for given attachment id.
	 */
	return apply_filters( 'classifai_generate_image_alt_tags_source_url', null, $post_id );
}

/**
 * Check if attachment is PDF document.
 *
 * @param int|\WP_Post $post Post object for the attachment being viewed.
 * @return bool
 */
function attachment_is_pdf( $post ): bool {
	$mime_type          = get_post_mime_type( $post );
	$matched_extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );

	if ( in_array( 'pdf', $matched_extensions, true ) ) {
		return true;
	}

	return false;
}

/**
 * Get asset info from extracted asset files.
 *
 * @param string $slug Asset slug as defined in build/webpack configuration.
 * @param string $attribute Optional attribute to get. Can be version or dependencies.
 * @return string|array
 */
function get_asset_info( string $slug, string $attribute = null ) {
	if ( file_exists( CLASSIFAI_PLUGIN_DIR . '/dist/' . $slug . '.asset.php' ) ) {
		$asset = require CLASSIFAI_PLUGIN_DIR . '/dist/' . $slug . '.asset.php';
	} else {
		return null;
	}

	if ( ! empty( $attribute ) && isset( $asset[ $attribute ] ) ) {
		return $asset[ $attribute ];
	}

	return $asset;
}

/**
 * Get the list of registered services.
 *
 * @return array Array of services.
 */
function get_services_menu(): array {
	$services = Plugin::$instance->services;
	if ( empty( $services ) || empty( $services['service_manager'] ) || ! $services['service_manager'] instanceof ServicesManager ) {
		return [];
	}

	/** @var ServicesManager $service_manager Instance of the services manager class. */
	$service_manager = $services['service_manager'];
	$services        = [];

	foreach ( $service_manager->service_classes as $service ) {
		$services[ $service->get_menu_slug() ] = $service->get_display_name();
	}
	return $services;
}

/**
 * Sanitizes and ensures an input variable is set.
 *
 * @param string  $key               $_GET or $_POST array key.
 * @param boolean $is_get            If the request is $_GET. Defaults to false.
 * @param string  $sanitize_callback Sanitize callback. Defaults to `sanitize_text_field`
 * @return string|boolean Sanitized string or `false` as fallback.
 */
function clean_input( string $key = '', bool $is_get = false, string $sanitize_callback = 'sanitize_text_field' ) {
	if ( ! is_callable( $sanitize_callback ) ) {
		$sanitize_callback = 'sanitize_text_field';
	}

	if ( $is_get ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return isset( $_GET[ $key ] ) ? call_user_func( $sanitize_callback, wp_unslash( $_GET[ $key ] ) ) : '';
	} else {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return isset( $_POST[ $key ] ) ? call_user_func( $sanitize_callback, wp_unslash( $_POST[ $key ] ) ) : '';
	}

	return false;
}

/**
 * Find the provider class that a service belongs to.
 *
 * @param array  $provider_classes Provider classes to look in.
 * @param string $provider_id      ID of the provider.
 * @return Provider|WP_Error
 */
function find_provider_class( array $provider_classes = [], string $provider_id = '' ) {
	$provider = '';

	foreach ( $provider_classes as $provider_class ) {
		if ( $provider_id === $provider_class::ID ) {
			$provider = $provider_class;
		}
	}

	if ( ! $provider ) {
		return new WP_Error( 'provider_class_required', esc_html__( 'Provider class not found.', 'classifai' ) );
	}

	return $provider;
}

/**
 * Get core and custom post statuses.
 *
 * @return array
 */
function get_all_post_statuses(): array {
	$all_statuses = wp_list_pluck(
		get_post_stati(
			[],
			'objects'
		),
		'label'
	);

	/*
	 * We unset the following because we want to limit the post
	 * statuses to the ones returned by `get_post_statuses()` and
	 * any custom post statuses registered using `register_post_status()`
	 */
	unset(
		$all_statuses['future'],
		$all_statuses['trash'],
		$all_statuses['auto-draft'],
		$all_statuses['inherit'],
		$all_statuses['request-pending'],
		$all_statuses['request-confirmed'],
		$all_statuses['request-failed'],
		$all_statuses['request-completed']
	);

	/*
	 * There is a minor difference in the label for 'pending' status between
	 * `get_post_statuses()` and `get_post_stati()`.
	 *
	 * `get_post_stati()` has the label as `Pending` whereas
	 * `get_post_statuses()` has the label as `Pending Review`.
	 *
	 * So we normalise it here.
	 */
	if ( isset( $all_statuses['pending'] ) ) {
		$all_statuses['pending'] = esc_html__( 'Pending Review', 'classifai' );
	}

	/**
	 * Hook to filter post statuses.
	 *
	 * @since 2.2.2
	 * @hook classifai_all_post_statuses
	 *
	 * @param {array} $all_statuses Array of post statuses.
	 *
	 * @return {array} Array of post statuses.
	 */
	return apply_filters( 'classifai_all_post_statuses', $all_statuses );
}

/**
 * Check if the current user has permission to create and assign terms.
 *
 * @param string $tax Taxonomy name.
 * @return bool|WP_Error
 */
function check_term_permissions( string $tax = '' ) {
	$taxonomy = get_taxonomy( $tax );

	if ( empty( $taxonomy ) || empty( $taxonomy->show_in_rest ) ) {
		return new WP_Error( 'invalid_taxonomy', esc_html__( 'Taxonomy not found. Double check your settings.', 'classifai' ) );
	}

	$create_cap = is_taxonomy_hierarchical( $taxonomy->name ) ? $taxonomy->cap->edit_terms : $taxonomy->cap->assign_terms;

	if ( ! current_user_can( $create_cap ) || ! current_user_can( $taxonomy->cap->assign_terms ) ) {
		return new WP_Error( 'rest_cannot_assign_term', esc_html__( 'Sorry, you are not alllowed to create or assign to this taxonomy.', 'classifai' ) );
	}

	return true;
}

/**
 * Renders a link to disable a specific feature.
 *
 * @since 2.5.0
 *
 * @param string $feature Feature key.
 */
function render_disable_feature_link( string $feature ) {
	$user_profile     = new UserProfile();
	$allowed_features = $user_profile->get_allowed_features( get_current_user_id() );
	$profile_url      = get_edit_profile_url( get_current_user_id() ) . '#classifai-profile-features-section';

	if ( ! empty( $allowed_features ) && isset( $allowed_features[ $feature ] ) ) {
		?>
		<a href="<?php echo esc_url( $profile_url ); ?>" target="_blank" rel="noopener noreferrer" class="classifai-disable-feature-link" aria-label="<?php esc_attr_e( 'Opt out of using this ClassifAI feature', 'classifai' ); ?>">
			<?php esc_html_e( 'Disable this ClassifAI feature', 'classifai' ); ?>
		</a>
		<?php
	}
}

/**
 * Sanitize the prompt data.
 * This is used for the repeater field.
 *
 * @since 2.4.0
 *
 * @param array $prompt_key Prompt key.
 * @param array $new_settings   Settings data.
 * @return array Sanitized prompt data.
 */
function sanitize_prompts( $prompt_key = '', array $new_settings = [] ): array {
	if ( isset( $new_settings[ $prompt_key ] ) && is_array( $new_settings[ $prompt_key ] ) ) {

		$prompts = $new_settings[ $prompt_key ];

		// Remove any prompts that don't have a title and prompt.
		$prompts = array_filter(
			$prompts,
			function ( $prompt ) {
				return ! empty( $prompt['title'] ) && ! empty( $prompt['prompt'] );
			}
		);

		// Sanitize the prompts and make sure only one prompt is marked as default.
		$has_default = false;

		$prompts = array_map(
			function ( $prompt ) use ( &$has_default ) {
				$default = isset( $prompt['default'] ) && $prompt['default'] && ! $has_default;

				if ( $default ) {
					$has_default = true;
				}

				return array(
					'title'    => sanitize_text_field( $prompt['title'] ),
					'prompt'   => sanitize_textarea_field( $prompt['prompt'] ),
					'default'  => absint( $default ),
					'original' => absint( $prompt['original'] ),
				);
			},
			$prompts
		);

		// If there is no default, use the first prompt.
		if ( false === $has_default && ! empty( $prompts ) ) {
			$prompts[0]['default'] = 1;
		}

		return $prompts;
	}

	return array();
}

/**
 * Get the default prompt for use.
 *
 * @since 2.4.0
 *
 * @param array $prompts Prompt data.
 * @return string|null Default prompt.
 */
function get_default_prompt( array $prompts ): ?string {
	$default_prompt = null;

	if ( ! empty( $prompts ) ) {
		$prompt_data = array_filter(
			$prompts,
			function ( $prompt ) {
				return isset( $prompt['default'] ) && $prompt['default'] && ! $prompt['original'];
			}
		);

		if ( ! empty( $prompt_data ) ) {
			$default_prompt = current( $prompt_data )['prompt'];
		} elseif ( ! empty( $prompts[0]['prompt'] ) && ! $prompts[0]['original'] ) {
			// If there is no default, use the first prompt, unless it's the original prompt.
			$default_prompt = $prompts[0]['prompt'];
		}
	}

	return $default_prompt;
}

/**
 * Sanitisation callback for number of responses.
 *
 * @param string $key The key of the value we are sanitizing.
 * @param array  $new_settings The settings array.
 * @param array  $settings     Current array.
 * @return int
 */
function sanitize_number_of_responses_field( string $key, array $new_settings, array $settings ): int {
	return absint( $new_settings[ $key ] ?? $settings[ $key ] ?? '' );
}

/**
 * Returns a bool based on whether the specified classification feature is enabled.
 *
 * @param string $classify_by Feature to check.
 * @return bool
 */
function get_classification_feature_enabled( string $classify_by ): bool {
	$settings = ( new Classification() )->get_settings();

	return ( ! empty( $settings[ $classify_by ] ) );
}

/**
 * Returns the Taxonomy for the specified NLU feature.
 *
 * Returns defaults in config.php if options have not been configured.
 *
 * @param string $classify_by NLU feature name.
 * @return string
 */
function get_classification_feature_taxonomy( string $classify_by = '' ): string {
	$taxonomy = '';
	$settings = ( new Classification() )->get_settings();

	if ( ! empty( $settings[ $classify_by . '_taxonomy' ] ) ) {
		$taxonomy = $settings[ $classify_by . '_taxonomy' ];
	}

	if ( NLU::ID !== $settings['provider'] ) {
		$taxonomy = $classify_by;
	}

	if ( empty( $taxonomy ) ) {
		$constant = 'WATSON_' . strtoupper( $classify_by ) . '_TAXONOMY';

		if ( defined( $constant ) ) {
			$taxonomy = constant( $constant );
		}
	}

	/**
	 * Filter the Taxonomy for the specified NLU feature.
	 *
	 * @since 3.0.0
	 * @hook classifai_feature_classification_taxonomy_for_feature
	 *
	 * @param {string} $taxonomy The slug of the taxonomy to use.
	 * @param {string} $classify_by The NLU feature this taxonomy is for.
	 *
	 * @return {string} The filtered taxonomy slug.
	 */
	return apply_filters( 'classifai_feature_classification_taxonomy_for_feature', $taxonomy, $classify_by );
}

/**
 * Get Classification mode.
 *
 * @since 2.5.0
 *
 * @return string
 */
function get_classification_mode(): string {
	$feature  = new Classification();
	$settings = $feature->get_settings();
	$value    = $settings['classification_mode'] ?? '';

	if ( $feature->is_feature_enabled() ) {
		if ( empty( $value ) ) {
			// existing users
			// default: automatic_classification
			return 'automatic_classification';
		}
	} else {
		// new users
		// default: manual_review
		return 'manual_review';
	}

	return $value;
}

/**
 * Determine if the legacy settings panel should be used.
 *
 * @since x.x.x
 *
 * @return bool
 */
function should_use_legacy_settings_panel(): bool {
	/**
	 * Filter to determine if the legacy settings panel should be used.'
	 *
	 * @since x.x.x
	 * @hook classifai_use_legacy_settings_panel
	 *
	 * @param {bool} $use_legacy_settings_panel Whether to use the legacy settings panel.
	 *
	 * @return {bool} Whether to use the legacy settings panel.
	 */
	return apply_filters( 'classifai_use_legacy_settings_panel', false );
}

/**
 * Get all parts from the current URL.
 *
 * For instance, if the URL is `https://example.com/this/is/a/test/`,
 * this function will return: `[ 'this', 'is', 'a', 'test' ]`.
 *
 * @return array
 */
function get_url_slugs(): array {
	global $wp;

	$parts = explode( '/', $wp->request );

	return array_filter( $parts );
}

/**
 * Get the last part from the current URL.
 *
 * For instance, if the URL is `https://example.com/this/is/a/test`,
 * this function will return: 'test'.
 *
 * @return string
 */
function get_last_url_slug(): string {
	$parts = get_url_slugs();

	return trim( end( $parts ) );
}

/**
 * Check if ElasticPress is installed.
 *
 * @return bool
 */
function is_elasticpress_installed(): bool {
	return class_exists( '\\ElasticPress\\Feature' );
}

/**
 * Get the Smart 404 results.
 *
 * @param array $args Arguments to pass to the search.
 * @return array
 */
function get_smart_404_results( array $args = [] ): array {
	// Run our query.
	$results = ( new Smart404() )->exact_knn_search( get_last_url_slug(), $args );

	// Ensure the query ran successfully.
	if ( is_wp_error( $results ) ) {
		return [];
	}

	// Convert the results to normal WP_Post objects.
	$results = ( new Smart404EPIntegration() )->convert_es_results_to_post_objects( $results );

	return $results;
}

/**
 * Render the Smart 404 results.
 *
 * @param array $args Arguments to pass to the search.
 */
function render_smart_404_results( array $args = [] ) {
	// Get the results.
	$results = get_smart_404_results( $args );

	// Handle situation where we don't have results.
	if ( empty( $results ) ) {
		return;
	}

	// Iterate through each result and render it.
	echo '<ul>';
	foreach ( $results as $result ) {
		?>
		<li>
			<a href="<?php echo esc_url( get_permalink( $result->ID ) ); ?>">
				<?php echo esc_html( $result->post_title ); ?>
			</a>
			<p>
				<?php echo esc_html( $result->post_excerpt ); ?>
			</p>
		</li>
		<?php
	}
	echo '</ul>';
}
