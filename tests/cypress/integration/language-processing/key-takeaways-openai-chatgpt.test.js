describe( '[Language processing] Key Takeaways Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.enableFeature();
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save Feature settings', () => {
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.selectProvider( 'openai_chatgpt' );
		cy.get( '#openai_chatgpt_api_key' ).clear().type( 'password' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	it( 'Can add the Key Takeaways block in a post', () => {
		// Create test post and add our block.
		cy.createPost( {
			title: 'Test Key Takeaways post',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.customInsertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find(
					'.wp-block-classifai-key-takeaways .wp-block-classifai-key-takeways__content'
				)
				.should( 'contain.text', 'OpenAI request failed' );
		} );
	} );

	it( 'Can set multiple custom prompts, select one as the default and delete one.', () => {
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );

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

	it( 'Can disable feature', () => {
		// Disable feature.
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.customInsertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can disable feature by role', () => {
		cy.visitFeatureSettings( 'language_processing/feature_key_takeaways' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_key_takeaways', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled user',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.customInsertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can disable feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_key_takeaways', [
			'administrator',
		] );

		cy.enableFeatureForUsers( 'feature_key_takeaways', [] );

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled user',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.customInsertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );

	it( 'User can opt-out of feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_key_takeaways', 'openai_chatgpt' );

		// opt-out
		cy.optOutFeature( 'feature_key_takeaways' );

		// Verify that the feature is not available.
		cy.createPost( {
			title: 'Test Key Takeaways post disabled',
			content: 'Test GPT content',
			beforeSave: () => {
				cy.customInsertBlock( 'classifai/key-takeaways' );
			},
		} ).then( () => {
			cy.getBlockEditor()
				.find( '.wp-block-classifai-key-takeaways' )
				.should( 'not.exist' );
		} );
	} );
} );
