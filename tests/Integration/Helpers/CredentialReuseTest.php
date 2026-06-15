<?php
/**
 * Tests for CredentialReuse.
 */

namespace Classifai\Tests\Helpers;

use Classifai\Tests\TestCase;
use Classifai\Helpers\CredentialReuse;
use Classifai\Features\TitleGeneration;
use Classifai\Features\Classification;

/**
 * @group helpers
 * @coversDefaultClass \Classifai\Helpers\CredentialReuse
 */
class CredentialReuseTest extends TestCase {

	public function tear_down() {
		delete_option( 'classifai_feature_title_generation' );
		delete_option( 'classifai_feature_classification' );
		parent::tear_down();
	}

	/**
	 * Configure TitleGeneration with an authenticated ChatGPT provider.
	 */
	private function configure_chatgpt() {
		update_option(
			'classifai_feature_title_generation',
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'openai_chatgpt' => [
					'api_key'       => 'sk-shared-key',
					'authenticated' => true,
				],
			]
		);
	}

	/**
	 * @covers ::get_configured_providers
	 */
	public function test_get_configured_providers_lists_authenticated() {
		$this->configure_chatgpt();

		$providers = CredentialReuse::get_configured_providers();

		$this->assertArrayHasKey( 'openai_chatgpt', $providers );
		$this->assertSame( TitleGeneration::ID, $providers['openai_chatgpt']['feature_id'] );
		$this->assertSame( 'sk-shared-key', $providers['openai_chatgpt']['credentials']['api_key'] );
	}

	/**
	 * @covers ::get_configured_providers
	 */
	public function test_get_configured_providers_excludes_unauthenticated() {
		update_option(
			'classifai_feature_title_generation',
			[
				'status'         => '1',
				'provider'       => 'openai_chatgpt',
				'openai_chatgpt' => [ 'api_key' => 'sk', 'authenticated' => false ],
			]
		);

		$this->assertArrayNotHasKey( 'openai_chatgpt', CredentialReuse::get_configured_providers() );
	}

	/**
	 * The source feature's own provider is excluded from its reusable set.
	 *
	 * @covers ::get_reusable_credentials
	 */
	public function test_reusable_excludes_own_feature() {
		$this->configure_chatgpt();

		$reusable = CredentialReuse::get_reusable_credentials( TitleGeneration::ID );

		$this->assertArrayNotHasKey( 'openai_chatgpt', $reusable );
	}

	/**
	 * A directly-compatible provider is offered for reuse to another feature.
	 *
	 * @covers ::get_reusable_credentials
	 * @covers ::is_provider_compatible
	 */
	public function test_reusable_for_directly_compatible_feature() {
		$this->configure_chatgpt();

		// ExcerptGeneration also supports openai_chatgpt.
		$reusable = CredentialReuse::get_reusable_credentials( 'feature_excerpt_generation' );

		$this->assertArrayHasKey( 'openai_chatgpt', $reusable );
		$this->assertSame( 'sk-shared-key', $reusable['openai_chatgpt']['credentials']['api_key'] );
	}

	/**
	 * Same-group reuse: ChatGPT credentials map to OpenAI Embeddings for Classification.
	 *
	 * @covers ::get_reusable_credentials
	 */
	public function test_reusable_same_group_maps_to_compatible_provider() {
		$this->configure_chatgpt();

		$reusable = CredentialReuse::get_reusable_credentials( Classification::ID );

		// Classification supports openai_embeddings (same 'openai' group), not openai_chatgpt.
		$this->assertArrayHasKey( 'openai_embeddings', $reusable );
		$this->assertSame( 'openai_chatgpt', $reusable['openai_embeddings']['source_provider_id'] );
		$this->assertSame( 'sk-shared-key', $reusable['openai_embeddings']['credentials']['api_key'] );
	}

	/**
	 * @covers ::copy_provider_credentials
	 */
	public function test_copy_provider_credentials() {
		$this->configure_chatgpt();

		$copied = CredentialReuse::copy_provider_credentials(
			TitleGeneration::ID,
			'feature_excerpt_generation',
			'openai_chatgpt'
		);

		$this->assertTrue( $copied );

		$target = get_option( 'classifai_feature_excerpt_generation' );
		$this->assertSame( 'openai_chatgpt', $target['provider'] );
		$this->assertSame( 'sk-shared-key', $target['openai_chatgpt']['api_key'] );

		delete_option( 'classifai_feature_excerpt_generation' );
	}
}
