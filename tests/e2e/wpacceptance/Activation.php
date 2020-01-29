<?php
/**
 * The plugin activates correctly.
 *
 * @package wpacceptance
 */
/**
 * PHPUnit test class
 */
class ActivationTest extends \TestCaseBase {

	/**
	 * @testdox Plugin successfully activates.
	 */
	public function testActivation() {
		$actor = $this->openBrowserPage();
		$actor->loginAs( 'admin' );

		// Activate the plugin.
		$this->activatePlugin( $actor );

		$actor->seeText( 'Plugin activated.', '#message' );
	}
}
