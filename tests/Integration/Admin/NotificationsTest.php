<?php
/**
 * Tests for the admin Notifications.
 */

namespace Classifai\Tests\Admin;

use Classifai\Tests\TestCase;
use Classifai\Admin\Notifications;

/**
 * @group admin
 * @coversDefaultClass \Classifai\Admin\Notifications
 */
class NotificationsTest extends TestCase {

	public function tear_down() {
		delete_option( 'classifai_display_v3_migration_notice' );
		unset( $_POST['action'], $_POST['notice_id'], $_POST['nonce'], $_REQUEST['nonce'] );
		parent::tear_down();
	}

	/**
	 * Capture the output of a notice renderer.
	 *
	 * @param callable $callback Renderer.
	 * @return string
	 */
	private function capture( callable $callback ): string {
		ob_start();
		$callback();
		return (string) ob_get_clean();
	}

	/**
	 * @covers ::v3_migration_completed_notice
	 */
	public function test_migration_notice_renders_only_when_flagged() {
		$notifications = new Notifications();
		$this->as_user_with_role( 'administrator' );

		// Not flagged → nothing.
		delete_option( 'classifai_display_v3_migration_notice' );
		$this->assertSame( '', $this->capture( [ $notifications, 'v3_migration_completed_notice' ] ) );

		// Flagged → renders the migration notice.
		update_option( 'classifai_display_v3_migration_notice', true );
		$output = $this->capture( [ $notifications, 'v3_migration_completed_notice' ] );
		$this->assertStringContainsString( 'classifai-migration-notice', $output );
		$this->assertStringContainsString( 'ClassifAI 3.0.0', $output );
	}

	/**
	 * @covers ::v3_migration_completed_notice
	 */
	public function test_migration_notice_hidden_when_dismissed() {
		$notifications = new Notifications();
		$user_id       = $this->as_user_with_role( 'administrator' );
		update_option( 'classifai_display_v3_migration_notice', true );
		update_user_meta( $user_id, 'classifai_dismissed_v3_migration_completed', true );

		$this->assertSame( '', $this->capture( [ $notifications, 'v3_migration_completed_notice' ] ) );
	}

	/**
	 * @covers ::render_legacy_settings_deprecation_notice
	 */
	public function test_legacy_deprecation_notice_gated_by_filter() {
		$notifications = new Notifications();
		$this->as_user_with_role( 'administrator' );

		// Default (new panel) → nothing.
		$this->assertSame( '', $this->capture( [ $notifications, 'render_legacy_settings_deprecation_notice' ] ) );

		// Legacy panel enabled → renders.
		add_filter( 'classifai_use_legacy_settings_panel', '__return_true' );
		$output = $this->capture( [ $notifications, 'render_legacy_settings_deprecation_notice' ] );
		remove_filter( 'classifai_use_legacy_settings_panel', '__return_true' );

		$this->assertStringContainsString( 'Legacy Settings Deprecation', $output );
	}

	/**
	 * @covers ::ajax_maybe_dismiss_notice
	 */
	public function test_ajax_dismiss_persists_with_valid_nonce() {
		$notifications = new Notifications();
		$user_id       = $this->as_user_with_role( 'administrator' );

		$_POST['action']    = 'classifai_dismiss_notice';
		$_POST['notice_id'] = 'v3_migration_completed';
		// check_ajax_referer() reads the nonce from $_REQUEST.
		$_POST['nonce']    = wp_create_nonce( 'classifai_dismissible_notice' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		$notifications->ajax_maybe_dismiss_notice();

		$this->assertTrue( (bool) get_user_meta( $user_id, 'classifai_dismissed_v3_migration_completed', true ) );
	}

	/**
	 * @covers ::ajax_maybe_dismiss_notice
	 */
	public function test_ajax_dismiss_ignores_wrong_action() {
		$notifications = new Notifications();
		$user_id       = $this->as_user_with_role( 'administrator' );

		$_POST['action'] = 'some_other_action';

		$notifications->ajax_maybe_dismiss_notice();

		$this->assertSame( '', get_user_meta( $user_id, 'classifai_dismissed_v3_migration_completed', true ) );
	}
}
