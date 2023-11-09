describe( '[Language processing] Speech to Text Tests', () => {
	before( () => {
		cy.login();
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get( '#enable_resize_content' ).check();
		cy.get( '#submit' ).click();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Resize content feature can grow and shrink content', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		cy.get( '#enable_resize_content' ).check();
		cy.get( '#openai_chatgpt_resize_content_roles_administrator' ).check();
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Add three custom shrink prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Add three custom grow prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset:first' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom shrink prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom shrink prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom grow prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom grow prompt' );

		// Set the third prompt as our default.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );

		cy.get( '#submit' ).click();

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

	it( 'Can enable/disable resize content feature by role', () => {
		// Disable admin role.
		cy.disableFeatureForRoles(
			'resize_content',
			[ 'administrator' ],
			'openai_chatgpt'
		);

		// Verify that the feature is not available.
		cy.verifyResizeContentEnabled( false );

		// Enable admin role.
		cy.enableFeatureForRoles(
			'resize_content',
			[ 'administrator' ],
			'openai_chatgpt'
		);

		// Verify that the feature is available.
		cy.verifyResizeContentEnabled( true );
	} );

	it( 'Can enable/disable resize content feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles(
			'resize_content',
			[ 'administrator' ],
			'openai_chatgpt'
		);

		// Verify that the feature is not available.
		cy.verifyResizeContentEnabled( false );

		// Enable feature for admin user.
		cy.enableFeatureForUsers(
			'resize_content',
			[ 'admin' ],
			'openai_chatgpt'
		);

		// Verify that the feature is available.
		cy.verifyResizeContentEnabled( true );
	} );

	it( 'User can opt-out resize content feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'resize_content', 'openai_chatgpt' );

		// opt-out
		cy.optOutFeature( 'resize_content' );

		// Verify that the feature is not available.
		cy.verifyResizeContentEnabled( false );

		// opt-in
		cy.optInFeature( 'resize_content' );

		// Verify that the feature is available.
		cy.verifyResizeContentEnabled( true );
	} );
} );
