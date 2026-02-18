<?php

namespace Classifai\Admin;

use Classifai\Providers\OpenAI\APIRequest;
use Classifai\Providers\OpenAI\UsageCosts;
use Classifai\Providers\CredentialObfuscator;
use Classifai\Providers\UsageFetcherInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI-specific usage tracker.
 *
 * Extends UsageTracker with OpenAI credential handling, the Admin API key,
 * project ID filtering, and all-time year-by-year cost accumulation.
 *
 * @since 3.8.0
 */
class OpenAIUsageTracker extends UsageTracker {

	/**
	 * WP option name used to block API requests when the hard limit is reached.
	 *
	 * Kept as a constant with the same string value as the previous
	 * OpenAIPricingController::HARD_LIMIT_OPTION so that APIRequest.php
	 * continues to work after just a class-reference update.
	 *
	 * @var string
	 */
	const HARD_LIMIT_OPTION = 'classifai_openai_hard_limit_reached';

	/**
	 * {@inheritdoc}
	 */
	public function get_provider_id(): string {
		return 'openai';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_provider_label(): string {
		return 'OpenAI';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_provider_option_defaults(): array {
		return [
			'admin_api_key'    => '',
			'project_id'       => '',
			'api_start_year'   => 2020,
			'all_year_pricing' => [],
		];
	}

	/**
	 * Returns the hard-limit option name, always pointing to the constant.
	 *
	 * {@inheritdoc}
	 */
	public function get_hard_limit_option(): string {
		return self::HARD_LIMIT_OPTION;
	}

	/**
	 * {@inheritdoc}
	 */
	public function make_fetcher( array $settings ): UsageFetcherInterface {
		return new UsageCosts(
			$settings['admin_api_key'] ?? '',
			$settings['project_id'] ?? ''
		);
	}

	/**
	 * Verifies the Admin API key can reach the OpenAI admin_api_keys endpoint.
	 *
	 * {@inheritdoc}
	 */
	public function authenticate_credentials( array $settings ) {
		if ( empty( $settings['admin_api_key'] ) ) {
			return new \WP_Error(
				'empty_admin_api_key',
				__( 'Admin API key is empty. Please enter a valid API key.', 'classifai' )
			);
		}

		$url      = add_query_arg( 'limit', 1, 'https://api.openai.com/v1/organization/admin_api_keys' );
		$request  = new APIRequest( trim( $settings['admin_api_key'] ) );
		$response = $request->get( $url, [ 'use_vip' => true ] );

		return ! is_wp_error( $response ) ? true : $response;
	}

	/**
	 * Returns settings, applying the admin API key filter unless suppressed.
	 *
	 * {@inheritdoc}
	 */
	public function get_settings( bool $suppress_filter = false ): array {
		$settings = parent::get_settings( $suppress_filter );

		if ( empty( $suppress_filter ) ) {
			/**
			 * Filter the OpenAI Admin API key used for usage tracking.
			 *
			 * Allows the key to be injected from outside the stored option,
			 * e.g. from an environment variable.
			 *
			 * @since 3.8.0
			 * @hook classifai_openai_admin_api_key
			 * @param string $admin_api_key The stored key.
			 * @param array  $settings      Full settings array.
			 * @return string
			 */
			$settings['admin_api_key'] = apply_filters(
				'classifai_openai_admin_api_key',
				$settings['admin_api_key'] ?? '',
				$settings
			);
		}

		return $settings;
	}

	/**
	 * Returns true only when an admin_api_key is present.
	 *
	 * {@inheritdoc}
	 */
	protected function has_required_credentials( array $settings ): bool {
		return ! empty( $settings['admin_api_key'] );
	}

	/**
	 * Sanitizes raw POST data into clean OpenAI usage settings.
	 *
	 * Handles credential obfuscation: if the submitted admin_api_key value is
	 * already obfuscated (unchanged from the previous GET response), the stored
	 * key is preserved rather than overwritten.
	 *
	 * {@inheritdoc}
	 */
	public function sanitize_settings( array $raw, array $current ) {
		$admin_api_key = $current['admin_api_key'] ?? '';

		if ( isset( $raw['admin_api_key'] ) ) {
			if ( CredentialObfuscator::is_obfuscated( $raw['admin_api_key'] ) ) {
				// Key was not changed in the UI — keep the stored value.
				$admin_api_key = $current['admin_api_key'] ?? '';
			} else {
				$admin_api_key = sanitize_text_field( $raw['admin_api_key'] );
			}
		}

		$soft_scope = isset( $raw['soft_threshold_scope'] )
			? sanitize_text_field( $raw['soft_threshold_scope'] )
			: 'current_month';

		$hard_scope = isset( $raw['hard_threshold_scope'] )
			? sanitize_text_field( $raw['hard_threshold_scope'] )
			: 'current_month';

		$refresh = isset( $raw['refresh_interval_minutes'] ) ? absint( $raw['refresh_interval_minutes'] ) : 15;
		if ( $refresh < 1 ) {
			$refresh = 15;
		}

		return array_merge(
			$current,
			[
				'admin_api_key'            => $admin_api_key,
				'admin_api_authenticated'  => true,
				'enabled'                  => ! empty( $raw['enabled'] ),
				'project_id'               => isset( $raw['project_id'] )
					? sanitize_text_field( $raw['project_id'] )
					: ( $current['project_id'] ?? '' ),
				'refresh_interval_minutes' => $refresh,
				'soft_threshold_enabled'   => ! empty( $raw['soft_threshold_enabled'] ),
				'soft_threshold_amount'    => isset( $raw['soft_threshold_amount'] )
					? abs( (float) $raw['soft_threshold_amount'] )
					: 0,
				'soft_threshold_scope'     => $soft_scope,
				'soft_threshold_emails'    => isset( $raw['soft_threshold_emails'] )
					? sanitize_textarea_field( $raw['soft_threshold_emails'] )
					: ( $current['soft_threshold_emails'] ?? '' ),
				'hard_threshold_amount'    => isset( $raw['hard_threshold_amount'] )
					? abs( (float) $raw['hard_threshold_amount'] )
					: 0,
				'hard_threshold_scope'     => $hard_scope,
				'hard_threshold_emails'    => isset( $raw['hard_threshold_emails'] )
					? sanitize_textarea_field( $raw['hard_threshold_emails'] )
					: ( $current['hard_threshold_emails'] ?? '' ),
				'hard_threshold_enabled'   => ! empty( $raw['hard_threshold_enabled'] ),
			]
		);
	}

	/**
	 * Obfuscates the admin_api_key before sending it to the browser.
	 *
	 * {@inheritdoc}
	 */
	protected function prepare_settings_for_api( array $settings ): array {
		if ( ! empty( $settings['admin_api_key'] ) ) {
			$settings['admin_api_key'] = CredentialObfuscator::obfuscate( $settings['admin_api_key'] );
		}
		return $settings;
	}
}
