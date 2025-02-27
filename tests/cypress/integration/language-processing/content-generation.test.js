describe( '[Language processing] Content Generation Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
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

	it( 'Can save Azure OpenAI ChatGPT settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'azure_openai' );
		cy.get( 'input#azure_openai_endpoint_url' )
			.clear()
			.type( 'https://e2e-test-azure-openai.test/' );
		cy.get( 'input#azure_openai_api_key' ).clear().type( 'password' );
		cy.get( 'input#azure_openai_deployment' ).clear().type( 'test' );

		cy.saveFeatureSettings();
	} );

	it( 'Can save Ollama settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'ollama' );

		cy.saveFeatureSettings();
		cy.get( '#true_model' ).select( 'deepseek-llm:latest' );
		cy.saveFeatureSettings();
	} );

	it( 'Can save OpenAI ChatGPT settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'openai_chatgpt' );
		cy.get( '#openai_chatgpt_api_key' ).clear().type( 'password' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	it( 'Can see the generate content button in a post', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.disableClassicEditor();

		// Create test post.
		cy.createPost( {
			title: 'Test Content Generation post',
			content: '',
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
					.find( '.classifai-post-status button.content' )
					.should( 'exist' );

				// Click on button and verify modal shows.
				cy.wrap( $panel )
					.find( '.classifai-post-status button.content' )
					.click();
			} else {
				// Verify button exists.
				cy.wrap( $newPanel )
					.find( '.classifai-post-status button.content' )
					.should( 'exist' );

				// Click on button and verify modal shows.
				cy.wrap( $newPanel )
					.find( '.classifai-post-status button.content' )
					.click();
			}
		} );

		cy.get( '.content-modal' ).should( 'exist' );

		// Verify you can add an initial summary and content loads in.
		cy.get( '.content-modal textarea' )
			.first()
			.type( '5 tips for using WordPress' );
		cy.get( '.content-modal button.is-primary' ).first().click();
		cy.get( '.content-modal .components-card' ).should( 'exist' );

		// Verify you can start over.
		cy.get( '.content-modal .components-card__footer' )
			.find( 'button.is-tertiary.is-destructive' )
			.click();
		cy.get( '.content-modal .components-card' ).should( 'not.exist' );

		// Verify you can iterate on the response.
		cy.get( '.content-modal textarea' )
			.first()
			.type( '5 tips for using WordPress' );
		cy.get( '.content-modal button.is-primary' ).first().click();
		cy.get( '.content-modal .components-card' ).should( 'exist' );
		cy.get( '.content-modal .components-card__footer' )
			.find( 'button.is-tertiary' )
			.first()
			.click();
		cy.get( '.content-modal textarea' ).first().type( 'Make it longer' );
		cy.get( '.content-modal button.is-primary' ).first().click();

		// Verify you can insert the content
		cy.get( '.content-modal .components-card__footer' )
			.find( 'button.is-primary' )
			.click();
		cy.get( '.content-modal' ).should( 'not.exist' );
	} );

	it( 'Can set multiple custom prompts, select one as the default and delete one.', () => {
		cy.disableClassicEditor();

		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
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
			.type( 'This is our first custom prompt' );

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
			.type( 'This is a custom prompt' );

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
	} );

	it( 'Can enable/disable feature', () => {
		// Disable features.
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifyContentGenerationEnabled( false );

		// Enable feature.
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifyContentGenerationEnabled( true );
	} );

	it( 'Can enable/disable feature by role', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_content_generation', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyContentGenerationEnabled( false );

		// enable admin role.
		cy.enableFeatureForRoles( 'feature_content_generation', [
			'administrator',
		] );

		// Verify that the feature is available.
		cy.verifyContentGenerationEnabled( true );
	} );

	it( 'Can enable/disable feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_content_generation', [
			'administrator',
		] );

		cy.enableFeatureForUsers( 'feature_content_generation', [] );

		// Verify that the feature is not available.
		cy.verifyContentGenerationEnabled( false );

		// Enable feature for admin user.
		cy.enableFeatureForUsers( 'feature_content_generation', [ 'admin' ] );

		// Verify that the feature is available.
		cy.verifyContentGenerationEnabled( true );
	} );

	it( 'User can opt-out of feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut(
			'feature_content_generation',
			'openai_chatgpt'
		);

		// opt-out
		cy.optOutFeature( 'feature_content_generation' );

		// Verify that the feature is not available.
		cy.verifyContentGenerationEnabled( false );

		// opt-in
		cy.optInFeature( 'feature_content_generation' );

		// Verify that the feature is available.
		cy.verifyContentGenerationEnabled( true );
	} );
} );
