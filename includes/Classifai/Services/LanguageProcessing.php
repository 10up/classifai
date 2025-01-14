<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

class LanguageProcessing extends Service {

	/**
	 * LanguageProcessing constructor.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Language Processing', 'classifai' ),
			'language_processing',
			self::get_service_providers()
		);
	}

	/**
	 * Get service providers for Language Processing.
	 *
	 * @return array
	 */
	public static function get_service_providers(): array {
		/**
		 * Filter the service providers for Language Processing service.
		 *
		 * @since 3.0.0
		 * @hook classifai_language_processing_service_providers
		 *
		 * @param {array} $providers Array of available providers for the service.
		 *
		 * @return {array} The filtered available providers.
		 */
		return apply_filters(
			'classifai_language_processing_service_providers',
			[
				'Classifai\Providers\Azure\Speech',
				'Classifai\Providers\OpenAI\TextToSpeech',
				'Classifai\Providers\OpenAI\ChatGPT',
				'Classifai\Providers\OpenAI\Embeddings',
				'Classifai\Providers\OpenAI\Moderation',
				'Classifai\Providers\OpenAI\Whisper',
				'Classifai\Providers\Watson\NLU',
				'Classifai\Providers\GoogleAI\GeminiAPI',
				'Classifai\Providers\Azure\OpenAI',
				'Classifai\Providers\AWS\AmazonPolly',
				'Classifai\Providers\Azure\Embeddings',
				'Classifai\Providers\Browser\ChromeAI',
				'Classifai\Providers\XAI\Grok',
			]
		);
	}
}
