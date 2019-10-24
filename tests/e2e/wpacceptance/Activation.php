<?php
/**
 * The plugin activates correctly.
 *
 * @package wpacceptance
 */
/**
 * PHPUnit test class
 */
class AdminPostTest extends \TestCaseBase {

	/**
	 * @testdox Plugin successfully activates.
	 */
	public function testPostPublish() {
		$actor = $this->openBrowserPage();
		$actor->loginAs( 'admin' );

		// Activate the plugin.
		$this->activatePlugin( $actor );

		$actor->seeText( 'Plugin activated.', '#message' );
	}
}
