<?php

namespace Classifai;

use Classifai\Taxonomy\TaxonomyFactory;

class PostClassifierTest extends \WP_UnitTestCase {

	public $classifier;

	function set_up() {
		parent::set_up();

		$this->classifier = new PostClassifier();
		$this->taxonomy_factory = new TaxonomyFactory();
		$this->taxonomy_factory->build_all();
	}

	function test_it_has_an_api_request() {
		$actual = $this->classifier->get_api_request();
		$this->assertInstanceOf( 'Classifai\Watson\APIRequest', $actual );
	}

	function test_it_has_a_normalizer() {
		$actual = $this->classifier->get_normalizer();
		$this->assertInstanceOf( 'Classifai\Watson\Normalizer', $actual );
	}

	function test_it_has_a_linker() {
		$actual = $this->classifier->get_linker();
		$this->assertInstanceOf( 'Classifai\Watson\Linker', $actual );
	}

	function test_it_has_a_classifier() {
		$actual = $this->classifier->get_classifier();
		$this->assertInstanceOf( 'Classifai\Watson\Classifier', $actual );
	}

	function test_it_can_link_post() {
		$output = [
			'categories' => [
				[
					'score' => 0.99,
					'label' => '/pets/dogs',
				],
				[
					'score' => 0.09,
					'label' => '/pets/cats',
				],
			],
			'keywords' => [
				[
					'relevance' => 0.99,
					'text' => 'lorem',
				],
				[
					'relevance' => 0.95,
					'text' => 'ipsum',
				],
			],
			'concepts' => [
				[
					'relevance' => 0.99,
					'text' => 'lorem',
					'dbpedia_resource' => 'http://dbpedia.org/resource/lorem'
				],
				[
					'relevance' => 0.95,
					'text' => 'ipsum',
					'dbpedia_resource' => 'http://dbpedia.org/resource/ipsum'
				],
			],
			'entities' => [
				[
					'relevance' => 0.99,
					'text' => 'lorem',
					'type' => 'text',
					'disambiguation' => [
						'name' => 'A lorem',
						'dbpedia_resource' => 'http://dbpedia.org/resource/lorem'
					]
				],
				[
					'relevance' => 0.95,
					'text' => 'ipsum',
					'type' => 'text',
					'disambiguation' => [
						'name' => 'B ipsum',
						'dbpedia_resource' => 'http://dbpedia.org/resource/ipsum'
					]
				],
			]
		];

		$post_id = $this->factory->post->create();
		$this->classifier->link( $post_id, $output );

		// categories
		$actual = wp_get_object_terms( $post_id, [ WATSON_CATEGORY_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['dogs', 'pets'], $actual );

		// keywords
		$actual = wp_get_object_terms( $post_id, [ WATSON_KEYWORD_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['ipsum', 'lorem'], $actual );

		// concepts
		$actual = wp_get_object_terms( $post_id, [ WATSON_CONCEPT_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['ipsum', 'lorem'], $actual );

		// entities
		$actual = wp_get_object_terms( $post_id, [ WATSON_ENTITY_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['A lorem', 'B ipsum'], $actual );
	}

	function test_it_can_classify_post() {
		$this->test_can_have_empty_assertion();

		if ( defined( 'WATSON_USERNAME' ) && ! empty( WATSON_USERNAME ) && defined( 'WATSON_PASSWORD' ) && ! empty( WATSON_PASSWORD ) ) {
			$text = 'The quick brown fox jumps over the lazy dog.';
			$post_id = $this->factory->post->create( [
				'post_content' => $text,
			] );

			$actual = $this->classifier->classify( $post_id );

			$this->assertNotEmpty( $actual['keywords'] );
			$this->assertNotEmpty( $actual['categories'] );
		}
	}

	function test_it_can_classify_and_link_post() {
		$this->test_can_have_empty_assertion();

		if ( defined( 'WATSON_USERNAME' ) && ! empty( WATSON_USERNAME ) && defined( 'WATSON_PASSWORD' ) && ! empty( WATSON_PASSWORD ) ) {
			$text = <<<TEXT
    Albert Einstein (/ˈaɪnstaɪn/; German: [ˈalbɛɐ̯t
ˈaɪnʃtaɪn] ; 14 March 1879 – 18 April 1955) was a German-born
theoretical physicist. He developed the general theory of relativity,
one of the two pillars of modern physics (alongside quantum mechanics).
Einstein's work is also known for its influence on the philosophy of
science. Einstein is best known in popular culture for his mass–energy
equivalence formula E = mc2 (which has been dubbed "the world's most
famous equation"). He received the 1921 Nobel Prize in Physics for his
"services to theoretical physics", in particular his discovery of the
law of the photoelectric effect, a pivotal step in the evolution of
quantum theory.';
TEXT;
			$post_id = $this->factory->post->create( [
				'post_title'   => 'Albert Einstein Biography',
				'post_content' => $text,
			] );

			$output = $this->classifier->classify_and_link( $post_id );

			$actual = wp_get_object_terms( $post_id, [ WATSON_CATEGORY_TAXONOMY ] );
			$this->assertNotEmpty( $actual );

			$actual = wp_get_object_terms( $post_id, [ WATSON_KEYWORD_TAXONOMY ] );
			$this->assertNotEmpty( $actual );

			$actual = wp_get_object_terms( $post_id, [ WATSON_CONCEPT_TAXONOMY ] );
			$this->assertNotEmpty( $actual );

			$actual = wp_get_object_terms( $post_id, [ WATSON_ENTITY_TAXONOMY ] );
			$this->assertNotEmpty( $actual );
		}
	}

	function test_it_only_classifies_configured_features() {
		$this->test_can_have_empty_assertion();

		if ( defined( 'WATSON_USERNAME' ) && ! empty( WATSON_USERNAME ) && defined( 'WATSON_PASSWORD' ) && ! empty( WATSON_PASSWORD ) ) {
			$text = <<<TEXT
    Albert Einstein (/ˈaɪnstaɪn/; German: [ˈalbɛɐ̯t
ˈaɪnʃtaɪn] ; 14 March 1879 – 18 April 1955) was a German-born
theoretical physicist. He developed the general theory of relativity,
one of the two pillars of modern physics (alongside quantum mechanics).
Einstein's work is also known for its influence on the philosophy of
science. Einstein is best known in popular culture for his mass–energy
equivalence formula E = mc2 (which has been dubbed "the world's most
famous equation"). He received the 1921 Nobel Prize in Physics for his
"services to theoretical physics", in particular his discovery of the
law of the photoelectric effect, a pivotal step in the evolution of
quantum theory.';
TEXT;
			$post_id = $this->factory->post->create( [
				'post_title'   => 'Albert Einstein Biography',
				'post_content' => $text,
			] );

			set_plugin_settings( [
				'features'             => [
					'category' => true,
					'keyword'  => false,
					'concept'  => false,
					'entity'   => false,
				]
			] );

			$output = $this->classifier->classify_and_link( $post_id );

			$actual = wp_get_object_terms( $post_id, [ WATSON_CATEGORY_TAXONOMY ] );
			$this->assertNotEmpty( $actual );

			$actual = wp_get_object_terms( $post_id, [ WATSON_KEYWORD_TAXONOMY ] );
			$this->assertEmpty( $actual );

			$actual = wp_get_object_terms( $post_id, [ WATSON_CONCEPT_TAXONOMY ] );
			$this->assertEmpty( $actual );

			$actual = wp_get_object_terms( $post_id, [ WATSON_ENTITY_TAXONOMY ] );
			$this->assertEmpty( $actual );
		}
	}

	/**
	 * Set test to not perform assertion to fix risky tests.
	 */
	public function test_can_have_empty_assertion() {
		if ( ! defined( 'WATSON_USERNAME' ) && ! defined( 'WATSON_PASSWORD' ) ) {
			$this->expectNotToPerformAssertions();
		}
	}
}
