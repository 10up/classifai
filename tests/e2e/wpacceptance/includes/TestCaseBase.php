<?php
/**
 * Test case class that provides us with some helper functionality.
 *
 * @package Classifai
 */
use WPAcceptance\Log;

/**
 * Class extends \WPAcceptance\PHPUnit\TestCase
 */
class TestCaseBase extends \WPAcceptance\PHPUnit\TestCase {
	/**
	 * Activate the plugin.
	 *
	 * @param \WPAcceptance\PHPUnit\Actor $actor The actor.
	 */
	protected function activatePlugin( $actor ) {
		// Activate the plugin.
		$actor->moveTo( '/wp-admin/plugins.php' );
		try {
			$element = $actor->getElement( '[data-slug="classifai"] .deactivate a' );
			if ( $element ) {
				$actor->click( $element );
				$actor->waitUntilElementVisible( '#message' );
			}
		} catch ( \Exception $e ) {}
		$actor->click( '[data-slug="classifai"] .activate a' );
		$actor->waitUntilElementVisible( '#message' );
	}
}
