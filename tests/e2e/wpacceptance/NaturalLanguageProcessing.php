<?php
/**
 * Test Watson features.
 *
 * @package wpacceptance
 */
/**
 * PHPUnit test class
 */
class NaturalLanguageProcessing extends \TestCaseBase {

	/**
	 * @testdox When the user enables/disables the Category feature, it shows/hides Watson Categories submenu under Posts and the meta box in post.php screen.
	 */
	public function testCategoryFeature() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->seeLink( 'Watson Categories' );

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->uncheckOptions( '#classifai-settings-category' );

		$I->click( '#submit' );

		$I->waitUntilNavigation();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->dontSeeLink( 'Watson Categories' );
	}

	/**
	 * @testdox When the user enables/disables the Keyword feature, it shows/hides Watson Keywords submenu under Posts and the meta box in post.php screen.
	 */
	public function testKeywordFeature() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->seeLink( 'Watson Keywords' );

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->uncheckOptions( '#classifai-settings-keyword' );

		$I->click( '#submit' );

		$I->waitUntilNavigation();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->dontSeeLink( 'Watson Keywords' );
	}

	/**
	 * @testdox When the user enables/disables the Concept feature, it shows/hides Watson Concepts submenu under Posts and the meta box in post.php screen.
	 */
	public function testConceptFeature() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->seeLink( 'Watson Concepts' );

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->uncheckOptions( '#classifai-settings-concept' );

		$I->click( '#submit' );

		$I->waitUntilNavigation();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->dontSeeLink( 'Watson Concepts' );
	}

	/**
	 * @testdox When the user enables/disables the Entity feature, it shows/hides Watson Entities submenu under Posts and the meta box in post.php screen.
	 */
	public function testEntityFeature() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->seeLink( 'Watson Entities' );

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->uncheckOptions( '#classifai-settings-entity' );

		$I->click( '#submit' );

		$I->waitUntilNavigation();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->dontSeeLink( 'Watson Entities' );
	}

	/**
	 * @testdox When the user enables/disables Posts under Post Types to Classify, it shows/hides Watson Keywords and other submenus under Posts menu.
	 */
	public function testTogglePost() {

		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->seeText( 'Watson Categories', '#taxonomy-watson-category' );

		$I->seeText( 'Watson Keywords', '#taxonomy-watson-keyword' );

		$I->seeText( 'Watson Concepts', '#taxonomy-watson-concept' );

		$I->seeText( 'Watson Entities', '#taxonomy-watson-entity' );

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->uncheckOptions( '#classifai-settings-post' );

		$I->click( '#submit' );

		$I->waitUntilNavigation();

		$I->moveTo( 'wp-admin/edit.php' );

		$I->dontSeeText( 'Watson Categories', '#taxonomy-watson-category' );

		$I->dontSeeText( 'Watson Keywords', '#taxonomy-watson-keyword' );

		$I->dontSeeText( 'Watson Concepts', '#taxonomy-watson-concept' );

		$I->dontSeeText( 'Watson Entities', '#taxonomy-watson-entity' );

	}

	/**
	 * @testdox When the user enables/disables Pages under Post Types to Classify, it shows/hides Watson Keywords and other submenus under Pages menu.
	 */
	public function testTogglePage() {

		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php?post_type=page' );

		$I->seeText( 'Watson Categories', '#taxonomy-watson-category' );

		$I->seeText( 'Watson Keywords', '#taxonomy-watson-keyword' );

		$I->seeText( 'Watson Concepts', '#taxonomy-watson-concept' );

		$I->seeText( 'Watson Entities', '#taxonomy-watson-entity' );

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->uncheckOptions( '#classifai-settings-page' );

		$I->click( '#submit' );

		$I->waitUntilNavigation();

		$I->moveTo( 'wp-admin/edit.php?post_type=page' );

		$I->dontSeeText( 'Watson Categories', '#taxonomy-watson-category' );

		$I->dontSeeText( 'Watson Keywords', '#taxonomy-watson-keyword' );

		$I->dontSeeText( 'Watson Concepts', '#taxonomy-watson-concept' );

		$I->dontSeeText( 'Watson Entities', '#taxonomy-watson-entity' );

	}

	/**
	 * @testdox When the user enables/disables Media under Post Types to Classify, it shows/hides Watson Keywords and other submenus under Media menu.
	 */
	public function testToggleMedia() {

		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/upload.php' );

		$I->dontSeeLink( 'Watson Categories', 'edit-tags.php?taxonomy=watson-category&post_type=attachment' );

		$I->dontSeeLink( 'Watson Keywords', 'edit-tags.php?taxonomy=watson-keyword&post_type=attachment' );

		$I->dontSeeLink( 'Watson Concepts', 'edit-tags.php?taxonomy=watson-concept&post_type=attachment' );

		$I->dontSeeLink( 'Watson Entities', 'edit-tags.php?taxonomy=watson-entity&post_type=attachment' );

		$I->moveTo( 'wp-admin/admin.php?page=language_processing' );

		$I->checkOptions( '#classifai-settings-attachment' );

		$I->click( '#submit' );

		$I->waitUntilNavigation();

		$I->moveTo( 'wp-admin/upload.php' );

		$I->seeLink( 'Watson Categories', 'edit-tags.php?taxonomy=watson-category&post_type=attachment' );

		$I->seeLink( 'Watson Keywords', 'edit-tags.php?taxonomy=watson-keyword&post_type=attachment' );

		$I->seeLink( 'Watson Concepts', 'edit-tags.php?taxonomy=watson-concept&post_type=attachment' );

		$I->seeLink( 'Watson Entities', 'edit-tags.php?taxonomy=watson-entity&post_type=attachment' );
	}

	public function testWatsonWorks() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php?post_type=page' );

		$I->hover( '#post-2' );

		$I->click( '#post-2 .editinline' );

		sleep( 1 );

		$I->click( '#edit-2 .button.save' );

		sleep( 3 );

		$this->assertNotEmpty( $I->getElementInnerText( '#post-2 .taxonomy-watson-category' ) );

		$this->assertNotEmpty( $I->getElementInnerText( '#post-2 .taxonomy-watson-keyword' ) );

		$this->assertNotEmpty( $I->getElementInnerText( '#post-2 .taxonomy-watson-concept' ) );

		$this->assertNotEmpty( $I->getElementInnerText( '#post-2 .taxonomy-watson-entity' ) );
	}

	public function testClassifyMenuItemShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/edit.php?post_type=page' );

		$I->hover( '#post-2' );

		$I->seeLink( 'Classify' );
	}
}
