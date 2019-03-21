<?php

namespace Classifai\Command;

use Classifai\Watson\APIRequest;
use Classifai\Watson\Classifier;
use Classifai\Watson\Normalizer;
use Classifai\PostClassifier;

/**
 * ClassifaiCommand is the command line interface of the Classifai plugin.
 * It provides subcommands to test classification results and batch
 * classify posts using the IBM Watson NLU API.
 */
class ClassifaiCommand extends \WP_CLI_Command {


	/**
	 * Batch classifies post(s) using the current Classifai configuration.
	 *
	 * ## Options
	 *
	 * [<post_ids>]
	 * : Post IDs to classify
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
			$post_ids = $args;
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

				$this->gc( $index );
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
				\WP_CLI::log( json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			} else {
				\WP_CLI::log( 'Failed to classify text.' );
				\WP_CLI::error( $result->get_error_message() );
			}
		} else {
			\WP_CLI::log( $plain_text );
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
	 * Restores the plugin configuration to factory defaults. IBM Watson
	 * credentials must be reentered after this command.
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
			'Defaults restored successfully. Please update the IBM Watson credentials.'
		);
	}

	/* helpers */

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
	 * TODO: gc
	 *
	 * @param int $index The index.
	 */
	private function gc( $index ) {
		if ( 0 === $index % 10 ) {
			// TODO
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Prints the output from the NLU API.
	 *
	 * @param mixed $output  The variable to oputput.
	 * @param int   $post_id The post id.
	 */
	private function print( $output, $post_id ) {
		if ( ! is_wp_error( $output ) ) {
			\WP_CLI::log( var_export( $output, true ) );
		} else {
			\WP_CLI::warning( "Failed to classify $post_id: " . $output->get_error_message() );
		}
	}

}

