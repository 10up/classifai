<?php
/**
 * Service definition for Language Processing
 */

namespace Classifai\Services;

use Classifai\Admin\SavePostHandler;
use Classifai\Features\AudioTranscriptsGeneration;
use Classifai\Features\ExcerptGeneration;
use Classifai\Features\ContentResizing;
use Classifai\Features\TitleGeneration;

use function Classifai\find_provider_class;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

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
	public static function get_service_providers() {
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
