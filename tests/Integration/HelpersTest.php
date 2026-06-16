<?php

namespace Classifai;

use Classifai\Features\Classification;

use function Classifai\Providers\Watson\get_username;
use function Classifai\Providers\Watson\get_password;
use function Classifai\Providers\Watson\get_feature_threshold;
use function Classifai\get_classification_feature_taxonomy;
use function Classifai\get_url_slugs;
use function Classifai\get_last_url_slug;

/**
 * @group helpers
 */
class HelpersTest extends \WP_UnitTestCase {

	/**
	 * Provides a Feature instance.
	 *
	 * @return Classification
	 */
	public function get_feature_class() : Classification {
		return new Classification();
	}

	/**
	 * Set up method.
	 */
	public function set_up() {
		register_post_status( 'unread', array(
			'label'                     => _x( 'Unread', 'post' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Unread <span class="count">(%s)</span>', 'Unread <span class="count">(%s)</span>' ),
		) );

		parent::set_up();
	}

	/**
	 * Tear down method.
	 */
	public function tear_down() {
		$this->remove_added_uploads();
		parent::tear_down();
	}

	function test_it_has_a_plugin_instance() {
		$actual = get_plugin();
		$this->assertInstanceOf( '\Classifai\Plugin', $actual );
	}

	function test_it_has_default_supported_post_types() {
		$actual = $this->get_feature_class()->get_supported_post_types();
		$this->assertEquals( ['post'], $actual );
	}

	function test_it_can_lookup_supported_post_types_from_option() {
		update_option(
			'classifai_feature_classification',
			[ 'post_types' => [ 'post' => 'post', 'page' => 'page' ] ]
		);

		$actual = $this->get_feature_class()->get_supported_post_types();
		$this->assertEquals( [ 'post', 'page' ], $actual );
	}

	function test_it_can_override_supported_post_types_with_filter() {
		add_filter( 'classifai_feature_classification_post_types', function() {
			return [ 'page' ];
		} );

		$actual = $this->get_feature_class()->get_supported_post_types();
		$this->assertEquals( [ 'page' ], $actual );
	}

	function test_it_has_feature_thresholds() {
		update_option(
			'classifai_feature_classification',
			[ 'category_threshold' => 50 ]
		);

		$actual = get_feature_threshold( 'category' );
		$this->assertEquals( 0.50, $actual );
	}

	function test_it_can_change_plugin_settings() {
		update_option(
			'classifai_feature_classification',
			[ 'category_threshold' => 25 ]
		);

		$actual = get_feature_threshold( 'category' );
		$this->assertEquals( 0.25, $actual );
	}

	function test_it_knows_configured_username() {
		update_option(
			'classifai_feature_classification',
			[ 'ibm_watson_nlu' => [ 'username' => 'foo' ] ]
		);

		$actual = get_username();
		$this->assertEquals( 'foo', $actual );
	}

	function test_it_knows_configured_password() {
		update_option(
			'classifai_feature_classification',
			[ 'ibm_watson_nlu' => [ 'password' => 'foo' ] ]
		);

		$actual = get_password();
		$this->assertEquals( 'foo', $actual );
	}

	function test_it_has_default_feature_taxonomies() {
		$expected = [
			'category' => WATSON_CATEGORY_TAXONOMY,
			'keyword'  => WATSON_KEYWORD_TAXONOMY,
			'concept'  => WATSON_CONCEPT_TAXONOMY,
			'entity'   => WATSON_ENTITY_TAXONOMY,
		];

		foreach ( $expected as $feature => $taxonomy ) {
			$actual = get_classification_feature_taxonomy( $feature );
			$this->assertEquals( $taxonomy, $actual );
		}
	}

	function test_it_knows_configured_feature_taxonomies() {
		// Custom taxonomies are only honored when the IBM Watson NLU provider is
		// selected; other providers force the taxonomy to the feature name.
		update_option(
			'classifai_feature_classification',
			[
				'provider'          => 'ibm_watson_nlu',
				'category_taxonomy' => 'a',
				'keyword_taxonomy'  => 'b',
				'concept_taxonomy'  => 'c',
				'entity_taxonomy'   => 'd',
			]
		);

		$expected = [
			'category' => 'a',
			'keyword'  => 'b',
			'concept'  => 'c',
			'entity'   => 'd',
		];

		foreach ( $expected as $feature => $taxonomy ) {
			$actual = get_classification_feature_taxonomy( $feature );
			$this->assertEquals( $taxonomy, $actual );
		}
	}

	/**
	 * @covers \Classifai\sort_images_by_size_cb
	 */
	public function test_sort_images_by_size_cb() {
		$this->assertEquals(
			0,
			sort_images_by_size_cb(
				[
					'height' => 4,
					'width'  => 6,
				],
				[
					'height' => 2,
					'width'  => 8,
				]
			)
		);

		$this->assertEquals(
			-1,
			sort_images_by_size_cb(
				[
					'height' => 4,
					'width'  => 7,
				],
				[
					'height' => 2,
					'width'  => 8,
				]
			)
		);

		$this->assertEquals(
			1,
			sort_images_by_size_cb(
				[
					'height' => 4,
					'width'  => 6,
				],
				[
					'height' => 2,
					'width'  => 9,
				]
			)
		);
	}

	/**
	 * @covers \Classifai\get_largest_acceptable_image_url
	 */
	public function test_get_largest_acceptable_image_url() {
		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/33772.jpg' ); // ~172KB image.

		$set_150kb_max_filesize = function() {
			return 150000;
		};
		add_filter( 'classifai_computer_vision_max_filesize', $set_150kb_max_filesize );

		$url = get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes'],
			computer_vision_max_filesize()
		);
		$this->assertEquals( sprintf( '%s/33772-1536x864.jpg', wp_upload_dir()['url'] ), $url );

		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA .'/images/2004-07-22-DSC_0008.jpg' ); // ~109kb image.
		$url = get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes'],
			computer_vision_max_filesize()
		);
		$this->assertEquals( sprintf( '%s/2004-07-22-DSC_0008.jpg', wp_upload_dir()['url'] ), $url );

		remove_filter( 'classifai_computer_vision_max_filesize', $set_150kb_max_filesize );

		$set_1kb_max_filesize = function() {
			return 1000;
		};
		add_filter( 'classifai_computer_vision_max_filesize', $set_1kb_max_filesize );

		$url = get_largest_acceptable_image_url(
			get_attached_file( $attachment ),
			wp_get_attachment_url( $attachment, 'full' ),
			wp_get_attachment_metadata( $attachment )['sizes'],
			computer_vision_max_filesize()
		);
		$this->assertNull( $url );

		remove_filter( 'classifai_computer_vision_max_filesize', $set_1kb_max_filesize );
	}

	public function test_clean_input() {
		$_POST['classify_test_string'] = '<h1>Hello, world!</h1>';
		$_GET['classify_test_int']     = -2.4;

		$sanitized_string = clean_input( 'classify_test_string' );
		$this->assertEquals( $sanitized_string, 'Hello, world!' );

		$sanitized_int = clean_input( 'classify_test_int', true, 'absint' );
		$this->assertEquals( $sanitized_int, 2 );
	}

	/**
	 * Tests for the get_post_statuses method.
	 */
	public function test_get_post_statuses() {
		$all_statuses  = get_all_post_statuses();
		$core_statuses = get_post_statuses();

		// This tells that $all_status contains all statuses that
		// are present in $core_statuses.
		$statuses_diff = array_diff( $core_statuses, $all_statuses );
		$this->assertEquals( 0, count( $statuses_diff ) );
		$this->assertArrayHasKey( 'unread', $all_statuses );
	}

	/**
	 * Tests for the get_last_url_slug method.
	 */
	public function test_get_url_slugs() {
		global $wp;

		// If URL is https://www.example.com/this/is/a/test/
		// $wp->request will be 'this/is/a/test'.
		$wp->request = 'this/is/a/test';

		$slugs = get_url_slugs();
		$this->assertEquals( [ 'this', 'is', 'a', 'test' ], $slugs );
	}

	/**
	 * Tests for the get_last_url_slug method.
	 */
	public function test_get_last_url_slug() {
		global $wp;

		$wp->request = 'https://example.com/this/is/a/test/';

		$slug = get_last_url_slug();
		$this->assertEquals( 'test', $slug );
	}

	/**
	 * @covers \Classifai\sanitize_prompts
	 */
	public function test_sanitize_prompts_strips_tags_and_enforces_one_default() {
		$result = sanitize_prompts(
			'prompts',
			[
				'prompts' => [
					[
						'title'    => '<b>Title One</b>',
						'prompt'   => '<script>alert(1)</script>Prompt one',
						'default'  => 1,
						'original' => 0,
					],
					[
						// Dropped: missing prompt.
						'title'    => 'Empty',
						'prompt'   => '',
						'default'  => 0,
						'original' => 0,
					],
					[
						'title'    => 'Title Two',
						'prompt'   => 'Prompt two',
						'default'  => 1,
						'original' => 0,
					],
				],
			]
		);

		$this->assertCount( 2, $result, 'Rows without a prompt are dropped.' );
		$this->assertSame( 'Title One', $result[0]['title'], 'Tags stripped from title.' );
		$this->assertStringNotContainsString( '<script>', $result[0]['prompt'], 'Disallowed markup removed from prompt.' );

		// Exactly one default survives.
		$this->assertSame( 1, $result[0]['default'] );
		$this->assertSame( 0, $result[2]['default'] );
	}

	/**
	 * @covers \Classifai\sanitize_prompts
	 */
	public function test_sanitize_prompts_defaults_to_first_when_none_marked() {
		$result = sanitize_prompts(
			'prompts',
			[
				'prompts' => [
					[
						'title'    => 'A',
						'prompt'   => 'a',
						'default'  => 0,
						'original' => 0,
					],
					[
						'title'    => 'B',
						'prompt'   => 'b',
						'default'  => 0,
						'original' => 0,
					],
				],
			]
		);

		$this->assertSame( 1, $result[0]['default'] );
		$this->assertSame( 0, $result[1]['default'] );
	}

	/**
	 * @covers \Classifai\sanitize_prompts
	 */
	public function test_sanitize_prompts_tolerates_non_array_input() {
		$this->assertSame( [], sanitize_prompts( 'prompts', [ 'prompts' => 'not-an-array' ] ) );
		$this->assertSame( [], sanitize_prompts( 'missing', [] ) );
	}

	/**
	 * @covers \Classifai\get_default_prompt
	 */
	public function test_get_default_prompt() {
		// Marked default, non-original.
		$this->assertSame(
			'Chosen',
			get_default_prompt(
				[
					[
						'prompt'   => 'Chosen',
						'default'  => 1,
						'original' => 0,
					],
				]
			)
		);

		// No default → first non-original prompt.
		$this->assertSame(
			'First',
			get_default_prompt(
				[
					[
						'prompt'   => 'First',
						'default'  => 0,
						'original' => 0,
					],
				]
			)
		);

		// Only an original prompt → null.
		$this->assertNull(
			get_default_prompt(
				[
					[
						'prompt'   => 'Original',
						'default'  => 0,
						'original' => 1,
					],
				]
			)
		);

		$this->assertNull( get_default_prompt( [] ) );
	}

	/**
	 * @covers \Classifai\get_temperature
	 */
	public function test_get_temperature_clamps() {
		$this->assertSame( 0.7, get_temperature( 0.7, 1 ), 'Single result returns the base temperature.' );
		$this->assertSame( 1.2, get_temperature( 0.7, 5 ), '0.7 + 5/10.' );
		$this->assertSame( 2.0, get_temperature( 0.7, 100 ), 'Never exceeds 2.0.' );
	}

	/**
	 * @covers \Classifai\sanitize_number_of_responses_field
	 */
	public function test_sanitize_number_of_responses_field() {
		$this->assertSame( 3, sanitize_number_of_responses_field( 'n', [ 'n' => '3' ], [ 'n' => 1 ] ) );
		$this->assertSame( 5, sanitize_number_of_responses_field( 'n', [], [ 'n' => 5 ] ), 'Falls back to existing setting.' );
		$this->assertSame( 0, sanitize_number_of_responses_field( 'n', [], [] ) );
	}

	/**
	 * @covers \Classifai\check_term_permissions
	 */
	public function test_check_term_permissions() {
		// Invalid taxonomy.
		$this->assertWPError( check_term_permissions( 'not-a-taxonomy' ) );

		// User without edit_terms capability.
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );
		$denied = check_term_permissions( 'category' );
		$this->assertWPError( $denied );
		$this->assertSame( 'rest_cannot_assign_term', $denied->get_error_code() );

		// Capable user.
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->assertTrue( check_term_permissions( 'category' ) );
	}

	/**
	 * @covers \Classifai\get_resource_type
	 * @covers \Classifai\is_attachment
	 * @covers \Classifai\is_remote_url
	 * @covers \Classifai\is_local_path
	 */
	public function test_resource_type_helpers() {
		$this->assertSame( 'attachment', get_resource_type( 123 ) );
		$this->assertSame( 'attachment', get_resource_type( '123' ) );
		$this->assertSame( 'url', get_resource_type( 'https://example.com/image.jpg' ) );
		$this->assertSame( 'path', get_resource_type( '/var/www/uploads/image.jpg' ) );
		$this->assertFalse( get_resource_type( [ 'not', 'scalar' ] ) );

		$this->assertTrue( is_attachment( '123' ) );
		$this->assertTrue( is_remote_url( 'https://example.com/image.jpg' ) );
		$this->assertTrue( is_local_path( '/var/www/uploads/image.jpg' ) );
		$this->assertFalse( is_local_path( 'https://example.com/image.jpg' ) );
	}

	/**
	 * @covers \Classifai\should_use_legacy_settings_panel
	 */
	public function test_should_use_legacy_settings_panel() {
		$this->assertFalse( should_use_legacy_settings_panel(), 'Defaults to the new panel.' );

		add_filter( 'classifai_use_legacy_settings_panel', '__return_true' );
		$this->assertTrue( should_use_legacy_settings_panel() );
		remove_filter( 'classifai_use_legacy_settings_panel', '__return_true' );
	}

	/**
	 * @covers \Classifai\find_provider_class
	 */
	public function test_find_provider_class() {
		$classes = [ \Classifai\Providers\OpenAI\ChatGPT::class ];

		$this->assertSame(
			\Classifai\Providers\OpenAI\ChatGPT::class,
			find_provider_class( $classes, 'openai_chatgpt' )
		);

		$error = find_provider_class( $classes, 'no_such_provider' );
		$this->assertWPError( $error );
		$this->assertSame( 'provider_class_required', $error->get_error_code() );
	}

	/**
	 * @covers \Classifai\safe_wp_remote_request
	 */
	public function test_safe_wp_remote_request_sets_user_agent_and_returns_response() {
		$captured = [];
		$filter   = function ( $preempt, $parsed_args, $url ) use ( &$captured ) {
			$captured = $parsed_args;
			return [
				'response' => [ 'code' => 200 ],
				'body'     => 'ok',
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$response = safe_wp_remote_request( 'get', 'https://example.com/endpoint' );

		$this->assertSame( 'ok', wp_remote_retrieve_body( $response ) );
		$this->assertSame( 'GET', $captured['method'], 'Method is upper-cased.' );
		$this->assertStringStartsWith( 'ClassifAI/', $captured['headers']['User-Agent'] );

		remove_filter( 'pre_http_request', $filter, 10 );
	}

	/**
	 * @covers \Classifai\safe_file_get_contents
	 */
	public function test_safe_file_get_contents_remote_and_local() {
		// Remote success.
		$success = function () {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => 'remote-body',
			];
		};
		add_filter( 'pre_http_request', $success, 10, 3 );
		$this->assertSame( 'remote-body', safe_file_get_contents( 'https://example.com/file.txt' ) );
		remove_filter( 'pre_http_request', $success, 10 );

		// Remote non-2xx returns false.
		$failure = function () {
			return [
				'response' => [ 'code' => 404 ],
				'body'     => 'missing',
			];
		};
		add_filter( 'pre_http_request', $failure, 10, 3 );
		$this->assertFalse( safe_file_get_contents( 'https://example.com/missing.txt' ) );
		remove_filter( 'pre_http_request', $failure, 10 );

		// Local path read.
		$tmp = wp_tempnam( 'classifai-test' );
		file_put_contents( $tmp, 'local-body' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->assertSame( 'local-body', safe_file_get_contents( $tmp ) );
		unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	/**
	 * sanitize_generated_block_tree() strips disallowed markup (e.g. scripts and
	 * event-handler attributes) from string props while preserving allowed
	 * inline HTML.
	 */
	function test_sanitize_generated_block_tree_strips_unsafe_html() {
		$tree = array(
			'root'     => 'p1',
			'elements' => array(
				'p1' => array(
					'key'   => 'p1',
					'type'  => 'core/paragraph',
					'props' => array(
						'content' => 'Hello <strong>world</strong><script>alert(1)</script><img src="x" onerror="alert(2)">',
					),
				),
			),
		);

		$sanitized = sanitize_generated_block_tree( $tree );
		$content   = $sanitized['elements']['p1']['props']['content'];

		$this->assertStringContainsString( '<strong>world</strong>', $content, 'Allowed inline HTML should be preserved.' );
		$this->assertStringNotContainsString( '<script>', $content, 'Script tags should be removed.' );
		$this->assertStringNotContainsString( 'onerror', $content, 'Event-handler attributes should be removed.' );
	}

	/**
	 * sanitize_generated_block_tree() treats `url` props as URLs, dropping unsafe
	 * protocols such as javascript:.
	 */
	function test_sanitize_generated_block_tree_sanitizes_url_props() {
		$tree = array(
			'root'     => 'img1',
			'elements' => array(
				'img1' => array(
					'key'   => 'img1',
					'type'  => 'core/image',
					'props' => array(
						'url' => 'javascript:alert(1)',
						'alt' => 'safe text',
					),
				),
			),
		);

		$sanitized = sanitize_generated_block_tree( $tree );

		$this->assertStringNotContainsString( 'javascript:', $sanitized['elements']['img1']['props']['url'], 'Unsafe URL protocols should be stripped.' );
		$this->assertSame( 'safe text', $sanitized['elements']['img1']['props']['alt'] );
	}

	/**
	 * sanitize_generated_block_tree() recurses into nested structures (e.g. table
	 * cells) and leaves non-string scalars untouched.
	 */
	function test_sanitize_generated_block_tree_recurses_and_preserves_scalars() {
		$tree = array(
			'root'     => 'h1',
			'elements' => array(
				'h1' => array(
					'key'      => 'h1',
					'type'     => 'core/heading',
					'props'    => array(
						'content' => 'Heading',
						'level'   => 2,
					),
				),
				't1' => array(
					'key'   => 't1',
					'type'  => 'core/table',
					'props' => array(
						'body' => array(
							array(
								'cells' => array(
									array(
										'content' => 'Cell <script>alert(1)</script>',
										'tag'     => 'td',
									),
								),
							),
						),
					),
				),
				'l1' => array(
					'key'   => 'l1',
					'type'  => 'core/list',
					'props' => array(
						'ordered' => true,
					),
				),
			),
		);

		$sanitized = sanitize_generated_block_tree( $tree );

		// Non-string scalars are preserved as-is.
		$this->assertSame( 2, $sanitized['elements']['h1']['props']['level'] );
		$this->assertTrue( $sanitized['elements']['l1']['props']['ordered'] );

		// Nested string values are sanitized.
		$cell = $sanitized['elements']['t1']['props']['body'][0]['cells'][0];
		$this->assertStringNotContainsString( '<script>', $cell['content'] );
		$this->assertSame( 'td', $cell['tag'] );
	}

	/**
	 * Mirrors how providers process a response: decode to objects, sanitize, and
	 * re-encode. Empty objects such as "props":{} must survive (decoding to
	 * arrays would turn them into "props":[], which the client schema rejects),
	 * while nested string props are still sanitized.
	 */
	function test_sanitize_generated_block_tree_preserves_empty_objects_on_round_trip() {
		$json = '{"root":"r","elements":{"r":{"key":"r","type":"fragment","props":{},"children":["p1"]},"p1":{"key":"p1","type":"core/paragraph","props":{"content":"Hi <script>alert(1)</script>"}}}}';

		$encoded = wp_json_encode( sanitize_generated_block_tree( json_decode( $json ) ) );

		$this->assertStringContainsString( '"props":{}', $encoded, 'Empty objects must remain objects.' );
		$this->assertStringNotContainsString( '"props":[]', $encoded, 'Empty objects must not become arrays.' );
		$this->assertStringNotContainsString( '<script>', $encoded, 'Nested string props must still be sanitized.' );
	}
}
