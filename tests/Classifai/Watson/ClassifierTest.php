<?php

namespace Classifai\Watson;

class ClassifierTest extends \WP_UnitTestCase {

	public $classifier;

	function setUp() {
		parent::setUp();

		$this->classifier = new Classifier();
	}

	function test_it_has_an_endpoint() {
		$actual = $this->classifier->endpoint;
		$this->assertContains( 'watsonplatform.net', $actual );
	}

	function test_it_has_a_request_object() {
		$actual = $this->classifier->get_request();
		$this->assertInstanceOf(
			'\Classifai\Watson\APIRequest',
			$actual
		);
	}

	function test_it_has_a_default_request_body() {
		$actual = $this->classifier->get_body( 'foo' );
		$this->assertNotEmpty( $actual );
	}

	function test_it_can_classify_text() {
		if ( defined( 'WATSON_USERNAME' ) && ! empty( WATSON_USERNAME ) && defined( 'WATSON_PASSWORD' ) && ! empty( WATSON_PASSWORD ) ) {
			$text = 'The quick brown fox jumps over the lazy dog.';
			$actual = $this->classifier->classify( $text );
			$this->assertNotEmpty( $actual['keywords'] );
			$this->assertNotEmpty( $actual['categories'] );
		}
	}

}
