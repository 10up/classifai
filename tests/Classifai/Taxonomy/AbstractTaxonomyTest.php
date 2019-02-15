<?php

namespace Classifai\Taxonomy;

class AbstractTaxonomyTest extends \WP_UnitTestCase {

	public $taxonomy;

	function setUp() {
		parent::setUp();

		$this->taxonomy = new ThingTaxonomy();
	}

	function test_it_has_a_name() {
		$actual = $this->taxonomy->get_name();
		$this->assertEquals( 'thing', $actual );
	}

	function test_it_has_singular_label() {
		$actual = $this->taxonomy->get_singular_label();
		$this->assertEquals( 'thing', $actual );
	}

	function test_it_has_a_plural_label() {
		$actual = $this->taxonomy->get_plural_label();
		$this->assertEquals( 'things', $actual );
	}

	function test_it_has_labels() {
		$labels = $this->taxonomy->get_labels();
		$this->assertEquals( 'things', $labels['name'] );
		$this->assertEquals( 'thing', $labels['singular_name'] );
	}

	function test_it_has_options() {
		$options = $this->taxonomy->get_options();
		$this->assertNotEmpty( $options );
	}

	function test_it_does_not_have_post_types() {
		$actual = $this->taxonomy->get_post_types();
		$this->assertNull( $actual );
	}

	function test_it_can_be_registered() {
		$this->taxonomy->register();
		$this->assertTrue( taxonomy_exists( 'thing' ) );
	}

}

class ThingTaxonomy extends AbstractTaxonomy {

	public $name = 'thing';

	public function get_name() {
		return $this->name;
	}

	public function get_singular_label() {
		return $this->name;
	}

	public function get_plural_label() {
		return $this->name . 's';
	}

	public function get_visibility() {
		return true;
	}

}
