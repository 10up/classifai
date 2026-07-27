<?php
/**
 * Custom table installer for ClassifAI embeddings.
 */

namespace Classifai\Embeddings;

class Schema {

	/**
	 * Schema version this code installs.
	 */
	const CURRENT_VERSION = '1.0.0';

	/**
	 * Option name that tracks the installed schema version.
	 */
	const OPTION_NAME = 'classifai_db_version';

	/**
	 * Fully-prefixed table name for the embeddings table.
	 *
	 * @return string
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'classifai_embeddings';
	}

	/**
	 * Add the embeddings table to the list of tables core drops when a site is deleted.
	 *
	 * Runs on the `wpmu_drop_tables` filter (multisite only) so the per-site
	 * embeddings table is cleaned up alongside core's own site tables.
	 *
	 * @param string[] $tables  Table names to be dropped.
	 * @param int      $site_id The ID of the site being deleted.
	 * @return string[]
	 */
	public static function add_to_drop_tables( array $tables, int $site_id ): array {
		global $wpdb;

		$tables[] = $wpdb->get_blog_prefix( $site_id ) . 'classifai_embeddings';

		return $tables;
	}

	/**
	 * Run dbDelta only when the stored schema version is behind CURRENT_VERSION.
	 */
	public static function maybe_install() {
		if ( get_option( self::OPTION_NAME ) === self::CURRENT_VERSION ) {
			return;
		}

		self::install();

		update_option( self::OPTION_NAME, self::CURRENT_VERSION, false );
	}

	/**
	 * Run dbDelta for the embeddings table. Idempotent.
	 */
	protected static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL auto_increment,
			object_type varchar(20) NOT NULL DEFAULT 'post',
			object_id bigint(20) unsigned NOT NULL,
			feature varchar(64) NOT NULL,
			provider varchar(64) NOT NULL,
			model varchar(128) NOT NULL,
			dimensions smallint unsigned NOT NULL,
			chunk_index smallint unsigned NOT NULL DEFAULT 0,
			`vector` longblob NOT NULL,
			content_hash char(64) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY object_feature_provider_chunk (object_type, object_id, feature, provider, model, chunk_index),
			KEY object_lookup (object_type, object_id),
			KEY feature_provider (feature, provider, model),
			KEY content_hash (content_hash)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
