describe( '[Language processing] Resize Content Tests', () => {
	before( () => {
		cy.login();
		cy.visit(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_content_resizing'
		);
		cy.get( '.classifai-enable-feature-toggle input' ).check();
		cy.selectProvider( 'googleai_gemini_api' );
		cy.get( 'input#googleai_gemini_api_api_key' ).clear().type( 'abc123' );
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Resize content feature can grow and shrink content', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_content_resizing'
		);

		cy.get( '.classifai-enable-feature-toggle input' ).check();
		cy.get( '.settings-allowed-roles input#administrator' ).check();
		cy.saveFeatureSettings();

		cy.createPost( {
			title: 'Resize content',
			content: 'Hello, world.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Expand this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+8 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+49 characters' );
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
		).should( 'contain.text', '-5 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-27 characters' );
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
