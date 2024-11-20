describe( '[Language processing] Smart 404 - Azure OpenAI Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( "See error message if ElasticPress isn't activate", () => {
		cy.disableElasticPress();

		cy.visitFeatureSettings( 'language_processing/feature_smart_404' );

		cy.get( '.elasticpress-required-notice.components-notice ' ).should(
			'exist'
		);
	} );

	it( 'Can save Smart 404 settings', () => {
		cy.enableElasticPress();

		cy.visitFeatureSettings( 'language_processing/feature_smart_404' );

		// Enabled Feature.
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
		cy.get( '#feature_smart_404_num' ).clear().type( 4 );
		cy.get( '#feature_smart_404_num_search' ).clear().type( 7000 );
		cy.get( '#feature_smart_404_threshold' ).clear().type( 3.25 );
		cy.get( '#feature_smart_404_rescore' ).uncheck();
		cy.get( '#feature_smart_404_fallback' ).check();
		cy.get( '#feature_smart_404_score_function' ).select( 'l1_norm' );
		cy.allowFeatureToAdmin();

		// Save settings.
		cy.saveFeatureSettings();

		cy.disableElasticPress();
	} );
} );
