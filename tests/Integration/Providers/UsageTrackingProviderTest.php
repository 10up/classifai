<?php
/**
 * Tests for the UsageTrackingProvider base class.
 */

namespace Classifai\Tests\Providers;

use Classifai\Tests\TestCase;
use Classifai\Providers\UsageTrackingProvider;
use Classifai\Features\APIUsageTracking;

/**
 * Concrete subclass with a canned process_api_response_data() that drives
 * pagination from a queue, so fetch_period() can be tested deterministically.
 */
class FakeUsageTrackingProvider extends UsageTrackingProvider {

	const ID = 'openai_usage_tracking';

	protected static string $api_url = 'https://api.example-usage.test/costs';

	/**
	 * Queue of responses returned by process_api_response_data().
	 *
	 * @var array
	 */
	public $queued_responses = [];

	public function __construct( $feature_instance = null ) {
		$this->feature_instance = $feature_instance;
	}

	public static function get_provider_ids(): array {
		return [ self::ID ];
	}

	public function sanitize_settings( array $new_settings ): array {
		return $new_settings;
	}

	public function process_api_response_data( array $data ): array {
		return array_shift( $this->queued_responses ) ?? [
			'is_null_range' => false,
			'total_amount'  => 0.0,
			'currency'      => 'USD',
			'has_more'      => false,
			'next_page'     => null,
		];
	}
}

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\UsageTrackingProvider
 */
class UsageTrackingProviderTest extends TestCase {

	public function tear_down() {
		delete_option( 'classifai_api_usage_tracking' );
		parent::tear_down();
	}

	/**
	 * Enable the API usage tracking feature with an authenticated provider.
	 *
	 * @param string $api_key API key to store.
	 */
	private function enable_feature( string $api_key = 'sk-usage' ) {
		update_option(
			'classifai_api_usage_tracking',
			[
				'status'                => '1',
				'provider'              => 'openai_usage_tracking',
				'openai_usage_tracking' => [
					'api_key'       => $api_key,
					'authenticated' => true,
				],
			]
		);
	}

	/**
	 * @covers ::usage_api_args
	 */
	public function test_usage_api_args() {
		$provider = new FakeUsageTrackingProvider( new APIUsageTracking() );

		$first = $provider->usage_api_args( 100, 200, '1' );
		$this->assertSame( 100, $first['start_time'] );
		$this->assertSame( 200, $first['end_time'] );
		$this->assertSame( 180, $first['limit'] );
		$this->assertSame( '', $first['page'], 'First page sends an empty page param.' );

		$second = $provider->usage_api_args( 100, 200, '2' );
		$this->assertSame( '2', $second['page'], 'Subsequent pages send the encoded page token.' );
	}

	/**
	 * @covers ::fetch_period
	 */
	public function test_fetch_period_requires_enabled_feature() {
		update_option( 'classifai_api_usage_tracking', [ 'status' => '0' ] );

		$provider = new FakeUsageTrackingProvider( new APIUsageTracking() );
		$this->assertWPErrorCode( 'feature_not_enabled', $provider->fetch_period( 1, 2 ) );
	}

	/**
	 * @covers ::fetch_period
	 */
	public function test_fetch_period_requires_api_key() {
		$this->enable_feature( '' );

		$provider = new FakeUsageTrackingProvider( new APIUsageTracking() );
		$this->assertWPErrorCode( 'api_key_not_set', $provider->fetch_period( 1, 2 ) );
	}

	/**
	 * Pagination: page one reports has_more, page two is final; totals merge and
	 * both pages are requested.
	 *
	 * @covers ::fetch_period
	 */
	public function test_fetch_period_paginates_and_merges() {
		$this->enable_feature();

		$http_calls = 0;
		$mock       = function () use ( &$http_calls ) {
			++$http_calls;
			return [
				'response' => [ 'code' => 200 ],
				'headers'  => [ 'content-type' => 'application/json' ],
				'body'     => wp_json_encode( [ 'data' => [ 'page' ] ] ),
			];
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );

		$provider                   = new FakeUsageTrackingProvider( new APIUsageTracking() );
		$provider->queued_responses = [
			[
				'is_null_range' => false,
				'total_amount'  => 1.5,
				'currency'      => 'USD',
				'has_more'      => true,
				'next_page'     => '2',
			],
			[
				'is_null_range' => false,
				'total_amount'  => 2.5,
				'currency'      => 'USD',
				'has_more'      => false,
				'next_page'     => null,
			],
		];

		$result = $provider->fetch_period( 1000, 2000 );

		remove_filter( 'pre_http_request', $mock, 10 );

		$this->assertSame( 4.0, $result['amount'], 'Page totals are summed.' );
		$this->assertSame( 'USD', $result['currency'] );
		$this->assertSame( 2, $http_calls, 'Both pages were requested.' );
	}
}
