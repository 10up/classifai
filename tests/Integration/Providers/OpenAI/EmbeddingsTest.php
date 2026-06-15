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

	private function provider(): Embeddings {
		return new Embeddings( new Classification() );
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
}
