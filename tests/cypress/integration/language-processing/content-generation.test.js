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
		cy.get( '#ollama_model' ).select( 'deepseek-llm:latest' );
		cy.saveFeatureSettings();
	} );

	it( 'Can save OpenAI ChatGPT settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'openai_chatgpt' );
		cy.get( '#openai_api_key' ).clear().type( 'password' );

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

		// Open the chat UI.
		cy.get( '.classifai-chat-button' ).first().click( { force: true } );
		cy.get( '.classifai-chat-ui' ).should( 'exist' );

		// Verify you can add an initial summary and content loads in.
		cy.get( '.classifai-chat-input textarea' ).type(
			'5 tips for using WordPress'
		);
		cy.get( '.classifai-chat-ui button.is-primary' ).click();
		cy.get( '.classifai-chat-history' ).should(
			'contain.text',
			'Hello there, how may I assist you today?'
		);

		// Verify you can start over.
		cy.get( '.classifai-chat-history' )
			.find( 'button.is-tertiary.is-destructive' )
			.click();
		cy.get( '.classifai-chat-history' ).should( 'not.exist' );

		// Verify you can iterate on the response.
		cy.get( '.classifai-chat-input textarea' ).type(
			'5 tips for using WordPress'
		);
		cy.get( '.classifai-chat-ui button.is-primary' ).click();
		cy.get( '.classifai-chat-history' ).should(
			'contain.text',
			'Hello there, how may I assist you today?'
		);
		cy.get( '.classifai-chat-input textarea' ).type( 'Make it longer' );
		cy.get( '.classifai-chat-ui button.is-primary' ).click();

		// Verify you can insert the content
		cy.get( '.classifai-chat-history' )
			.find( 'button.is-tertiary' )
			.last()
			.click();
		cy.get( '.classifai-chat-ui' ).should( 'not.exist' );
	} );

	it( 'Can save Quick Draft integration settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_generation'
		);
		cy.get( '.settings-enable-quick-draft input' ).check();
		cy.saveFeatureSettings();
	} );

	it( 'Can see the generate content button in the Quick Draft widget', () => {
		cy.visit( '/wp-admin/index.php' );
		cy.get( '#dashboard_quick_press' ).should( 'exist' );
		cy.get( '#classifai-generate-content' ).click();

		// Should show error message.
		cy.get( '#dashboard_quick_press .notice' ).should( 'contain.text', 'Please enter some content to generate a draft from.' );

		// Add title and content.
		cy.get( '#dashboard_quick_press #title' ).clear().type( 'Test Content Generation post' );
		cy.get( '#dashboard_quick_press #content' ).clear().type( '5 tips for using WordPress' );

		// Click the generate button.
		cy.get( '#classifai-generate-content' ).click();

		// Should show success message.
		cy.get( '#dashboard_quick_press .notice' ).should( 'contain.text', 'Draft created successfully!' );

		// Refresh the page and verify the draft is created.
		cy.reload();
		cy.get( '#dashboard_quick_press .drafts li:first-child .draft-title' ).should( 'contain.text', 'Test Content Generation post' );
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
