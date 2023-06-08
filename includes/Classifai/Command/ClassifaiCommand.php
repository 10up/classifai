<?php

namespace Classifai\Command;

use Classifai\Admin\SavePostHandler;
use Classifai\Watson\APIRequest;
use Classifai\Watson\Classifier;
use Classifai\Watson\Normalizer;
use Classifai\PostClassifier;
use Classifai\Providers\Azure\ComputerVision;
use Classifai\Providers\Azure\SmartCropping;
use Classifai\Providers\Azure\TextToSpeech;

/**
 * ClassifaiCommand is the command line interface of the ClassifAI plugin.
 * It provides subcommands to test classification results and batch
 * classify posts using the IBM Watson NLU API and images using the
 * Azure AI Computer Vision API.
 */
// phpcs:ignore WordPressVIPMinimum.Classes.RestrictedExtendClasses.wp_cli
class ClassifaiCommand extends \WP_CLI_Command {

	/**
	 * Batch classifies post(s) using the current ClassifAI configuration.
	 *
	 * ## Options
	 *
	 * [<post_ids>]
	 * : Comma-delimited list of post IDs to classify
	 *
	 * [--post_type=<post_type>]
	 * : Batch classify posts belonging to this post type. If false relies on post_ids in args
	 *
	 * [--limit=<limit>]
	 * : Limit classification to N posts. Default false
	 *
	 * [--link=<link>]
	 * : Whether to link classification results to Taxonomy terms. Default true
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function post( $args = [], $opts = [] ) {
		$defaults = [
			'post_type' => false,
			'limit'     => false,
			'link'      => true,
		];

		$opts = wp_parse_args( $opts, $defaults );

		if ( empty( $opts['post_type'] ) ) {
			$post_ids = explode( ',', $args[0] );
		} else {
			$post_ids = $this->get_posts_to_classify( $opts );
		}

		$total      = count( $post_ids );
		$classifier = new PostClassifier();
		$limit      = $opts['limit'];
		$link       = $opts['link'];
		$link       = filter_var( $link, FILTER_VALIDATE_BOOLEAN );

		if ( ! empty( $total ) ) {
			if ( ! empty( $limit ) ) {
				$limit_total = min( $limit, $total );
			} else {
				$limit_total = $total;
			}

			$errors       = [];
			$message      = "Classifying $limit_total posts ...";
			$progress_bar = \WP_CLI\Utils\make_progress_bar( $message, $limit_total );

			for ( $index = 0; $index < $limit_total; $index++ ) {
				$post_id = $post_ids[ $index ];

				$progress_bar->tick();

				if ( $link ) {
					$output = $classifier->classify_and_link( $post_id, $opts );

					if ( is_wp_error( $output ) ) {
						$errors[ $post_id ] = $output;
					}
				} else {
					$output = $classifier->classify( $post_id, $opts );
					$this->print( $output, $post_id );
				}
			}

			$progress_bar->finish();

			$total_errors  = count( $errors );
			$total_success = $total - $total_errors;

			\WP_CLI::success( "Classified $total_success posts, $total_errors errors." );

			foreach ( $errors as $post_id => $error ) {
				\WP_CLI::log( $post_id . ': ' . $error->get_error_code() . ' - ' . $error->get_error_message() );
			}
		} else {
			\WP_CLI::log( 'No posts to classify.' );
		}

	}

	/**
	 * Classifies the specified text using Watson NLU API and returns
	 * corresponding results.
	 *
	 * ## Options
	 *
	 * [<text>]
	 * : Text to classify
	 *
	 * [--category=<bool>]
	 * : Enables NLU category feature, Default: true
	 *
	 * [--keyword=<bool>]
	 * : Enables NLU keyword feature, Default: true
	 *
	 * [--concept=<bool>]
	 * : Enables NLU concept feature, Default false
	 *
	 * [--entity=<bool>]
	 * : Enables NLU entity feature, Default false
	 *
	 * [--input=<input>]
	 * : Path to input file or URL, Default false
	 *
	 * [--only-normalize=<bool>]
	 * : Prints the normalized text that will be sent to the NLU API, Default false
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function text( $args = [], $opts = [] ) {
		$defaults = [
			'category'       => true,
			'keyword'        => true,
			'concept'        => false,
			'entity'         => false,
			'input'          => false,
			'only-normalize' => false,
		];

		$opts = wp_parse_args( $opts, $defaults );

		$classifier = new Classifier();
		$username   = \Classifai\get_watson_username();
		$password   = \Classifai\get_watson_password();

		if ( empty( $username ) ) {
			\WP_CLI::error( 'Watson Username not found in options or constant.' );
		}

		if ( empty( $password ) ) {
			\WP_CLI::error( 'Watson Password not found in options or constant.' );
		}

		if ( ! empty( $opts['input'] ) ) {
			$text = file_get_contents( $opts['input'] );
		} elseif ( ! empty( $args ) ) {
			$text = $args[0];
		} else {
			\WP_CLI::error( 'Please specify text to classify' );
		}

		$options = [
			'features' => [],
		];

		if ( $opts['category'] ) {
			$options['features']['categories'] = (object) [];
		}

		if ( $opts['keyword'] ) {
			$options['features']['keywords'] = [
				'emotion'   => false,
				'sentiment' => false,
				'limit'     => 10,
			];
		}

		if ( $opts['concept'] ) {
			$options['features']['concepts'] = (object) [];
		}

		if ( $opts['entity'] ) {
			$options['features']['entities'] = (object) [];
		}

		$normalizer = new Normalizer();
		$plain_text = $normalizer->normalize_content( $text );

		if ( ! $opts['only-normalize'] ) {
			$result = $classifier->classify( $plain_text, $options );

			if ( ! is_wp_error( $result ) ) {
				\WP_CLI::log( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			} else {
				\WP_CLI::log( 'Failed to classify text.' );
				\WP_CLI::error( $result->get_error_message() );
			}
		} else {
			\WP_CLI::log( $plain_text );
		}
	}

	/**
	 * Batch trigger generation of text-to-speech depending on passed-in settings.
	 *
	 * ## Options
	 *
	 * [<post_ids>]
	 * : Comma-delimited list of post IDs to generate text-to-speech for
	 *
	 * [--post_type=<post_type>]
	 * : Batch process items belonging to this post type. If not used, relies on post_ids in args
	 *
	 * [--post_status=<post_status>]
	 * : Batch process items that have this post status. Default publish

	 * [--per_page=<int>]
	 * : How many items should be processed at a time. Default 100
	 *
	 * [--dry-run=<bool>]
	 * : Whether to run as a dry-run. Default true
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function text_to_speech( $args = [], $opts = [] ) {
		$defaults = [
			'post_type'   => false,
			'post_status' => 'publish',
			'per_page'    => 100,
		];

		$opts               = wp_parse_args( $opts, $defaults );
		$opts['per_page']   = (int) $opts['per_page'] > 0 ? $opts['per_page'] : 100;
		$allowed_post_types = TextToSpeech::get_supported_post_types();

		$count  = 0;
		$errors = 0;

		$save_post_handler = new SavePostHandler();

		// Determine if this is a dry run or not.
		if ( isset( $opts['dry-run'] ) ) {
			if ( 'false' === $opts['dry-run'] ) {
				$dry_run = false;
			} else {
				$dry_run = (bool) $opts['dry-run'];
			}
		} else {
			$dry_run = true;
		}

		if ( $dry_run ) {
			\WP_CLI::line( '--- Running command in dry-run mode ---' );
		}

		// If we have a post type specified, process all items in that type.
		if ( ! empty( $opts['post_type'] ) ) {
			// Only allow processing post types that are enabled in settings.
			if ( $opts['post_type'] && ! in_array( $opts['post_type'], $allowed_post_types, true ) ) {
				\WP_CLI::error( sprintf( 'The "%s" post type is not enabled for Text to Speech processing', $opts['post_type'] ) );
			}

			// Only allow processing post statuses that are valid for a particular post type.
			if ( ! in_array( $opts['post_status'], get_available_post_statuses( $opts['post_type'] ), true ) ) {
				\WP_CLI::error( sprintf( 'The "%s" post status is not valid for the "%s" post type', $opts['post_status'], $opts['post_type'] ) );
			}

			\WP_CLI::log( sprintf( 'Starting processing of "%s" post type items that have the "%s" status in batches of %d', $opts['post_type'], $opts['post_status'], $opts['per_page'] ) );

			$paged = 1;

			do {
				$posts = get_posts(
					array(
						'post_type'        => $opts['post_type'],
						'posts_per_page'   => $opts['per_page'],
						'paged'            => $paged,
						'post_status'      => $opts['post_status'],
						'suppress_filters' => 'false',
						'fields'           => 'ids',
					)
				);
				$total = count( $posts );

				foreach ( $posts as $post_id ) {
					if ( ! $dry_run ) {
						$result = $save_post_handler->synthesize_speech( $post_id );

						if ( is_wp_error( $result ) ) {
							\WP_CLI::log( sprintf( 'Error while processing item ID %s: %s', $post_id, $result->get_error_message() ) );
							$errors ++;
						}
					}

					$count ++;
				}

				$this->inmemory_cleanup();

				if ( $total ) {
					\WP_CLI::log( sprintf( 'Batch %d is done, proceeding to next batch', $paged ) );
				}

				$paged ++;
			} while ( $total );
		} else {
			// If no post type is specified, we have to have a list of post IDs.
			if ( ! isset( $args[0] ) ) {
				\WP_CLI::error( 'Please specify a comma-delimited list of post IDs to process' );
			}

			$post_ids = array_map( 'absint', explode( ',', $args[0] ) );

			\WP_CLI::log( sprintf( 'Starting processing of %s items', count( $post_ids ) ) );

			$progress_bar = \WP_CLI\Utils\make_progress_bar( 'Processing ...', count( $post_ids ) );

			foreach ( $post_ids as $post_id ) {
				// Ensure we have a valid post ID.
				if ( ! get_post( $post_id ) ) {
					\WP_CLI::log( sprintf( 'Item ID %d does not exist', $post_id ) );
					$errors ++;
					continue;
				}

				// Ensure we have a valid post type.
				$post_type = get_post_type( $post_id );
				if ( ! $post_type || ! in_array( $post_type, $allowed_post_types, true ) ) {
					\WP_CLI::log( sprintf( 'The "%s" post type is not enabled for Text to Speech processing', $post_type ) );
					$errors ++;
					continue;
				}

				if ( ! $dry_run ) {
					$result = $save_post_handler->synthesize_speech( $post_id );

					if ( is_wp_error( $result ) ) {
						\WP_CLI::log( sprintf( 'Error while processing item ID %s: %s', $post_id, $result->get_error_message() ) );
						$errors ++;
					}
				}

				$progress_bar->tick();
				$count ++;
			}

			$progress_bar->finish();
		}

		if ( ! $dry_run ) {
			\WP_CLI::success( sprintf( '%d items have been processed', $count ) );
		} else {
			\WP_CLI::success( sprintf( '%d items would have been processed', $count ) );
		}

		\WP_CLI::log( sprintf( '%d items had errors', $errors ) );
	}

	/**
	 * Batch classifies attachments(s) using the current ClassifAI configuration.
	 *
	 * ## Options
	 *
	 * [<attachment_ids>]
	 * : Comma delimeted Attachment IDs to classify
	 *
	 * [--limit=<limit>]
	 * : Limit classification to N attachments. Default 100.
	 *
	 * [--skip=<skip>]
	 * : Skip first N attachments. Default false.
	 *
	 * [--force]
	 * : Force classification to N attachments. Default false.
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function image( $args = [], $opts = [] ) {
		$default_opts = [
			'limit' => false,
			'force' => false,
		];

		$opts = wp_parse_args( $opts, $default_opts );

		if ( ! empty( $args[0] ) ) {
			$attachment_ids = explode( ',', $args[0] );
		} else {
			$attachment_ids = $this->get_attachment_to_classify( $opts );
		}

		$total      = count( $attachment_ids );
		$classifier = new ComputerVision( false );

		if ( empty( $total ) ) {
			return \WP_CLI::log( 'No images to classify.' );
		}

		$limit_total = $total;
		if ( $opts['limit'] ) {
			$limit_total = min( $total, intval( $opts['limit'] ) );
		}

		$errors  = [];
		$message = "Classifying $limit_total images ...";

		$progress_bar = \WP_CLI\Utils\make_progress_bar( $message, $limit_total );

		for ( $index = 0; $index < $limit_total; $index++ ) {
			$attachment_id = $attachment_ids[ $index ];

			$progress_bar->tick();

			$current_meta = wp_get_attachment_metadata( $attachment_id );
			\WP_CLI::line( 'Processing ' . $attachment_id );
			$classifier->generate_image_alt_tags( $current_meta, $attachment_id );
			$classifier->smart_crop_image( $current_meta, $attachment_id );
		}

		$progress_bar->finish();

		$total_errors  = count( $errors );
		$total_success = $total - $total_errors;

		\WP_CLI::success( "Classified $total_success images, $total_errors errors." );

		foreach ( $errors as $attachment_id => $error ) {
			\WP_CLI::log( $attachment_id . ': ' . $error->get_error_code() . ' - ' . $error->get_error_message() );
		}
	}

	/**
	 * Batch crop image(s).
	 *
	 * ## Options
	 *
	 * [<attachment_ids>]
	 * : Comma delimited Attachment IDs to crop.
	 *
	 * [--limit=<limit>]
	 * : Limit cropping to N attachments. Default 100.
	 *
	 * [--skip=<skip>]
	 * : Skip first N attachments. Default false.
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function crop( $args = [], $opts = [] ) {
		$classifier     = new ComputerVision( false );
		$settings       = $classifier->get_settings();
		$smart_cropping = new SmartCropping( $settings );
		$default_opts   = [
			'limit' => false,
		];

		$opts = wp_parse_args( $opts, $default_opts );

		if ( ! empty( $args[0] ) ) {
			$attachment_ids = explode( ',', $args[0] );
		} else {
			$attachment_ids = $this->get_attachment_to_classify( array_merge( $opts, [ 'force' => true ] ) );
		}

		$total = count( $attachment_ids );

		if ( empty( $total ) ) {
			return \WP_CLI::log( 'No images to crop.' );
		}

		$limit_total = $total;
		if ( $opts['limit'] ) {
			$limit_total = min( $total, intval( $opts['limit'] ) );
		}

		$errors  = [];
		$message = "Cropping $limit_total images ...";

		$progress_bar = \WP_CLI\Utils\make_progress_bar( $message, $limit_total );

		for ( $index = 0; $index < $limit_total; $index++ ) {
			$attachment_id = $attachment_ids[ $index ];

			$progress_bar->tick();

			$current_meta = wp_get_attachment_metadata( $attachment_id );

			foreach ( $current_meta['sizes'] as $size => $size_data ) {
				if ( ! $smart_cropping->should_crop( $size ) ) {
					continue;
				}

				$data = [
					'width'  => $size_data['width'],
					'height' => $size_data['height'],
				];

				$smart_thumbnail = $smart_cropping->get_cropped_thumbnail( $attachment_id, $data );

				if ( is_wp_error( $smart_thumbnail ) ) {
					$errors[ $attachment_id . ':' . $size_data['width'] . 'x' . $size_data['height'] ] = $smart_thumbnail;
				}
			}
		}

		$progress_bar->finish();

		$total_errors  = count( $errors );
		$total_success = $total - $total_errors;

		foreach ( $errors as $attachment_id => $error ) {
			\WP_CLI::log(
				sprintf(
					'%1$s: %2$s (%3$s).',
					$attachment_id,
					$error->get_error_message(),
					$error->get_error_code()
				)
			);
		}

		if ( $total_success > 0 ) {
			\WP_CLI::success( "Cropped $total_success images, $total_errors errors." );
		} else {
			\WP_CLI::error( "Cropped $total_success images, $total_errors errors." );
		}

	}

	/**
	 * Prints the Basic Auth header based on credentials configured in
	 * the plugin.
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function auth( $args = [], $opts = [] ) {
		$username = \Classifai\get_watson_username();
		$password = \Classifai\get_watson_password();

		if ( empty( $username ) ) {
			\WP_CLI::error( 'Watson Username not found in options or constant.' );
		}

		if ( empty( $password ) ) {
			\WP_CLI::error( 'Watson Password not found in options or constant.' );
		}

		$request           = new APIRequest();
		$request->username = $username;
		$request->password = $password;

		$auth_header = $request->get_auth_header();

		\WP_CLI::log( $auth_header );
	}

	/**
	 * Restores the plugin configuration to factory defaults. Any API credentials will need to be re-entered after this is ran.
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function reset( $args = [], $opts = [] ) {
		\WP_CLI::warning(
			'This will restore the plugin to its default configuration.'
		);

		\WP_CLI::confirm( 'Are you sure?' );

		\Classifai\reset_plugin_settings();

		\WP_CLI::success(
			'Defaults restored successfully. Please update all your API credentials.'
		);
	}

	/* helpers */

	/**
	 * Clear in-memory local object cache and reset in-memory database query log.
	 *
	 * Copied from WordPress.com VIP's implementation:
	 * https://github.com/Automattic/vip-go-mu-plugins/blob/develop/vip-helpers/vip-wp-cli.php
	 */
	private function inmemory_cleanup() {
		global $wp_object_cache, $wpdb;

		if ( is_object( $wp_object_cache ) ) {
			$wp_object_cache->group_ops      = [];
			$wp_object_cache->memcache_debug = [];
			$wp_object_cache->cache          = [];

			if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
				$wp_object_cache->__remoteset();
			}
		}

		$wpdb->queries = [];
	}

	/**
	 * Returns the list of post ids to classify with Watson
	 *
	 * @param array $opts Options from WP CLI.
	 * @return array
	 */
	private function get_posts_to_classify( $opts = [] ) {
		$query_params = [
			'post_type'      => ! empty( $opts['post_type'] ) ? $opts['post_type'] : 'any',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1,
		];

		\WP_CLI::log( 'Fetching posts to classify ...' );

		$query = new \WP_Query( $query_params );
		$posts = $query->posts;

		\WP_CLI::log( 'Fetching posts to classify ... DONE (' . count( $posts ) . ')' );

		return $posts;
	}

	/**
	 * Returns the list of attachment ids to classify with Azure Compute Vision
	 *
	 * @param array $opts Options from WP CLI.
	 * @return array
	 */
	private function get_attachment_to_classify( $opts = [] ) {
		$limit = is_numeric( $opts['limit'] ) ? $opts['limit'] : 100;

		$query_params = [
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/gif', 'image/bmp' ),
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => $limit,
		];

		if ( ! empty( $opts['skip'] ) ) {
			$query_params['offset'] = $opts['skip'];
		}

		if ( ! $opts['force'] ) {
			$query_params['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				[
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
					'value'   => '',
				],
				[
					'key'     => '_wp_attachment_image_alt',
					'compare' => '=',
					'value'   => '',
				],
			];
		}

		\WP_CLI::log( 'Fetching images ...' );

		$query  = new \WP_Query( $query_params );
		$images = $query->posts;

		\WP_CLI::log( 'Fetching images ... DONE (' . count( $images ) . ')' );

		return $images;
	}

	/**
	 * Prints the output from the NLU API.
	 *
	 * @param mixed $output  The variable to oputput.
	 * @param int   $post_id The post id.
	 */
	private function print( $output, $post_id ) {
		if ( ! is_wp_error( $output ) ) {
			\WP_CLI::log( var_export( $output, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		} else {
			\WP_CLI::warning( "Failed to classify $post_id: " . $output->get_error_message() );
		}
	}

}

try {
	\WP_CLI::add_command( 'classifai', __NAMESPACE__ . '\\ClassifaiCommand' );
} catch ( \Exception $e ) {
	error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}
