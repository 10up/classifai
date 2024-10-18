describe( '[Language processing] Smart 404 - OpenAI Tests', () => {
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
		cy.selectProvider( 'openai_embeddings' );
		cy.get( '#openai_api_key' ).clear().type( 'password' );

		// Change all settings.
		cy.get( '#feature_smart_404_num' ).clear().type( 5 );
		cy.get( '#feature_smart_404_num_search' ).clear().type( 8000 );
		cy.get( '#feature_smart_404_threshold' ).clear().type( 2.55 );
		cy.get( '#feature_smart_404_rescore' ).check();
		cy.get( '#feature_smart_404_fallback' ).uncheck();
		cy.get( '#feature_smart_404_score_function' ).select( 'dot_product' );
		cy.allowFeatureToAdmin();

		// Save settings.
		cy.saveFeatureSettings();

		cy.disableElasticPress();
	} );
} );
