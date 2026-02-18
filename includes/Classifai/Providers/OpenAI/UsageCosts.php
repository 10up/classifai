<?php

namespace Classifai\Providers\OpenAI;

use Classifai\Providers\UsageFetcherInterface;
use WP_Error;

use function Classifai\safe_wp_remote_get;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches usage and cost data from the OpenAI Administration API (Costs endpoint).
 *
 * Requires an OpenAI Admin API key (organization-level). The regular project API keys
 * used by ClassifAI features are not sufficient for the Usage/Costs API.
 *
 * @see https://developers.openai.com/api/reference/resources/organization/subresources/audit_logs/methods/get_costs
 */
class UsageCosts implements UsageFetcherInterface {

	/**
	 * Base URL for the OpenAI API.
	 *
	 * @var string
	 */
	const COSTS_API_URL = 'https://api.openai.com/v1/organization/costs';

	/**
	 * Maximum number of items per page (API limit 1–180).
	 *
	 * @var int
	 */
	const PAGE_LIMIT = 180;

	/**
	 * Admin API key for the organization.
	 *
	 * @var string
	 */
	private $admin_api_key;

	/**
	 * Optional project ID to filter costs.
	 *
	 * @var string
	 */
	private $project_id;

	/**
	 * Constructor.
	 *
	 * @param string $admin_api_key OpenAI Admin API key (organization-level).
	 * @param string $project_id    Optional. OpenAI Project ID to restrict costs to.
	 */
	public function __construct( string $admin_api_key = '', string $project_id = '' ) {
		$this->admin_api_key = $admin_api_key;
		$this->project_id    = $project_id;
	}

	/**
	 * Fetches all-time costs from the OpenAI Costs API.
	 *
	 * OpenAI API released first on June 11, 2020. ref: https://openai.com/index/openai-api/
	 * So, we can fetch the costs for each year from 2020 onwards for all time costs.
	 *
	 * @see https://en.wikipedia.org/wiki/Products_and_applications_of_OpenAI#cite_note-gpt3-whynotfullmodel-30
	 *
	 * @param bool       $force_refresh Whether to refresh the cached costs.
	 * @param array|null $ytd           Current year-to-date costs (avoids redundant API call).
	 * @param array|null $cached_data   Full cached settings/data; if provided, option reads
	 *                                  are skipped. The caller is responsible for persisting
	 *                                  the returned year-pricing data.
	 * @return array|WP_Error Array with 'amount', 'currency', 'all_year_pricing', and
	 *                        'api_start_year' keys, or WP_Error on failure.
	 */
	public function fetch_all_time( bool $force_refresh = false, $ytd = null, $cached_data = null ) {
		$cached = is_array( $cached_data ) ? $cached_data : [];

		if (
			! $force_refresh
			&& ! empty( $cached['all_time_total'] )
			&& ! empty( $cached['usage_currency'] )
			&& ! empty( $cached['all_year_pricing'] )
		) {
			return [
				'amount'           => $cached['all_time_total'],
				'currency'         => $cached['usage_currency'],
				'all_year_pricing' => $cached['all_year_pricing'],
				'api_start_year'   => isset( $cached['api_start_year'] ) ? (int) $cached['api_start_year'] : 2020,
			];
		}

		$tz  = wp_timezone();
		$now = new \DateTimeImmutable( 'now', $tz );

		$current_year     = (int) $now->format( 'Y' );
		$all_year_pricing = [];
		$usage_currency   = 'USD';
		$start_year       = isset( $cached['api_start_year'] ) ? (int) $cached['api_start_year'] : 2020;

		for ( $year = $start_year; $year <= $current_year; $year++ ) {
			// If the year is already cached and it's not the current year, skip the API call.
			if ( isset( $cached['all_year_pricing'][ $year ] ) && $year !== $current_year ) {
				$all_year_pricing[ $year ] = $cached['all_year_pricing'][ $year ];
				continue;
			}

			// If ytd is set and this is the current year, reuse the ytd amount to avoid
			// a redundant API call. (ytd covers Jan 1 through now, same range we'd fetch.)
			if ( ! empty( $ytd ) && $year === $current_year && isset( $ytd['amount'] ) ) {
				$all_year_pricing[ $year ] = $ytd['amount'];
				if ( ! empty( $ytd['currency'] ) ) {
					$usage_currency = $ytd['currency'];
				}
				continue;
			}

			$start_date = new \DateTimeImmutable( $year . '-01-01', $tz );
			$end_date   = new \DateTimeImmutable( $year . '-12-31', $tz );

			if ( $year === $current_year ) {
				$end_date = $now;
			}

			$pricing = $this->fetch_period( $start_date->getTimestamp(), $end_date->getTimestamp() );

			if ( is_wp_error( $pricing ) ) {
				continue;
			}

			if ( ! $pricing['is_null_range'] ) {
				// Set the pricing for the year, even if it's 0.0.
				$all_year_pricing[ $year ] = $pricing['amount'];
				$usage_currency            = $pricing['currency'];
			}

			// If all_year_pricing is empty and the range is null, advance the start year.
			// This means the API key was not created or used before this year.
			if ( empty( $all_year_pricing ) && $pricing['is_null_range'] ) {
				$start_year = $year;
			}
		}

		return [
			'amount'           => array_sum( $all_year_pricing ),
			'currency'         => $usage_currency,
			'all_year_pricing' => $all_year_pricing,
			'api_start_year'   => (int) $start_year,
		];
	}

	/**
	 * Fetches costs for a time range from the Costs API with pagination.
	 *
	 * @param int $start_ts Unix timestamp for range start (inclusive).
	 * @param int $end_ts   Unix timestamp for range end (inclusive).
	 * @return array|WP_Error Array with 'amount', 'currency', 'is_null_range' keys, or WP_Error.
	 */
	public function fetch_period( int $start_ts, int $end_ts ) {
		if ( empty( $this->admin_api_key ) ) {
			return new WP_Error( 'missing_admin_key', __( 'OpenAI Admin API key is required to fetch costs.', 'classifai' ) );
		}

		$total_amount = 0.0;
		$currency     = 'USD';
		$page         = 1;
		$has_more     = true;
		$is_null_data = false;

		while ( $has_more ) {
			$args = array_filter(
				[
					'start_time' => $start_ts,
					'end_time'   => $end_ts,
					'limit'      => self::PAGE_LIMIT,
					'page'       => 1 !== $page ? rawurlencode( $page ) : '',
				]
			);
			$url  = add_query_arg( $args, self::COSTS_API_URL );

			if ( ! empty( $this->project_id ) ) {
				$url = add_query_arg( 'project_ids', $this->project_id, $url );
			}

			$options = [
				'timeout' => 90, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'headers' => [
					'Authorization' => 'Bearer ' . trim( $this->admin_api_key ),
					'Content-Type'  => 'application/json',
				],
			];

			$response = safe_wp_remote_get( $url, $options );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			if ( $code < 200 || $code >= 300 ) {
				$message = __( 'OpenAI Costs API request failed.', 'classifai' );
				$json    = json_decode( $body, true );
				if ( ! empty( $json['error']['message'] ) ) {
					$message = $json['error']['message'];
				}
				return new WP_Error( 'costs_api_error', $message, [ 'status' => $code ] );
			}

			$data = json_decode( $body, true );
			if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
				return new WP_Error( 'invalid_json', __( 'Invalid response from OpenAI Costs API.', 'classifai' ) );
			}

			$buckets = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : [];
			foreach ( $buckets as $bucket ) {
				if ( empty( $bucket['results'] ) ) {
					continue;
				}
				$costs_obj = $bucket['results'];
				foreach ( $costs_obj as $cost_obj ) {
					if ( isset( $cost_obj['amount']['value'] ) && is_numeric( $cost_obj['amount']['value'] ) ) {
						$total_amount += (float) $cost_obj['amount']['value'];
					}
					if ( ! empty( $cost_obj['amount']['currency'] ) ) {
						$currency = sanitize_text_field( $cost_obj['amount']['currency'] );
					}
				}
			}

			$has_more = ! empty( $data['has_more'] );
			if ( $has_more && ! empty( $data['next_page'] ) ) {
				$page = $data['next_page'];
			} else {
				$has_more = false;
			}

			// If the data array is empty, this API key had no activity for this range.
			$is_null_data = ( isset( $data['data'] ) && empty( $data['data'] ) );

			if ( empty( $buckets ) && ! $has_more && ! $is_null_data ) {
				break;
			}
		}

		return [
			'is_null_range' => $is_null_data,
			'amount'        => round( $total_amount, 2 ),
			'currency'      => $currency,
		];
	}
}
