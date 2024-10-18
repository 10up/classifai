import { getChatGPTData } from '../../plugins/functions';

describe( '[Language processing] Title Generation Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save Azure OpenAI "Language Processing" title settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );

		cy.selectProvider( 'azure_openai' );
		cy.get( 'input#azure_openai_endpoint_url' )
			.clear()
			.type( 'https://e2e-test-azure-openai.test/' );
		cy.get( 'input#azure_openai_api_key' ).clear().type( 'password' );
		cy.get( 'input#azure_openai_deployment' ).clear().type( 'test' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.get( '#azure_openai_number_of_suggestions' ).clear().type( 1 );
		cy.saveFeatureSettings();
	} );

	it( 'Can see the generate titles button in a post', () => {
		const data = getChatGPTData();

		// Create test post.
		cy.createPost( {
			title: 'Test Azure OpenAI generate titles',
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

	it( 'Can see the generate titles button in a post (Classic Editor)', () => {
		cy.enableClassicEditor();

		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		const data = getChatGPTData();

		cy.visit( '/wp-admin/post-new.php' );

		cy.get( '#classifai-title-generation__title-generate-btn' ).click();
		cy.get( '#classifai-title-generation__modal' ).should( 'be.visible' );
		cy.get( '.classifai-title-generation__result-item' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );

		cy.get( '.classifai-title-generation__select-title' ).first().click();
		cy.get( '#classifai-title-generation__modal' ).should( 'not.be.visible' );
		cy.get( '#title' ).should( 'have.value', data );

		cy.disableClassicEditor();
	} );
} );
