describe( '[Language processing] Smart 404 - OpenAI Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save Smart 404 settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_smart_404'
		);

		// Enabled Feature.
		cy.get( '#status' ).check();

		// Setup Provider.
		cy.get( '#provider' ).select( 'openai_embeddings' );
		cy.get( '#api_key' ).clear().type( 'password' );

		// Change all settings.
		cy.get( '#num' ).clear().type( 5 );
		cy.get( '#num_search' ).clear().type( 8000 );
		cy.get( '#threshold' ).clear().type( 2.55 );
		cy.get( '#rescore' ).check();
		cy.get( '#fallback' ).uncheck();
		cy.get( '#score_function' ).select( 'dot_product' );
		cy.get( '#classifai_feature_smart_404_roles_administrator' ).check();

		// Save settings.
		cy.get( '#submit' ).click();
	} );
} );
