import { getChatGPTData } from '../../plugins/functions';

describe( '[Language processing] Excerpt Generation Tests', () => {
	it( 'Can save OpenAI ChatGPT "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		cy.get( '#api_key' ).clear().type( 'password' );

		cy.get( '#enable_excerpt' ).check();
		cy.get( '#excerpt_generation_role_based_access' ).check();
		cy.get( '#openai_chatgpt_excerpt_generation_roles_administrator' ).check();
		cy.get( '#length' ).clear().type( 35 );
		cy.get( '#submit' ).click();
	} );

	it( 'Can see the generate excerpt button in a post', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( '#deactivate-classic-editor' ).length > 0 ) {
				cy.get('#deactivate-classic-editor').click();
			}
		} );

		const data = getChatGPTData('excerpt');

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
		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#activate-classic-editor' ).click();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get( '#enable_excerpt' ).check();
		cy.get( '#submit' ).click();

		const data = getChatGPTData('excerpt');

		cy.classicCreatePost( {
			title: 'Excerpt test classic',
			content: 'Test GPT content.',
			postType: 'post',
		} );

		// Ensure excerpt metabox is shown.
		cy.get( '#show-settings-link' ).click();
		cy.get( '#postexcerpt-hide' ).check( { force: true } );

		// Verify button exists.
		cy.get( '#classifai-openai__excerpt-generate-btn' ).should( 'exist' );

		// Click on button and verify data loads in.
		cy.get( '#classifai-openai__excerpt-generate-btn' ).click();
		cy.get( '#excerpt' ).should( 'have.value', data );

		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#deactivate-classic-editor' ).click();
	} );

	it( 'Can set multiple custom excerpt generation prompts, select one as the default and delete one.', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Add three custom prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][0][default]"]'
		)
			.parents( 'td' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom excerpt prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom excerpt prompt' );

		// Set the third prompt as our default.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );

		cy.get( '#submit' ).click();

		const data = getChatGPTData( 'excerpt' );

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

	it( 'Can disable excerpt generation feature', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable features.
		cy.get( '#enable_excerpt' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT post disabled',
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

			// Verify button doesn't exist.
			cy.wrap( $panel )
				.find( '.editor-post-excerpt button' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can enable/disable excerpt generation feature by role', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get( '#enable_excerpt' ).check();
		cy.get( '#submit' ).click();

		// Disable admin role.
		cy.disableFeatureForRoles('excerpt_generation', ['administrator'], 'openai_chatgpt');

		// Verify that the feature is not available.
		cy.verifyExcerptGenerationEnabled(false);

		// enable admin role.
		cy.enableFeatureForRoles('excerpt_generation', ['administrator'], 'openai_chatgpt');

		// Verify that the feature is available.
		cy.verifyExcerptGenerationEnabled(true);
	} );

	it( 'Can enable/disable excerpt generation feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles('excerpt_generation', ['administrator'], 'openai_chatgpt');

		// Verify that the feature is not available.
		cy.verifyExcerptGenerationEnabled(false);

		// Enable feature for admin user.
		cy.enableFeatureForUsers('excerpt_generation', ['admin'], 'openai_chatgpt');

		// Verify that the feature is available.
		cy.verifyExcerptGenerationEnabled(true);
	} );

	it( 'User can opt-out excerpt generation feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut('excerpt_generation', 'openai_chatgpt');

		// opt-out
		cy.optOutFeature('excerpt_generation');

		// Verify that the feature is not available.
		cy.verifyExcerptGenerationEnabled(false);

		// opt-in
		cy.optInFeature('excerpt_generation');

		// Verify that the feature is available.
		cy.verifyExcerptGenerationEnabled(true);
	} );
} );
