describe( 'Language processing Tests', () => {
	before( () => {
		cy.login();

		// Ignore WP 5.2 Synchronous XHR error.
		Cypress.on( 'uncaught:exception', ( err ) => {
			if ( err.message.includes( 'Failed to execute \'send\' on \'XMLHttpRequest\': Failed to load \'http://localhost:8889/wp-admin/admin-ajax.php\': Synchronous XHR in page dismissal' ) ) {
				return false;
			}
		} );
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

	it( 'Can create post and taxonomy terms get created by ClassifAI (with default threshold)', () => {
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

	it( 'Can create post and taxonomy terms get created by ClassifAI (with 75 threshold)', () => {
		const threshold = 75;

		// Update Threshold to 75.
		cy.visit( '/wp-admin/admin.php?page=language_processing' );

		cy.get( '#classifai-settings-category_threshold' ).clear().type( threshold );
		cy.get( '#classifai-settings-keyword_threshold' ).clear().type( threshold );
		cy.get( '#classifai-settings-entity_threshold' ).clear().type( threshold );
		cy.get( '#classifai-settings-concept_threshold' ).clear().type( threshold );
		cy.get( '#submit' ).click();

		// Create Test Post
		cy.createPost( {
			title: 'Test NLU post with 75 Threshold',
			content: 'Test NLU Content with 75 Threshold'
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
			cy.verifyPostTaxonomyTerms( taxonomy, threshold / 100 );
		} );

	} );

	// Skiping this until issue get fixed.
	it.skip( 'Can create post and tags get created by ClassifAI', () => {
		const threshold = 75;
		cy.visit( '/wp-admin/admin.php?page=language_processing' );

		cy.get( '#classifai-settings-category_taxonomy' ).select( 'post_tag' );
		cy.get( '#classifai-settings-keyword_taxonomy' ).select( 'post_tag' );
		cy.get( '#classifai-settings-entity_taxonomy' ).select( 'post_tag' );
		cy.get( '#classifai-settings-concept_taxonomy' ).select( 'post_tag' );
		cy.get( '#submit' ).click();

		// Create Test Post
		cy.createPost( {
			title: 'Test NLU post for tags',
			content: 'Test NLU Content for tags'
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
		cy.verifyPostTaxonomyTerms( 'tags', threshold / 100 );
	} );

} );
