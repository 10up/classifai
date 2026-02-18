<?php

namespace Classifai\Admin;

use Classifai\Providers\UsageFetcherInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for provider usage tracking.
 *
 * Handles all shared behavior: cron scheduling, usage refresh, threshold
 * checking, email alerts, dashboard widget, admin notices, and REST endpoints.
 *
 * To add usage tracking for a new provider, extend this class and implement
 * the abstract methods. Then register an instance in Plugin::init_admin_helpers().
 *
 * @since 3.9.0
 */
abstract class UsageTracker {

	/**
	 * Default refresh interval in seconds (15 minutes).
	 */
	const DEFAULT_REFRESH_INTERVAL = ( 15 * MINUTE_IN_SECONDS );

	// =========================================================================
	// Abstract methods — subclasses must implement
	// =========================================================================

	/**
	 * Returns a machine-readable provider slug (e.g. 'openai').
	 *
	 * Drives option names, hook tags, widget IDs, and REST paths.
	 *
	 * @return string
	 */
	abstract public function get_provider_id(): string;

	/**
	 * Returns a human-readable provider label (e.g. 'OpenAI').
	 *
	 * Used in UI labels and notification emails.
	 *
	 * @return string
	 */
	abstract public function get_provider_label(): string;

	/**
	 * Creates and returns a fetcher for this provider.
	 *
	 * @param array $settings Full merged settings.
	 * @return UsageFetcherInterface
	 */
	abstract public function make_fetcher( array $settings ): UsageFetcherInterface;

	/**
	 * Verifies that the stored credentials can authenticate with the provider API.
	 *
	 * @param array $settings Full merged settings.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	abstract public function authenticate_credentials( array $settings );

	/**
	 * Returns provider-specific option defaults (credentials, IDs, etc.).
	 *
	 * Merged on top of the base defaults returned by get_base_option_defaults().
	 *
	 * @return array
	 */
	abstract public function get_provider_option_defaults(): array;

	/**
	 * Sanitizes raw POST data into a clean settings array.
	 *
	 * Should handle credential obfuscation checks and return either the
	 * sanitized array or WP_Error on validation failure.
	 *
	 * @param array $raw     Raw request data (from REST POST body).
	 * @param array $current Current stored settings.
	 * @return array|WP_Error
	 */
	abstract public function sanitize_settings( array $raw, array $current );

	// =========================================================================
	// Option / hook name helpers
	// =========================================================================

	/**
	 * Returns the WP option name for this provider's usage settings.
	 *
	 * @return string e.g. 'classifai_openai_usage'
	 */
	public function get_option_name(): string {
		return 'classifai_' . $this->get_provider_id() . '_usage';
	}

	/**
	 * Returns the Action Scheduler hook name for periodic refreshes.
	 *
	 * @return string e.g. 'classifai_openai_usage_refresh'
	 */
	public function get_cron_hook(): string {
		return 'classifai_' . $this->get_provider_id() . '_usage_refresh';
	}

	/**
	 * Returns the option name used to flag when the hard limit has been reached.
	 *
	 * @return string e.g. 'classifai_openai_hard_limit_reached'
	 */
	public function get_hard_limit_option(): string {
		return 'classifai_' . $this->get_provider_id() . '_hard_limit_reached';
	}

	// =========================================================================
	// Registration
	// =========================================================================

	/**
	 * Whether this tracker should register (admin and not legacy settings).
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return is_admin() && ! \Classifai\should_use_legacy_settings_panel();
	}

	/**
	 * Registers admin hooks: cron, dashboard widget, and styles.
	 *
	 * REST routes are registered separately via Plugin::register_usage_tracker_rest_routes()
	 * (hooked to rest_api_init from Plugin::init()) so they are available on all requests,
	 * not just admin page loads where this method runs.
	 */
	public function register(): void {
		$cron_hook = $this->get_cron_hook();

		add_action( 'init', [ $this, 'schedule_cron_if_needed' ] );
		add_action( $cron_hook, [ $this, 'run_usage_refresh' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_dashboard_styles' ] );

		// Register with the usage tracker registry so Notifications and other
		// consumers can iterate over all active trackers generically.
		add_filter(
			'classifai_registered_usage_trackers',
			function ( array $trackers ) {
				$trackers[ $this->get_provider_id() ] = $this;
				return $trackers;
			}
		);
	}

	// =========================================================================
	// Settings
	// =========================================================================

	/**
	 * Returns the base option defaults shared by all providers.
	 *
	 * @return array
	 */
	protected function get_base_option_defaults(): array {
		return [
			'enabled'                  => false,
			'refresh_interval_minutes' => ( self::DEFAULT_REFRESH_INTERVAL / MINUTE_IN_SECONDS ),
			'soft_threshold_enabled'   => false,
			'soft_threshold_amount'    => 0,
			'soft_threshold_scope'     => 'current_month',
			'soft_threshold_emails'    => '',
			'hard_threshold_amount'    => 0,
			'hard_threshold_scope'     => 'current_month',
			'hard_threshold_emails'    => '',
			'hard_threshold_enabled'   => false,
			'this_month_total'         => 0,
			'ytd_total'                => 0,
			'all_time_total'           => 0,
			'usage_currency'           => 'USD',
			'usage_last_updated'       => 0,
		];
	}

	/**
	 * Returns the full settings array, merging base and provider defaults.
	 *
	 * @param bool $suppress_filter When true, skip any provider-specific filters
	 *                              (e.g. credential injection). Use this when about
	 *                              to write back to the option.
	 * @return array
	 */
	public function get_settings( bool $suppress_filter = false ): array {
		$defaults = array_merge( $this->get_base_option_defaults(), $this->get_provider_option_defaults() );
		$option   = get_option( $this->get_option_name(), [] );
		return wp_parse_args( is_array( $option ) ? $option : [], $defaults );
	}

	/**
	 * Returns true when the settings contain the credentials needed to fetch usage.
	 *
	 * Override in subclasses to check provider-specific credential fields.
	 *
	 * @param array $settings Full merged settings.
	 * @return bool
	 */
	protected function has_required_credentials( array $settings ): bool {
		return true;
	}

	// =========================================================================
	// Cron scheduling
	// =========================================================================

	/**
	 * Schedules or unschedules the recurring Action Scheduler task.
	 */
	public function schedule_cron_if_needed(): void {
		if (
			! function_exists( 'as_schedule_recurring_action' )
			|| ! function_exists( 'as_unschedule_all_actions' )
			|| ! function_exists( 'as_has_scheduled_action' )
		) {
			return;
		}

		$settings = $this->get_settings();
		if ( empty( $settings['enabled'] ) || ! $this->has_required_credentials( $settings ) ) {
			\as_unschedule_all_actions( $this->get_cron_hook() );
			return;
		}

		$interval = $this->get_refresh_interval_seconds();
		if ( ! \as_has_scheduled_action( $this->get_cron_hook() ) ) {
			\as_schedule_recurring_action( time(), $interval, $this->get_cron_hook(), [], 'classifai' );
		}
	}

	/**
	 * Returns the refresh interval in seconds (filtered).
	 *
	 * @return int
	 */
	private function get_refresh_interval_seconds(): int {
		$settings = $this->get_settings();
		$minutes  = isset( $settings['refresh_interval_minutes'] ) ? absint( $settings['refresh_interval_minutes'] ) : 0;
		$seconds  = $minutes > 0 ? $minutes * MINUTE_IN_SECONDS : self::DEFAULT_REFRESH_INTERVAL;

		/**
		 * Filter the usage refresh interval in minutes for a specific provider.
		 *
		 * The dynamic portion of the hook name, `{provider_id}`, refers to the
		 * value returned by get_provider_id() (e.g. 'openai').
		 *
		 * @since 3.9.0
		 * @hook classifai_{provider_id}_usage_refresh_interval_minutes
		 * @param int $seconds Interval in seconds.
		 * @return int
		 */
		return (int) apply_filters(
			'classifai_' . $this->get_provider_id() . '_usage_refresh_interval_minutes',
			$seconds
		);
	}

	// =========================================================================
	// Usage refresh
	// =========================================================================

	/**
	 * Fetches fresh usage data, updates the cache, and checks thresholds.
	 *
	 * Called by Action Scheduler on the recurring cron hook, and by the REST
	 * endpoint when force_refresh is requested.
	 *
	 * @param bool $force Whether to bypass the all-time cache and re-fetch.
	 */
	public function run_usage_refresh( bool $force = false ): void {
		$settings = $this->get_settings( true );
		if ( empty( $settings['enabled'] ) || ! $this->has_required_credentials( $settings ) ) {
			return;
		}

		$fetcher     = $this->make_fetcher( $settings );
		$tz          = wp_timezone();
		$now         = new \DateTimeImmutable( 'now', $tz );
		$month_start = $now->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'm' ), 1 )->setTime( 0, 0, 0 );
		$year_start  = $now->setDate( (int) $now->format( 'Y' ), 1, 1 )->setTime( 0, 0, 0 );

		$this_month = $fetcher->fetch_period( $month_start->getTimestamp(), $now->getTimestamp() );
		$ytd        = $fetcher->fetch_period( $year_start->getTimestamp(), $now->getTimestamp() );
		$all_time   = $fetcher->fetch_all_time( $force, is_wp_error( $ytd ) ? null : $ytd, $settings );

		$currency       = 'USD';
		$this_month_val = 0.0;
		$ytd_val        = 0.0;
		$all_time_val   = 0.0;

		if ( ! is_wp_error( $this_month ) ) {
			$this_month_val = (float) $this_month['amount'];
			$currency       = $this_month['currency'] ?? $currency;
		}
		if ( ! is_wp_error( $ytd ) ) {
			$ytd_val  = (float) $ytd['amount'];
			$currency = $ytd['currency'] ?? $currency;
		}
		if ( ! is_wp_error( $all_time ) ) {
			$all_time_val = (float) $all_time['amount'];
			$currency     = $all_time['currency'] ?? $currency;
		}

		// Re-read without filter to get a clean base for writing back.
		$settings = $this->get_settings( true );

		$settings['this_month_total']   = $this_month_val;
		$settings['ytd_total']          = $ytd_val;
		$settings['all_time_total']     = $all_time_val;
		$settings['usage_currency']     = $currency;
		$settings['usage_last_updated'] = time();

		// Persist year-level data returned by the fetcher.
		if ( ! is_wp_error( $all_time ) ) {
			if ( isset( $all_time['all_year_pricing'] ) ) {
				$settings['all_year_pricing'] = $all_time['all_year_pricing'];
			}
			if ( isset( $all_time['api_start_year'] ) ) {
				$settings['api_start_year'] = $all_time['api_start_year'];
			}
		}

		update_option( $this->get_option_name(), $settings );

		$cached = [
			'this_month_total' => $this_month_val,
			'ytd_total'        => $ytd_val,
			'all_time_total'   => $all_time_val,
			'currency'         => $currency,
			'last_updated'     => time(),
		];

		/**
		 * Fires after provider usage has been refreshed from the API.
		 *
		 * The dynamic portion of the hook name, `{provider_id}`, refers to the
		 * value returned by get_provider_id() (e.g. 'openai').
		 *
		 * @since 3.9.0
		 * @hook classifai_{provider_id}_usage_updated
		 * @param array $usage Cached usage totals.
		 * @param array $raw   Reserved for future use.
		 */
		do_action( 'classifai_' . $this->get_provider_id() . '_usage_updated', $cached, [] );

		$this->check_soft_threshold( $settings );
		$this->check_hard_threshold( $settings );
	}

	// =========================================================================
	// Cached usage
	// =========================================================================

	/**
	 * Returns cached usage totals for display.
	 *
	 * @return array{ this_month_total: float, ytd_total: float, all_time_total: float, currency: string, last_updated: int }
	 */
	public function get_cached_usage(): array {
		$settings = $this->get_settings();
		return [
			'this_month_total' => (float) ( $settings['this_month_total'] ?? 0 ),
			'ytd_total'        => (float) ( $settings['ytd_total'] ?? 0 ),
			'all_time_total'   => (float) ( $settings['all_time_total'] ?? 0 ),
			'currency'         => $settings['usage_currency'] ?? 'USD',
			'last_updated'     => (int) ( $settings['usage_last_updated'] ?? 0 ),
		];
	}

	/**
	 * Returns the usage amount for a given scope.
	 *
	 * @param array  $settings Full settings array.
	 * @param string $scope    'current_month', 'year_to_date', or 'all_time'.
	 * @return float
	 */
	public function get_amount_for_scope( array $settings, string $scope ): float {
		switch ( $scope ) {
			case 'current_month':
				return (float) ( $settings['this_month_total'] ?? 0 );
			case 'year_to_date':
				return (float) ( $settings['ytd_total'] ?? 0 );
			case 'all_time':
				return (float) ( $settings['all_time_total'] ?? 0 );
			default:
				return 0.0;
		}
	}

	// =========================================================================
	// Threshold checking
	// =========================================================================

	/**
	 * Checks the soft threshold and sends an alert email once per period.
	 *
	 * @param array $settings Updated settings array (freshly written to option).
	 */
	private function check_soft_threshold( array $settings ): void {
		$option_name = $this->get_option_name();
		$sent_key    = 'last_soft_alert_sent_for_period';

		if ( empty( $settings['soft_threshold_enabled'] ) || empty( $settings['soft_threshold_amount'] ) ) {
			unset( $settings[ $sent_key ] );
			update_option( $option_name, $settings );
			return;
		}

		$scope     = $settings['soft_threshold_scope'] ?? 'current_month';
		$amount    = $this->get_amount_for_scope( $settings, $scope );
		$threshold = (float) $settings['soft_threshold_amount'];

		if ( $amount < $threshold ) {
			unset( $settings[ $sent_key ] );
			update_option( $option_name, $settings );
			return;
		}

		/**
		 * Fires when the soft threshold is exceeded.
		 *
		 * The dynamic portion of the hook name, `{provider_id}`, refers to
		 * get_provider_id() (e.g. 'openai').
		 *
		 * @since 3.9.0
		 * @hook classifai_{provider_id}_soft_threshold_exceeded
		 * @param array  $settings  Full settings.
		 * @param float  $amount    Current usage amount.
		 * @param float  $threshold Configured threshold.
		 * @param string $scope     Threshold scope.
		 */
		do_action(
			'classifai_' . $this->get_provider_id() . '_soft_threshold_exceeded',
			$settings,
			$amount,
			$threshold,
			$scope
		);

		$period_key = $this->get_period_key( $scope );

		if ( isset( $settings[ $sent_key ] ) && $period_key === $settings[ $sent_key ] ) {
			return;
		}

		$emails = $this->parse_email_list( $settings['soft_threshold_emails'] ?? '' );
		if ( empty( $emails ) ) {
			return;
		}

		$provider_label = $this->get_provider_label();
		/* translators: %s: provider label e.g. 'OpenAI' */
		$subject      = sprintf( __( 'ClassifAI: %s usage exceeded soft limit', 'classifai' ), $provider_label );
		$period_label = $this->get_period_label( $scope );
		$message      = sprintf(
			/* translators: 1: provider label, 2: formatted amount, 3: currency code, 4: period label */
			__( '%1$s usage has exceeded your soft limit of %2$s %3$s for this period (%4$s).', 'classifai' ),
			$provider_label,
			number_format_i18n( $threshold, 2 ),
			$settings['usage_currency'] ?? 'USD',
			$period_label
		);

		$bcc = 'Bcc: ' . implode( ', ', array_slice( $emails, 1 ) );
		wp_mail( $emails[0], $subject, $message, [ $bcc ] );

		$settings[ $sent_key ] = $period_key;
		update_option( $option_name, $settings );
	}

	/**
	 * Checks the hard threshold, sets the block flag, and sends an alert email.
	 *
	 * @param array $settings Updated settings array (freshly written to option).
	 */
	private function check_hard_threshold( array $settings ): void {
		$option_name    = $this->get_option_name();
		$hard_limit_opt = $this->get_hard_limit_option();
		$sent_key       = 'hard_alert_sent_for_period';

		if ( empty( $settings['hard_threshold_enabled'] ) || empty( $settings['hard_threshold_amount'] ) ) {
			delete_option( $hard_limit_opt );
			unset( $settings[ $sent_key ] );
			update_option( $option_name, $settings );
			return;
		}

		$scope     = $settings['hard_threshold_scope'] ?? 'current_month';
		$amount    = $this->get_amount_for_scope( $settings, $scope );
		$threshold = (float) $settings['hard_threshold_amount'];

		if ( $amount < $threshold ) {
			delete_option( $hard_limit_opt );
			unset( $settings[ $sent_key ] );
			update_option( $option_name, $settings );
			return;
		}

		update_option( $hard_limit_opt, true );

		/**
		 * Fires when the hard threshold is exceeded.
		 *
		 * The dynamic portion of the hook name, `{provider_id}`, refers to
		 * get_provider_id() (e.g. 'openai').
		 *
		 * @since 3.9.0
		 * @hook classifai_{provider_id}_hard_threshold_exceeded
		 * @param array  $settings  Full settings.
		 * @param float  $amount    Current usage amount.
		 * @param float  $threshold Configured threshold.
		 * @param string $scope     Threshold scope.
		 */
		do_action(
			'classifai_' . $this->get_provider_id() . '_hard_threshold_exceeded',
			$settings,
			$amount,
			$threshold,
			$scope
		);

		$period_key = $this->get_period_key( $scope );

		if ( isset( $settings[ $sent_key ] ) && $period_key === $settings[ $sent_key ] ) {
			return;
		}

		$emails = $this->parse_email_list( $settings['hard_threshold_emails'] ?? $settings['soft_threshold_emails'] ?? '' );
		if ( ! empty( $emails ) ) {
			$provider_label = $this->get_provider_label();
			/* translators: %s: provider label e.g. 'OpenAI' */
			$subject = sprintf( __( 'ClassifAI: %s usage exceeded hard limit', 'classifai' ), $provider_label );
			$message = sprintf(
				/* translators: 1: provider label, 2: formatted amount, 3: currency code */
				__( '%1$s usage has exceeded your hard limit of %2$s %3$s. %1$s features have been disabled. Re-enable in ClassifAI → Pricing.', 'classifai' ),
				$provider_label,
				number_format_i18n( $threshold, 2 ),
				$settings['usage_currency'] ?? 'USD'
			);
			wp_mail( $emails[0], $subject, $message, [ 'Bcc: ' . implode( ', ', array_slice( $emails, 1 ) ) ] );
		}

		$settings[ $sent_key ] = $period_key;
		update_option( $option_name, $settings );
	}

	/**
	 * Returns a period key string used to deduplicate alert emails.
	 *
	 * @param string $scope 'current_month', 'year_to_date', or 'all_time'.
	 * @return string
	 */
	private function get_period_key( string $scope ): string {
		switch ( $scope ) {
			case 'current_month':
				return gmdate( 'Y-m' );
			case 'year_to_date':
				// e.g. '2026-01-2026-02' for Feb 2026.
				return gmdate( 'Y' ) . '-01-' . gmdate( 'Y-m' );
			case 'all_time':
				// e.g. 'all-2026-02'.
				return 'all-' . gmdate( 'Y-m' );
			default:
				return '';
		}
	}

	/**
	 * Returns a human-readable period label for email messages.
	 *
	 * @param string $scope 'current_month', 'year_to_date', or 'all_time'.
	 * @return string
	 */
	private function get_period_label( string $scope ): string {
		switch ( $scope ) {
			case 'current_month':
				return __( 'current month', 'classifai' );
			case 'year_to_date':
				return __( 'year to date', 'classifai' );
			case 'all_time':
				return __( 'all time', 'classifai' );
			default:
				return '';
		}
	}

	/**
	 * Parses a comma/newline-separated string into valid email addresses.
	 *
	 * @param string $input Raw input string.
	 * @return string[]
	 */
	protected function parse_email_list( string $input ): array {
		$input = sanitize_textarea_field( $input );
		$parts = preg_split( '/[\s,]+/', $input, -1, PREG_SPLIT_NO_EMPTY );
		$out   = [];
		foreach ( $parts as $part ) {
			$email = sanitize_email( trim( $part ) );
			if ( is_email( $email ) ) {
				$out[] = $email;
			}
		}
		return $out;
	}

	// =========================================================================
	// Dashboard widget
	// =========================================================================

	/**
	 * Registers the dashboard widget when tracking is enabled and configured.
	 */
	public function register_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_settings();
		if ( empty( $settings['enabled'] ) || ! $this->has_required_credentials( $settings ) ) {
			return;
		}

		$provider_id    = $this->get_provider_id();
		$provider_label = $this->get_provider_label();

		wp_add_dashboard_widget(
			'classifai_' . $provider_id . '_usage',
			/* translators: %s: provider label e.g. 'OpenAI' */
			sprintf( __( '%s usage (ClassifAI)', 'classifai' ), $provider_label ),
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
		$usage          = $this->get_cached_usage();
		$currency       = $usage['currency'];
		$provider_label = $this->get_provider_label();
		$fmt            = function ( $val ) use ( $currency ) {
			return number_format_i18n( $val, 2 ) . ' ' . $currency;
		};

		echo '<p class="classifai-usage-disclaimer">';
		echo esc_html(
			sprintf(
				/* translators: %s: provider label e.g. 'OpenAI' */
				__( 'Usage and costs shown here are from the %s API for this project/site. If you use the same API key or project elsewhere, this data does not represent only ClassifAI.', 'classifai' ),
				$provider_label
			)
		);
		echo '</p>';
		echo '<ul class="classifai-usage-list">';
		echo '<li><strong>' . esc_html__( 'This month', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['this_month_total'] ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Year to date', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['ytd_total'] ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'All time', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['all_time_total'] ) ) . '</li>';
		echo '</ul>';
		if ( 0 < $usage['last_updated'] ) {
			echo '<p class="classifai-usage-updated">';
			$date_format      = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$last_updated_str = wp_date( $date_format, $usage['last_updated'] );
			echo esc_html(
				sprintf(
					/* translators: %s: formatted date/time */
					__( 'Last updated: %s', 'classifai' ),
					$last_updated_str
				)
			);
			echo '</p>';
		} else {
			echo '<p class="classifai-usage-updated">' . esc_html__( 'Updating…', 'classifai' ) . '</p>';
		}
		$settings_url = admin_url( 'tools.php?page=classifai#/pricing' );
		echo '<p><a href="' . esc_url( $settings_url ) . '" class="button button-secondary">' . esc_html__( 'Configure alerts', 'classifai' ) . '</a></p>';
	}

	/**
	 * Enqueues minimal inline styles for the dashboard widget.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function maybe_enqueue_dashboard_styles( string $hook_suffix ): void {
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}
		$css = '.classifai-usage-disclaimer { margin-bottom: 1em; font-size: 12px; color: #646970; } .classifai-usage-list { margin: 0.5em 0; }';
		wp_add_inline_style( 'wp-admin', $css );
	}

	// =========================================================================
	// REST routes
	// =========================================================================

	/**
	 * Registers REST routes for this provider's usage tracker.
	 *
	 * Routes registered:
	 *   GET  /classifai/v1/usage/{provider_id}          — cached totals
	 *   GET  /classifai/v1/usage/{provider_id}/settings — full config (credentials obfuscated)
	 *   POST /classifai/v1/usage/{provider_id}/settings — save settings or force refresh
	 */
	public function register_routes(): void {
		$provider_id = $this->get_provider_id();
		$namespace   = 'classifai/v1';

		register_rest_route(
			$namespace,
			'usage/' . $provider_id,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_usage_callback' ],
					'permission_callback' => [ $this, 'usage_permissions_check' ],
				],
			]
		);

		register_rest_route(
			$namespace,
			'usage/' . $provider_id . '/settings',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings_callback' ],
					'permission_callback' => [ $this, 'usage_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings_callback' ],
					'permission_callback' => [ $this, 'usage_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Permission check for all usage REST endpoints.
	 *
	 * @return bool
	 */
	public function usage_permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /classifai/v1/usage/{provider_id}
	 *
	 * @return \WP_REST_Response
	 */
	public function get_usage_callback(): \WP_REST_Response {
		return rest_ensure_response( $this->get_cached_usage() );
	}

	/**
	 * GET /classifai/v1/usage/{provider_id}/settings
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings_callback(): \WP_REST_Response {
		$settings           = $this->get_settings( true );
		$hard_limit_reached = (bool) get_option( $this->get_hard_limit_option(), false );
		$settings           = $this->prepare_settings_for_api( $settings );

		return rest_ensure_response(
			[
				'pricing'            => $settings,
				'hard_limit_reached' => $hard_limit_reached,
			]
		);
	}

	/**
	 * POST /classifai/v1/usage/{provider_id}/settings
	 *
	 * Accepts either { force_refresh: true } or { pricing: { ... } }.
	 *
	 * @param \WP_REST_Request $request Full request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_settings_callback( \WP_REST_Request $request ) {
		$params  = $request->get_json_params();
		$current = $this->get_settings( true );

		if ( isset( $params['force_refresh'] ) && true === $params['force_refresh'] ) {
			$this->run_usage_refresh( true );
			return rest_ensure_response( [ 'success' => true ] );
		}

		$raw = $params['pricing'] ?? [];
		if ( ! is_array( $raw ) ) {
			return new \WP_Error(
				'invalid_pricing',
				__( 'Invalid pricing data.', 'classifai' ),
				[ 'status' => 400 ]
			);
		}

		$new = $this->sanitize_settings( $raw, $current );
		if ( is_wp_error( $new ) ) {
			return rest_ensure_response( $new );
		}

		$authenticated = $this->authenticate_credentials( $new );
		if ( is_wp_error( $authenticated ) && 429 !== (int) $authenticated->get_error_code() ) {
			$error_message = $authenticated->get_error_message();

			// For incorrect API key the error body is nested.
			if ( is_array( $error_message ) && ! empty( $error_message['body'] ) ) {
				$error_message = json_decode( $error_message['body'], true );
				$error_message = empty( $error_message['error']['message'] ) ? '' : $error_message['error']['message'];
			}

			delete_option( $this->get_option_name() );

			return rest_ensure_response(
				new \WP_Error( 'authentication_failed', $error_message, [ 'status' => 400 ] )
			);
		}

		update_option( $this->get_option_name(), $new );
		$this->schedule_cron_if_needed();

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Prepares settings for the GET settings API response.
	 *
	 * Override in subclasses to obfuscate credentials or strip sensitive fields
	 * before sending them to the browser.
	 *
	 * @param array $settings Full settings (already read with suppress_filter=true).
	 * @return array
	 */
	protected function prepare_settings_for_api( array $settings ): array {
		return $settings;
	}
}
