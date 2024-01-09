<?php
/**
 * OpenAI Tokenizer
 */

namespace Classifai\Providers\OpenAI;

class Tokenizer {

	/**
	 * Maximum number of tokens our model supports
	 *
	 * @var int
	 */
	public $max_tokens;

	/**
	 * How many characters in one token (roughly)
	 *
	 * @var float
	 */
	public $characters_in_token = 3.5;

	/**
	 * How many tokens a word will take (roughly)
	 *
	 * @var float
	 */
	public $tokens_per_word = 1.5;

	/**
	 * OpenAI Tokenizer constructor.
	 *
	 * @param int $max_tokens Maximum tokens the model supports.
	 */
	public function __construct( $max_tokens ) {
		$this->max_tokens = $max_tokens;

		/**
		 * How many characters in one token (roughly)
		 *
		 * @since 2.4..0
		 * @hook classifai_openai_characters_in_token
		 *
		 * @param {int} $characters_in_token How many characters in one token (roughly)
		 * @param {int} $max_tokens Maximum tokens the model supports.
		 *
		 * @return {int}
		 */
		$this->characters_in_token = apply_filters( 'classifai_openai_characters_in_token', $this->characters_in_token, $max_tokens );

		/**
		 * How many tokens a word will take (roughly)
		 *
		 * @since 2.4.0
		 * @hook classifai_openai_tokens_per_word
		 *
		 * @param {int} $tokens_per_word How many tokens a word will take (roughly)
		 * @param {int} $max_tokens Maximum tokens the model supports.
		 *
		 * @return {int}
		 */
		$this->tokens_per_word = apply_filters( 'classifai_openai_tokens_per_word', $this->tokens_per_word, $max_tokens );
	}

	/**
	 * Determine roughly how many tokens a string contains.
	 *
	 * @param string $content Content to analyze.
	 * @return int
	 */
	public function tokens_in_content( string $content = '' ) {
		$tokens = ceil( mb_strlen( $content ) / $this->characters_in_token );

		return (int) $tokens;
	}

	/**
	 * Determine how many tokens are in a certain number of words.
	 *
	 * @param int $words Number of words we want.
	 * @return int
	 */
	public function tokens_in_words( int $words = 1 ) {
		$tokens = ceil( $this->tokens_per_word * absint( $words ) );

		return (int) $tokens;
	}

	/**
	 * Trim our content, if needed, to be under our max token number.
	 *
	 * @param string $content Content to trim.
	 * @param int    $max_tokens Maximum tokens our content can have.
	 * @return string
	 */
	public function trim_content( string $content = '', int $max_tokens = 0 ) {
		// Remove linebreaks that may have been added.
		$content = str_replace( "\n\n", ' ', $content );

		// Determine how many tokens the content has.
		$content_tokens = $this->tokens_in_content( $content );

		// If we don't need to trim, return full content.
		if ( $content_tokens < $max_tokens ) {
			return $content;
		}

		/**
		 * Next we determine how many tokens we need to trim by taking the
		 * number of tokens in the content and subtracting the max tokens
		 * we can have.
		 *
		 * Then we convert that token number to characters.
		 *
		 * Finally we determine what the max character length our content
		 * can be and trim it up.
		 */
		$tokens_to_trim     = $content_tokens - $max_tokens;
		$characters_to_trim = (int) ceil( $tokens_to_trim * $this->characters_in_token );
		$max_content_length = mb_strlen( $content ) - $characters_to_trim;
		$trimmed_content    = mb_substr( $content, 0, $max_content_length );

		// Ensure we our final string ends on a full word instead of truncating in the middle.
		if ( ! preg_match( '/\\W/u', mb_substr( $content, $max_content_length - 1, 2 ) ) ) {
			if ( preg_match( '/.*\\W/u', $trimmed_content, $matches ) ) {
				$trimmed_content = $matches[0];
			}
		}

		return trim( $trimmed_content );
	}
}
