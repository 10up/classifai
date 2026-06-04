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
