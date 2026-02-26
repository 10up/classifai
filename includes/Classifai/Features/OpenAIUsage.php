<?php

namespace Classifai\Features;

use Classifai\Providers\OpenAI as OpenAIProvider;
use Classifai\Providers\OpenAI\UsageTracking as OpenAIUsageTracking;
use Classifai\Services\UsageTracking as UsageTrackingService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

use function Classifai\get_asset_info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OpenAIUsage
 */
class OpenAIUsage extends Feature {

	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'feature_openai_usage';

	/**
	 * Cron hook for the usage refresh.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'classifai_openai_usage_refresh';

	/**
	 * Cron hook for the usage refresh.
	 *
	 * @var string
	 */
	const FORCE_CRON_HOOK = 'classifai_openai_usage_refresh_force';

	/**
	 * Default refresh interval in minutes.
	 *
	 * @var int
	 */
	const DEFAULT_REFRESH_INTERVAL = ( 15 * MINUTE_IN_SECONDS );

	/**
	 * Key for the usage data in the options.
	 *
	 * @var string
	 */
	const USAGE_DATA_KEY = 'classifai_openai_usage_data';

	/**
	 * Key for the hard limit option in the options.
	 *
	 * @var string
	 */
	const HARD_LIMIT_REACHED_KEY = 'classifai_openai_usage_hard_limit_reached';

	/**
	 * Key for the force refresh option in the options.
	 *
	 * @var string
	 */
	const FORCE_REFRESH_KEY = 'classifai_openai_usage_force_refresh';

	/**
	 * Usage data.
	 *
	 * @var array
	 */
	public $usage_data = [
		'mtd'          => 0, // Current month to date usage.
		'ytd'          => 0, // Year to date usage.
		'all_time'     => 0, // All time usage.
		'years'        => [], // Yearly usage by year.
		'years_total'  => 0, // Total usage for all past years.
		'months'       => [], // Monthly usage by month for current year.
		'months_total' => 0, // Total usage for all past months.
		'currency'     => 'USD', // Currency of the usage.
		'last_updated' => null, // Last updated timestamp.
		'start_year'   => 2020, // Start year of the usage.
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'OpenAI usage tracking', 'classifai' );

		// Contains all Providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( UsageTrackingService::get_service_providers() );

		// Contains just the Providers this Feature supports.
		$this->supported_providers = [
			OpenAIUsageTracking::ID => __( 'OpenAI Usage Tracking', 'classifai' ),
		];
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Monitor OpenAI usage and set levels for alerting and deactivating Features.', 'classifai' );
	}

	/**
	 * Get the usage data.
	 *
	 * @return array
	 */
	public function get_usage_data(): array {
		return get_option( self::USAGE_DATA_KEY, [] );
	}

	/**
	 * Set the usage data.
	 *
	 * @param array $usage_data Usage data.
	 * @return void
	 */
	public function set_usage_data( array $usage_data ): void {
		$usage_data = wp_parse_args( $usage_data, $this->usage_data );
		update_option( self::USAGE_DATA_KEY, $usage_data );
	}

	/**
	 * Returns the default settings for the Feature.
	 *
	 * @return array
	 */
	public function get_feature_default_settings(): array {
		return [
			'provider'       => OpenAIUsageTracking::ID,
			'api_start_year' => 2020,
			'usage_data'     => [],
		];
	}

	/**
	 * Get the OpenAI Provider IDs.
	 *
	 * This is used to check if the API request is allowed for the OpenAI Providers.
	 *
	 * @return array
	 */
	public static function get_openai_provider_ids(): array {
		return [
			OpenAIProvider\ChatGPT::ID,
			OpenAIProvider\Embeddings::ID,
			OpenAIProvider\Images::ID,
			OpenAIProvider\Moderation::ID,
			OpenAIProvider\SpeechToText::ID,
			OpenAIProvider\TextToSpeech::ID,
		];
	}

	/**
	 * Set up necessary hooks.
	 */
	public function feature_setup() {

		add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
		add_action( 'admin_init', [ $this, 'maybe_schedule_cron' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );

		add_action( self::FORCE_CRON_HOOK, [ $this, 'run_usage_force_refresh' ] );
		add_action( self::CRON_HOOK, [ $this, 'run_usage_refresh' ] );
	}

	/**
	 * Register any needed endpoints.
	 */
	public function rest_api_init() {

		register_setting(
			$this->get_option_name(),
			self::FORCE_REFRESH_KEY,
			[
				'show_in_rest' => true,
				'type'         => 'boolean',
				'description'  => __( 'Whether to force refresh the usage data.', 'classifai' ),
				'default'      => false,
			]
		);

		register_rest_route(
			'classifai/v1',
			'/openai-usage/force-refresh',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'rest_endpoint_callback' ],
				'permission_callback' => [ $this, 'rest_endpoint_permissions_check' ],
			]
		);
	}

	/**
	 * Generic request handler for all our custom routes.
	 *
	 * @param WP_REST_Request $request The full request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_endpoint_callback( WP_REST_Request $request ) {

		$route = $request->get_route();

		if (
			strpos( $route, '/classifai/v1/openai-usage/force-refresh' ) !== 0
			|| ! function_exists( 'as_enqueue_async_action' )
			|| (
				function_exists( 'as_has_scheduled_action' )
				&& \as_has_scheduled_action( self::FORCE_CRON_HOOK, [], 'classifai' )
			)
		) {
			return parent::rest_endpoint_callback( $request );
		}

		$cron_scheduled = \as_enqueue_async_action( self::FORCE_CRON_HOOK, [], 'classifai' );

		if ( empty( $cron_scheduled ) ) {
			return rest_ensure_response(
				new WP_Error( 'failed_to_schedule_cron', __( 'Failed to schedule cron job.', 'classifai' ) )
			);
		}

		update_option( self::FORCE_REFRESH_KEY, true );

		return rest_ensure_response(
			[
				'success' => true,
			]
		);
	}

	/**
	 * Checks if the user has permission to access the endpoint.
	 *
	 * @return bool|WP_Error
	 */
	public function rest_endpoint_permissions_check() {

		// Ensure the Feature is enabled. Also runs a user check.
		if ( ! $this->is_feature_enabled() ) {
			return new WP_Error( 'not_enabled', esc_html__( 'OpenAI usage tracking is not currently enabled.', 'classifai' ) );
		}

		return true;
	}

	/**
	 * Schedules the next cron run when the option exists and we have an admin key.
	 */
	public function maybe_schedule_cron(): void {

		// We return early if Feature is not enabled or action scheduler is not active.
		if (
			! $this->is_enabled()
			|| ! function_exists( 'as_schedule_recurring_action' )
			|| ! function_exists( 'as_has_scheduled_action' )
		) {
			return;
		}

		$settings = $this->get_settings( OpenAIUsageTracking::ID );

		if ( ! empty( $settings['refresh_interval_minutes'] ) ) {
			$interval = $settings['refresh_interval_minutes'] * MINUTE_IN_SECONDS;
		} else {
			$interval = self::DEFAULT_REFRESH_INTERVAL;
		}

		/**
		 * Filter the refresh interval for the OpenAI usage tracking.
		 *
		 * @since x.x.x
		 * @hook classifai_openai_usage_refresh_interval
		 *
		 * @param int $interval The refresh interval in seconds.
		 * @return int The filtered refresh interval.
		 */
		$interval = apply_filters( 'classifai_openai_usage_refresh_interval', $interval );

		if ( ! \as_has_scheduled_action( self::CRON_HOOK ) ) {
			\as_schedule_recurring_action( time(), $interval, self::CRON_HOOK, [], 'classifai' );
		}
	}

	/**
	 * Enqueues the admin scripts.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( string $hook_suffix ): void {
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'classifai-plugin-openai-usage-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-openai-usage.js',
			get_asset_info( 'classifai-plugin-openai-usage', 'dependencies' ),
			get_asset_info( 'classifai-plugin-openai-usage', 'version' ),
			true
		);
	}

	/**
	 * Registers the dashboard widget when configured.
	 */
	public function register_dashboard_widget(): void {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		wp_add_dashboard_widget(
			'classifai_openai_usage',
			__( 'OpenAI usage (ClassifAI)', 'classifai' ),
			[ $this, 'render_dashboard_widget' ],
			null,
			null,
			'normal'
		);
	}

	/**
	 * Renders the dashboard widget content.
	 */
	public function render_dashboard_widget(): void {
		$usage    = $this->get_usage_data();
		$currency = $usage['currency'] ?? 'USD';
		$fmt      = function ( $val ) use ( $currency ) {
			return number_format_i18n( $val, 2 ) . ' ' . $currency;
		};

		// TODO: Update markup to bit cleaner.
		echo '<p class="classifai-openai-usage-disclaimer">';
		esc_html_e( 'Usage and costs shown here are from the OpenAI API for this project/site. If you use the same API key or project elsewhere, this data does not represent only ClassifAI.', 'classifai' );
		echo '</p>';
		echo '<ul class="classifai-openai-usage-list">';
		echo '<li><strong>' . esc_html__( 'This month', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['mtd'] ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Year to date', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['ytd'] ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'All time', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['all_time'] ) ) . '</li>';
		echo '</ul>';

		if ( 0 < $usage['last_updated'] ) {
			echo '<p class="classifai-openai-usage-updated">';
			$date_format      = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$last_updated_str = wp_date( $date_format, $usage['last_updated'] );
			echo esc_html(
				sprintf(
					/* translators: %s: human-readable time */
					__( 'Last updated: %s', 'classifai' ),
					$last_updated_str
				)
			);
			echo '</p>';
		} else {
			echo '<p class="classifai-openai-usage-updated">' . esc_html__( 'Updating…', 'classifai' ) . '</p>';
		}
		$settings_url = admin_url( 'tools.php?page=classifai#/usage_tracking/feature_openai_usage' );
		echo '<p>';
		echo '<a href="' . esc_url( $settings_url ) . '" class="components-button is-primary">' . esc_html__( 'Configure alerts', 'classifai' ) . '</a>';
		echo '<button type="button" id="openai_usage_tracking_force_refresh_data" class="components-button is-secondary" style="margin-left: 10px;">' . esc_html__( 'Force refresh usage', 'classifai' ) . '</button>';
		echo '</p>';
	}

	/**
	 * Runs the usage force refresh: fetch costs, cache, check thresholds.
	 *
	 * @return void
	 */
	public function run_usage_force_refresh(): void {
		$this->run_usage_refresh( true );
	}

	/**
	 * Runs the usage refresh: fetch costs, cache, check thresholds.
	 *
	 * @param bool $force_refresh Whether to force the refresh of the usage data.
	 *
	 * @return void
	 */
	public function run_usage_refresh( bool $force_refresh = false ): void {

		if ( ! $this->is_enabled() ) {
			return;
		}

		$provider = $this->get_feature_provider_instance();

		if ( empty( $provider ) || ! $provider instanceof OpenAIUsageTracking ) {
			return;
		}

		if ( $force_refresh ) {
			$usage_data = $provider->get_all_usage_data( true, true, true );
		} else {
			$usage_data = $provider->get_all_usage_data( true );
		}

		$usage_data = wp_parse_args( $usage_data, $this->get_usage_data() );
		$this->set_usage_data( $usage_data );

		$settings = $this->get_settings( OpenAIUsageTracking::ID );

		/**
		 * Fires after OpenAI usage has been updated from the API.
		 *
		 * @since x.x.x
		 *
		 * @hook classifai_openai_usage_updated
		 *
		 * @param array    $usage_data Usage data.
		 * @param array    $settings Settings.
		 * @param bool     $force_refresh Whether to force the refresh of the usage data.
		 */
		do_action( 'classifai_openai_usage_updated', $usage_data, $settings ?? [], $force_refresh );

		$this->check_hard_threshold( $usage_data, $settings );
		$this->check_soft_threshold( $usage_data, $settings );

		if ( $force_refresh ) {
			// Reset the force refresh option.
			delete_option( self::FORCE_REFRESH_KEY );
		}
	}

	/**
	 * Returns the amount to compare for threshold based on scope.
	 *
	 * @param array  $usage_data Usage data.
	 * @param string $scope   'current_month', 'year_to_date', 'all_time'.
	 * @return float
	 */
	public function get_amount_for_scope( array $usage_data, string $scope ): float {
		switch ( $scope ) {
			case 'current_month':
				return $usage_data['mtd'];
			case 'year_to_date':
				return $usage_data['ytd'];
			case 'all_time':
				return $usage_data['all_time'];
			default:
				return 0;
		}
	}

	/**
	 * Parses a comma separated list into valid email addresses.
	 *
	 * @param string $input Raw input.
	 * @return array List of email strings.
	 */
	private function get_email_list( string $input ): array {
		$input  = sanitize_textarea_field( $input );
		$emails = explode( ',', $input );
		$emails = array_map( 'trim', $emails );
		$emails = array_map( 'sanitize_email', $emails );
		$emails = array_filter( $emails, 'is_email' );
		$emails = array_unique( $emails );

		return $emails;
	}

	/**
	 * Checks soft threshold and sends email once per period.
	 *
	 * @param array $usage_data Usage data.
	 * @param array $settings Settings.
	 */
	private function check_soft_threshold( array $usage_data, array $settings ): void {
		$is_hard_limit_reached = get_option( self::HARD_LIMIT_REACHED_KEY, false );

		// If the hard limit is reached, and the hard threshold is enabled, return early.
		if ( $is_hard_limit_reached && ! empty( $settings['hard_threshold_enabled'] ) ) {
			return;
		}

		$sent_key = 'last_soft_alert_sent_for_period';

		if ( empty( $settings['soft_threshold_enabled'] ) || empty( $settings['soft_threshold_amount'] ) ) {
			// Remove the sent key from the pricing option if already set.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ OpenAIUsageTracking::ID => $settings ] );
			return;
		}

		$scope     = isset( $settings['soft_threshold_scope'] ) ? $settings['soft_threshold_scope'] : 'current_month';
		$amount    = $this->get_amount_for_scope( $usage_data, $scope );
		$threshold = (float) $settings['soft_threshold_amount'];

		if ( $amount < $threshold ) {
			// Remove the sent key from the pricing option if already set.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ OpenAIUsageTracking::ID => $settings ] );
			return;
		}

		/**
		 * Fires when the soft threshold is exceeded.
		 *
		 * @since x.x.x
		 *
		 * @hook classifai_openai_soft_threshold_exceeded
		 *
		 * @param array $settings Updated pricing option.
		 * @param array $usage_data Usage data.
		 * @param float $amount Amount of usage.
		 * @param float $threshold Threshold amount.
		 * @param string $scope Scope of the threshold.
		 */
		do_action( 'classifai_openai_soft_threshold_exceeded', $settings, $usage_data, $amount, $threshold, $scope );

		$emails = $this->get_email_list( $settings['soft_threshold_emails'] ?? '' );

		if ( empty( $emails ) ) {
			return;
		}

		$period_key   = '';
		$period_label = '';

		// Period key is used to track the last sent alert for a period. Sent only once a month.
		switch ( $scope ) {
			case 'current_month':
				$period_key   = gmdate( 'Y-m' );
				$period_label = __( 'current month', 'classifai' );
				break;
			case 'year_to_date':
				// i.e 2026-01-2026-02 for feb month.
				$period_key   = gmdate( 'Y' ) . '-01-' . gmdate( 'Y-m' );
				$period_label = __( 'year to date', 'classifai' );
				break;
			case 'all_time':
				// i.e all-2026-02 for current year feb month.
				$period_key   = 'all-' . gmdate( 'Y-m' );
				$period_label = __( 'all time', 'classifai' );
				break;
		}

		if ( isset( $settings[ $sent_key ] ) && $period_key === $settings[ $sent_key ] ) {
			return;
		}

		$subject = __( 'ClassifAI: OpenAI usage exceeded soft limit', 'classifai' );
		$message = sprintf(
			/* translators: 1: amount, 2: currency, 3: period */
			__( 'OpenAI usage has exceeded your soft limit of %1$s %2$s for this period (%3$s).', 'classifai' ),
			number_format_i18n( $threshold, 2 ),
			$usage_data['currency'],
			$period_label
		);

		$headers    = [];
		$bcc_emails = array_slice( $emails, 1 );
		if ( ! empty( $bcc_emails ) ) {
			$headers[] = 'Bcc: ' . implode( ', ', $bcc_emails );
		}
		wp_mail( $emails[0], $subject, $message, $headers );

		$settings[ $sent_key ] = $period_key;
		$this->update_settings( [ OpenAIUsageTracking::ID => $settings ] );
	}

	/**
	 * Checks hard threshold, sets option to disable Features, sends email.
	 *
	 * @param array $usage_data Usage data.
	 * @param array $settings Settings.
	 */
	private function check_hard_threshold( array $usage_data, array $settings ): void {
		$sent_key = 'hard_alert_sent_for_period';

		if ( empty( $settings['hard_threshold_enabled'] ) || empty( $settings['hard_threshold_amount'] ) ) {
			delete_option( self::HARD_LIMIT_REACHED_KEY );

			// Remove the sent key from the pricing option.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ OpenAIUsageTracking::ID => $settings ] );
			return;
		}

		$scope     = isset( $settings['hard_threshold_scope'] ) ? $settings['hard_threshold_scope'] : 'current_month';
		$amount    = $this->get_amount_for_scope( $usage_data, $scope );
		$threshold = (float) $settings['hard_threshold_amount'];

		if ( $amount < $threshold ) {
			delete_option( self::HARD_LIMIT_REACHED_KEY );

			// Remove the sent key from the pricing option.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ OpenAIUsageTracking::ID => $settings ] );
			return;
		}

		update_option( self::HARD_LIMIT_REACHED_KEY, true );

		/**
		 * Fires when the hard threshold is exceeded.
		 *
		 * @since x.x.x
		 *
		 * @hook classifai_openai_hard_threshold_exceeded
		 *
		 * @param array $settings Updated pricing option.
		 * @param array $usage_data Usage data.
		 * @param float $amount Amount of usage.
		 * @param float $threshold Threshold amount.
		 * @param string $scope Scope of the threshold.
		 */
		do_action( 'classifai_openai_hard_threshold_exceeded', $settings, $usage_data, $amount, $threshold, $scope );

		$emails = $this->get_email_list( $settings['hard_threshold_emails'] ?? '' );

		if ( empty( $emails ) ) {
			return;
		}

		$period_key   = '';
		$period_label = '';

		// Period key is used to track the last sent alert for a period. Sent only once a month.
		switch ( $scope ) {
			case 'current_month':
				$period_key   = gmdate( 'Y-m' );
				$period_label = __( 'current month', 'classifai' );
				break;
			case 'year_to_date':
				// i.e 2026-01-2026-02 for feb month.
				$period_key   = gmdate( 'Y' ) . '-01-' . gmdate( 'Y-m' );
				$period_label = __( 'year to date', 'classifai' );
				break;
			case 'all_time':
				// i.e all-2026-02 for current year feb month.
				$period_key   = 'all-' . gmdate( 'Y-m' );
				$period_label = __( 'all time', 'classifai' );
				break;
		}

		if ( isset( $settings[ $sent_key ] ) && $period_key === $settings[ $sent_key ] ) {
			return;
		}

		$subject = __( 'ClassifAI: OpenAI usage exceeded hard limit', 'classifai' );
		$message = sprintf(
			/* translators: 1: amount, 2: currency, 3: period */
			__( 'OpenAI usage has exceeded your hard limit of %1$s %2$s for this period (%3$s). OpenAI features have been disabled. Re-enable in ClassifAI → Pricing.', 'classifai' ),
			number_format_i18n( $threshold, 2 ),
			$usage_data['currency'],
			$period_label
		);

		$headers    = [];
		$bcc_emails = array_slice( $emails, 1 );
		if ( ! empty( $bcc_emails ) ) {
			$headers[] = 'Bcc: ' . implode( ', ', $bcc_emails );
		}
		wp_mail( $emails[0], $subject, $message, $headers );

		$settings[ $sent_key ] = $period_key;
		$this->update_settings( [ OpenAIUsageTracking::ID => $settings ] );
	}
}
