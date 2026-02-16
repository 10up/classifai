<?php

namespace Classifai\Admin;

use Classifai\Providers\OpenAI\UsageCosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls OpenAI usage/cost fetching, caching, dashboard widget, and soft/hard thresholds.
 */
class OpenAIPricingController {

	const OPTION_NAME = 'classifai_openai_pricing';

	const CRON_HOOK = 'classifai_openai_usage_refresh';

	const HARD_LIMIT_OPTION = 'classifai_openai_hard_limit_reached';

	/**
	 * Default refresh interval in minutes.
	 *
	 * @var int
	 */
	const DEFAULT_REFRESH_INTERVAL = ( 15 * MINUTE_IN_SECONDS );

	/**
	 * Whether this controller can register (admin and not legacy settings).
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return is_admin() && ! \Classifai\should_use_legacy_settings_panel();
	}

	/**
	 * Registers hooks and cron.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'schedule_cron_if_needed' ] );
		add_action( self::CRON_HOOK, [ $this, 'run_usage_refresh' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_dashboard_styles' ] );
	}

	/**
	 * Schedules the next cron run when the option exists and we have an admin key.
	 */
	public function schedule_cron_if_needed(): void {
		// We return early if action scheduler is not active.
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		$pricing = $this->get_pricing_option();
		if ( empty( $pricing['enabled'] ) || empty( $pricing['admin_api_key'] ) ) {
			\as_unschedule_all_actions( self::CRON_HOOK );
			return;
		}

		$interval = $this->get_refresh_interval_minutes();
		if ( ! \as_has_scheduled_action( self::CRON_HOOK ) ) {
			\as_schedule_recurring_action( time(), $interval, self::CRON_HOOK, [], 'classifai' );
		}
	}

	/**
	 * Gets the refresh interval in minutes (filtered).
	 *
	 * @return int
	 */
	private function get_refresh_interval_minutes(): int {
		$pricing = $this->get_pricing_option();
		$default = isset( $pricing['refresh_interval_minutes'] ) ? absint( $pricing['refresh_interval_minutes'] ) * MINUTE_IN_SECONDS : self::DEFAULT_REFRESH_INTERVAL;

		if ( $default < 1 ) {
			$default = self::DEFAULT_REFRESH_INTERVAL;
		}
		/**
		 * Filter the OpenAI usage refresh interval in minutes.
		 *
		 * @since 3.x.x
		 * @hook classifai_openai_usage_refresh_interval_minutes
		 * @param int $interval_minutes Interval in minutes (default 15).
		 * @return int
		 */
		return (int) apply_filters( 'classifai_openai_usage_refresh_interval_minutes', $default );
	}

	/**
	 * Runs the usage refresh: fetch costs, cache, check thresholds.
	 *
	 * @param bool $force Whether to force refresh the data.
	 *
	 * @return void
	 */
	public function run_usage_refresh( bool $force = false ): void {
		$pricing = $this->get_pricing_option();
		if ( empty( $pricing['enabled'] ) || empty( $pricing['admin_api_key'] ) ) {
			return;
		}

		$usage = new UsageCosts(
			$pricing['admin_api_key'],
			isset( $pricing['project_id'] ) ? $pricing['project_id'] : ''
		);

		$tz          = wp_timezone();
		$now         = new \DateTimeImmutable( 'now', $tz );
		$month_start = $now->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'm' ), 1 )->setTime( 0, 0, 0 );
		$year_start  = $now->setDate( (int) $now->format( 'Y' ), 1, 1 )->setTime( 0, 0, 0 );

		$this_month = $usage->fetch_costs( $month_start->getTimestamp(), $now->getTimestamp() );
		$ytd        = $usage->fetch_costs( $year_start->getTimestamp(), $now->getTimestamp() );
		$all_time   = $usage->fetch_all_time_costs( $force, is_wp_error( $ytd ) ? null : $ytd );

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

		$cached = [
			'this_month_total' => $this_month_val,
			'ytd_total'        => $ytd_val,
			'all_time_total'   => $all_time_val,
			'currency'         => $currency,
			'last_updated'     => time(),
		];

		// Update the cached pricing option.
		$pricing = $this->get_pricing_option();

		$pricing['this_month_total']   = $this_month_val;
		$pricing['ytd_total']          = $ytd_val;
		$pricing['all_time_total']     = $all_time_val;
		$pricing['usage_currency']     = $currency;
		$pricing['usage_last_updated'] = time();
		update_option( self::OPTION_NAME, $pricing );

		/**
		 * Fires after OpenAI usage has been updated from the API.
		 *
		 * @since 3.x.x
		 * @hook classifai_openai_usage_updated
		 * @param array    $usage Cached usage (this_month_total, ytd_total, all_time_total, currency, last_updated).
		 * @param array    $raw   Summary of raw API responses (optional).
		 */
		do_action( 'classifai_openai_usage_updated', $cached, [] );

		$this->check_soft_threshold( $pricing );
		$this->check_hard_threshold( $pricing );
	}

	/**
	 * Gets the full pricing option (settings + cached usage).
	 *
	 * @return array
	 */
	public function get_pricing_option(): array {
		$defaults = [
			'enabled'                  => false,
			'admin_api_key'            => '',
			'project_id'               => '',
			'refresh_interval_minutes' => ( self::DEFAULT_REFRESH_INTERVAL / MINUTE_IN_SECONDS ),
			'soft_threshold_enabled'   => false,
			'soft_threshold_amount'    => 0,
			'soft_threshold_scope'     => 'current_month',
			'soft_threshold_emails'    => '',
			'hard_threshold_amount'    => 0,
			'hard_threshold_scope'     => 'current_month',
			'hard_threshold_emails'    => '',
			'hard_threshold_enabled'   => false,
			'api_start_year'           => 2020,
			'all_year_pricing'         => [],
			'this_month_total'         => 0,
			'ytd_total'                => 0,
			'all_time_total'           => 0,
			'usage_currency'           => 'USD',
			'usage_last_updated'       => 0,
		];
		$option   = get_option( self::OPTION_NAME, [] );

		$admin_api_key = apply_filters( 'classifai_openai_admin_api_key', $option['admin_api_key'] ?? '', $option );

		if ( ! empty( $admin_api_key ) ) {
			$option['admin_api_key'] = $admin_api_key;
		}

		return wp_parse_args( is_array( $option ) ? $option : [], $defaults );
	}

	/**
	 * Gets cached usage for display (filtered).
	 *
	 * @return array
	 */
	public function get_cached_usage(): array {
		$pricing = $this->get_pricing_option();
		$usage   = [
			'this_month_total' => (float) ( $pricing['this_month_total'] ?? 0 ),
			'ytd_total'        => (float) ( $pricing['ytd_total'] ?? 0 ),
			'all_time_total'   => (float) ( $pricing['all_time_total'] ?? 0 ),
			'currency'         => isset( $pricing['usage_currency'] ) ? $pricing['usage_currency'] : 'USD',
			'last_updated'     => isset( $pricing['usage_last_updated'] ) ? (int) $pricing['usage_last_updated'] : 0,
		];

		/**
		 * Filter cached OpenAI usage before display.
		 *
		 * @since 3.x.x
		 * @hook classifai_openai_cached_usage
		 * @param array $usage Cached usage array.
		 * @return array
		 */
		return apply_filters( 'classifai_openai_cached_usage', $usage );
	}

	/**
	 * Returns the amount to compare for threshold based on scope.
	 *
	 * @param array  $pricing Full pricing option.
	 * @param string $scope   'current_month' or 'last_n_days'.
	 * @return float
	 */
	public function get_amount_for_scope( array $pricing, string $scope ): float {
		switch ( $scope ) {
			case 'current_month':
				return $pricing['this_month_total'];
			case 'year_to_date':
				return $pricing['ytd_total'];
			case 'all_time':
				return $pricing['all_time_total'];
			default:
				return 0;
		}
	}

	/**
	 * Checks soft threshold and sends email once per period.
	 *
	 * @param array $pricing Updated pricing option.
	 */
	private function check_soft_threshold( array $pricing ): void {
		if ( empty( $pricing['soft_threshold_enabled'] ) || empty( $pricing['soft_threshold_amount'] ) ) {
			return;
		}

		$scope     = isset( $pricing['soft_threshold_scope'] ) ? $pricing['soft_threshold_scope'] : 'current_month';
		$amount    = $this->get_amount_for_scope( $pricing, $scope );
		$threshold = (float) $pricing['soft_threshold_amount'];
		$sent_key  = 'last_soft_alert_sent_for_period';

		if ( $amount < $threshold ) {
			// Remove the sent key from the pricing option if already set.
			unset( $pricing[ $sent_key ] );
			update_option( self::OPTION_NAME, $pricing );
			return;
		}

		$period_key = '';

		// Period key is used to track the last sent alert for a period. Sent only once a month.
		switch ( $scope ) {
			case 'current_month':
				$period_key = gmdate( 'Y-m' );
				break;
			case 'year_to_date':
				// i.e 2026-01-2026-02 for feb month.
				$period_key = gmdate( 'Y' ) . '-01-' . gmdate( 'Y-m' );
				break;
			case 'all_time':
				// i.e all-2026-02 for current year feb month.
				$period_key = 'all-' . gmdate( 'Y-m' );
				break;
		}

		if ( isset( $pricing[ $sent_key ] ) && $period_key === $pricing[ $sent_key ] ) {
			return;
		}

		$emails = $this->parse_email_list( $pricing['soft_threshold_emails'] ?? '' );
		if ( empty( $emails ) ) {
			return;
		}

		$subject = __( 'ClassifAI: OpenAI usage exceeded soft limit', 'classifai' );
		/* translators: 1: amount, 2: currency, 3: period description */
		$period_label = '';
		switch ( $scope ) {
			case 'current_month':
				$period_label = __( 'current month', 'classifai' );
				break;
			case 'year_to_date':
				$period_label = __( 'year to date', 'classifai' );
				break;
			case 'all_time':
				$period_label = __( 'all time', 'classifai' );
				break;
		}

		$message = sprintf(
			/* translators: 1: amount, 2: currency, 3: period */
			__( 'OpenAI usage has exceeded your soft limit of %1$s %2$s for this period (%3$s).', 'classifai' ),
			number_format_i18n( $threshold, 2 ),
			$pricing['usage_currency'],
			$period_label
		);
		$bcc = 'Bcc: ' . implode( ', ', array_slice( $emails, 1 ) );
		wp_mail( $emails[0], $subject, $message, [ $bcc ] );

		$pricing[ $sent_key ] = $period_key;
		update_option( self::OPTION_NAME, $pricing );
	}

	/**
	 * Checks hard threshold, sets option to disable features, sends email.
	 *
	 * @param array $pricing Updated pricing option.
	 */
	private function check_hard_threshold( array $pricing ): void {
		if ( empty( $pricing['hard_threshold_enabled'] ) || empty( $pricing['hard_threshold_amount'] ) ) {
			return;
		}

		$scope     = isset( $pricing['hard_threshold_scope'] ) ? $pricing['hard_threshold_scope'] : 'current_month';
		$amount    = $this->get_amount_for_scope( $pricing, $scope );
		$threshold = (float) $pricing['hard_threshold_amount'];
		$sent_key  = 'hard_alert_sent_for_period';

		if ( $amount < $threshold ) {
			delete_option( self::HARD_LIMIT_OPTION );

			// Remove the sent key from the pricing option.
			unset( $pricing[ $sent_key ] );
			update_option( self::OPTION_NAME, $pricing );
			return;
		}

		update_option( self::HARD_LIMIT_OPTION, true );

		$period_key = '';
		// Period key is used to track the last sent alert for a period. Sent only once a month.
		switch ( $scope ) {
			case 'current_month':
				$period_key = gmdate( 'Y-m' );
				break;
			case 'year_to_date':
				// i.e 2026-01-2026-02 for feb month.
				$period_key = gmdate( 'Y' ) . '-01-' . gmdate( 'Y-m' );
				break;
			case 'all_time':
				// i.e all-2026-02 for feb month.
				$period_key = 'all-' . gmdate( 'Y-m' );
				break;
		}

		if ( isset( $pricing[ $sent_key ] ) && $period_key === $pricing[ $sent_key ] ) {
			return;
		}

		$emails = $this->parse_email_list( $pricing['hard_threshold_emails'] ?? $pricing['soft_threshold_emails'] ?? '' );
		if ( ! empty( $emails ) ) {
			$subject = __( 'ClassifAI: OpenAI usage exceeded hard limit', 'classifai' );
			$message = sprintf(
				/* translators: 1: amount, 2: currency */
				__( 'OpenAI usage has exceeded your hard limit of %1$s %2$s. OpenAI features have been disabled. Re-enable in ClassifAI → Pricing.', 'classifai' ),
				number_format_i18n( $threshold, 2 ),
				$pricing['usage_currency']
			);
			wp_mail( $emails[0], $subject, $message, [ 'Bcc: ' . implode( ', ', array_slice( $emails, 1 ) ) ] );
		}

		$pricing[ $sent_key ] = $period_key;
		update_option( self::OPTION_NAME, $pricing );
	}

	/**
	 * Parses a comma/newline list into valid email addresses.
	 *
	 * @param string $input Raw input.
	 * @return array List of email strings.
	 */
	private function parse_email_list( string $input ): array {
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

	/**
	 * Registers the dashboard widget when configured.
	 */
	public function register_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pricing = $this->get_pricing_option();
		if ( empty( $pricing['enabled'] ) || empty( $pricing['admin_api_key'] ) ) {
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
		$usage    = $this->get_cached_usage();
		$currency = $usage['currency'];
		$fmt      = function ( $val ) use ( $currency ) {
			return number_format_i18n( $val, 2 ) . ' ' . $currency;
		};

		echo '<p class="classifai-openai-usage-disclaimer">';
		esc_html_e( 'Usage and costs shown here are from the OpenAI API for this project/site. If you use the same API key or project elsewhere, this data does not represent only ClassifAI.', 'classifai' );
		echo '</p>';
		echo '<ul class="classifai-openai-usage-list">';
		echo '<li><strong>' . esc_html__( 'This month', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['this_month_total'] ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Year to date', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['ytd_total'] ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'All time', 'classifai' ) . ':</strong> ' . esc_html( $fmt( $usage['all_time_total'] ) ) . '</li>';
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
		$settings_url = admin_url( 'tools.php?page=classifai#/pricing' );
		echo '<p><a href="' . esc_url( $settings_url ) . '" class="button button-secondary">' . esc_html__( 'Configure alerts', 'classifai' ) . '</a></p>';
	}

	/**
	 * Enqueues minimal styles for the dashboard widget if on dashboard.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function maybe_enqueue_dashboard_styles( string $hook_suffix ): void {
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}
		$css = '.classifai-openai-usage-disclaimer { margin-bottom: 1em; font-size: 12px; color: #646970; } .classifai-openai-usage-list { margin: 0.5em 0; }';
		wp_add_inline_style( 'wp-admin', $css );
	}
}
