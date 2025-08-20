describe( '[Language processing] Term Cleanup - Ollama Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'language_processing/feature_term_cleanup' );
		cy.enableFeature();
		cy.selectProvider( 'ollama_embeddings' );
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( "ElasticPress option is hidden if the plugin isn't active", () => {
		cy.disableElasticPress();
		cy.get( '#use_ep' ).should( 'be.disabled' );
	} );

	it( 'Can save Term Cleanup settings', () => {
		cy.enableElasticPress();

		// Setup Provider.
		cy.selectProvider( 'ollama_embeddings' );
		cy.get( '#ollama_embeddings_model' ).select(
			'nomic-embed-text:latest'
		);

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
