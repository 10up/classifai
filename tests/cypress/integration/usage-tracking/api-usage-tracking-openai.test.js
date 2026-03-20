describe( '[Usage Tracking] API Usage Tracking (OpenAI) Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save AI Usage Tracking settings', () => {
		cy.visitFeatureSettings( 'usage_tracking/api_usage_tracking' );

		cy.enableFeature();
		cy.selectProvider( 'openai_usage_tracking' );
		cy.get( '#openai_usage_tracking_api_key' ).clear().type( 'sk-admin-password' );
		cy.get( '#openai_usage_tracking_project_id' ).clear().type( 'proj_cypress_openai_1' );

		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	it( 'Can see "AI Usage Tracking" Widget on WP Dashboard', () => {
		cy.visit( `/wp-admin/index.php` );

		const aiUsageWidget = cy.get( '#dashboard-widgets #classifai_api_usage' );

		aiUsageWidget.should( 'exist' );
		aiUsageWidget.get( 'h2' ).should( 'contain', 'AI Usage Tracking' );
		aiUsageWidget.get( '.classifai-api-usage-list li:first-child' ).should( 'contain', 'This month:' );
		aiUsageWidget.get( '.classifai-api-usage-list li:first-child' ).should( 'contain', 'Updating…' );
		aiUsageWidget.get( '.classifai-api-usage-list li:nth-child(2)' ).should( 'contain', 'Year to date:' );
		aiUsageWidget.get( '.classifai-api-usage-list li:nth-child(2)' ).should( 'contain', 'Updating…' );
		aiUsageWidget.get( '.classifai-api-usage-list li:nth-child(3)' ).should( 'contain', 'All time:' );
		aiUsageWidget.get( '.classifai-api-usage-list li:nth-child(3)' ).should( 'contain', 'Updating…' );
		aiUsageWidget.get( '.classifai-api-usage-updated' ).should( 'not.exist' );
	} );
} );
