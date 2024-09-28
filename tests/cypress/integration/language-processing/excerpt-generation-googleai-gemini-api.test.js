import { getGeminiAPIData } from '../../plugins/functions';

describe( '[Language processing] Excerpt Generation Tests', () => {
	before( () => {
		cy.login();
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_excerpt_generation'
		);
		cy.get( '#status' ).check();
		cy.get(
			'#classifai_feature_excerpt_generation_post_types_post'
		).check();
		cy.get( '#submit' ).click();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save Google AI (Gemini API) "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_excerpt_generation'
		);
		cy.get( '#provider' ).select( 'googleai_gemini_api' );
		cy.get(
			'input[name="classifai_feature_excerpt_generation[googleai_gemini_api][api_key]"]'
		)
			.clear()
			.type( 'password' );

		cy.get( '#status' ).check();
		cy.get(
			'#classifai_feature_excerpt_generation_roles_administrator'
		).check();
		cy.get( '#length' ).clear().type( 35 );
		cy.get( '#submit' ).click();
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

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_excerpt_generation'
		);
		cy.get( '#status' ).check();
		cy.get( '#submit' ).click();

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
