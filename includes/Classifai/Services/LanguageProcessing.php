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
	 * Init service for Language Processing.
	 */
	public function init() {
		parent::init();
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
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
				'Classifai\Providers\Watson\NLU',
				'Classifai\Providers\OpenAI\ChatGPT',
				'Classifai\Providers\OpenAI\Embeddings',
				'Classifai\Providers\OpenAI\Whisper',
				'Classifai\Providers\OpenAI\DallE',
				'Classifai\Providers\Azure\Speech',
			]
		);
	}

	/**
	 * Create endpoints for Language Processing.
	 *
	 * @since 1.8.0
	 */
	public function register_endpoints() {}
}
