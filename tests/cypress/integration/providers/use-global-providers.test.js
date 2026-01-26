import { getChatGPTData } from '../../plugins/functions';

describe( 'Use global Providers with Features', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Title Generation can use global OpenAI provider', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.selectProvider( 'openai_chatgpt' );

		// Delete existing API key first.
		cy.get(
			'.classifai-provider-override-credentials input[type="checkbox"]'
		).check();
		cy.get( '#openai_api_key' ).clear();
		cy.saveFeatureSettings();

		// Set to use global Provider credentials.
		cy.get(
			'.classifai-provider-override-credentials input[type="checkbox"]'
		).uncheck();
		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();

		const data = getChatGPTData();

		// Create test post.
		cy.createPost( {
			title: 'Test Global OpenAI generate titles',
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

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button,.editor-sidebar__panel .editor-post-panel__section .editor-post-card-panel`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Support pre WP 6.6+.
			const $newPanel = $panelButton.parents(
				'.editor-post-panel__section'
			);

			if ( $newPanel.length === 0 ) {
				// Find the panel container.
				const $panel = $panelButton.parents(
					'.components-panel__body'
				);

				// Open panel.
				if ( ! $panel.hasClass( 'is-opened' ) ) {
					cy.wrap( $panelButton ).click();
				}

				// Verify button exists.
				cy.wrap( $panel )
					.find( '.classifai-post-status button.title' )
					.should( 'exist' );

				// Click on button and verify modal shows.
				cy.wrap( $panel )
					.find( '.classifai-post-status button.title' )
					.click();
			} else {
				// Verify button exists.
				cy.wrap( $newPanel )
					.find( '.classifai-post-status button.title' )
					.should( 'exist' );

				// Click on button and verify modal shows.
				cy.wrap( $newPanel )
					.find( '.classifai-post-status button.title' )
					.click();
			}
		} );

		cy.get( '.title-modal' ).should( 'exist' );

		// Click on button and verify data loads in.
		cy.get( '.title-modal .classifai-title' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );
		cy.get( '.title-modal .classifai-title' )
			.first()
			.find( 'button' )
			.click();

		cy.get( '.title-modal' ).should( 'not.exist' );
		cy.getBlockEditor()
			.find( '.editor-post-title__input' )
			.should( ( $el ) => {
				expect( $el.first() ).to.contain( data );
			} );
	} );

	it( 'Image Generation can use global Google AI provider', () => {
		cy.visitFeatureSettings(
			'image_processing/feature_image_generation'
		);
		cy.selectProvider( 'googleai_images' );

		// Delete existing API key first.
		cy.get(
			'.classifai-provider-override-credentials input[type="checkbox"]'
		).check();
		cy.get( '#googleai_gemini_api_key' ).clear();
		cy.saveFeatureSettings();

		// Set to use global Provider credentials.
		cy.get(
			'.classifai-provider-override-credentials input[type="checkbox"]'
		).uncheck();
		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();

		// Ensure we can generate images in the Media Library.
		cy.visit( '/wp-admin/upload.php' );
		cy.get(
			`.wp-has-current-submenu.wp-menu-open li a:contains("Generate Image")`
		).click();

		// Verify tab exists.
		cy.get( '#menu-item-generate' ).should( 'exist' );

		// Click into the tab and submit a prompt.
		cy.get( '#menu-item-generate' ).click();
		cy.get( '.prompt-view .prompt' ).type( 'A sunset over the mountains' );
		cy.get( '.prompt-view .button-generate' ).click();

		// Verify images show up.
		cy.get( '.generated-images ul li' ).should( 'have.length', 2 );
	} );
} );
