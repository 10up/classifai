<?php

namespace Classifai\Watson;

class APIRequestTest extends \WP_UnitTestCase {

	public $request;

	function setUp() {
		parent::setUp();

		$this->request = new APIRequest();
	}

	function test_it_uses_local_username_if_present() {
		$this->request->username = 'foo';
		$actual = $this->request->get_username();
		$this->assertEquals( 'foo', $actual );
	}

	function test_it_uses_constant_username_if_present() {
		if ( defined( 'WATSON_USERNAME' ) ) {
			$actual = $this->request->get_username();
			$this->assertEquals( WATSON_USERNAME, $actual );
		}
	}

	function test_it_uses_option_username_if_present() {
		update_option( 'classifai_watson_nlu', [ 'credentials' => [ 'watson_username' => 'foo-option' ] ] );
		$actual = $this->request->get_username();
		$this->assertEquals( 'foo-option', $actual );
	}

	function test_it_uses_local_password_if_present() {
		$this->request->password = 'foo';
		$actual = $this->request->get_password();
		$this->assertEquals( 'foo', $actual );
	}

	function test_it_constant_password_if_present() {
		if ( defined( 'WATSON_PASSWORD' ) ) {
			$actual = $this->request->get_password();
			$this->assertEquals( WATSON_PASSWORD, $actual );
		}
	}

	function test_it_uses_option_password_if_present() {
		update_option( 'classifai_watson_nlu', [ 'credentials' => [ 'watson_password' => 'foo-option' ] ] );
		$actual = $this->request->get_password();
		$this->assertEquals( 'foo-option', $actual );
	}

	function test_it_can_build_auth_hash() {
		$this->request->username = 'a';
		$this->request->password = 'b';

		$actual = $this->request->get_auth_hash();
		$expected = base64_encode( 'a:b' );
		$this->assertEquals( $expected, $actual );
	}

	function test_it_can_build_auth_header() {
		$this->request->username = 'a';
		$this->request->password = 'b';

		$actual = $this->request->get_auth_header();
		$expected = 'Basic ' . base64_encode( 'a:b' );
		$this->assertEquals( $expected, $actual );
	}

	function test_it_can_add_auth_header() {
		$this->request->username = 'a';
		$this->request->password = 'b';

		$options = [];
		$this->request->add_headers( $options );

		$actual = $options['headers']['Authorization'];
		$expected = 'Basic ' . base64_encode( 'a:b' );
		$this->assertEquals( $expected, $actual );
	}

	function test_it_can_make_an_api_request() {
		if ( defined( 'WATSON_USERNAME' ) && defined( 'WATSON_PASSWORD' ) ) {
			$url = 'https://gateway.watsonplatform.net/natural-language-understanding/api/v1/analyze?version=2017-02-27';
			$options = [
				'body' => json_encode( [
					'text' => 'Lorem ipsum dolor sit amet.',
					'language' => 'en',
					'features' => [
						'keywords' => [
							'emotion' => false,
							'limit' => 20,
						]
					]
				] )
			];

			$actual = $this->request->post( $url, $options );
			$this->assertTrue( ! empty( $actual['keywords'] ) );
		}
	}

}
