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

		cy.visitFeatureSettings( 'language_processing/feature_term_cleanup' );

		cy.get( '#use_ep' ).should( 'be.disabled' );
	} );

	it( 'Can save Term Cleanup settings', () => {
		cy.enableElasticPress();

		cy.visitFeatureSettings( 'language_processing/feature_term_cleanup' );

		// Enable Feature.
		cy.enableFeature();

		// Setup Provider.
		cy.selectProvider( 'openai_embeddings' );
		cy.get( '#openai_api_key' ).clear().type( 'password' );

		// Change all settings.
		cy.get( '#category-enabled' ).uncheck();
		cy.get( '#category-threshold' ).clear().type( 80 );
		cy.get( '#post_tag-enabled' ).check();
		cy.get( '#post_tag-threshold' ).clear().type( 80 );

		// Save settings.
		cy.saveFeatureSettings();

		// Ensure settings page now exists.
		cy.visit(
			'/wp-admin/tools.php?page=classifai-term-cleanup&tax=post_tag'
		);

		cy.get( '.classifai-wrapper .submit-wrapper' ).should( 'exist' );

		cy.disableElasticPress();
	} );
} );
