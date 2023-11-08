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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		cy.get( '#api_key' ).clear().type( 'password' );
		cy.get( '#enable_titles' ).check();
		cy.get( '#openai_chatgpt_title_generation_roles_administrator' ).check();
		cy.get( '#number_titles' ).select( 1 );
		cy.get( '#submit' ).click();
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
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

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

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get( '#enable_titles' ).check();
		cy.get( '#submit' ).click();

		const data = getChatGPTData();

		cy.visit( '/wp-admin/post-new.php' );

		cy.get( '#classifai-openai__title-generate-btn' ).click();
		cy.get( '#classifai-openai__modal' ).should( 'be.visible' );
		cy.get( '.classifai-openai__result-item' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );

		cy.get( '.classifai-openai__select-title' ).first().click();
		cy.get( '#classifai-openai__modal' ).should( 'not.be.visible' );
		cy.get( '#title' ).should( 'have.value', data );

		cy.disableClassicEditor();
	} );

	it( 'Can set multiple custom title generation prompts, select one as the default and delete one.', () => {
		cy.disableClassicEditor();
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Add three custom prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom title prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom title prompt' );

		// Set the third prompt as our default.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );

		cy.get( '#submit' ).click();

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
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

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

	it( 'Can disable title generation feature', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable features.
		cy.get( '#enable_titles' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT generate titles disabled',
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
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button doesn't exist.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can enable/disable title generation feature by role', () => {
		// Enable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get( '#enable_titles' ).check();
		cy.get( '#submit' ).click();

		// Disable admin role.
		cy.disableFeatureForRoles('title_generation', ['administrator'], 'openai_chatgpt');

		// Verify that the feature is not available.
		cy.verifyTitleGenerationEnabled(false);

		// Enable admin role.
		cy.enableFeatureForRoles('title_generation', ['administrator'], 'openai_chatgpt');

		// Verify that the feature is available.
		cy.verifyTitleGenerationEnabled(true);
	} );

	it( 'Can enable/disable title generation feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles('title_generation', ['administrator'], 'openai_chatgpt');

		// Verify that the feature is not available.
		cy.verifyTitleGenerationEnabled(false);

		// Enable feature for admin user.
		cy.enableFeatureForUsers('title_generation', ['admin'], 'openai_chatgpt');

		// Verify that the feature is available.
		cy.verifyTitleGenerationEnabled(true);
	} );

	it( 'User can opt-out title generation feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut('title_generation', 'openai_chatgpt');

		// opt-out
		cy.optOutFeature('title_generation');

		// Verify that the feature is not available.
		cy.verifyTitleGenerationEnabled(false);

		// opt-in
		cy.optInFeature('title_generation');

		// Verify that the feature is available.
		cy.verifyTitleGenerationEnabled(true);
	} );
} );
