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
	public function testSettings() {
		$actor = $this->openBrowserPage();
		$actor->loginAs( 'admin' );

		// Go to the settings page and test.
		$actor->moveTo( '/wp-admin/admin.php?page=classifai_settings' );
		$actor->seeText( 'Registered Email', 'label[for="email"]' );
		$actor->seeText( 'Registration Key', 'label[for="license_key"]' );
	}

	/**
	 * @testdox Language Processing credentials are set.
	 */
	public function testLanguageProcessingSettings() {
		$actor = $this->openBrowserPage();
		$actor->loginAs( 'admin' );

		// Go to the settings page and test.
		$actor->moveTo( '/wp-admin/admin.php?page=language_processing' );
		$this->assertTrue( '' != $actor->getElementProperty( '#classifai-settings-watson_url', 'value' ) );
		$this->assertTrue( '' != $actor->getElementProperty( '#classifai-settings-watson_username', 'value' ) );
		$this->assertTrue( '' != $actor->getElementProperty( '#classifai-settings-watson_password', 'value' ) );
	}

	/**
	 * @testdox Image Processing credentials are set.
	 */
	public function testImageProccessingSettings() {
		$actor = $this->openBrowserPage();
		$actor->loginAs( 'admin' );

		// Go to the settings page and test.
		$actor->moveTo( '/wp-admin/admin.php?page=image_processing' );
		$this->assertTrue( '' != $actor->getElementProperty( '#classifai-settings-url', 'value' ) );
		$this->assertTrue( '' != $actor->getElementProperty( '#classifai-settings-api_key', 'value' ) );
	}

	/**
	 * @testdox If the user enables the plugin, it should add the setting page in the WordPress Dashboard.
	 */
	public function testAdminMenuShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->seeText( 'ClassifAI' );
	}

	/**
	 * @testdox If the user enables the plugin, it should add Language Processing and Image Processing as submenus.
	 */
	public function testAdminSubmenuShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/admin.php?page=classifai_settings' );

		$I->seeLink( 'Language Processing' );

		$I->seeLink( 'Image Processing' );
	}

	/**
	 * @testdox When the user click on ClassifAI top menu, it shows registration form.
	 */
	public function testRegistrationFormShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/admin.php?page=classifai_settings' );

		$I->seeText( 'Registered Email ');

		$I->seeText( 'Registration Key');
	}

	/**
	 * @testdox When the user goes to the Language Processing submenu, it loads the Watson settings page.
	 */
	public function testWatsonSettingsShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->seeText( 'Natural Language Understanding' );

		$I->seeText( 'Entity' );
	}

	/**
	 * @testdox When the user goes to the Image Processing submenu, it loads the Computer Vision settings page.
	 */
	public function testComputerVisionSettingsShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/admin.php?page=image_processing' );

		$I->seeText( 'Computer Vision' );

		$I->seeText( 'Automatically Caption Images' );
	}
}

