<?php
/**
 * OpenAI Usage Tracking.
 */

namespace Classifai\Providers\OpenAI;

use Classifai\Features\APIUsageTracking;
use Classifai\Providers\UsageTrackingProvider;
use WP_Error;

class UsageTracking extends UsageTrackingProvider {
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
	 * Base URL for the OpenAI API usage data fetch.
	 *
	 * @var string
	 */
	protected static string $api_url = 'https://api.openai.com/v1/organization/costs';

	/**
	 * OpenAI UsageTracking constructor.
	 *
	 * @param \Classifai\Features\Feature $feature_instance The feature instance.
	 */
	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	/**
	 * Get the OpenAI Provider IDs.
	 *
	 * This is used to check if the API request is allowed for the OpenAI Providers.
	 *
	 * @return array
	 */
	public static function get_provider_ids(): array {
		return [
			ChatGPT::ID,
			Embeddings::ID,
			Images::ID,
			Moderation::ID,
			SpeechToText::ID,
			TextToSpeech::ID,
		];
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

		if ( $this->feature_instance instanceof APIUsageTracking ) {
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

				$soft_threshold_amount = empty( $new_settings[ static::ID ]['soft_threshold_amount'] ) ? 1.00 : $new_settings[ static::ID ]['soft_threshold_amount'];

				if (
					! empty( $new_settings[ static::ID ]['hard_threshold_amount'] )
					&& (float) $new_settings[ static::ID ]['hard_threshold_amount'] <= $soft_threshold_amount
				) {
					// Keep hard threshold amount greater than soft threshold amount.
					$new_settings[ static::ID ]['hard_threshold_amount'] = (string) ( $soft_threshold_amount + 1.00 );
				}
			} else {
				unset( $new_settings[ static::ID ]['hard_threshold_enabled'] );
				unset( $new_settings[ static::ID ]['hard_threshold_amount'] );
				unset( $new_settings[ static::ID ]['hard_threshold_scope'] );
				unset( $new_settings[ static::ID ]['hard_threshold_emails'] );

				// Delete the hard limit reached option if the hard threshold is disabled.
				delete_option( APIUsageTracking::HARD_LIMIT_REACHED_KEY );
			}

			// Unschedule the cron job if the refresh interval is changed.
			if ( $settings[ static::ID ]['refresh_interval_minutes'] !== $new_settings[ static::ID ]['refresh_interval_minutes'] ) {
				as_unschedule_action( APIUsageTracking::CRON_HOOK, [], 'classifai' );
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
	public function process_api_response_data( array $data ): array {
		// If the data is null, set the is_null_range flag to true.
		// This means this api was not generated or used for the given time range.
		if ( isset( $data['data'] ) && empty( $data['data'] ) ) {
			return [
				'is_null_range' => true,
				'total_amount'  => 0.0,
				'currency'      => 'USD',
				'has_more'      => false,
				'next_page'     => null,
			];
		}

		$total_amount = 0.0;
		$currency     = 'USD';
		$buckets      = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : [];

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

		return [
			'is_null_range' => false,
			'total_amount'  => $total_amount,
			'currency'      => $currency,
			'has_more'      => $data['has_more'],
			'next_page'     => $data['next_page'],
		];
	}
}
