<?php

namespace Classifai\Watson;

class LinkerTest extends \WP_UnitTestCase {

	/* @var Linker $linker */
	public $linker;

	function set_up() {
		parent::set_up();

		$this->linker = new Linker();
	}

	function test_it_can_link_nlu_categories() {
		$categories = [
			[
				'score' => 0.99,
				'label' => '/pets/dogs',
			],
			[
				'score' => 0.09,
				'label' => '/pets/cats',
			],
		];

		$post_id = $this->factory->post->create();
		$this->linker->link_categories( $post_id, $categories );

		$actual = wp_get_object_terms( $post_id, [ WATSON_CATEGORY_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['dogs', 'pets'], $actual );
	}

	function test_it_can_link_nlu_keywords() {
		$keywords = [
			[
				'relevance' => 0.99,
				'text' => 'lorem',
			],
			[
				'relevance' => 0.95,
				'text' => 'ipsum',
			],
		];

		$post_id = $this->factory->post->create();
		$this->linker->link_keywords( $post_id, $keywords );

		$actual = wp_get_object_terms( $post_id, [ WATSON_KEYWORD_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['ipsum', 'lorem'], $actual );
	}

	function test_it_can_link_nlu_concepts() {
		$concepts = [
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
		];

		$post_id = $this->factory->post->create();
		$this->linker->link_concepts( $post_id, $concepts );

		$actual = wp_get_object_terms( $post_id, [ WATSON_CONCEPT_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['ipsum', 'lorem'], $actual );
	}

	function test_it_can_link_nlu_entities() {
		$entities = [
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
		];

		$post_id = $this->factory->post->create();
		$this->linker->link_entities( $post_id, $entities );

		$actual = wp_get_object_terms( $post_id, [ WATSON_ENTITY_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['A lorem', 'B ipsum'], $actual );
	}

	function test_it_can_link_nlu_features() {
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
		$this->linker->link( $post_id, $output );

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

	function test_it_removes_old_links_before_linking() {
		$output_a = [
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
		];

		$output_b = [
			'categories' => [
				[
					'score' => 0.99,
					'label' => '/pets/birds',
				],
			],
		];

		$post_id = $this->factory->post->create();
		$this->linker->link( $post_id, $output_a );

		// categories
		$actual = wp_get_object_terms( $post_id, [ WATSON_CATEGORY_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['dogs', 'pets'], $actual );

		$this->linker->link( $post_id, $output_b );

		// new categories
		$actual = wp_get_object_terms( $post_id, [ WATSON_CATEGORY_TAXONOMY ] );
		$actual = array_map( function( $term ) {
			return $term->name;
		}, $actual );

		$this->assertEquals( ['birds', 'pets'], $actual );
	}
}
