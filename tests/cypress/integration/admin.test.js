describe( 'Admin can login and make sure plugin is activated', () => {
	beforeEach( () => {
		cy.login();
	} );

	it( 'Can deactivate and activate plugin', () => {
		cy.deactivatePlugin( 'classifai' );
		cy.activatePlugin( 'classifai' );
	} );

	it( 'Can see "ClassifAI" menu and Can visit "ClassifAI" settings page.', () => {
		cy.visit( '/wp-admin/' );

		// Check ClassifAI menu.
		cy.get( '#adminmenu li#menu-tools ul.wp-submenu li' ).contains(
			'ClassifAI'
		);

		// Check Heading
		cy.visit( '/wp-admin/tools.php?page=classifai' );
		cy.get( '#wpbody h2' ).contains( 'Classification Settings' );
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a' ).first().contains( 'Language Processing' );
	} );

	it( 'Can visit "Language Processing" settings page.', () => {
		// Check Selected Navigation menu
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a.active-tab' )
			.first()
			.contains( 'Language Processing' );
	} );

	it( 'Can see "Image Processing" menu and Can visit "Image Processing" settings page.', () => {
		// Check Selected Navigation menu
		cy.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a.active-tab' )
			.first()
			.contains( 'Image Processing' );
	} );
} );
