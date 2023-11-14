describe( 'Language processing Tests', () => {
	it( 'Check Classification Mode toggle button is on', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-automatic_classification' ).check();
		cy.get( '#submit' ).click();
	
		// Create Test Post
		cy.createPost( {
			title: 'Test Classification Mode post',
			content: 'Test Classification Mode post',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Open Panel
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("ClassifAI")`;
		cy.get(panelButtonSelector).then(($button) => {
			// Find the panel container
			const $panel = $button.parents('.components-panel__body');

			// Open Panel
			if ( !$panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $button ).click();
			}
		} );
		
		// Check the toggle button is on
		cy.get( '.classifai-panel .components-form-toggle' ).should(
			'have.class',
			'is-checked'
		);
	} );

	it( 'Check Classification Mode toggle button is off, display popup, then add/remove terms', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-manual_review' ).check();
		cy.get( '#submit' ).click();
	
		// Create Test Post
		cy.createPost( {
			title: 'Test Classification Mode post',
			content: 'Test Classification Mode post',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Open Panel
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("ClassifAI")`;
		cy.get(panelButtonSelector).then(($button) => {
			// Find the panel container
			const $panel = $button.parents('.components-panel__body');

			// Open Panel.
			if (!$panel.hasClass('is-opened')) {
				cy.wrap($button).click();
			}
		} );
		
		// Check the toggle button is off
		cy.get( '.classifai-panel .components-form-toggle' ).should(
			'not.have.class',
			'is-checked'
		);

		cy.get( '#classify-post-component button' ).click();
		
		// see if there is a label with "Watson Categories" text exists
		cy.get( '.components-form-token-field__label' ).contains( 'Watson Categories' );

		// check if a term can be removed
		cy.get('.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item')
			.then(listing => {
				const totalTerms = Cypress.$(listing).length;

				// Remove 1 term
				cy.get( '.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item:first-child .components-form-token-field__remove-token' ).click();

				// Now confirm if the term is reduced
				cy.get( listing ).should( 'have.length', totalTerms - 1 );

				// enter a new term "TEST" as input and press enter key in js
				cy.get( '.classify-modal > div > div:nth-child(2) > div:first-of-type .components-form-token-field__input' ).type( 'NewTestTerm' );				
				
				// press enter key in js
				cy.get( '.classify-modal > div > div:nth-child(2) > div:first-of-type .components-form-token-field__input' ).type( '{enter}' );

				// Click the save button
				cy.get( '.classify-modal .components-button' ).contains( 'Save' ).click();

				// Confirm the new term addition
				cy.get( '.components-flex-item span' ).contains( 'NewTestTerm' );
			}
		);
	} );
} );
