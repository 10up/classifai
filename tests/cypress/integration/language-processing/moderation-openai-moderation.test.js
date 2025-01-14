describe( '[Language processing] Moderation Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'language_processing/feature_moderation' );
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save OpenAI Moderation "Language Processing" settings', () => {
		cy.visitFeatureSettings( 'language_processing/feature_moderation' );

		cy.selectProvider( 'openai_moderation' );
		cy.get( '#openai_api_key' ).clear().type( 'password' );
		cy.enableFeature();
		cy.get( '.settings-moderation-content-types input#comments' ).check();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	it( 'Can run moderation on a comment', () => {
		cy.visit( '/wp-admin/edit-comments.php' );

		cy.get( '#cb-select-1' ).check();
		cy.get( '#bulk-action-selector-top' ).select( 'feature_moderation' );
		cy.get( '#doaction' ).click();

		cy.get( '#comment-1 .column-moderation_flagged div' ).contains( 'Yes' );
		cy.get( '#comment-1 .column-moderation_flags div' ).contains(
			'harassment/threatening, violence'
		);
	} );

	it( 'Can enable/disable moderation feature', () => {
		// Disable features.
		cy.visitFeatureSettings( 'language_processing/feature_moderation' );
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifyModerationEnabled( false );

		// Enable feature.
		cy.visitFeatureSettings( 'language_processing/feature_moderation' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifyModerationEnabled( true );
	} );

	it( 'Can enable/disable moderation feature by role', () => {
		cy.visitFeatureSettings( 'language_processing/feature_moderation' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_moderation', [ 'administrator' ] );

		// Verify that the feature is not available.
		cy.verifyModerationEnabled( false );

		// enable admin role.
		cy.enableFeatureForRoles( 'feature_moderation', [ 'administrator' ] );

		// Verify that the feature is available.
		cy.verifyModerationEnabled( true );
	} );

	it( 'Can enable/disable moderation feature by user', () => {
		cy.visitFeatureSettings( 'language_processing/feature_moderation' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_moderation', [ 'administrator' ] );

		cy.enableFeatureForUsers( 'feature_moderation', [] );

		// Verify that the feature is not available.
		cy.verifyModerationEnabled( false );

		// Enable feature for admin user.
		cy.enableFeatureForUsers( 'feature_moderation', [ 'admin' ] );

		// Verify that the feature is available.
		cy.verifyModerationEnabled( true );
	} );

	it( 'User can opt-out of moderation feature', () => {
		cy.visitFeatureSettings( 'language_processing/feature_moderation' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_moderation' );

		// opt-out
		cy.optOutFeature( 'feature_moderation' );

		// Verify that the feature is not available.
		cy.verifyModerationEnabled( false );

		// opt-in
		cy.optInFeature( 'feature_moderation' );

		// Verify that the feature is available.
		cy.verifyModerationEnabled( true );
	} );
} );
