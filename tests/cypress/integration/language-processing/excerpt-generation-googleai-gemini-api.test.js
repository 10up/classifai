import { getGeminiAPIData } from '../../plugins/functions';

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

	it( 'Can save Google AI (Gemini API) "Language Processing" settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'googleai_gemini_api' );
		cy.get( 'input#googleai_gemini_api_api_key' )
			.clear()
			.type( 'password' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.get( '#excerpt_length' ).clear().type( 35 );
		cy.saveFeatureSettings();
	} );

	it( 'Can see the generate excerpt button in a post', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.disableClassicEditor();

		const data = getGeminiAPIData();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT post',
			content: 'Test GPT content',
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
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Excerpt")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button exists.
			cy.wrap( $panel )
				.find( '.editor-post-excerpt button' )
				.should( 'exist' );

			// Click on button and verify data loads in.
			cy.wrap( $panel ).find( '.editor-post-excerpt button' ).click();
			cy.wrap( $panel ).find( 'textarea' ).should( 'have.value', data );
		} );
	} );

	it( 'Can see the generate excerpt button in a post (Classic Editor)', () => {
		cy.enableClassicEditor();

		cy.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		const data = getGeminiAPIData();

		cy.classicCreatePost( {
			title: 'Excerpt test classic',
			content: 'Test GPT content.',
			postType: 'post',
		} );

		// Ensure excerpt metabox is shown.
		cy.get( '#show-settings-link' ).click();
		cy.get( '#postexcerpt-hide' ).check( { force: true } );

		// Verify button exists.
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).should( 'exist' );

		// Click on button and verify data loads in.
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).click();
		cy.get( '#excerpt' ).should( 'have.value', data );

		cy.disableClassicEditor();
	} );
} );
