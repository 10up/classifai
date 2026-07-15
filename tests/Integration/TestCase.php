<?php
/**
 * Shared base test case for ClassifAI integration tests.
 */

namespace Classifai\Tests;

use WP_UnitTestCase;

/**
 * Base test case providing HTTP mocking, fixture loading and settings/user
 * helpers shared across the integration suite.
 */
abstract class TestCase extends WP_UnitTestCase {

	/**
	 * Closures registered on `pre_http_request` that must be removed on tear down.
	 *
	 * @var callable[]
	 */
	private $http_filters = [];

	/**
	 * Whether the e2e fixture filter is currently registered.
	 *
	 * @var bool
	 */
	private $e2e_fixtures_loaded = false;

	/**
	 * Tear down: remove any HTTP filters registered during the test.
	 */
	public function tear_down() {
		foreach ( $this->http_filters as $filter ) {
			remove_filter( 'pre_http_request', $filter, 10 );
		}
		$this->http_filters = [];

		if ( $this->e2e_fixtures_loaded ) {
			remove_filter( 'pre_http_request', 'classifai_test_mock_http_requests', 10 );
			$this->e2e_fixtures_loaded = false;
		}

		parent::tear_down();
	}

	/**
	 * Register a `pre_http_request` filter returning a canned response.
	 *
	 * The filter is automatically removed in tear_down().
	 *
	 * @param array|\WP_Error $response      Response to return (WP HTTP response array or WP_Error).
	 * @param string          $url_substring Only respond when the request URL contains this substring. Empty matches all.
	 * @return callable The registered filter (so it can be removed early if needed).
	 */
	protected function mock_http( $response, string $url_substring = '' ): callable {
		$filter = function ( $preempt, $parsed_args, $url ) use ( $response, $url_substring ) {
			if ( '' === $url_substring || false !== strpos( (string) $url, $url_substring ) ) {
				return $response;
			}
			return $preempt;
		};

		add_filter( 'pre_http_request', $filter, 10, 3 );
		$this->http_filters[] = $filter;

		return $filter;
	}

	/**
	 * Normalize a body string into the WP HTTP response shape with a status code.
	 *
	 * @param string $body Response body.
	 * @param int    $code HTTP status code.
	 * @return array
	 */
	protected function http_response( string $body, int $code = 200 ): array {
		return [
			'headers'     => [],
			'cookies'     => [],
			'filename'    => null,
			'response'    => [
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Error',
			],
			'status_code' => $code,
			'success'     => $code >= 200 && $code < 300 ? 1 : 0,
			'body'        => $body,
		];
	}

	/**
	 * Load the e2e test plugin's HTTP mock so provider fixtures are returned for
	 * real provider URLs. Removed automatically in tear_down().
	 */
	protected function load_e2e_fixtures() {
		$plugin = dirname( __DIR__ ) . '/test-plugin/e2e-test-plugin.php';

		if ( ! function_exists( 'classifai_test_mock_http_requests' ) ) {
			require_once $plugin;
		}

		add_filter( 'pre_http_request', 'classifai_test_mock_http_requests', 10, 3 );
		$this->e2e_fixtures_loaded = true;
	}

	/**
	 * Decode a JSON fixture from the test plugin directory.
	 *
	 * @param string $name Fixture name, with or without the `.json` extension.
	 * @return mixed Decoded fixture (associative array) or null on failure.
	 */
	protected function get_fixture( string $name ) {
		$name = preg_replace( '/\.json$/', '', $name );
		$path = dirname( __DIR__ ) . '/test-plugin/' . $name . '.json';

		if ( ! file_exists( $path ) ) {
			$this->fail( "Fixture not found: {$path}" );
		}

		return json_decode( (string) file_get_contents( $path ), true );
	}

	/**
	 * Write a feature's `classifai_{ID}` option with overrides merged over defaults.
	 *
	 * @param string|object $feature_class Feature class name or instance.
	 * @param array         $overrides     Settings to merge over the feature defaults.
	 * @return array The settings written.
	 */
	protected function set_feature_settings( $feature_class, array $overrides ): array {
		$feature = is_object( $feature_class ) ? $feature_class : new $feature_class();

		// Feature::get_default_settings() is protected; reach it via reflection.
		$method = new \ReflectionMethod( $feature, 'get_default_settings' );
		$method->setAccessible( true );
		$defaults = $method->invoke( $feature );

		$settings = array_replace_recursive( $defaults, $overrides );

		update_option( $feature->get_option_name(), $settings );

		return $settings;
	}

	/**
	 * Create a user with the given role and make it the current user.
	 *
	 * @param string $role WordPress role slug.
	 * @return int Created user ID.
	 */
	protected function as_user_with_role( string $role ): int {
		$user_id = $this->factory->user->create( [ 'role' => $role ] );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Assert the value is a WP_Error with the expected error code.
	 *
	 * @param string $code   Expected error code.
	 * @param mixed  $actual Value under test.
	 */
	protected function assertWPErrorCode( string $code, $actual ) {
		$this->assertWPError( $actual );
		$this->assertSame( $code, $actual->get_error_code() );
	}
}
