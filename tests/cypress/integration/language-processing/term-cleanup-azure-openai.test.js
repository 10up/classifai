describe( '[Language processing] Term Cleanup - Azure OpenAI Tests', () => {
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
		cy.selectProvider( 'azure_openai_embeddings' );
		cy.get( 'input#azure_openai_embeddings_endpoint_url' )
			.clear()
			.type( 'https://e2e-test-azure-openai.test/' );
		cy.get( 'input#azure_openai_embeddings_api_key' )
			.clear()
			.type( 'password' );
		cy.get( 'input#azure_openai_embeddings_deployment' )
			.clear()
			.type( 'test' );

		// Change all settings.
		cy.get( '#use_ep' ).check();
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
