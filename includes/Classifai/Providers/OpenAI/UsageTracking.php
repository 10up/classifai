<?php
/**
 * OpenAI Usage Tracking.
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\OpenAIUsage as FeatureOpenAIUsage;
use Classifai\Providers\Provider;
use WP_Error;

class UsageTracking extends Provider {
	use OpenAI;

	const ID = 'openai_usage_tracking';

	/**
	 * OpenAI organization admin API key endpoint.
	 * Used to authenticate the organization admin API key.
	 *
	 * @var string
	 */
	protected $org_admin_api_key_url = 'https://api.openai.com/v1/organization/admin_api_keys';

	/**
	 * Maximum number of items per page (API limit 1–180).
	 *
	 * @var int
	 */
	const PAGE_LIMIT = 180;

	/**
	 * Base URL for the OpenAI API.
	 *
	 * @var string
	 */
	const COSTS_API_URL = 'https://api.openai.com/v1/organization/costs';

	/**
	 * OpenAI UsageTracking constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Returns the default settings for the provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		$common_settings = [
			'api_key'                  => '',
			'authenticated'            => false,
			'project_id'               => '',
			'refresh_interval_minutes' => 15,
			'soft_threshold_enabled'   => false,
			'soft_threshold_amount'    => 10,
			'soft_threshold_scope'     => 'current_month',
			'soft_threshold_emails'    => '',
			'hard_threshold_enabled'   => false,
			'hard_threshold_amount'    => 20,
			'hard_threshold_scope'     => 'current_month',
			'hard_threshold_emails'    => '',
		];

		switch ( $this->feature_instance::ID ) {
			case FeatureOpenAIUsage::ID:
				return array_merge(
					$common_settings,
					[
						'api_key' => '',
					]
				);
		}

		return $common_settings;
	}

	/**
	 * Sanitization for the options being saved.
	 *
	 * @param array $new_settings Array of settings about to be saved.
	 * @return array The sanitized settings to be saved.
	 */
	public function sanitize_settings( array $new_settings ): array {
		$settings                                    = $this->feature_instance->get_settings();
		$api_key_settings                            = $this->sanitize_api_key_settings( $new_settings, $settings );
		$new_settings[ static::ID ]['api_key']       = $api_key_settings[ static::ID ]['api_key'];
		$new_settings[ static::ID ]['authenticated'] = $api_key_settings[ static::ID ]['authenticated'];

		if ( $this->feature_instance instanceof FeatureOpenAIUsage ) {
			$threshold_scope = [ 'current_month', 'year_to_date', 'all_time' ];

			$soft_threshold_enabled = filter_var( $new_settings[ static::ID ]['soft_threshold_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$hard_threshold_enabled = filter_var( $new_settings[ static::ID ]['hard_threshold_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );

			if ( $soft_threshold_enabled ) {
				$soft_threshold_scope  = sanitize_text_field( $new_settings[ static::ID ]['soft_threshold_scope'] ?? '' );
				$soft_threshold_scope  = in_array( $soft_threshold_scope, $threshold_scope, true ) ? $soft_threshold_scope : 'current_month';
				$soft_threshold_emails = sanitize_text_field( $new_settings[ static::ID ]['soft_threshold_emails'] ?? '' );

				if ( ! empty( $soft_threshold_emails ) ) {
					$soft_threshold_emails = explode( ',', $soft_threshold_emails );
					$soft_threshold_emails = array_map( 'trim', $soft_threshold_emails );
					$soft_threshold_emails = array_map( 'sanitize_email', $soft_threshold_emails );
					$soft_threshold_emails = array_filter( $soft_threshold_emails, 'is_email' );
					$soft_threshold_emails = array_unique( $soft_threshold_emails );
					$soft_threshold_emails = implode( ',', $soft_threshold_emails );
				}

				$new_settings[ static::ID ]['soft_threshold_enabled'] = $soft_threshold_enabled;
				$new_settings[ static::ID ]['soft_threshold_amount']  = filter_var(
					$new_settings[ static::ID ]['soft_threshold_amount'] ?? false,
					FILTER_VALIDATE_FLOAT,
					[
						'options' => [
							'min_range' => 1.00,
							'default'   => 1.00,
						],
					]
				);
				$new_settings[ static::ID ]['soft_threshold_scope']   = $soft_threshold_scope;
				$new_settings[ static::ID ]['soft_threshold_emails']  = $soft_threshold_emails;
			} else {
				unset( $new_settings[ static::ID ]['soft_threshold_enabled'] );
				unset( $new_settings[ static::ID ]['soft_threshold_amount'] );
				unset( $new_settings[ static::ID ]['soft_threshold_scope'] );
				unset( $new_settings[ static::ID ]['soft_threshold_emails'] );
			}

			if ( $hard_threshold_enabled ) {
				$hard_threshold_scope  = sanitize_text_field( $new_settings[ static::ID ]['hard_threshold_scope'] ?? '' );
				$hard_threshold_scope  = in_array( $hard_threshold_scope, $threshold_scope, true ) ? $hard_threshold_scope : 'current_month';
				$hard_threshold_emails = sanitize_text_field( $new_settings[ static::ID ]['hard_threshold_emails'] ?? '' );

				if ( ! empty( $hard_threshold_emails ) ) {
					$hard_threshold_emails = explode( ',', $hard_threshold_emails );
					$hard_threshold_emails = array_map( 'trim', $hard_threshold_emails );
					$hard_threshold_emails = array_map( 'sanitize_email', $hard_threshold_emails );
					$hard_threshold_emails = array_filter( $hard_threshold_emails, 'is_email' );
					$hard_threshold_emails = array_unique( $hard_threshold_emails );
					$hard_threshold_emails = implode( ',', $hard_threshold_emails );
				}

				$new_settings[ static::ID ]['hard_threshold_enabled'] = $hard_threshold_enabled;
				$new_settings[ static::ID ]['hard_threshold_amount']  = filter_var(
					$new_settings[ static::ID ]['hard_threshold_amount'] ?? false,
					FILTER_VALIDATE_FLOAT,
					[
						'options' => [
							'min_range' => 1.00,
							'default'   => 1.00,
						],
					]
				);
				$new_settings[ static::ID ]['hard_threshold_scope']   = $hard_threshold_scope;
				$new_settings[ static::ID ]['hard_threshold_emails']  = $hard_threshold_emails;
			} else {
				unset( $new_settings[ static::ID ]['hard_threshold_enabled'] );
				unset( $new_settings[ static::ID ]['hard_threshold_amount'] );
				unset( $new_settings[ static::ID ]['hard_threshold_scope'] );
				unset( $new_settings[ static::ID ]['hard_threshold_emails'] );

				// Delete the hard limit reached option if the hard threshold is disabled.
				delete_option( FeatureOpenAIUsage::HARD_LIMIT_REACHED_KEY );
			}
		}

		return $new_settings;
	}

	/**
	 * Authenticate our credentials.
	 *
	 * @param array $settings Settings being saved.
	 * @return bool|WP_Error
	 */
	protected function authenticate_credentials( array $settings = [] ) {
		$api_url = add_query_arg( 'limit', 1, $this->org_admin_api_key_url );

		// Make request to ensure credentials work.
		$request  = new APIRequest( '', $this->feature_instance::ID, $this, $settings );
		$response = $request->get( $api_url, [ 'use_vip' => true ] );

		return ! is_wp_error( $response ) ? true : $response;
	}

	/**
	 * Get all usage data.
	 *
	 * @param bool $force_mtd Whether to force the fetch for the current month to date.
	 * @param bool $force_ytd Whether to force the fetch for the year to date.
	 * @param bool $force_all_time Whether to force the fetch for all time.
	 *
	 * @return array
	 */
	public function get_all_usage_data( bool $force_mtd = false, bool $force_ytd = false, bool $force_all_time = false ): array {
		$current_mtd_costs         = $this->fetch_current_month_to_date_costs( $force_mtd );
		$current_year_months_costs = $this->fetch_past_months_costs( $force_ytd );
		$past_years_costs          = $this->fetch_past_years_costs( $force_all_time );

		return [
			'mtd'      => $current_mtd_costs,
			'ytd'      => $current_year_months_costs + $current_mtd_costs,
			'all_time' => $past_years_costs + $current_year_months_costs + $current_mtd_costs,
		];
	}

	/**
	 * Fetch current month to date costs.
	 *
	 * @param bool $force Whether to force the fetch.
	 *
	 * @return float
	 */
	public function fetch_current_month_to_date_costs( bool $force = false ): float {
		$cached_data = $this->feature_instance->get_usage_data();

		if ( ! empty( $cached_data['mtd'] ) && ! $force ) {
			return $cached_data['mtd'];
		}

		$tz          = wp_timezone();
		$now         = new \DateTimeImmutable( 'now', $tz );
		$month_start = $now->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'm' ), 1 )->setTime( 0, 0, 0 );
		$pricing     = $this->fetch_period( $month_start->getTimestamp(), $now->getTimestamp() );

		if ( is_wp_error( $pricing ) ) {
			return 0;
		}

		$cached_data['mtd'] = $pricing['amount'];

		$this->feature_instance->set_usage_data( $cached_data );

		return $cached_data['mtd'];
	}

	/**
	 * Fetch past months costs.
	 *
	 * @param bool $force Whether to force the fetch.
	 *
	 * @return float
	 */
	public function fetch_past_months_costs( bool $force = false ): float {

		$cached_data = $this->feature_instance->get_usage_data();

		if ( ! empty( $cached_data['months_total'] ) && ! $force ) {
			return $cached_data['months_total'];
		}

		$tz                = wp_timezone();
		$now               = new \DateTimeImmutable( 'now', $tz );
		$current_year      = (int) $now->format( 'Y' );
		$current_month     = (int) $now->format( 'm' );
		$all_month_pricing = [];
		$usage_currency    = 'USD';

		for ( $month = 1; $month < $current_month; $month++ ) {
			// If the month is already in the cached array, skip the API call.
			if ( isset( $cached_data['months'][ $month ] ) ) {
				$all_month_pricing[ $month ] = $cached_data['months'][ $month ];
				continue;
			}

			$start_date = new \DateTimeImmutable( $current_year . '-' . $month . '-01', $tz );
			$end_date   = new \DateTimeImmutable( "$current_year-$month-01 last day of this month", $tz );

			$pricing = $this->fetch_period( $start_date->getTimestamp(), $end_date->getTimestamp() );

			if ( is_wp_error( $pricing ) ) {
				continue;
			}

			if ( ! $pricing['is_null_range'] ) {
				// Set the pricing for the year, even if it's 0.0.
				$all_month_pricing[ $month ] = $pricing['amount'];
				$usage_currency              = $pricing['currency'];
			}
		}

		$cached_data['months']       = $all_month_pricing;
		$cached_data['months_total'] = array_sum( $all_month_pricing );
		$cached_data['currency']     = $usage_currency;
		$cached_data['last_updated'] = time();

		$this->feature_instance->set_usage_data( $cached_data );

		return $cached_data['months_total'] ?? 0;
	}

	/**
	 * Fetch past years costs.
	 *
	 * @param bool $force Whether to force the fetch.
	 *
	 * @return float
	 */
	public function fetch_past_years_costs( bool $force = false ): float {

		$cached_data = $this->feature_instance->get_usage_data();

		if ( ! empty( $cached_data['years_total'] ) && ! $force ) {
			return $cached_data['years_total'];
		}

		$default_settings = $this->feature_instance->get_feature_default_settings();
		$tz               = wp_timezone();
		$now              = new \DateTimeImmutable( 'now', $tz );
		$current_year     = (int) $now->format( 'Y' );
		$all_year_pricing = [];
		$usage_currency   = 'USD';
		$start_year       = isset( $cached_data['start_year'] ) ? (int) $cached_data['start_year'] : $default_settings['api_start_year'];

		for ( $year = $start_year; $year < $current_year; $year++ ) {
			// If the year is already in the cached array, skip the API call.
			if ( isset( $cached_data['years'][ $year ] ) ) {
				$all_year_pricing[ $year ] = $cached_data['years'][ $year ];
				continue;
			}

			$start_date = new \DateTimeImmutable( $year . '-01-01', $tz );
			$end_date   = new \DateTimeImmutable( $year . '-12-31', $tz );

			$pricing = $this->fetch_period( $start_date->getTimestamp(), $end_date->getTimestamp() );

			if ( is_wp_error( $pricing ) ) {
				continue;
			}

			if ( ! $pricing['is_null_range'] ) {
				// Set the pricing for the year, even if it's 0.0.
				$all_year_pricing[ $year ] = $pricing['amount'];
				$usage_currency            = $pricing['currency'];
			}

			// If the all_year_pricing array is empty and the pricing is for a null range, set the start year to the current year.
			// Since current api is generated or used after this year only.
			if ( empty( $all_year_pricing ) && $pricing['is_null_range'] ) {
				$start_year = $year + 1;
			}
		}

		$cached_data['years']        = $all_year_pricing;
		$cached_data['years_total']  = array_sum( $all_year_pricing );
		$cached_data['currency']     = $usage_currency;
		$cached_data['last_updated'] = time();
		$cached_data['start_year']   = (int) $start_year;

		$this->feature_instance->set_usage_data( $cached_data );

		return $cached_data['years_total'] ?? 0;
	}

	/**
	 * Fetch costs from the OpenAI API.
	 *
	 * @param int $start_ts The start timestamp.
	 * @param int $end_ts The end timestamp.
	 *
	 * @return array|WP_Error Array with 'amount', 'currency', 'is_null_range' keys, or WP_Error.
	 */
	public function fetch_period( int $start_ts, int $end_ts ) {

		if ( ! $this->feature_instance->is_enabled() ) {
			return new WP_Error( 'feature_not_enabled', __( 'Feature is not enabled.', 'classifai' ) );
		}

		$settings = $this->feature_instance->get_settings();
		$api_key  = $this->get_credential( 'api_key', $settings );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'api_key_not_set', __( 'API key is not set.', 'classifai' ) );
		}

		$project_id = $this->get_credential( 'project_id', $settings );

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

			if ( ! empty( $project_id ) ) {
				$url = add_query_arg( 'project_ids', $project_id, $url );
			}

			$options = [
				'timeout' => 90, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			];

			$request = new APIRequest( '', $this->feature_instance::ID, $this, $settings );
			$data    = $request->get( $url, $options );

			if ( is_wp_error( $data ) ) {
				return $data;
			}

			$buckets = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : [];
			foreach ( $buckets as $bucket ) {
				if ( empty( $bucket['results'] ) ) {
					continue;
				}

				foreach ( $bucket['results'] as $cost_obj ) {
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

			// If the data is null, set the is_null_data flag to true.
			// This means this api was not generated or used for the given time range.
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
