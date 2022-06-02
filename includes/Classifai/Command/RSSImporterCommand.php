<?php

namespace Classifai\Command;

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * RSSImporterCommand provides basic support for importing RSS feeds
 * into WordPress Posts.
 *
 * For development use only.
 */
// phpcs:ignore WordPressVIPMinimum.Classes.RestrictedExtendClasses.wp_cli
class RSSImporterCommand extends \WP_CLI_Command {

	/**
	 * Import an RSS feed.
	 *
	 * @param array $args Arguments.
	 * @param array $opts Options.
	 */
	public function import( $args = [], $opts = [] ) {
		$defaults = [
			'limit' => 1,
		];

		$opts     = wp_parse_args( $opts, $defaults );
		$feeds    = $this->get_rss_feeds();
		$imported = 0;
		$limit    = $opts['limit'];

		foreach ( $feeds as $feed ) {
			$rss    = $this->get_rss_feed( $feed );
			$source = (string) $rss->channel->title;
			$items  = $rss->channel->item;

			foreach ( $items as $item ) {
				$item_info = $this->get_rss_item_info( $item );
				$result    = $this->import_post( $item_info, $source );

				if ( $result && ++$imported >= $limit && ! empty( $limit ) ) {
					break 2;
				}
			}
		}

		\WP_CLI::success( "Imported $imported posts." );
	}

	// Helpers.

	/**
	 * Import a post.
	 *
	 * @param array  $info  The post info.
	 * @param string $source The post source.
	 */
	public function import_post( $info, $source ) {
		if ( empty( $info['meta'] ) || empty( $info['meta']['content'] ) ) {
			return false;
		}

		$post_date_gmt = gmdate( 'Y-m-d H:i:s', strtotime( $info['date'] ) );

		$post = [
			'post_title'    => $info['title'],
			'post_excerpt'  => $info['description'],
			'post_content'  => $info['meta']['content'],
			'post_status'   => 'publish',
			'post_date'     => $post_date_gmt,
			'post_date_gmt' => $post_date_gmt,
			'post_author'   => $this->get_post_author( $source ),
		];

		$result = wp_insert_post( $post, true );

		if ( ! is_wp_error( $result ) ) {
			if ( ! empty( $info['meta']['lead_image_url'] ) ) {
				$this->import_thumbnail(
					$result,
					$info['meta']['lead_image_url'],
					$info['meta']['excerpt']
				);
			}

			update_post_meta( $result, 'imported_from_url', $info['link'] );

			\WP_CLI::log( 'Imported: ' . $info['link'] );
			return $result;
		} else {
			\WP_CLI::warning( 'Failed to import Post: ' . $result->get_error_message() );
			return false;
		}
	}

	/**
	 * Import a post thumbnail.
	 *
	 * @param int    $post_id     The post id.
	 * @param string $thumbnail   URL of the image to download.
	 * @param string $description Description for the image.
	 */
	public function import_thumbnail( $post_id, $thumbnail, $description ) {
		$attachment_id = media_sideload_image(
			$thumbnail,
			$post_id,
			'',
			'id'
		);

		if ( ! empty( $attachment_id ) ) {
			$post = [
				'ID'           => $attachment_id,
				'post_content' => $description,
			];

			wp_update_post( $post );
			set_post_thumbnail( $post_id, $attachment_id );
		}
	}

	/**
	 * Get information about an RSS item.
	 *
	 * @param object $item The item to get information on.
	 */
	public function get_rss_item_info( $item ) {
		$info                = [];
		$info['title']       = (string) $item->title;
		$info['description'] = (string) $item->description;
		$info['date']        = (string) $item->pubDate; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$info['thumbnail']   = (string) $item->thumbnail->attributes['url'];
		$info['link']        = (string) $item->link;
		$info['meta']        = $this->get_url_meta( (string) $item->link );

		return $info;
	}

	/**
	 * Get an RSS feed.
	 *
	 * @param string $feed_url The feed URL.
	 */
	public function get_rss_feed( $feed_url ) {
		$rss = simplexml_load_file( $feed_url );
		return $rss;
	}

	/**
	 * Get the RSS feeds.
	 */
	public function get_rss_feeds() {
		return [
			'http://feeds.bbci.co.uk/news/world/rss.xml',
			'http://feeds.bbci.co.uk/news/business/rss.xml',
			'http://feeds.bbci.co.uk/news/technology/rss.xml',
		];
	}

	/**
	 * Get an author by name.
	 *
	 * @param string $name The author name.
	 */
	public function get_post_author( $name ) {
		$slug = sanitize_title_with_dashes( $name );
		$user = get_user_by( 'slug', $slug );

		if ( $user ) {
			return $user->ID;
		} else {
			$userdata = [
				'display_name' => $name,
				'user_login'   => $slug,
				'user_pass'    => uniqid(),
			];

			$result = wp_insert_user( $userdata );

			if ( ! is_wp_error( $result ) ) {
				return $result;
			} else {
				\WP_CLI::log( 'Failed to insert user: ' . $result->get_error_message() );
				return false;
			}
		}
	}

	/**
	 * Get meta for a URL.
	 *
	 * @param $string $url The URL.
	 */
	public function get_url_meta( $url ) {
		$options = [];

		if ( empty( $options['headers'] ) ) {
			$options['headers'] = [];
		}

		$options['headers']['x-api-key'] = MERCURY_PARSER_API_KEY;
		$options['timeout']              = 60; // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout

		$request_url = 'https://mercury.postlight.com/parser?url=' . urlencode( $url );
		$response    = wp_remote_get( $request_url, $options ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get

		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( $body, true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( ! empty( $json['content'] ) ) {
					$allowed = wp_kses_allowed_html( 'post' );

					unset( $allowed['img'] );
					unset( $allowed['picture'] );
					unset( $allowed['figure'] );
					unset( $allowed['figurecaption'] );

					$json['content'] = wp_kses( $json['content'], $allowed );
				}
				return $json;
			} else {
				return new \WP_Error( 'Invalid JSON: ' . json_last_error_msg(), $body );
			}
		} else {
			return $response;
		}
	}

}
