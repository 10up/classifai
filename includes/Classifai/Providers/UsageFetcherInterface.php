<?php

namespace Classifai\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for provider-specific usage data fetchers.
 *
 * Each provider (OpenAI, Azure, etc.) implements this to fetch raw
 * usage/cost data from its API. The UsageTracker base class calls
 * these methods and handles caching, threshold checks, and persistence.
 */
interface UsageFetcherInterface {

	/**
	 * Fetch usage for an arbitrary time window.
	 *
	 * @param int $start_ts Unix timestamp for range start (inclusive).
	 * @param int $end_ts   Unix timestamp for range end (inclusive).
	 * @return array|WP_Error Array with 'amount', 'currency', and optionally
	 *                        'is_null_range' keys, or WP_Error on failure.
	 */
	public function fetch_period( int $start_ts, int $end_ts );

	/**
	 * Fetch all-time usage total.
	 *
	 * @param bool       $force_refresh   Whether to bypass cached data and re-fetch.
	 * @param array|null $ytd             Optional year-to-date data already fetched
	 *                                   (avoids a redundant API call for the current year).
	 * @param array|null $cached_data     Optional full cached settings/data to use
	 *                                   instead of reading from the database.
	 * @return array|WP_Error Array with 'amount', 'currency', and provider-specific
	 *                        year-pricing keys, or WP_Error on failure.
	 */
	public function fetch_all_time( bool $force_refresh = false, $ytd = null, $cached_data = null );
}
