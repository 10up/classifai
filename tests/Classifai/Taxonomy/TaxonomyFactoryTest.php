<?php

namespace Classifai\Taxonomy;

class TaxonomyFactoryTest extends \WP_UnitTestCase {

	public $factory;

	function set_up() {
		parent::set_up();

		$this->factory = new TaxonomyFactory();
	}

	function test_it_can_be_created() {
		$this->assertInstanceOf(
			'\Classifai\Taxonomy\TaxonomyFactory',
			$this->factory
		);
	}

	function test_it_knows_if_taxonomy_does_not_exist() {
		$actual = $this->factory->exists( 'bar' );
		$this->assertFalse( $actual );
	}

	function test_it_knows_if_taxonomy_exists() {
		$this->factory->taxonomies[ 'foo' ] = new \stdClass();
		$actual = $this->factory->exists( 'foo' );
		$this->assertTrue( $actual );
	}

	function test_it_registers_the_taxonomy_with_wordpress_on_build() {
		$this->factory->build( WATSON_CATEGORY_TAXONOMY );
		$actual = taxonomy_exists( WATSON_CATEGORY_TAXONOMY );
		$this->assertTrue( $actual );
	}

	function test_it_will_not_rebuild_existing_taxonomy() {
		$this->factory->taxonomies[ 'foo' ] = 'cached';
		$actual = $this->factory->build_if( 'foo' );
		$this->assertEquals( 'cached', $actual );
	}

	function test_it_can_build_all_supported_taxonomies() {
		$this->factory->build_all();

		$this->assertTrue( taxonomy_exists( WATSON_CATEGORY_TAXONOMY ) );
		$this->assertTrue( taxonomy_exists( WATSON_KEYWORD_TAXONOMY ) );
		$this->assertTrue( taxonomy_exists( WATSON_CONCEPT_TAXONOMY ) );
		$this->assertTrue( taxonomy_exists( WATSON_ENTITY_TAXONOMY ) );
	}

	function test_it_connects_watson_taxonomies_to_post_type() {
		add_filter( 'classifai_post_types', function() {
			return [ 'post' ];
		} );

		$this->factory->build_all();

		$actual = get_object_taxonomies( 'post' );

		$this->assertContains( WATSON_CATEGORY_TAXONOMY, $actual );
		$this->assertContains( WATSON_KEYWORD_TAXONOMY, $actual );
		$this->assertContains( WATSON_CONCEPT_TAXONOMY, $actual );
		$this->assertContains( WATSON_ENTITY_TAXONOMY, $actual );
	}

}
