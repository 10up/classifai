<?php

namespace Classifai\Tests\Integration\Embeddings;

use Classifai\Embeddings\Schema;

/**
 * @group embeddings
 */
class SchemaTest extends \WP_UnitTestCase {

	public function tear_down() {
		global $wpdb;
		// Clean the option so each test starts from a known state.
		delete_option( 'classifai_db_version' );
		// Drop the table so install tests can re-create it.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}classifai_embeddings" ); // phpcs:ignore WordPress.DB
		parent::tear_down();
	}

	public function test_table_name_includes_wpdb_prefix() {
		global $wpdb;
		$this->assertSame( $wpdb->prefix . 'classifai_embeddings', Schema::table_name() );
	}

	public function test_maybe_install_creates_table() {
		global $wpdb;
		Schema::maybe_install();

		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'classifai_embeddings' ) ); // phpcs:ignore WordPress.DB
		$this->assertSame( $wpdb->prefix . 'classifai_embeddings', $exists );
	}

	public function test_maybe_install_writes_version_option() {
		Schema::maybe_install();
		$this->assertSame( Schema::CURRENT_VERSION, get_option( 'classifai_db_version' ) );
	}

	public function test_maybe_install_is_idempotent() {
		Schema::maybe_install();
		Schema::maybe_install();
		Schema::maybe_install();

		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'classifai_embeddings' ) ); // phpcs:ignore WordPress.DB
		$this->assertNotEmpty( $count );
	}

	public function test_installed_table_has_expected_columns() {
		global $wpdb;
		Schema::maybe_install();

		$columns = $wpdb->get_col( "DESCRIBE {$wpdb->prefix}classifai_embeddings", 0 ); // phpcs:ignore WordPress.DB
		$this->assertContains( 'id', $columns );
		$this->assertContains( 'object_type', $columns );
		$this->assertContains( 'object_id', $columns );
		$this->assertContains( 'feature', $columns );
		$this->assertContains( 'provider', $columns );
		$this->assertContains( 'model', $columns );
		$this->assertContains( 'dimensions', $columns );
		$this->assertContains( 'chunk_index', $columns );
		$this->assertContains( 'vector', $columns );
		$this->assertContains( 'content_hash', $columns );
		$this->assertContains( 'created_at', $columns );
		$this->assertContains( 'updated_at', $columns );
	}

	public function test_vector_column_is_longblob() {
		global $wpdb;
		Schema::maybe_install();

		$row = $wpdb->get_row( "SHOW COLUMNS FROM {$wpdb->prefix}classifai_embeddings WHERE Field='vector'", ARRAY_A ); // phpcs:ignore WordPress.DB
		$this->assertSame( 'longblob', strtolower( $row['Type'] ) );
	}

	public function test_drop_tables_filter_is_registered() {
		$this->assertNotFalse( has_filter( 'wpmu_drop_tables', [ Schema::class, 'add_to_drop_tables' ] ) );
	}

	public function test_add_to_drop_tables_appends_embeddings_table() {
		global $wpdb;

		$tables = [ $wpdb->get_blog_prefix( 2 ) . 'posts' ];
		$result = Schema::add_to_drop_tables( $tables, 2 );

		$this->assertContains( $wpdb->get_blog_prefix( 2 ) . 'classifai_embeddings', $result );
		// Existing entries are preserved.
		$this->assertContains( $wpdb->get_blog_prefix( 2 ) . 'posts', $result );
	}

	public function test_embeddings_table_is_dropped_on_site_deletion() {
		$this->skipWithoutMultisite();

		global $wpdb;

		$blog_id = self::factory()->blog->create();

		// Install the embeddings table on the new site.
		switch_to_blog( $blog_id );
		Schema::maybe_install();
		$table  = Schema::table_name();
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB
		restore_current_blog();

		$this->assertSame( $table, $exists );

		wp_delete_site( $blog_id );

		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB
		$this->assertNull( $exists );
	}

	public function test_unique_key_on_object_feature_provider_chunk() {
		global $wpdb;
		Schema::maybe_install();

		$indexes      = $wpdb->get_results( "SHOW INDEXES FROM {$wpdb->prefix}classifai_embeddings", ARRAY_A ); // phpcs:ignore WordPress.DB
		$key_name     = 'object_feature_provider_chunk';
		$key_columns  = [];
		foreach ( $indexes as $idx ) {
			if ( $idx['Key_name'] === $key_name ) {
				$key_columns[ (int) $idx['Seq_in_index'] ] = $idx['Column_name'];
				$this->assertSame( '0', (string) $idx['Non_unique'] );
			}
		}
		ksort( $key_columns );
		$this->assertSame(
			[ 'object_type', 'object_id', 'feature', 'provider', 'model', 'chunk_index' ],
			array_values( $key_columns )
		);
	}
}
