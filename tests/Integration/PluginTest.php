<?php

namespace Classifai;

class PluginTest extends \WP_UnitTestCase {

	public $plugin;

	/**
	 * Option names involved in the v3 migration.
	 *
	 * @var string[]
	 */
	private $migration_options = [
		'classifai_v3_migration_completed',
		'classifai_display_v3_migration_notice',
		// Legacy options.
		'classifai_watson_nlu',
		'classifai_openai_embeddings',
		'classifai_openai_whisper',
		'classifai_openai_chatgpt',
		'classifai_azure_text_to_speech',
		'classifai_computer_vision',
		'classifai_openai_dalle',
		// v3 feature options.
		'classifai_feature_classification',
		'classifai_feature_title_generation',
		'classifai_feature_excerpt_generation',
		'classifai_feature_content_resizing',
		'classifai_feature_descriptive_text_generator',
		'classifai_feature_image_tags_generator',
		'classifai_feature_image_cropping',
		'classifai_feature_image_to_text_generator',
		'classifai_feature_pdf_to_text_generation',
		'classifai_feature_image_generation',
	];

	function set_up() {
		parent::set_up();

		// Start each migration test from a clean slate.
		foreach ( $this->migration_options as $option ) {
			delete_option( $option );
		}
	}

	function test_it_is_a_singleton() {
		$a = Plugin::get_instance();
		$b = Plugin::get_instance();

		$this->assertSame( $a, $b );
	}

	/**
	 * @covers \Classifai\Plugin::maybe_migrate_to_v3
	 */
	public function test_migrate_with_no_legacy_options() {
		Plugin::get_instance()->maybe_migrate_to_v3();

		$this->assertTrue( get_option( 'classifai_v3_migration_completed' ) );
		$this->assertFalse( get_option( 'classifai_display_v3_migration_notice' ), 'No notice without a migration.' );
		$this->assertFalse( get_option( 'classifai_feature_classification' ), 'No feature options created.' );
	}

	/**
	 * Idempotency: once completed, the migration must not overwrite v3 options.
	 *
	 * @covers \Classifai\Plugin::maybe_migrate_to_v3
	 */
	public function test_migrate_is_idempotent() {
		update_option( 'classifai_v3_migration_completed', true, false );
		update_option( 'classifai_feature_classification', [ 'sentinel' => 'untouched' ] );
		// A legacy option is present but must be ignored because migration is done.
		update_option( 'classifai_watson_nlu', [ 'authenticated' => true ] );

		Plugin::get_instance()->maybe_migrate_to_v3();

		$this->assertSame(
			[ 'sentinel' => 'untouched' ],
			get_option( 'classifai_feature_classification' )
		);
	}

	/**
	 * @covers \Classifai\Plugin::maybe_migrate_to_v3
	 */
	public function test_migrate_watson_creates_classification_option() {
		update_option( 'classifai_watson_nlu', [ 'authenticated' => true ] );

		Plugin::get_instance()->maybe_migrate_to_v3();

		$this->assertIsArray( get_option( 'classifai_feature_classification' ) );
		$this->assertTrue( get_option( 'classifai_display_v3_migration_notice' ) );
	}

	/**
	 * @covers \Classifai\Plugin::maybe_migrate_to_v3
	 */
	public function test_migrate_chatgpt_creates_text_feature_options() {
		update_option( 'classifai_openai_chatgpt', [ 'authenticated' => true ] );

		Plugin::get_instance()->maybe_migrate_to_v3();

		$this->assertIsArray( get_option( 'classifai_feature_title_generation' ) );
		$this->assertIsArray( get_option( 'classifai_feature_excerpt_generation' ) );
		$this->assertIsArray( get_option( 'classifai_feature_content_resizing' ) );
	}

	/**
	 * @covers \Classifai\Plugin::maybe_migrate_to_v3
	 */
	public function test_migrate_computer_vision_creates_image_feature_options() {
		update_option( 'classifai_computer_vision', [ 'authenticated' => true ] );

		Plugin::get_instance()->maybe_migrate_to_v3();

		$this->assertIsArray( get_option( 'classifai_feature_descriptive_text_generator' ) );
		$this->assertIsArray( get_option( 'classifai_feature_image_tags_generator' ) );
		$this->assertIsArray( get_option( 'classifai_feature_image_cropping' ) );
		$this->assertIsArray( get_option( 'classifai_feature_image_to_text_generator' ) );
		$this->assertIsArray( get_option( 'classifai_feature_pdf_to_text_generation' ) );
	}

	/**
	 * @covers \Classifai\Plugin::maybe_block_ai_crawlers
	 */
	public function test_block_ai_crawlers_disabled_by_default() {
		delete_option( 'classifai_settings' );

		$robots = "User-agent: *\nDisallow:\n";
		$this->assertSame( $robots, Plugin::get_instance()->maybe_block_ai_crawlers( $robots ) );
	}

	/**
	 * @covers \Classifai\Plugin::maybe_block_ai_crawlers
	 */
	public function test_block_ai_crawlers_when_enabled() {
		update_option( 'classifai_settings', [ 'block_ai_bots' => '1' ] );

		$output = Plugin::get_instance()->maybe_block_ai_crawlers( "User-agent: *\nDisallow:\n" );

		foreach ( [ 'GPTbot', 'ClaudeBot', 'CCBot', 'Google-Extended', 'Applebot-Extended', 'FacebookBot', 'Meta-ExternalAgent' ] as $bot ) {
			$this->assertStringContainsString( "User-agent: {$bot}", $output );
		}

		delete_option( 'classifai_settings' );
	}
}
