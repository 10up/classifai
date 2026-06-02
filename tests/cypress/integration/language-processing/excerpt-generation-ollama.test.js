import { getChatGPTData } from '../../plugins/functions';

describe( '[Language processing] Excerpt Generation Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.enableFeature();
		cy.get( '.settings-allowed-post-types input#post' ).check();
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save Ollama settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'ollama' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();

		cy.get( '#ollama_model' ).select( 'deepseek-llm:latest' );
		cy.saveFeatureSettings();
	} );

	it( 'Can see the generate excerpt button in a post', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.disableClassicEditor();

		const data = getChatGPTData();

		// Create test post.
		cy.createPost( {
			title: 'Test Ollama Excerpt post',
			content: 'Test content',
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

		// Find and open the excerpt panel.
		const panelButtonSelector = `.editor-post-excerpt__dropdown button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Open panel.
			cy.wrap( $panelButton ).click();

			// Click on button and verify data loads in.
			cy.get( '.classifai-excerpt-generation button' ).click();
			cy.get( '.editor-post-excerpt .editor-post-excerpt__textarea textarea' ).should( 'have.value', data );
		} );
	} );

	it( 'Can see the generate excerpt button in a post (Classic Editor)', () => {
		cy.enableClassicEditor();

		cy.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		const data = getChatGPTData();

		cy.classicCreatePost( {
			title: 'Ollama excerpt test classic',
			content: 'Test content.',
			postType: 'post',
		} );

		// Ensure excerpt metabox is shown.
		cy.get( '#show-settings-link' ).click();
		cy.get( '#postexcerpt-hide' ).check( { force: true } );

		// Verify button exists.
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).should(
			'exist'
		);

		// Click on button and verify data loads in.
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).click();
		cy.get( '#excerpt' ).should( 'have.value', data );

		cy.disableClassicEditor();
	} );
} );
