<?php

namespace Classifai\Features;

use Classifai\Providers\UsageTrackingProvider;
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
 * Class APIUsageTracking
 */
class APIUsageTracking extends Feature {

	/**
	 * Default refresh interval in minutes.
	 *
	 * @var int
	 */
	const DEFAULT_REFRESH_INTERVAL = ( 15 * MINUTE_IN_SECONDS );

	/**
	 * ID of the current feature.
	 *
	 * @var string
	 */
	const ID = 'api_usage_tracking';

	/**
	 * Cron hook for the usage refresh.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'classifai_api_usage_refresh';

	/**
	 * Cron hook for forcing the usage refresh.
	 *
	 * @var string
	 */
	const FORCE_CRON_HOOK = 'classifai_api_usage_force_refresh';

	/**
	 * Key for the usage data in the options.
	 *
	 * @var string
	 */
	const USAGE_DATA_KEY = 'classifai_api_usage_data';

	/**
	 * Key for the hard limit option in the options.
	 *
	 * @var string
	 */
	const HARD_LIMIT_REACHED_KEY = 'classifai_api_usage_hard_limit_reached';

	/**
	 * Key for the force refresh option in the options.
	 *
	 * @var string
	 */
	const FORCE_REFRESH_KEY = 'classifai_api_usage_force_refresh';

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
		$this->label = __( 'AI Usage Tracking', 'classifai' );

		// Contains all Providers that are registered to the service.
		$this->provider_instances = $this->get_provider_instances( UsageTrackingService::get_service_providers() );

		// Contains just the Providers this Feature supports.
		$this->supported_providers = [
			OpenAIUsageTracking::ID => __( 'OpenAI', 'classifai' ),
		];
	}

	/**
	 * Returns the default settings for the feature.
	 *
	 * @return array
	 */
	protected function get_default_settings(): array {
		$settings = parent::get_default_settings();

		if ( empty( $settings['roles'] ) ) {
			$settings['roles'] = [];
		}

		$settings['roles'] = [
			'administrator' => 'administrator',
		];

		return $settings;
	}

	/**
	 * Get the description for the enable field.
	 *
	 * @return string
	 */
	public function get_enable_description(): string {
		return esc_html__( 'Monitor AI API usage and set levels for alerting and deactivating Features.', 'classifai' );
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
	 * Set up necessary hooks.
	 */
	public function feature_setup() {

		add_filter( 'classifai_pre_fetch_feature_response', [ $this, 'pre_fetch_feature_response' ], 10, 2 );

		add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
		add_action( 'admin_init', [ $this, 'maybe_schedule_cron' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );

		add_action( self::FORCE_CRON_HOOK, [ $this, 'run_usage_force_refresh' ] );
		add_action( self::CRON_HOOK, [ $this, 'run_usage_refresh' ] );
	}

	/**
	 * Pre-fetch the feature response to check if the feature is allowed to run based on the usage tracking data.
	 *
	 * @param mixed                         $response Response to return.
	 * @param \Classifai\Providers\Provider $provider_instance provider used.
	 *
	 * @return mixed Response to return.
	 */
	public function pre_fetch_feature_response( $response, $provider_instance ) {
		$usage_tracking_provider = $this->get_feature_provider_instance();
		$provider_ids            = [];

		if ( ! empty( $usage_tracking_provider ) && $usage_tracking_provider instanceof UsageTrackingProvider ) {
			$provider_ids = $usage_tracking_provider->get_provider_ids();
		}

		if (
			empty( $provider_ids )
			|| empty( $provider_instance::ID )
			|| ! in_array( $provider_instance::ID, $provider_ids, true )
		) {
			return $response;
		}

		$limit_reached = get_option( self::HARD_LIMIT_REACHED_KEY, false );

		if ( $limit_reached ) {
			return new WP_Error(
				'classifai_hard_limit_reached',
				__( 'Usage has reached the configured hard limit. Re-enable in ClassifAI -> Usage Tracking -> AI usage tracking.', 'classifai' ),
				[
					'status' => 403,
				]
			);
		}

		return $response;
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
			'/api-usage-tracking/force-refresh',
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
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_endpoint_callback( WP_REST_Request $request ) {

		$route = $request->get_route();

		if ( strpos( $route, '/classifai/v1/api-usage-tracking/force-refresh' ) !== 0 ) {
			return parent::rest_endpoint_callback( $request );
		}

		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return rest_ensure_response(
				new WP_Error( 'action_scheduler_not_active', __( 'Action Scheduler is not active.', 'classifai' ) )
			);
		}

		if ( function_exists( 'as_has_scheduled_action' ) && \as_has_scheduled_action( self::FORCE_CRON_HOOK, [], 'classifai' ) ) {
			return rest_ensure_response(
				new WP_Error( 'cron_already_scheduled', __( 'Cron job already scheduled.', 'classifai' ) )
			);
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

		// Ensure the Feature is enabled only current user is admin.
		if ( ! $this->is_feature_enabled() || ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'not_enabled', esc_html__( 'AI Usage Tracking is not currently enabled or you are not authorized to access this endpoint.', 'classifai' ) );
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

		$provider = $this->get_feature_provider_instance();

		if ( empty( $provider ) || ! $provider instanceof UsageTrackingProvider ) {
			return;
		}

		$settings = $this->get_settings( $provider::ID );

		if ( ! empty( $settings['refresh_interval_minutes'] ) ) {
			$interval = $settings['refresh_interval_minutes'] * MINUTE_IN_SECONDS;
		} else {
			$interval = self::DEFAULT_REFRESH_INTERVAL;
		}

		/**
		 * Filter the refresh interval for the AI API usage tracking.
		 *
		 * @since 3.8.0
		 * @hook classifai_api_usage_refresh_interval
		 *
		 * @param int $interval The refresh interval in seconds.
		 *
		 * @return int The filtered refresh interval.
		 */
		$interval = apply_filters( 'classifai_api_usage_refresh_interval', $interval );

		if ( ! \as_has_scheduled_action( self::CRON_HOOK, [], 'classifai' ) ) {
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
			'classifai-plugin-api-usage-tracking-js',
			CLASSIFAI_PLUGIN_URL . 'dist/classifai-plugin-api-usage-tracking.js',
			get_asset_info( 'classifai-plugin-api-usage-tracking', 'dependencies' ),
			get_asset_info( 'classifai-plugin-api-usage-tracking', 'version' ),
			true
		);
	}

	/**
	 * Registers the dashboard widget when configured.
	 */
	public function register_dashboard_widget(): void {

		wp_add_dashboard_widget(
			'classifai_api_usage',
			__( 'AI Usage Tracking', 'classifai' ),
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
		$date_format  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$usage        = $this->get_usage_data();
		$currency     = $usage['currency'] ?? 'USD';
		$settings_url = admin_url( 'tools.php?page=classifai#/usage_tracking/api_usage_tracking' );
		$fmt          = function ( $val ) use ( $currency ) {
			return '$' . number_format_i18n( $val, 2 ) . ' ' . $currency;
		};
		$is_updating  = empty( $usage['last_updated'] ) || ( 0 > $usage['last_updated'] );

		?>
		<div class="classifai-api-usage-widget">
			<p class="classifai-api-usage-disclaimer">
				<?php esc_html_e( 'Usage and costs shown here are from the AI API for this project/site. If you use the same API key or project elsewhere, this data does not represent only ClassifAI.', 'classifai' ); ?>
			</p>
			<ul class="classifai-api-usage-list">
				<li>
					<strong><?php esc_html_e( 'This month', 'classifai' ); ?>:</strong>
					<?php
					if ( $is_updating && empty( $usage['mtd'] ) ) {
						esc_html_e( 'Updating…', 'classifai' );
					} else {
						echo esc_html( $fmt( $usage['mtd'] ) );
					}
					?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Year to date', 'classifai' ); ?>:</strong>
					<?php
					if ( $is_updating && empty( $usage['ytd'] ) ) {
						esc_html_e( 'Updating…', 'classifai' );
					} else {
						echo esc_html( $fmt( $usage['ytd'] ) );
					}
					?>
				</li>
				<li>
					<strong><?php esc_html_e( 'All time', 'classifai' ); ?>:</strong>
					<?php
					if ( $is_updating && empty( $usage['all_time'] ) ) {
						esc_html_e( 'Updating…', 'classifai' );
					} else {
						echo esc_html( $fmt( $usage['all_time'] ) );
					}
					?>
				</li>
			</ul>
			<?php if ( ! empty( $usage['last_updated'] ) && 0 < $usage['last_updated'] ) { ?>
				<p class="classifai-api-usage-updated">
					<?php
					/* translators: %s: human-readable time */
					echo esc_html( sprintf( __( 'Last updated: %s', 'classifai' ), wp_date( $date_format, $usage['last_updated'] ?? 0 ) ) );
					?>
				</p>
			<?php } ?>

			<p>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="components-button is-primary"><?php esc_html_e( 'Configure Alerts', 'classifai' ); ?></a>
				<button type="button" id="api_usage_tracking_force_refresh_data" class="components-button is-secondary" style="margin-left: 10px;"><?php esc_html_e( 'Refresh Usage Data', 'classifai' ); ?></button>
			</p>
		</div>
		<?php
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

		if ( empty( $provider ) || ! $provider instanceof UsageTrackingProvider ) {
			return;
		}

		if ( $force_refresh ) {
			$usage_data = $provider->get_all_usage_data( true, true, true );
		} else {
			$usage_data = $provider->get_all_usage_data( true );
		}

		$usage_data = wp_parse_args( $usage_data, $this->get_usage_data() );
		$this->set_usage_data( $usage_data );

		$settings = $this->get_settings( $provider::ID );

		/**
		 * Fires after AI usage has been updated from the API.
		 *
		 * @since 3.8.0
		 *
		 * @hook classifai_api_usage_updated
		 *
		 * @param array $usage_data Usage data.
		 * @param array $settings Settings.
		 * @param bool  $force_refresh Whether to force the refresh of the usage data.
		 */
		do_action( 'classifai_api_usage_updated', $usage_data, $settings ?? [], $force_refresh );

		$this->check_hard_threshold( $usage_data, $settings, $provider );
		$this->check_soft_threshold( $usage_data, $settings, $provider );

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
				return $usage_data['mtd'] ?? 0;
			case 'year_to_date':
				return $usage_data['ytd'] ?? 0;
			case 'all_time':
				return $usage_data['all_time'] ?? 0;
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
	 * @param array                 $usage_data Usage data.
	 * @param array                 $settings Settings.
	 * @param UsageTrackingProvider $provider Provider instance.
	 */
	private function check_soft_threshold( array $usage_data, array $settings, UsageTrackingProvider $provider ): void {
		$is_hard_limit_reached = get_option( self::HARD_LIMIT_REACHED_KEY, false );

		// If the hard limit is reached, and the hard threshold is enabled, return early.
		if ( $is_hard_limit_reached && ! empty( $settings['hard_threshold_enabled'] ) ) {
			return;
		}

		$sent_key = 'last_soft_alert_sent_for_period';

		if ( empty( $settings['soft_threshold_enabled'] ) || empty( $settings['soft_threshold_amount'] ) ) {
			// Remove the sent key from the pricing option if already set.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ $provider::ID => $settings ] );
			return;
		}

		$scope     = isset( $settings['soft_threshold_scope'] ) ? $settings['soft_threshold_scope'] : 'current_month';
		$amount    = $this->get_amount_for_scope( $usage_data, $scope );
		$threshold = (float) $settings['soft_threshold_amount'];

		if ( $amount < $threshold ) {
			// Remove the sent key from the pricing option if already set.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ $provider::ID => $settings ] );
			return;
		}

		/**
		 * Fires when the soft threshold is exceeded.
		 *
		 * @since 3.8.0
		 *
		 * @hook classifai_api_soft_threshold_exceeded
		 *
		 * @param UsageTrackingProvider $provider Provider instance.
		 * @param array                 $settings Updated pricing option.
		 * @param array                 $usage_data Usage data.
		 * @param float                 $amount Amount of usage for the scope.
		 */
		do_action( 'classifai_api_soft_threshold_exceeded', $provider, $settings, $usage_data, $amount );

		$emails = $this->get_email_list( $settings['soft_threshold_emails'] ?? '' );

		if ( empty( $emails ) ) {
			return;
		}

		$period_data  = $this->get_scope_period_data( $scope );
		$period_key   = $period_data['period_key'] ?? '';
		$period_label = $period_data['period_label'] ?? '';

		if ( isset( $settings[ $sent_key ] ) && $period_key === $settings[ $sent_key ] ) {
			return;
		}

		$subject = __( 'ClassifAI: AI usage exceeded soft limit', 'classifai' );
		$message = sprintf(
			/* translators: 1: amount, 2: currency, 3: period */
			__( 'AI usage has exceeded your soft limit of %1$s %2$s for this period (%3$s).', 'classifai' ),
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
		$this->update_settings( [ $provider::ID => $settings ] );
	}

	/**
	 * Checks hard threshold, sets option to disable Features, sends email.
	 *
	 * @param array                 $usage_data Usage data.
	 * @param array                 $settings Settings.
	 * @param UsageTrackingProvider $provider Provider instance.
	 */
	private function check_hard_threshold( array $usage_data, array $settings, UsageTrackingProvider $provider ): void {
		$sent_key = 'hard_alert_sent_for_period';

		if ( empty( $settings['hard_threshold_enabled'] ) || empty( $settings['hard_threshold_amount'] ) ) {
			delete_option( self::HARD_LIMIT_REACHED_KEY );

			// Remove the sent key from the pricing option.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ $provider::ID => $settings ] );
			return;
		}

		$scope     = isset( $settings['hard_threshold_scope'] ) ? $settings['hard_threshold_scope'] : 'current_month';
		$amount    = $this->get_amount_for_scope( $usage_data, $scope );
		$threshold = (float) $settings['hard_threshold_amount'];

		if ( $amount < $threshold ) {
			delete_option( self::HARD_LIMIT_REACHED_KEY );

			// Remove the sent key from the pricing option.
			unset( $settings[ $sent_key ] );
			$this->update_settings( [ $provider::ID => $settings ] );
			return;
		}

		update_option( self::HARD_LIMIT_REACHED_KEY, true );

		/**
		 * Fires when the hard threshold is exceeded.
		 *
		 * @since 3.8.0
		 *
		 * @hook classifai_api_hard_threshold_exceeded
		 *
		 * @param UsageTrackingProvider $provider Provider instance.
		 * @param array $settings Updated pricing option.
		 * @param array $usage_data Usage data.
		 * @param float $amount Amount of usage for the scope.
		 */
		do_action( 'classifai_api_hard_threshold_exceeded', $provider, $settings, $usage_data, $amount );

		$emails = $this->get_email_list( $settings['hard_threshold_emails'] ?? '' );

		if ( empty( $emails ) ) {
			return;
		}

		$period_data  = $this->get_scope_period_data( $scope );
		$period_key   = $period_data['period_key'] ?? '';
		$period_label = $period_data['period_label'] ?? '';

		if ( isset( $settings[ $sent_key ] ) && $period_key === $settings[ $sent_key ] ) {
			return;
		}

		$subject = __( 'ClassifAI: AI usage exceeded hard limit', 'classifai' );
		$message = sprintf(
			/* translators: 1: amount, 2: currency, 3: period */
			__( 'AI usage has exceeded your hard limit of %1$s %2$s for this period (%3$s). AI features have been disabled. Re-enable in ClassifAI → Usage Tracking → AI usage tracking.', 'classifai' ),
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
		$this->update_settings( [ $provider::ID => $settings ] );
	}

	/**
	 * Returns the period data for a given scope.
	 *
	 * @param string $scope The scope to get the period data for.
	 *
	 * @return array The period data.
	 */
	public function get_scope_period_data( string $scope ): array {
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

		return [
			'period_key'   => $period_key,
			'period_label' => $period_label,
		];
	}
}
