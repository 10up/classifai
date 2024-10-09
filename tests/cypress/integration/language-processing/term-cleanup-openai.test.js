describe( '[Language processing] Term Cleanup - OpenAI Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( "ElasticPress option is hidden if the plugin isn't active", () => {
		cy.disableElasticPress();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_term_cleanup'
		);

		cy.get( '#use_ep' ).should( 'be:hidden' );
	} );

	it( 'Can save Term Cleanup settings', () => {
		cy.enableElasticPress();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_term_cleanup'
		);

		// Enabled Feature.
		cy.get( '#status' ).check();

		// Setup Provider.
		cy.get( '#provider' ).select( 'openai_embeddings' );
		cy.get( '#api_key' ).clear().type( 'password' );

		// Change all settings.
		cy.get( '#use_ep' ).check();
		cy.get( '#category' ).uncheck();
		cy.get( '#category_threshold' ).clear().type( 80 );
		cy.get( '#post_tag' ).check();
		cy.get( '#post_tag_threshold' ).clear().type( 80 );

		// Save settings.
		cy.get( '#submit' ).click();

		// Ensure settings page now exists.
		cy.visit(
			'/wp-admin/tools.php?page=classifai-term-cleanup&tax=post_tag'
		);

		cy.get( '.classifai-wrapper .submit-wrapper' ).should( 'exist' );

		cy.disableElasticPress();
	} );
} );
