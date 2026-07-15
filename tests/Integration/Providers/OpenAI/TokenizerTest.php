<?php
/**
 * Tests for the OpenAI Tokenizer.
 */

namespace Classifai\Tests\Providers\OpenAI;

use Classifai\Tests\TestCase;
use Classifai\Providers\OpenAI\Tokenizer;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\OpenAI\Tokenizer
 */
class TokenizerTest extends TestCase {

	/**
	 * @covers ::tokens_in_content
	 */
	public function test_tokens_in_content() {
		$tokenizer = new Tokenizer( 1000 );

		// ceil( strlen / 3.5 ).
		$this->assertSame( 0, $tokenizer->tokens_in_content( '' ) );
		$this->assertSame( 2, $tokenizer->tokens_in_content( 'hello' ) ); // ceil(5/3.5) = 2.
		$this->assertSame( 3, $tokenizer->tokens_in_content( 'helloworld' ) ); // ceil(10/3.5) = 3.
	}

	/**
	 * @covers ::tokens_in_content
	 */
	public function test_tokens_in_content_counts_multibyte_characters() {
		$tokenizer = new Tokenizer( 1000 );

		// "café" is 4 characters (mb_strlen) even though it is 5 bytes.
		$this->assertSame( 2, $tokenizer->tokens_in_content( 'café' ) ); // ceil(4/3.5) = 2.
	}

	/**
	 * @covers ::tokens_in_words
	 */
	public function test_tokens_in_words() {
		$tokenizer = new Tokenizer( 1000 );

		$this->assertSame( 0, $tokenizer->tokens_in_words( 0 ) );
		$this->assertSame( 2, $tokenizer->tokens_in_words( 1 ) ); // ceil(1.5 * 1) = 2.
		$this->assertSame( 6, $tokenizer->tokens_in_words( 4 ) ); // ceil(1.5 * 4) = 6.
	}

	/**
	 * @covers ::trim_content
	 */
	public function test_trim_content_under_limit_returns_unchanged() {
		$tokenizer = new Tokenizer( 1000 );

		$this->assertSame( 'Hello there', $tokenizer->trim_content( 'Hello there', 1000 ) );
	}

	/**
	 * @covers ::trim_content
	 */
	public function test_trim_content_collapses_double_linebreaks() {
		$tokenizer = new Tokenizer( 1000 );

		$this->assertSame( 'Hello there', $tokenizer->trim_content( "Hello\n\nthere", 1000 ) );
	}

	/**
	 * @covers ::trim_content
	 */
	public function test_trim_content_trims_to_word_boundary() {
		$tokenizer = new Tokenizer( 1000 );

		// "The quick brown fox jumps" = 25 chars → 8 tokens. Trimming to 4 tokens
		// removes 14 chars and snaps back to the last word boundary.
		$this->assertSame( 'The quick', $tokenizer->trim_content( 'The quick brown fox jumps', 4 ) );
	}

	/**
	 * @covers ::trim_content
	 */
	public function test_trim_content_preserves_multibyte_characters() {
		$tokenizer = new Tokenizer( 1000 );

		$content = str_repeat( 'é', 30 ); // 30 chars / 60 bytes → 9 tokens.
		$trimmed = $tokenizer->trim_content( $content, 3 );

		$this->assertSame( 9, mb_strlen( $trimmed ), 'Trimmed to the expected character count.' );
		$this->assertSame( 18, strlen( $trimmed ), 'Multibyte characters not corrupted (2 bytes each).' );
		$this->assertSame( str_repeat( 'é', 9 ), $trimmed );
	}

	/**
	 * @covers ::__construct
	 */
	public function test_characters_in_token_filter_is_honored() {
		$callback = function () {
			return 7.0;
		};
		add_filter( 'classifai_openai_characters_in_token', $callback );

		$tokenizer = new Tokenizer( 1000 );
		$this->assertSame( 7.0, $tokenizer->characters_in_token );
		$this->assertSame( 2, $tokenizer->tokens_in_content( 'hello world!' ) ); // ceil(12/7) = 2.

		remove_filter( 'classifai_openai_characters_in_token', $callback );
	}

	/**
	 * @covers ::__construct
	 */
	public function test_tokens_per_word_filter_is_honored() {
		$callback = function () {
			return 3.0;
		};
		add_filter( 'classifai_openai_tokens_per_word', $callback );

		$tokenizer = new Tokenizer( 1000 );
		$this->assertSame( 3.0, $tokenizer->tokens_per_word );
		$this->assertSame( 6, $tokenizer->tokens_in_words( 2 ) ); // ceil(3 * 2) = 6.

		remove_filter( 'classifai_openai_tokens_per_word', $callback );
	}
}
