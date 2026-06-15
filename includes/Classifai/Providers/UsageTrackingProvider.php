<?php
/**
 * Usage Tracking Provider.
 */

namespace Classifai\Providers;

use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Features\Feature;
use WP_Error;

abstract class UsageTrackingProvider extends Provider {

	/**
	 * Maximum number of items per page (API limit 1–180).
	 *
	 * @var int
	 */
	const PAGE_LIMIT = 180;

	/**
	 * Base URL for the API.
	 *
	 * @var string
	 */
	protected static string $api_url;

	/**
	 * Feature instance.
	 *
	 * @var \Classifai\Features\APIUsageTracking|null
	 */
	protected $feature_instance = null;

	/**
	 * Get the API Provider IDs.
	 *
	 * This is used to check if the API request is allowed for the Providers.
	 *
	 * @return array
	 */
	abstract public static function get_provider_ids(): array;

	/**
	 * Process the data from the API.
	 *
	 * @param array $data The data from the API.
	 *
	 * @return array {
	 *     @type bool $is_null_range Whether the data is null.
	 *     @type float $total_amount The total amount.
	 *     @type string $currency The currency.
	 *     @type bool $has_more Whether there is more data.
	 *     @type int|null $next_page The next page number.
	 * }
	 */
	abstract public function process_api_response_data( array $data ): array;

	/**
	 * Returns the default settings for the provider.
	 *
	 * @return array
	 */
	public function get_default_provider_settings(): array {
		return [
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
	}

	/**
	 * Get the API args for the usage API.
	 *
	 * @param int    $start_ts The start timestamp.
	 * @param int    $end_ts The end timestamp.
	 * @param string $page The page number.
	 *
	 * @return array
	 */
	public function usage_api_args( int $start_ts, int $end_ts, string $page = '1' ): array {
		return [
			'start_time' => $start_ts,
			'end_time'   => $end_ts,
			'limit'      => static::PAGE_LIMIT,
			'page'       => '1' !== $page ? rawurlencode( $page ) : '',
		];
	}

	/**
	 * Fetch costs from the respective provider AI API.
	 *
	 * @param int $start_ts The start timestamp.
	 * @param int $end_ts The end timestamp.
	 *
	 * @return array|WP_Error Array with 'amount', 'currency', 'is_null_range' keys, or WP_Error.
	 */
	public function fetch_period( int $start_ts, int $end_ts ) {

		if ( empty( $this->feature_instance ) || ! $this->feature_instance instanceof Feature ) {
			return new WP_Error( 'feature_not_set', __( 'Feature is not set or is not an instance of Feature.', 'classifai' ) );
		}

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
		$page         = '1';
		$has_more     = true;
		$is_null_data = false;

		while ( $has_more ) {
			$args = $this->usage_api_args( $start_ts, $end_ts, $page );
			$args = array_filter( $args );
			$url  = add_query_arg( $args, static::$api_url );

			if ( ! empty( $project_id ) ) {
				$url = add_query_arg( 'project_ids', $project_id, $url );
			}

			$options = [
				'timeout' => 90, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			];

			$request = new APIRequest( '', $this->feature_instance::ID, $this, $settings );
			$data    = $request->get( $url, $options );

			if ( is_wp_error( $data ) || empty( $data ) ) {
				break;
			}

			$proceed_data = [
				'total_amount' => $total_amount,
				'currency'     => $currency,
				'has_more'     => false,
				'next_page'    => null,
			];

			$proceed_data = $this->process_api_response_data( $data );

			$total_amount += $proceed_data['total_amount'] ?? 0.0;
			$currency      = $proceed_data['currency'] ?? $currency;
			$has_more      = $proceed_data['has_more'] ?? false;
			$page          = $proceed_data['next_page'] ?? null;
			$is_null_data  = $proceed_data['is_null_range'] ?? false;
		}

		return [
			'is_null_range' => $is_null_data,
			'amount'        => round( $total_amount, 2 ),
			'currency'      => $currency,
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
	 * i.e If the current month is March 2026, fetch the costs for January and February 2026.
	 * TODO: Usecase, what happens if year changes? Need to reset cached data. Also maybe move to transient cache?
	 *
	 * @param bool $force Whether to force the fetch.
	 *
	 * @return float
	 */
	public function fetch_past_months_costs( bool $force = false ): float {

		$cached_data       = $this->feature_instance->get_usage_data();
		$tz                = wp_timezone();
		$now               = new \DateTimeImmutable( 'now', $tz );
		$current_year      = (int) $now->format( 'Y' );
		$last_month_date   = $now->modify( '-1 month' );
		$last_month        = (int) $last_month_date->format( 'm' );
		$last_month_year   = (int) $last_month_date->format( 'Y' );
		$all_month_pricing = [];
		$usage_currency    = 'USD';

		// Year changed and current month is January, so no past months to fetch. It will be handled by the past years costs fetch.
		if ( $current_year !== $last_month_year ) {
			$cached_data['months']       = [];
			$cached_data['months_total'] = 0.0;
			$cached_data['currency']     = $usage_currency;
			$cached_data['last_updated'] = time();

			$this->feature_instance->set_usage_data( $cached_data );

			return $cached_data['months_total'];
		}

		if (
			! empty( $cached_data['months'][ $last_month ] )
			&& ! empty( $cached_data['months_total'] )
			&& ! $force
		) {
			return $cached_data['months_total'];
		}

		for ( $month = 1; $month <= $last_month; $month++ ) {
			// If the month is already in the cached array, skip the API call.
			if ( isset( $cached_data['months'][ $month ] ) && ! $force ) {
				$all_month_pricing[ $month ] = $cached_data['months'][ $month ];
				continue;
			}

			$start_date = new \DateTimeImmutable( $last_month_year . '-' . $month . '-01', $tz );
			$end_date   = new \DateTimeImmutable( "$last_month_year-$month-01 last day of this month", $tz );

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
		$past_years_costs          = $this->fetch_past_years_costs( $force_all_time );
		$current_year_months_costs = $this->fetch_past_months_costs( $force_ytd );

		return [
			'mtd'      => $current_mtd_costs,
			'ytd'      => $current_year_months_costs + $current_mtd_costs,
			'all_time' => $past_years_costs + $current_year_months_costs + $current_mtd_costs,
		];
	}
}
