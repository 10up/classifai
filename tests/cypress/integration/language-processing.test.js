describe( 'Language processing Tests', () => {
	before( () => {
		cy.login();
	} );

	it( 'Can save "Language Processing" settings', () => {
		cy.visit( '/wp-admin/admin.php?page=language_processing' );

		cy.get( '#classifai-settings-watson_url' ).clear().type( 'http://e2e-test-nlu-server.test/' );
		cy.get( '#classifai-settings-watson_password' ).clear().type( 'password' );

		cy.get( '#classifai-settings-post' ).check();
		cy.get( '#classifai-settings-page' ).check();
		cy.get( '#classifai-settings-draft' ).check();
		cy.get( '#classifai-settings-pending' ).check();
		cy.get( '#classifai-settings-private' ).check();
		cy.get( '#classifai-settings-publish' ).check();


		cy.get( '#classifai-settings-category' ).check();
		cy.get( '#classifai-settings-keyword' ).check();
		cy.get( '#classifai-settings-entity' ).check();
		cy.get( '#classifai-settings-concept' ).check();
		cy.get( '#submit' ).click();
	} );

	it( 'Can select Watson taxonomies "Language Processing" settings', () => {
		cy.visit( '/wp-admin/admin.php?page=language_processing' );

		cy.get( '#classifai-settings-category_taxonomy' ).select( 'watson-category' );
		cy.get( '#classifai-settings-keyword_taxonomy' ).select( 'watson-keyword' );
		cy.get( '#classifai-settings-entity_taxonomy' ).select( 'watson-entity' );
		cy.get( '#classifai-settings-concept_taxonomy' ).select( 'watson-concept' );
		cy.get( '#submit' ).click();
	} );

	it( 'Can see Watson taxonomies under "Posts" Menu.', () => {
		cy.visit( '/wp-admin/edit.php' );

		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Categories")' )
			.should( 'have.length', 1 );
		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Keywords")' )
			.should( 'have.length', 1 );
		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Entities")' )
			.should( 'have.length', 1 );
		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Concepts")' )
			.should( 'have.length', 1 );
	} );

	it( 'Can create post with texonomies terms get created by ClassifAI', () => {
		const threshold = 0.70;
		// Create Test Post
		cy.createPost( {
			title: 'Test NLU post',
			content: 'Test NLU Content'
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( $body => {
			if ( 0 < $body.find( closePanelSelector ).length ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		['categories', 'keywords', 'concepts', 'entities'].forEach( taxonomy => {
			cy.verifyPostTaxonomyTerms( taxonomy, threshold );
		} );

	} );
} );
