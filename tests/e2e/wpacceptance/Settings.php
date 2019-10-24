<?php
/**
 * The plugin settings page loads correctly.
 *
 * @package wpacceptance
 */
/**
 * PHPUnit test class
 */
class Settings extends \TestCaseBase {

	/**
	 * @testdox Plugin settings page loads correctly.
	 */
	public function testPostPublish() {
		$actor = $this->openBrowserPage();
		$actor->loginAs( 'admin' );

		// Activate the plugin.
		$this->activatePlugin( $actor );

		// Go to the settings page and test.
		$actor->moveTo( '/wp-admin/admin.php?page=classifai_settings' );
		$actor->seeText( 'Registered Email', 'label[for="email"]' );
		$actor->seeText( 'Registration Key', 'label[for="license_key"]' );
	}
}
