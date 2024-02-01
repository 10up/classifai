describe( '[Language processing] Moderation Tests', () => {
	before( () => {
		cy.login();
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_moderation'
		);
		cy.get( '#submit' ).click();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save OpenAI Moderation "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_moderation'
		);

		cy.get( '#api_key' ).clear().type( 'password' );
		cy.get( '#status' ).check();
		cy.get(
			'#classifai_feature_moderation_content_types_comments'
		).check();
		cy.get( '#role_based_access' ).check();
		cy.get( '#classifai_feature_moderation_roles_administrator' ).check();
		cy.get( '#submit' ).click();
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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_moderation'
		);
		cy.get( '#status' ).uncheck();
		cy.get( '#submit' ).click();

		// Verify that the feature is not available.
		cy.verifyModerationEnabled( false );

		// Enable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_moderation'
		);
		cy.get( '#status' ).check();
		cy.get( '#submit' ).click();

		// Verify that the feature is available.
		cy.verifyModerationEnabled( true );
	} );

	it( 'Can enable/disable moderation feature by role', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_moderation'
		);
		cy.get( '#status' ).check();
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_moderation'
		);
		cy.get( '#status' ).check();
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_moderation'
		);
		cy.get( '#status' ).check();
		cy.get( '#submit' ).click();

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
