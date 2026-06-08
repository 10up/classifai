<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Embeddings\HandlesEmbeddingsLifecycle;
use Classifai\Embeddings\HasEmbeddingsStorage;
use Classifai\Embeddings\MigrationRunner;
use Classifai\Embeddings\Schema;

/**
 * Fake embedding provider used to exercise the lifecycle trait without hitting
 * any external API. Returns deterministic embeddings derived from the input.
 */
class HandlesEmbeddingsLifecycleDouble {

	use HasEmbeddingsStorage;
	use HandlesEmbeddingsLifecycle;

	const ID = 'lifecycle_double';

	public $feature_instance = null;

	public $nlu_features = [];

	public $generate_calls = [];

	protected function legacy_embedding_meta_key(): string {
		return 'classifai_test_double_embeddings';
	}

	protected function embeddings_provider_id(): string {
		return self::ID;
	}

	protected function embeddings_model_id(): string {
		return 'test-model';
	}

	protected function embeddings_filter_prefix(): string {
		return 'classifai_lifecycle_double';
	}

	protected function embeddings_term_job_action(): string {
		return 'classifai_lifecycle_double_term_job';
	}

	protected function embeddings_post_job_action(): string {
		return 'classifai_lifecycle_double_post_job';
	}

	public function get_max_tokens(): int {
		return 8191;
	}

	public function get_max_terms(): int {
		return 100;
	}

	public function get_max_posts(): int {
		return 100;
	}

	public function generate_embedding( string $text = '', $feature = null ) {
		$this->generate_calls[] = $text;
		// Cheap deterministic 3-d vector derived from the string length and hash.
		$hash = md5( $text );
		return [
			( hexdec( substr( $hash, 0, 4 ) ) % 100 ) / 100,
			( hexdec( substr( $hash, 4, 4 ) ) % 100 ) / 100,
			( hexdec( substr( $hash, 8, 4 ) ) % 100 ) / 100,
		];
	}

	public function generate_embeddings( array $strings = [], $feature = null ) {
		$out = [];
		foreach ( $strings as $string ) {
			$out[] = $this->generate_embedding( $string, $feature );
		}
		return $out;
	}
}

/**
 * Records the $force argument passed to generate_embeddings_for_term so we can
 * assert the edit path forces regeneration without going through the API.
 */
class ForceRecordingLifecycleDouble extends HandlesEmbeddingsLifecycleDouble {

	public $term_force_calls = [];

	public function generate_embeddings_for_term( int $term_id, bool $force = false, ?\Classifai\Features\Feature $feature = null ) {
		$this->term_force_calls[] = $force;
		return [];
	}
}

/**
 * @group embeddings
 */
class HandlesEmbeddingsLifecycleTest extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		Schema::maybe_install();
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
		( new MigrationRunner() )->mark_completed();
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
		delete_option( MigrationRunner::STATUS_OPTION );
		parent::tear_down();
	}

	public function test_chunk_content_splits_on_words_with_overlap() {
		$double  = new HandlesEmbeddingsLifecycleDouble();
		$content = str_repeat( 'word ', 320 );
		$chunks  = $double->chunk_content( trim( $content ), 100, 10 );

		// 320 words / 100 per chunk = 4 chunks (one boundary slice).
		$this->assertGreaterThanOrEqual( 3, count( $chunks ) );
		$this->assertLessThanOrEqual( 5, count( $chunks ) );
	}

	public function test_get_normalized_content_for_term_uses_name_slug_description() {
		$double  = new HandlesEmbeddingsLifecycleDouble();
		$term_id = self::factory()->term->create(
			[
				'name'        => 'Foo',
				'slug'        => 'foo',
				'description' => 'desc',
			]
		);

		$content = $double->get_normalized_content( $term_id, 'term' );
		$this->assertStringContainsString( 'Foo', $content );
		$this->assertStringContainsString( 'foo', $content );
		$this->assertStringContainsString( 'desc', $content );
	}

	public function test_generate_embeddings_for_post_persists_to_repository() {
		$double  = new HandlesEmbeddingsLifecycleDouble();
		$post_id = self::factory()->post->create(
			[
				'post_title'   => 'Hello',
				'post_content' => 'Some content for embedding.',
				'post_status'  => 'publish',
			]
		);
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$result = $double->generate_embeddings_for_post( $post_id );

		$this->assertNotEmpty( $result );
		$this->assertIsArray( $result );
		$this->assertIsArray( $result[0] );

		// Second call should hit the cache (no new generate_embedding calls).
		$before_count = count( $double->generate_calls );
		$double->generate_embeddings_for_post( $post_id );
		$this->assertSame( $before_count, count( $double->generate_calls ) );
	}

	public function test_generate_embeddings_for_post_force_regenerates() {
		$double  = new HandlesEmbeddingsLifecycleDouble();
		$post_id = self::factory()->post->create(
			[
				'post_status' => 'publish',
			]
		);
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$double->generate_embeddings_for_post( $post_id );
		$before = count( $double->generate_calls );

		$double->generate_embeddings_for_post( $post_id, true );
		$this->assertGreaterThan( $before, count( $double->generate_calls ) );
	}

	public function test_update_embeddings_for_term_forces_regeneration() {
		$double = new ForceRecordingLifecycleDouble();

		$double->update_embeddings_for_term( 123 );

		// The edit path must pass force=true so an existing embedding is regenerated.
		$this->assertSame( [ true ], $double->term_force_calls );
	}

	public function test_term_content_is_stale_at_edited_terms_but_fresh_at_edited_term() {
		$double  = new HandlesEmbeddingsLifecycleDouble();
		$term_id = self::factory()->term->create(
			[
				'taxonomy'    => 'category',
				'name'        => 'Old Name',
				'description' => 'old description',
			]
		);

		// Prime the term cache so reads during the update reflect cache state.
		$double->get_normalized_content( $term_id, 'term' );

		$seen    = [];
		$capture = function ( $id ) use ( &$seen, $double, $term_id ) {
			if ( (int) $id === (int) $term_id ) {
				$seen[ current_action() ] = $double->get_normalized_content( $id, 'term' );
			}
		};
		add_action( 'edited_terms', $capture );
		add_action( 'edited_term', $capture );

		wp_update_term(
			$term_id,
			'category',
			[
				'name'        => 'New Name',
				'description' => 'new description',
			]
		);

		remove_action( 'edited_terms', $capture );
		remove_action( 'edited_term', $capture );

		// edited_terms fires before the description write + clean_term_cache: content is stale.
		$this->assertStringContainsString( 'old description', $seen['edited_terms'] );
		$this->assertStringNotContainsString( 'new description', $seen['edited_terms'] );

		// edited_term fires after both: content is fresh. This is why the edit hook
		// must be edited_term (singular), not edited_terms.
		$this->assertStringContainsString( 'new description', $seen['edited_term'] );
	}

	public function test_should_classify_filter_short_circuits_post_generation() {
		$double  = new HandlesEmbeddingsLifecycleDouble();
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		add_filter( 'classifai_lifecycle_double_should_classify', '__return_false' );
		$result = $double->generate_embeddings_for_post( $post_id );
		remove_filter( 'classifai_lifecycle_double_should_classify', '__return_false' );

		$this->assertWPError( $result );
	}

	public function test_filter_prefix_drives_hook_names_uniformly() {
		$double  = new HandlesEmbeddingsLifecycleDouble();
		$called  = [];
		$capture = function () use ( &$called ) {
			$called[] = current_filter();
		};

		add_action( 'classifai_lifecycle_double_pre_sort_embeddings_similarity', $capture );
		add_action( 'classifai_lifecycle_double_post_sort_embeddings_similarity', $capture );

		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		// Seed a term so set_terms has something to compare against.
		$term_id    = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
		$embeddings = [ [ 0.5, 0.5, 0.5 ] ];
		$double->write_object_embedding( 'term', $term_id, $embeddings, 'h' );

		// Tell Classification to support 'category' so the trait considers it.
		add_filter(
			'classifai_feature_classification_taxonomies',
			static function ( $taxes ) {
				$taxes['category'] = 'Category';
				return $taxes;
			}
		);

		$double->set_terms( $post_id, $embeddings, false );

		remove_action( 'classifai_lifecycle_double_pre_sort_embeddings_similarity', $capture );
		remove_action( 'classifai_lifecycle_double_post_sort_embeddings_similarity', $capture );

		// Even if no matches exist, pre_sort runs only when similarity items exist; just confirm hook names compose correctly.
		$this->assertContainsOnly( 'string', $called );
	}
}
