<?php

class ImageProcessing extends \TestCaseBase {
	/**
	 * @testdox With ClassifAI activated, it shows two buttons for generating alt and image tags in the media modal.
	 */
	public function testMediaModalGenerateButtonShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/upload.php?item=827' );

		sleep( 5 );

		$I->seeText( 'Generate', '#classifai-rescan-alt-tags' );

		$I->seeText( 'Generate', 'classifai-rescan-image-tags' );
	}

	/**
	 * @testdox If the image has image tags and/or alt tag, it shows two buttons for rescanning alt and image tags in the media modal.
	 */
	public function testMediaModalRescanButtonShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/upload.php?item=768' );

		sleep( 5 );

		$I->seeText( 'Rescan', '#classifai-rescan-alt-tags' );
	}

	/**
	 * @testdox With ClassifAI activated, it shows a metabox with Azure Computer Vision Scan as the title and two checkboxes for generating alt and image tags on the media edit page.
	 */
	public function testGenerateCheckboxesShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/post.php?post=1692&action=edit' );

		$I->seeText( 'Azure Computer Vision Scan' );

		$I->seeText( 'Generate Captions' );

		$I->seeText( 'Generate Tags' );
	}

	public function testRescanCheckboxesShows() {
		$I = $this->openBrowserPage();

		$I->login();

		$I->moveTo( 'wp-admin/post.php?post=763&action=edit' );

		$I->seeText( 'Azure Computer Vision Scan' );

		$I->seeText( 'Rescan Captions' );
	}
}
