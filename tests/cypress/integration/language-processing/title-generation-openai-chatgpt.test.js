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

	it( 'Can save OpenAI ChatGPT "Language Processing" title settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'openai_chatgpt' );
		cy.get( '#openai_chatgpt_api_key' ).clear().type( 'password' );
		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.get( '#openai_chatgpt_number_of_suggestions' ).type( 1 );
		cy.saveFeatureSettings();
	} );

	it( 'Can see the generate titles button in a post', () => {
		const data = getChatGPTData();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT generate titles',
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

	it( 'Can set multiple custom title generation prompts, select one as the default and delete one.', () => {
		cy.disableClassicEditor();
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);

		// Add three custom prompts.
		cy.get( 'button.components-button.action__add_prompt' )
			.click()
			.click()
			.click();
		cy.get(
			'.classifai-prompts div.classifai-field-type-prompt-setting'
		).should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get( '#classifai-prompt-setting-1 .classifai-prompt-title input' )
			.clear()
			.type( 'First custom prompt' );
		cy.get( '#classifai-prompt-setting-1 .classifai-prompt-text textarea' )
			.clear()
			.type( 'This is our first custom title prompt' );

		cy.get( '#classifai-prompt-setting-2 .classifai-prompt-title input' )
			.clear()
			.type( 'Second custom prompt' );
		cy.get( '#classifai-prompt-setting-2 .classifai-prompt-text textarea' )
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get( '#classifai-prompt-setting-3 .classifai-prompt-title input' )
			.clear()
			.type( 'Third custom prompt' );
		cy.get( '#classifai-prompt-setting-3 .classifai-prompt-text textarea' )
			.clear()
			.type( 'This is a custom title prompt' );

		// Set the third prompt as our default.
		cy.get(
			'#classifai-prompt-setting-3 .actions-rows button.action__set_default'
		).click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'#classifai-prompt-setting-2 .actions-rows button.action__remove_prompt'
		).click( { force: true } );
		cy.get( 'div.components-confirm-dialog button.is-primary' ).click();
		cy.get(
			'.classifai-prompts div.classifai-field-type-prompt-setting'
		).should( 'have.length', 3 );

		cy.saveFeatureSettings();

		const data = getChatGPTData( 'title' );

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT generate titles',
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

	it( 'Can enable/disable title generation feature', () => {
		// Disable features.
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifyTitleGenerationEnabled( false );

		// Enable feature.
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifyTitleGenerationEnabled( true );
	} );

	it( 'Can enable/disable title generation feature by role', () => {
		// Enable feature.
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_title_generation', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyTitleGenerationEnabled( false );

		// Enable admin role.
		cy.enableFeatureForRoles( 'feature_title_generation', [
			'administrator',
		] );

		// Verify that the feature is available.
		cy.verifyTitleGenerationEnabled( true );
	} );

	it( 'Can enable/disable title generation feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_title_generation', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyTitleGenerationEnabled( false );

		// Enable feature for admin user.
		cy.enableFeatureForUsers( 'feature_title_generation', [ 'admin' ] );

		// Verify that the feature is available.
		cy.verifyTitleGenerationEnabled( true );
	} );

	it( 'User can opt-out title generation feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_title_generation' );

		// opt-out
		cy.optOutFeature( 'feature_title_generation' );

		// Verify that the feature is not available.
		cy.verifyTitleGenerationEnabled( false );

		// opt-in
		cy.optInFeature( 'feature_title_generation' );

		// Verify that the feature is available.
		cy.verifyTitleGenerationEnabled( true );
	} );
} );
