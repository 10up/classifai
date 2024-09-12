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

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_smart_404'
		);

		cy.get( '.classifai-nlu-sections .notice-error' ).should( 'exist' );
	} );

	it( 'Can save Smart 404 settings', () => {
		cy.enableElasticPress();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_smart_404'
		);

		// Enabled Feature.
		cy.get( '#status' ).check();

		// Setup Provider.
		cy.get( '#provider' ).select( 'azure_openai_embeddings' );
		cy.get(
			'input[name="classifai_feature_smart_404[azure_openai_embeddings][endpoint_url]"]'
		)
			.clear()
			.type( 'https://e2e-test-azure-openai.test/' );
		cy.get(
			'input[name="classifai_feature_smart_404[azure_openai_embeddings][api_key]"]'
		)
			.clear()
			.type( 'password' );
		cy.get(
			'input[name="classifai_feature_smart_404[azure_openai_embeddings][deployment]"]'
		)
			.clear()
			.type( 'test' );

		// Change all settings.
		cy.get( '#num' ).clear().type( 4 );
		cy.get( '#num_search' ).clear().type( 7000 );
		cy.get( '#threshold' ).clear().type( 3.25 );
		cy.get( '#rescore' ).uncheck();
		cy.get( '#fallback' ).check();
		cy.get( '#score_function' ).select( 'l1_norm' );
		cy.get( '#classifai_feature_smart_404_roles_administrator' ).check();

		// Save settings.
		cy.get( '#submit' ).click();

		cy.disableElasticPress();
	} );
} );
