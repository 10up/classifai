describe( '[Language processing] Classify Content (Ollama) Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.enableFeature();
		cy.selectProvider( 'ollama_embeddings' );
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save Ollama Embeddings "Language Processing" settings', () => {
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.selectProvider( 'ollama_embeddings' );

		cy.saveFeatureSettings();
		cy.get( '#ollama_embeddings_model' ).select(
			'nomic-embed-text:latest'
		);
		cy.get(
			'.settings-allowed-post-statuses input#post_status_publish'
		).check();
		cy.get( '#category-enabled' ).check();
		cy.get( '#category-threshold' ).clear().type( 100 );

		cy.saveFeatureSettings();
	} );

	it( 'Can create category and post and category will get auto-assigned', () => {
		// Create test term.
		cy.deleteAllTerms( 'category' );
		cy.createTerm( 'Test', 'category' );

		// Create test post.
		cy.createPost( {
			title: 'Test Ollama embeddings',
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
			title: 'Ollama Embeddings test classic',
			content: "This feature uses Ollama's Embeddings capabilities.",
			postType: 'post',
		} );

		cy.get( '#classifai_language_processing_metabox' ).should( 'exist' );
		cy.get( '#classifai-process-content' ).check();

		cy.disableClassicEditor();
	} );
} );
