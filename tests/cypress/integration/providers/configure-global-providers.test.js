describe( 'Can configure global Providers', () => {
	before( () => {
		cy.login();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can configure OpenAI globally', () => {
		cy.visitFeatureSettings( 'providers' );

		// Find the button with OpenAI text in it.
		cy.get( '.classifai-providers-settings button' ).contains( 'OpenAI' ).click();

		// Add our API key.
		cy.get( '#openai_api_key' ).should( 'be.visible' );
		cy.get( '#openai_api_key' ).clear().type( 'password' );

		// Save the settings.
		cy.get( '.classifai-provider-profile-form__actions button' ).contains( 'Save' ).click();

		// Ensure the settings are valid.
		cy.get( '.classifai-providers-settings .components-panel__body-title .classifai-providers-settings__status .dashicons-yes-alt' ).should( 'exist' );
	} );

	it( 'Can configure Google AI globally', () => {
		cy.visitFeatureSettings( 'providers' );

		// Find the button with Google AI text in it.
		cy.get( '.classifai-providers-settings button' ).contains( 'Google AI' ).click();

		// Add our API key.
		cy.get( '#googleai_gemini_api_key' ).should( 'be.visible' );
		cy.get( '#googleai_gemini_api_key' ).clear().type( 'password' );

		// Save the settings.
		cy.get( '.classifai-provider-profile-form__actions button' ).contains( 'Save' ).click();

		// Ensure the settings are valid.
		cy.get( '.classifai-providers-settings .components-panel__body-title .classifai-providers-settings__status .dashicons-yes-alt' ).should( 'exist' );
	} );

	it( 'Can see global configuration message in Feature settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.selectProvider( 'ollama' );
		cy.get(
			'.classifai-provider-override-credentials input[type="checkbox"]'
		).uncheck();
		cy.get(
			'.classifai-provider-notice'
		).should(
			'exist'
		).contains(
			'Configure this Provider in the'
		);
	} );

	it( 'Can see prompt to switch to global configuration message in Feature settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.selectProvider( 'ollama' );
		cy.get(
			'.classifai-provider-override-credentials input[type="checkbox"]'
		).check();
		cy.saveFeatureSettings();

		// Refresh the page
		cy.reload();

		cy.get(
			'.classifai-provider-notice'
		).should(
			'exist'
		).contains(
			'Using Feature-level credentials. Configure this Provider in the'
		);
	} );
} );
