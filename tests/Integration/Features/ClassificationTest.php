<?php
/**
 * Tests for Classification settings sanitization and mode gating.
 */

namespace Classifai\Tests\Features;

use Classifai\Tests\TestCase;
use Classifai\Features\Classification;

use function Classifai\get_classification_mode;

/**
 * @group features
 * @coversDefaultClass \Classifai\Features\Classification
 */
class ClassificationTest extends TestCase {

	const OPTION = 'classifai_feature_classification';

	public function tear_down() {
		delete_option( self::OPTION );
		remove_action( 'rest_after_insert_post', [ new Classification(), 'rest_after_insert' ] );
		parent::tear_down();
	}

	/**
	 * @covers ::sanitize_default_feature_settings
	 */
	public function test_sanitize_mode_and_method() {
		$sanitized = ( new Classification() )->sanitize_default_feature_settings(
			[
				'provider'              => 'ibm_watson_nlu',
				'classification_mode'   => 'automatic_classification',
				'classification_method' => 'recommended_terms',
			]
		);

		$this->assertSame( 'automatic_classification', $sanitized['classification_mode'] );
		$this->assertSame( 'recommended_terms', $sanitized['classification_method'] );
	}

	/**
	 * Embeddings providers can only use existing terms.
	 *
	 * @covers ::sanitize_default_feature_settings
	 */
	public function test_sanitize_forces_existing_terms_for_embeddings() {
		$sanitized = ( new Classification() )->sanitize_default_feature_settings(
			[
				'provider'              => 'openai_embeddings',
				'classification_mode'   => 'automatic_classification',
				'classification_method' => 'recommended_terms',
			]
		);

		$this->assertSame( 'existing_terms', $sanitized['classification_method'] );
	}

	/**
	 * @covers ::sanitize_default_feature_settings
	 */
	public function test_sanitize_post_types_and_statuses() {
		$sanitized = ( new Classification() )->sanitize_default_feature_settings(
			[
				'provider'      => 'ibm_watson_nlu',
				'post_types'    => [ 'post' => 'post', 'page' => '<b>page</b>' ],
				'post_statuses' => [ 'publish' => 'publish' ],
			]
		);

		$this->assertSame( 'page', $sanitized['post_types']['page'], 'Tags stripped.' );
		$this->assertSame( 'publish', $sanitized['post_statuses']['publish'] );
	}

	/**
	 * Automatic mode opts content in by default; manual mode opts it out.
	 *
	 * @covers ::default_post_metadata
	 */
	public function test_classification_mode_gates_default_process_content() {
		$classification = new Classification();
		// get_classification_mode() only honors the stored mode when the feature
		// is enabled; otherwise it always returns 'manual_review'.
		$this->as_user_with_role( 'administrator' );
		$base = [
			'status'         => '1',
			'provider'       => 'ibm_watson_nlu',
			'ibm_watson_nlu' => [ 'authenticated' => true ],
			'roles'          => [ 'administrator' => 'administrator' ],
		];

		update_option( self::OPTION, array_merge( $base, [ 'classification_mode' => 'automatic_classification' ] ) );
		$this->assertSame( 'automatic_classification', get_classification_mode() );
		$this->assertSame( 'yes', $classification->default_post_metadata( null, 0, '_classifai_process_content' ) );

		update_option( self::OPTION, array_merge( $base, [ 'classification_mode' => 'manual_review' ] ) );
		$this->assertSame( 'manual_review', get_classification_mode() );
		$this->assertSame( 'no', $classification->default_post_metadata( null, 0, '_classifai_process_content' ) );

		// Unrelated meta keys pass through untouched.
		$this->assertSame( 'untouched', $classification->default_post_metadata( 'untouched', 0, 'some_other_key' ) );
	}

	/**
	 * feature_setup registers the rest_after_insert hook for each supported post type.
	 *
	 * @covers ::feature_setup
	 */
	public function test_feature_setup_registers_rest_after_insert_for_post_types() {
		update_option( self::OPTION, [ 'post_types' => [ 'post' => 'post' ] ] );

		$classification = new Classification();
		$classification->feature_setup();

		$this->assertNotFalse(
			has_action( 'rest_after_insert_post', [ $classification, 'rest_after_insert' ] )
		);
	}
}
