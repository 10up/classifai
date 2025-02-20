describe( '[Language processing] Resize Content Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_content_resizing'
		);
		cy.enableFeature();
		cy.selectProvider( 'xai_grok' );
		cy.get( '#xai_grok_api_key' ).type( 'abc123' );
		cy.get(
			'.settings-condense-text-prompt #classifai-prompt-setting-0'
		).then( ( $prompt ) => {
			if (
				$prompt
					.find( '.actions-rows button.action__set_default' )
					.text() === 'Set as default prompt'
			) {
				cy.get(
					'.settings-condense-text-prompt #classifai-prompt-setting-0 .actions-rows button.action__set_default'
				).click();
			}
		} );
		cy.get(
			'.settings-expand-text-prompt #classifai-prompt-setting-0'
		).then( ( $prompt ) => {
			if (
				$prompt
					.find( '.actions-rows button.action__set_default' )
					.text() === 'Set as default prompt'
			) {
				cy.get(
					'.settings-expand-text-prompt #classifai-prompt-setting-0 .actions-rows button.action__set_default'
				).click();
			}
		} );
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
} );
