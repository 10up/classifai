describe( '[Language processing] Classify Content (OpenAI) Tests', () => {
	before( () => {
		cy.login();
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);
		cy.get( '#enable_classification' ).check();
		cy.get( '#submit' ).click();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save OpenAI Embeddings "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);

		cy.get( '#api_key' ).clear().type( 'password' );
		cy.get( '#enable_classification' ).check();
		cy.get( '#openai_embeddings_post_types_post' ).check();
		cy.get( '#openai_embeddings_post_statuses_publish' ).check();
		cy.get( '#openai_embeddings_taxonomies_category' ).check();
		cy.get( '#number' ).clear().type( 1 );
		cy.get( '#submit' ).click();
	} );

	it( 'Can create category and post and category will get auto-assigned', () => {
		// Remove custom taxonomies so those don't interfere with the test.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get( '#classifai-settings-category' ).uncheck();
		cy.get( '#classifai-settings-keyword' ).uncheck();
		cy.get( '#classifai-settings-entity' ).uncheck();
		cy.get( '#classifai-settings-concept' ).uncheck();
		cy.get( '#submit' ).click();

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
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first label'
				)
				.contains( 'Test' );
		} );
	} );

	it( 'Can see the privew on settings page', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);

		// Click the Preview button.
		const closePanelSelector = '#get-classifier-preview-data-btn';
		cy.get( closePanelSelector ).click();

		// Check the term is received is visible.
		cy.get( '.tax-row--Category' ).should( 'be.visible' );
	} );

	it( 'Can create category and post and category will not get auto-assigned if feature turned off', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);
		cy.get( '#enable_classification' ).uncheck();
		cy.get( '#submit' ).click();

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

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);

		cy.get( '#enable_classification' ).check();
		cy.get( '#openai_embeddings_post_types_post' ).check();
		cy.get( '#openai_embeddings_post_statuses_publish' ).check();
		cy.get( '#openai_embeddings_taxonomies_category' ).check();
		cy.get( '#number' ).clear().type( 1 );
		cy.get( '#submit' ).click();

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
		// Disable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);
		cy.get( '#enable_classification' ).uncheck();
		cy.get( '#submit' ).click();

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);
		cy.get( '#enable_classification' ).check();
		cy.get( '#submit' ).click();

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can enable/disable content classification feature by role', () => {
		// Remove custom taxonomies so those don't interfere with the test.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get( '#classifai-settings-category' ).uncheck();
		cy.get( '#classifai-settings-keyword' ).uncheck();
		cy.get( '#classifai-settings-entity' ).uncheck();
		cy.get( '#classifai-settings-concept' ).uncheck();
		cy.get( '#submit' ).click();

		// Disable admin role.
		cy.disableFeatureForRoles(
			'classification',
			[ 'administrator' ],
			'openai_embeddings'
		);

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable admin role.
		cy.enableFeatureForRoles(
			'classification',
			[ 'administrator' ],
			'openai_embeddings'
		);

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can enable/disable content classification feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles(
			'classification',
			[ 'administrator' ],
			'openai_embeddings'
		);

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable feature for admin user.
		cy.enableFeatureForUsers(
			'classification',
			[ 'admin' ],
			'openai_embeddings'
		);

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'User can opt-out content classification feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'classification', 'openai_embeddings' );

		// opt-out
		cy.optOutFeature( 'classification' );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// opt-in
		cy.optInFeature( 'classification' );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );
} );
