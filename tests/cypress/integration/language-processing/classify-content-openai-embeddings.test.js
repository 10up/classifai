describe( '[Language processing] Classify Content (OpenAI) Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.enableFeature();
		cy.selectProvider( 'openai_embeddings' );
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save OpenAI Embeddings "Language Processing" settings', () => {
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.selectProvider( 'openai_embeddings' );
		cy.get( '#openai_api_key' ).clear().type( 'password' );
		cy.enableFeature();
		cy.get( '.settings-allowed-post-types input#post' ).check();
		cy.get(
			'.settings-allowed-post-statuses input#post_status_publish'
		).check();
		cy.get( '#category-enabled' ).check();
		cy.get( '#category-threshold' ).clear().type( 100 ); // "Test" requires 80% confidence. At 81%, it does not apply.
		cy.saveFeatureSettings();
	} );

	it( 'Can create category and post and category will get auto-assigned', () => {
		// Create test term.
		cy.deleteAllTerms( 'category' );
		cy.createTerm( 'Test', 'category' );

		// Create test post.
		cy.createPost( {
			title: 'Test embeddings',
			content: 'Test embeddings content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the category panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Categories")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Ensure our test category is checked.
			cy.wrap( $panel )
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first input'
				)
				.should( 'be.checked' );
			cy.wrap( $panel )
				.find( '.editor-post-taxonomies__hierarchical-terms-list' )
				.children()
				.contains( 'Test' );
		} );
	} );

	// TODO: Update this test to use new previewer.
	it.skip( 'Can see the preview on the settings page', () => {
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.saveFeatureSettings();

		// Click the Preview button.
		const closePanelSelector = '#get-classifier-preview-data-btn';
		cy.get( closePanelSelector ).click();

		// Check the term is received and visible.
		cy.get( '.tax-row--category' ).should( 'exist' );
	} );

	it( 'Can create category and post and category will not get auto-assigned if feature turned off', () => {
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Create test term.
		cy.deleteAllTerms( 'category' );
		cy.createTerm( 'Test', 'category' );

		// Create test post.
		cy.createPost( {
			title: 'Test embeddings disabled',
			content: 'Test embeddings content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the category panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Categories")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Ensure our test category is not checked.
			cy.wrap( $panel )
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first input'
				)
				.should( 'be.checked' );
			cy.wrap( $panel )
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first label'
				)
				.contains( 'Uncategorized' );
		} );
	} );

	it( 'Can see the enable button in a post (Classic Editor)', () => {
		cy.enableClassicEditor();

		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.enableFeature();
		cy.get( '.settings-allowed-post-types input#post' ).check();
		cy.get(
			'.settings-allowed-post-statuses input#post_status_publish'
		).check();
		cy.get( '#category-enabled' ).check();
		cy.saveFeatureSettings();

		cy.classicCreatePost( {
			title: 'Embeddings test classic',
			content: "This feature uses OpenAI's Embeddings capabilities.",
			postType: 'post',
		} );

		cy.get( '#classifai_language_processing_metabox' ).should( 'exist' );
		cy.get( '#classifai-process-content' ).check();

		cy.disableClassicEditor();
	} );

	it( 'Can enable/disable content classification feature ', () => {
		cy.disableClassicEditor();

		// Disable feature.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable feature.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can enable/disable content classification feature by role', () => {
		// Remove custom taxonomies so those don't interfere with the test.
		cy.visitFeatureSettings( 'language_processing' );

		// Disable access for all users.
		cy.disableFeatureForUsers();

		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_classification', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable admin role.
		cy.enableFeatureForRoles( 'feature_classification', [
			'administrator',
		] );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can enable/disable content classification feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_classification', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable feature for admin user.
		cy.enableFeatureForUsers( 'feature_classification', [ 'admin' ] );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'User can opt-out content classification feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_classification', 'openai_embeddings' );

		// opt-out
		cy.optOutFeature( 'feature_classification' );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// opt-in
		cy.optInFeature( 'feature_classification' );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );
} );
