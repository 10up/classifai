<?php
/**
 * Tests for the Ollama provider.
 */

namespace Classifai\Tests\Providers\Localhost;

use Classifai\Tests\TestCase;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Localhost\Ollama;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\Localhost\Ollama
 */
class OllamaTest extends TestCase {

	const OPTION = 'classifai_feature_title_generation';

	public function tear_down() {
		delete_option( self::OPTION );
		parent::tear_down();
	}

	/**
	 * @covers ::get_api_model_url
	 */
	public function test_get_api_model_url_appends_tags() {
		$provider = new Ollama( new TitleGeneration() );

		$this->assertSame( 'http://localhost:11434/api/tags', $provider->get_api_model_url( 'http://localhost:11434' ) );
		$this->assertSame( 'http://localhost:11434/api/tags', $provider->get_api_model_url( 'http://localhost:11434/' ) );
	}

	/**
	 * @covers ::get_models
	 */
	public function test_get_models_parses_fixture() {
		$this->load_e2e_fixtures();
		update_option( self::OPTION, [ 'provider' => 'ollama' ] );

		$models = ( new Ollama( new TitleGeneration() ) )->get_models( [ 'endpoint_url' => 'http://localhost:11434/' ] );

		$this->assertCount( 3, $models );
		$this->assertArrayHasKey( 'llava:latest', $models );
		$this->assertSame( 'llava:latest', $models['llava:latest'] );
	}

	/**
	 * @covers ::get_models
	 */
	public function test_get_models_empty_endpoint_returns_empty() {
		update_option( self::OPTION, [ 'provider' => 'ollama' ] );

		$this->assertSame( [], ( new Ollama( new TitleGeneration() ) )->get_models( [ 'endpoint_url' => '' ] ) );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_authenticates_and_normalizes_endpoint() {
		$this->load_e2e_fixtures();
		update_option( self::OPTION, [ 'provider' => 'ollama' ] );

		$sanitized = ( new Ollama( new TitleGeneration() ) )->sanitize_settings(
			[ 'ollama' => [ 'endpoint_url' => 'http://localhost:11434' ] ]
		);

		$this->assertTrue( $sanitized['ollama']['authenticated'] );
		$this->assertSame( 'http://localhost:11434/', $sanitized['ollama']['endpoint_url'], 'Trailing slash added.' );
		$this->assertNotEmpty( $sanitized['ollama']['models'] );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_empty_endpoint_falls_back() {
		update_option( self::OPTION, [ 'provider' => 'ollama' ] );

		$sanitized = ( new Ollama( new TitleGeneration() ) )->sanitize_settings(
			[ 'ollama' => [ 'endpoint_url' => '' ] ]
		);

		// Falls back to the default endpoint and stays unauthenticated.
		$this->assertSame( 'http://localhost:11434/', $sanitized['ollama']['endpoint_url'] );
		$this->assertFalse( $sanitized['ollama']['authenticated'] );
	}
}
