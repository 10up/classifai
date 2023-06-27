<?php

namespace Classifai;

use Classifai\Providers\Provider;
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
 * Returns the ClassifAI plugin's stored settings in the WP options.
 *
 * @param string $service The service to get settings from, defaults to the ServiceManager class.
 * @param string $provider The provider service name to get settings from, defaults to the first one found.
 * @return array The array of ClassifAi settings.
 */
function get_plugin_settings( $service = '', $provider = '' ) {
	$services = Plugin::$instance->services;
	if ( empty( $services ) || empty( $services['service_manager'] ) || ! $services['service_manager'] instanceof ServicesManager ) {
		return [];
	}

	/** @var ServicesManager $service_manager Instance of the services manager class. */
	$service_manager = $services['service_manager'];
	if ( empty( $service ) ) {
		return $service_manager->get_settings();
	}

	if ( ! isset( $service_manager->service_classes[ $service ] ) || ! $service_manager->service_classes[ $service ] instanceof Service ) {
		return [];
	}

	// Ensure we have at least one provider.
	$providers = $service_manager->service_classes[ $service ]->provider_classes;

	if ( empty( $providers ) ) {
		return [];
	}

	// If we want settings for a specific provider, find the proper provider service.
	if ( ! empty( $provider ) ) {
		foreach ( $providers as $provider_class ) {
			if ( $provider_class->provider_service_name === $provider ) {
				return $provider_class->get_settings();
			}
		}

		return [];
	}

	/** @var Provider $provider An instance or extension of the provider abstract class. */
	$provider = $providers[0];
	return $provider->get_settings();
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
function set_plugin_settings( $settings ) {
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
 * Returns the currently configured Watson API URL. Lookup order is,
 *
 * - Options
 * - Constant
 *
 * @return string
 */
function get_watson_api_url() {
	$settings = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );
	$creds    = ! empty( $settings['credentials'] ) ? $settings['credentials'] : [];

	if ( ! empty( $creds['watson_url'] ) ) {
		return $creds['watson_url'];
	} elseif ( defined( 'WATSON_URL' ) ) {
		return WATSON_URL;
	} else {
		return '';
	}
}


/**
 * Returns the currently configured Watson username. Lookup order is,
 *
 * - Options
 * - Constant
 *
 * @return string
 */
function get_watson_username() {
	$settings = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );
	$creds    = ! empty( $settings['credentials'] ) ? $settings['credentials'] : [];

	if ( ! empty( $creds['watson_username'] ) ) {
		return $creds['watson_username'];
	} elseif ( defined( 'WATSON_USERNAME' ) ) {
		return WATSON_USERNAME;
	} else {
		return '';
	}
}

/**
 * Returns the currently configured Watson username. Lookup order is,
 *
 * - Options
 * - Constant
 *
 * @return string
 */
function get_watson_password() {
	$settings = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );
	$creds    = ! empty( $settings['credentials'] ) ? $settings['credentials'] : [];

	if ( ! empty( $creds['watson_password'] ) ) {
		return $creds['watson_password'];
	} elseif ( defined( 'WATSON_PASSWORD' ) ) {
		return WATSON_PASSWORD;
	} else {
		return '';
	}
}

/**
 * Get post types we want to show in the language processing settings
 *
 * @since 1.6.0
 *
 * @return array
 */
function get_post_types_for_language_settings() {
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
function get_post_statuses_for_language_settings() {
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
 * The list of post types that get the ClassifAI taxonomies. Defaults
 * to 'post'.
 *
 * return array
 */
function get_supported_post_types() {
	$classifai_settings = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );

	if ( empty( $classifai_settings ) ) {
		$post_types = [];
	} else {
		$post_types = [];
		foreach ( $classifai_settings['post_types'] as $post_type => $enabled ) {
			if ( ! empty( $enabled ) ) {
				$post_types[] = $post_type;
			}
		}
	}

	/**
	 * Filter post types supported for language processing.
	 *
	 * @since 1.0.0
	 * @hook classifai_post_types
	 *
	 * @param {array} $post_types Array of post types to be classified with language processing.
	 *
	 * @return {array} Array of post types.
	 */
	$post_types = apply_filters( 'classifai_post_types', $post_types );

	return $post_types;
}

/**
 * The list of post statuses that get the ClassifAI taxonomies. Defaults
 * to 'publish'.
 *
 * @return array
 */
function get_supported_post_statuses() {
	$classifai_settings = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );

	if ( empty( $classifai_settings ) ) {
		$post_statuses = [ 'publish' ];
	} else {
		$post_statuses = [];
		foreach ( $classifai_settings['post_statuses'] as $post_status => $enabled ) {
			if ( ! empty( $enabled ) ) {
				$post_statuses[] = $post_status;
			}
		}
	}

	/**
	 * Filter post statuses supported for language processing.
	 *
	 * @since 1.7.1
	 * @hook classifai_post_statuses
	 *
	 * @param {array} $post_types Array of post statuses to be classified with language processing.
	 *
	 * @return {array} Array of post statuses.
	 */
	$post_statuses = apply_filters( 'classifai_post_statuses', $post_statuses );

	return $post_statuses;
}

/**
 * Returns a bool based on whether the specified feature is enabled
 *
 * @param string $feature category,keyword,entity,concept
 * @return bool
 */
function get_feature_enabled( $feature ) {
	$settings = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );

	if ( ! empty( $settings ) && ! empty( $settings['features'] ) ) {
		if ( ! empty( $settings['features'][ $feature ] ) ) {
			return filter_var(
				$settings['features'][ $feature ],
				FILTER_VALIDATE_BOOLEAN
			);
		}
	}

	return false;
}

/**
 * Check if any language processing features are enabled
 *
 * @since 1.6.0
 *
 * @return true
 */
function language_processing_features_enabled() {
	$features = [
		'category',
		'concept',
		'entity',
		'keyword',
	];

	foreach ( $features as $feature ) {
		if ( get_feature_enabled( $feature ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Returns the feature threshold based on current configuration. Lookup
 * order is.
 *
 * - Option
 * - Constant
 *
 * Any results below the threshold will be ignored.
 *
 * @param string $feature The feature whose threshold to lookup
 * @return int
 */
function get_feature_threshold( $feature ) {
	$settings  = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );
	$threshold = 0;

	if ( ! empty( $settings ) && ! empty( $settings['features'] ) ) {
		if ( ! empty( $settings['features'][ $feature . '_threshold' ] ) ) {
			$threshold = filter_var(
				$settings['features'][ $feature . '_threshold' ],
				FILTER_VALIDATE_INT
			);
		}
	}

	if ( empty( $threshold ) ) {
		$constant = 'WATSON_' . strtoupper( $feature ) . '_THRESHOLD';

		if ( defined( $constant ) ) {
			$threshold = intval( constant( $constant ) );
		}
	}

	$threshold = empty( $threshold ) ? 0.7 : $threshold / 100;
	/**
	 * Filter the threshold for a specific feature. Any results below the
	 * threshold will be ignored.
	 *
	 * @since 1.0.0
	 * @hook classifai_feature_threshold
	 *
	 * @param {string} $threshold The threshold to use, expressed as a decimal between 0 and 1 inclusive.
	 * @param {string} $feature   The feature in question.
	 *
	 * @return {string} The filtered threshold.
	 */
	return apply_filters( 'classifai_feature_threshold', $threshold, $feature );
}

/**
 * Returns the Taxonomy for the specified NLU feature. Returns defaults
 * in config.php if options have not been configured.
 *
 * @param string $feature NLU feature name
 * @return string Taxonomy mapped to the feature
 */
function get_feature_taxonomy( $feature ) {
	$settings = get_plugin_settings( 'language_processing', 'Natural Language Understanding' );
	$taxonomy = 0;

	if ( ! empty( $settings ) && ! empty( $settings['features'] ) ) {
		if ( ! empty( $settings['features'][ $feature . '_taxonomy' ] ) ) {
			$taxonomy = $settings['features'][ $feature . '_taxonomy' ];
		}
	}

	if ( empty( $taxonomy ) ) {
		$constant = 'WATSON_' . strtoupper( $feature ) . '_TAXONOMY';

		if ( defined( $constant ) ) {
			$taxonomy = constant( $constant );
		}
	}

	/**
	 * Filter the Taxonomy for the specified NLU feature.
	 *
	 * @since 1.1.0
	 * @hook classifai_taxonomy_for_feature
	 *
	 * @param {string} $taxonomy The slug of the taxonomy to use.
	 * @param {string} $feature  The NLU feature this taxonomy is for.
	 *
	 * @return {string} The filtered taxonomy slug.
	 */
	return apply_filters( 'classifai_taxonomy_for_feature', $taxonomy, $feature );
}

/**
 * Provides the max filesize for the Computer Vision service.
 *
 * @since 1.4.0
 *
 * @return int
 */
function computer_vision_max_filesize() {
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
function sort_images_by_size_cb( $size_1, $size_2 ) {
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
function get_largest_acceptable_image_url( $full_image, $full_url, $sizes, $max = MB_IN_BYTES ) {
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
function get_largest_size_and_dimensions_image_url( $full_image, $full_url, $metadata, $width = [ 0, 4200 ], $height = [ 0, 4200 ], $max_size = MB_IN_BYTES ) {
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
 *
 * @return mixed
 */
function get_modified_image_source_url( $post_id ) {
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
 * @param \WP_post $post Post object for the attachment being viewed.
 */
function attachment_is_pdf( $post ) {
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
function get_asset_info( $slug, $attribute = null ) {
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
function get_services_menu() {
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
 *
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
 * @param string $service_name Service name to look for.
 * @return Provider|WP_Error
 */
function find_provider_class( array $provider_classes = [], string $service_name = '' ) {
	$provider = '';

	foreach ( $provider_classes as $provider_class ) {
		if ( $service_name === $provider_class->provider_service_name ) {
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
function get_all_post_statuses() {
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
