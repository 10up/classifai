describe( '[Language processing] Resize Content Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);
		cy.enableFeature();
		cy.selectProvider( 'openai_chatgpt' );
		cy.get( '#openai_chatgpt_api_key' ).type( 'abc123' );
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Resize content feature can grow and shrink content', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();

		cy.createPost( {
			title: 'Resize content',
			content: 'Hello, world.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Expand this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+7 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+40 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic building block of one narrative.'
			);

		cy.createPost( {
			title: 'Resize content',
			content:
				'Start with the basic building block of one narrative to begin with the editorial process.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Condense this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-6 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-36 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic building block of one narrative.'
			);
	} );

	it( 'Can set multiple custom resize generation prompts, select one as the default and delete one.', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);

		// Add three custom shrink prompts.
		cy.get(
			'.settings-condense-text-prompt button.components-button.action__add_prompt'
		)
			.click()
			.click()
			.click();
		cy.get(
			'.settings-condense-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
		).should( 'have.length', 4 );

		// Add three custom grow prompts.
		cy.get(
			'.settings-expand-text-prompt button.components-button.action__add_prompt'
		)
			.click()
			.click()
			.click();
		cy.get(
			'.settings-expand-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
		).should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-1 .classifai-prompt-title input'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-1 .classifai-prompt-text textarea'
		)
			.clear()
			.type( 'This is our first custom shrink prompt' );

		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-2 .classifai-prompt-title input'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-2 .classifai-prompt-text textarea'
		)
			.clear()
			.type( 'This prompt should be deleted' );

		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-3 .classifai-prompt-title input'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-3 .classifai-prompt-text textarea'
		)
			.clear()
			.type( 'This is a custom shrink prompt' );

		// Expand prompts.
		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-1 .classifai-prompt-title input'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-1 .classifai-prompt-text textarea'
		)
			.clear()
			.type( 'This is our first custom grow prompt' );

		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-2 .classifai-prompt-title input'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-2 .classifai-prompt-text textarea'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-3 .classifai-prompt-title input'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-3 .classifai-prompt-text textarea'
		)
			.clear()
			.type( 'This is a custom grow prompt' );

		// Set the third prompt as our default.
		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-3 .actions-rows button.action__set_default'
		).click( { force: true } );

		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-3 .actions-rows button.action__set_default'
		).click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-2 .actions-rows button.action__remove_prompt'
		).click( { force: true } );
		cy.get( 'div.components-confirm-dialog button.is-primary' ).click();
		cy.get(
			'.settings-condense-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
		).should( 'have.length', 3 );

		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-2 .actions-rows button.action__remove_prompt'
		).click( { force: true } );
		cy.get( 'div.components-confirm-dialog button.is-primary' ).click();
		cy.get(
			'.settings-expand-text-prompt .classifai-prompts div.classifai-field-type-prompt-setting'
		).should( 'have.length', 3 );

		cy.saveFeatureSettings();

		cy.createPost( {
			title: 'Resize content',
			content: 'Hello, world.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Expand this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+6 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+31 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic block of one narrative.'
			);

		cy.createPost( {
			title: 'Resize content',
			content:
				'Start with the basic building block of one narrative to begin with the editorial process.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Condense this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-7 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-45 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic block of one narrative.'
			);
	} );

	it( 'Can enable/disable resize content feature', () => {
		// Disable feature.
		cy.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifyResizeContentEnabled( false );

		// Enable feature.
		cy.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifyResizeContentEnabled( true );
	} );

	it( 'Can enable/disable resize content feature by role', () => {
		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_content_resizing', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyResizeContentEnabled( false );

		// Enable admin role.
		cy.enableFeatureForRoles( 'feature_content_resizing', [
			'administrator',
		] );

		// Verify that the feature is available.
		cy.verifyResizeContentEnabled( true );
	} );

	it( 'Can enable/disable resize content feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_content_resizing', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyResizeContentEnabled( false );

		// Enable feature for admin user.
		cy.enableFeatureForUsers( 'feature_content_resizing', [ 'admin' ] );

		// Verify that the feature is available.
		cy.verifyResizeContentEnabled( true );
	} );

	it( 'User can opt-out resize content feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_content_resizing' );

		// opt-out
		cy.optOutFeature( 'feature_content_resizing' );

		// Verify that the feature is not available.
		cy.verifyResizeContentEnabled( false );

		// opt-in
		cy.optInFeature( 'feature_content_resizing' );

		// Verify that the feature is available.
		cy.verifyResizeContentEnabled( true );
	} );
} );
