describe( 'Admin can login and make sure plugin is activated', () => {
	before( () => {
		cy.login();
	} );

	it( 'Can deactivate and activate plugin ', () => {
		cy.deactivatePlugin( 'classifai' );
		cy.activatePlugin( 'classifai' );
	} );

	it( 'Can see "ClassifAI" menu and Can visit "ClassifAI" settings page.', () => {
		// Check ClassifAI menu.
		cy.get( '#adminmenu li.toplevel_page_classifai_settings' ).contains( 'ClassifAI' );

		// Check Heading
		cy.visit( '/wp-admin/admin.php?page=classifai_settings' );
		cy.get( '#wpbody h2' ).contains( 'ClassifAI Settings' );
	} );

	it( 'Can see "Language Processing" menu and Can visit "Language Processing" settings page.', () => {
		// Check Language Processing menu.
		cy.get( 'li.toplevel_page_classifai_settings ul.wp-submenu li' )
			.filter( ':contains("Language Processing")' )
			.should( 'have.length', 1 );

		// Check Heading
		cy.visit( '/wp-admin/admin.php?page=language_processing' );
		cy.get( '#wpbody h2' ).contains( 'Language Processing' );
	} );

	it( 'Can see "Image Processing" menu and Can visit "Image Processing" settings page.', () => {
		// Check Language Processing menu.
		cy.get( 'li.toplevel_page_classifai_settings ul.wp-submenu li' )
			.filter( ':contains("Image Processing")' )
			.should( 'have.length', 1 );

		// Check Heading
		cy.visit( '/wp-admin/admin.php?page=image_processing' );
		cy.get( '#wpbody h2' ).contains( 'Image Processing' );
	} );
} );
