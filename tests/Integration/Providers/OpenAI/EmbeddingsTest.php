<?php
/**
 * Tests for the OpenAI Embeddings provider.
 *
 * Term ranking is driven by cosine distance (covered directly in
 * EmbeddingCalculationsTest) over the full NLU/Classification taxonomy
 * pipeline, which the E2E suite exercises end to end. Here we cover the
 * deterministic content chunking and the input guards.
 */

namespace Classifai\Tests\Providers\OpenAI;

use Classifai\Tests\TestCase;
use Classifai\Features\Classification;
use Classifai\Providers\OpenAI\Embeddings;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\OpenAI\Embeddings
 */
class EmbeddingsTest extends TestCase {

	const OPTION = 'classifai_feature_classification';

	public function tear_down() {
		delete_option( self::OPTION );
		parent::tear_down();
	}

	private function provider(): Embeddings {
		return new Embeddings( new Classification() );
	}

	private function enable_classification() {
		$this->as_user_with_role( 'administrator' );
		update_option(
			self::OPTION,
			[
				'status'              => '1',
				'provider'            => 'openai_embeddings',
				'post_types'          => [ 'post' => 'post' ],
				'post_statuses'       => [ 'publish' => 'publish' ],
				'category'            => 1,
				'category_threshold'  => 75,
				'category_taxonomy'   => 'category',
				'roles'               => [ 'administrator' => 'administrator' ],
				'openai_embeddings'   => [
					'api_key'       => 'sk-test',
					'authenticated' => true,
				],
			]
		);
	}

	/**
	 * @covers ::chunk_content
	 */
	public function test_chunk_content_splits_with_overlap() {
		$content = 'w0 w1 w2 w3 w4 w5 w6 w7 w8 w9'; // 10 words.

		$chunks = $this->provider()->chunk_content( $content, 5, 2 );

		$this->assertCount( 2, $chunks );
		// First chunk: words 0..6 (chunk_size 5 + overlap 2, clamped at start).
		$this->assertSame( 'w0 w1 w2 w3 w4 w5 w6', $chunks[0] );
		// Second chunk starts at index 3 (5 - 2 overlap) through the end.
		$this->assertSame( 'w3 w4 w5 w6 w7 w8 w9', $chunks[1] );
	}

	/**
	 * @covers ::chunk_content
	 */
	public function test_chunk_content_short_content_single_chunk() {
		$chunks = $this->provider()->chunk_content( 'one two three', 5, 2 );

		$this->assertCount( 1, $chunks );
		$this->assertSame( 'one two three', $chunks[0] );
	}

	/**
	 * @covers ::chunk_content
	 */
	public function test_chunk_content_collapses_whitespace() {
		$chunks = $this->provider()->chunk_content( "one   two\nthree\t four", 100, 25 );

		$this->assertSame( 'one two three four', $chunks[0] );
	}

	/**
	 * @covers ::get_terms
	 */
	public function test_get_terms_requires_embeddings() {
		$this->assertWPErrorCode( 'data_required', $this->provider()->get_terms( [] ) );
	}

	/**
	 * Generated post embeddings are saved as a normalized 2D array of float vectors.
	 *
	 * @covers ::generate_embeddings_for_post
	 */
	public function test_generate_embeddings_for_post_saves_normalized_array() {
		$this->enable_classification();
		$this->load_e2e_fixtures();

		$post_id    = self::factory()->post->create( [ 'post_content' => 'Short post content.' ] );
		$embeddings = $this->provider()->generate_embeddings_for_post( $post_id, true );
		$saved_meta = get_post_meta( $post_id, 'classifai_openai_embeddings', true );

		$expected = [
			[
				0.0023064255,
				-0.009327292,
				-0.0028842222,
			],
		];

		$this->assertSame( $expected, $embeddings );
		$this->assertSame( $expected, $saved_meta );
	}

	/**
	 * Legacy single-level post embedding meta is normalized and updated in place.
	 *
	 * @covers ::generate_embeddings_for_post
	 */
	public function test_generate_embeddings_for_post_normalizes_legacy_single_array_meta() {
		$this->enable_classification();

		$post_id = self::factory()->post->create( [ 'post_content' => 'Legacy post.' ] );
		update_post_meta(
			$post_id,
			'classifai_openai_embeddings',
			[ '0.0023064255', '-0.009327292', '-0.0028842222' ]
		);

		$embeddings = $this->provider()->generate_embeddings_for_post( $post_id, false );
		$saved_meta = get_post_meta( $post_id, 'classifai_openai_embeddings', true );

		$expected = [
			[
				0.0023064255,
				-0.009327292,
				-0.0028842222,
			],
		];

		$this->assertSame( $expected, $embeddings );
		$this->assertSame( $expected, $saved_meta );
	}

	/**
	 * Legacy single-level term embedding meta is normalized and updated in place.
	 *
	 * @covers ::generate_embeddings_for_term
	 */
	public function test_generate_embeddings_for_term_normalizes_legacy_single_array_meta() {
		$this->enable_classification();

		$term_id = self::factory()->term->create(
			[
				'taxonomy' => 'category',
				'name'     => 'Legacy Category',
			]
		);
		update_term_meta(
			$term_id,
			'classifai_openai_embeddings',
			[ '0.0023064255', '-0.009327292', '-0.0028842222' ]
		);

		$embeddings = $this->provider()->generate_embeddings_for_term( $term_id, false );
		$saved_meta = get_term_meta( $term_id, 'classifai_openai_embeddings', true );

		$expected = [
			[
				0.0023064255,
				-0.009327292,
				-0.0028842222,
			],
		];

		$this->assertSame( $expected, $embeddings );
		$this->assertSame( $expected, $saved_meta );
	}
}
