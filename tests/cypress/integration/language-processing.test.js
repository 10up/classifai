describe( 'Language processing Tests', () => {
	before( () => {
		cy.login();
	} );

	it( 'Can save "Language Processing" settings', () => {
		cy.visit( '/wp-admin/admin.php?page=language_processing' );

		cy.get( '#classifai-settings-watson_url' ).clear().type( 'http://nlu-server.test/' );
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
} );
