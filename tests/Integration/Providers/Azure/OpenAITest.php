<?php
/**
 * Tests for the Azure OpenAI provider URL construction and settings.
 */

namespace Classifai\Tests\Providers\Azure;

use Classifai\Tests\TestCase;
use Classifai\Features\TitleGeneration;
use Classifai\Providers\Azure\OpenAI;

/**
 * @group providers
 * @coversDefaultClass \Classifai\Providers\Azure\OpenAI
 */
class OpenAITest extends TestCase {

	const OPTION = 'classifai_feature_title_generation';

	public function tear_down() {
		delete_option( self::OPTION );
		parent::tear_down();
	}

	/**
	 * Invoke the protected prep_api_url().
	 *
	 * @param OpenAI           $provider Provider instance.
	 * @param TitleGeneration  $feature  Feature instance.
	 * @return string
	 */
	private function prep_api_url( OpenAI $provider, TitleGeneration $feature ): string {
		$method = new \ReflectionMethod( $provider, 'prep_api_url' );
		$method->setAccessible( true );
		return $method->invoke( $provider, $feature );
	}

	/**
	 * @covers ::prep_api_url
	 */
	public function test_prep_api_url_composes_deployment_url() {
		update_option(
			self::OPTION,
			[
				'provider'     => 'azure_openai',
				'azure_openai' => [
					'endpoint_url' => 'https://my.azure.test',
					'deployment'   => 'gpt-deploy',
					'api_key'      => 'key',
				],
			]
		);

		$url = $this->prep_api_url( new OpenAI( new TitleGeneration() ), new TitleGeneration() );

		$this->assertSame(
			'https://my.azure.test/openai/deployments/gpt-deploy/chat/completions?api-version=2024-10-21',
			$url
		);
	}

	/**
	 * @covers ::prep_api_url
	 */
	public function test_prep_api_url_handles_trailing_slash_endpoint() {
		update_option(
			self::OPTION,
			[
				'provider'     => 'azure_openai',
				'azure_openai' => [
					'endpoint_url' => 'https://my.azure.test/',
					'deployment'   => 'gpt-deploy',
					'api_key'      => 'key',
				],
			]
		);

		$url = $this->prep_api_url( new OpenAI( new TitleGeneration() ), new TitleGeneration() );

		$this->assertStringNotContainsString( '.test//openai', $url, 'No double slash after the host.' );
	}

	/**
	 * Without a deployment the URL stays as the bare endpoint.
	 *
	 * @covers ::prep_api_url
	 */
	public function test_prep_api_url_without_deployment() {
		update_option(
			self::OPTION,
			[
				'provider'     => 'azure_openai',
				'azure_openai' => [
					'endpoint_url' => 'https://my.azure.test',
					'deployment'   => '',
					'api_key'      => 'key',
				],
			]
		);

		$url = $this->prep_api_url( new OpenAI( new TitleGeneration() ), new TitleGeneration() );

		$this->assertSame( 'https://my.azure.test', $url );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_authenticates_with_full_credentials() {
		$this->load_e2e_fixtures();
		update_option( self::OPTION, [ 'provider' => 'azure_openai' ] );

		$provider  = new OpenAI( new TitleGeneration() );
		$sanitized = $provider->sanitize_settings(
			[
				'azure_openai' => [
					'endpoint_url' => 'https://e2e-test-azure-openai.test',
					'api_key'      => 'azure-key',
					'deployment'   => 'gpt-deploy',
				],
			]
		);

		$this->assertTrue( $sanitized['azure_openai']['authenticated'] );
		$this->assertSame( 'https://e2e-test-azure-openai.test', $sanitized['azure_openai']['endpoint_url'] );
		$this->assertSame( 'gpt-deploy', $sanitized['azure_openai']['deployment'] );
	}

	/**
	 * @covers ::sanitize_settings
	 */
	public function test_sanitize_settings_fails_without_endpoint() {
		update_option( self::OPTION, [ 'provider' => 'azure_openai' ] );

		$provider  = new OpenAI( new TitleGeneration() );
		$sanitized = $provider->sanitize_settings(
			[
				'azure_openai' => [
					'endpoint_url' => '',
					'api_key'      => '',
					'deployment'   => '',
				],
			]
		);

		$this->assertFalse( $sanitized['azure_openai']['authenticated'] );
	}
}
