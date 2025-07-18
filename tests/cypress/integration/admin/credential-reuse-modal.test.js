describe( 'Credential Reuse Modal Tests', () => {
	beforeEach( () => {
		cy.login();
		// Clear localStorage before each test to ensure clean state
		cy.window().then( ( win ) => {
			win.localStorage.removeItem( 'classifai_dont_ask_credential_reuse' );
		} );
	} );

	describe( 'Modal Display Behavior', () => {
		it( 'Should display credential reuse modal when enabling a feature without localStorage flag', () => {
			// Visit settings page
			cy.visitFeatureSettings( 'language_processing/feature_title_generation' );

			// Enable the credential reuse modal (ensure it's not disabled)
			cy.enableCredentialReuseModal();

			// Mock the API response to simulate available credentials
			cy.intercept( 'GET', '**/wp-json/classifai/v1/credential-reuse/feature_title_generation**', {
				statusCode: 200,
				body: {
					openai_chatgpt: {
						feature_id: 'feature_excerpt_generation',
						feature_label: 'Excerpt Generation',
						provider_display_name: 'OpenAI ChatGPT',
						credentials: {
							authenticated: true
						}
					}
				}
			} ).as( 'checkCredentials' );

			// Disable if already enabled.
			cy.disableFeatureIfEnabled();

			cy.enableFeature( false );

			// Wait for API call
			cy.wait( '@checkCredentials' );

			// Check modal is visible
			cy.get( '.components-modal__header' ).should( 'be.visible' );
			cy.get( '.components-modal__header-heading' ).should( 'contain', 'Reuse Existing Credentials' );
		} );

		it( 'Should not display modal when localStorage flag is set', () => {
			// Set the flag first
			cy.disableCredentialReuseModal();

			// Visit settings page
			cy.visitFeatureSettings( 'language_processing/feature_title_generation' );

			// Mock the API response
			cy.intercept( 'GET', '**/wp-json/classifai/v1/credential-reuse/feature_title_generation**', {
				statusCode: 200,
				body: {
					openai_chatgpt: {
						feature_id: 'feature_excerpt_generation',
						feature_label: 'Excerpt Generation',
						provider_display_name: 'OpenAI ChatGPT',
						credentials: {
							authenticated: true
						}
					}
				}
			} ).as( 'checkCredentials' );

			// Disable if already enabled.
			cy.disableFeatureIfEnabled();

			// Enable the feature
			cy.enableFeature( false );

			// API should not be called when flag is set
			cy.get( '.components-modal__header' ).should( 'not.exist' );
		} );

		it( 'Should not display modal when no compatible credentials are available', () => {
			// Visit settings page
			cy.visitFeatureSettings( 'language_processing/feature_title_generation' );

			// Enable the credential reuse modal
			cy.enableCredentialReuseModal();

			// Mock empty response
			cy.intercept( 'GET', '**/wp-json/classifai/v1/credential-reuse/feature_title_generation**', {
				statusCode: 200,
				body: {}
			} ).as( 'checkCredentials' );

			// Disable if already enabled.
			cy.disableFeatureIfEnabled();

			// Enable the feature
			cy.enableFeature( false );

			// Wait for API call
			cy.wait( '@checkCredentials' );

			// Modal should not appear
			cy.get( '.components-modal__header' ).should( 'not.exist' );
		} );
	} );

	describe( 'Modal Interaction', () => {
		beforeEach( () => {
			// Setup common mocks
			cy.intercept( 'GET', '**/wp-json/classifai/v1/credential-reuse/feature_title_generation**', {
				statusCode: 200,
				body: {
					openai_chatgpt: {
						feature_id: 'feature_excerpt_generation',
						feature_label: 'Excerpt Generation',
						provider_display_name: 'OpenAI ChatGPT',
						credentials: {
							authenticated: true
						}
					},
					ollama_embeddings: {
						feature_id: 'feature_excerpt_generation',
						feature_label: 'Excerpt Generation',
						provider_display_name: 'Ollama Embeddings',
						credentials: {
							authenticated: true
						}
					}
				}
			} ).as( 'checkCredentials' );
		} );

		it( 'Should close modal when X button is clicked', () => {
			cy.visitFeatureSettings( 'language_processing/feature_title_generation' );
			cy.enableCredentialReuseModal();

			// Disable if already enabled.
			cy.disableFeatureIfEnabled();

			// Trigger modal
			cy.enableFeature( false );
			cy.wait( '@checkCredentials' );

			// Click X button
			cy.get( 'button[aria-label="Close"]' ).click();

			// Modal should close
			cy.get( '.components-modal__header' ).should( 'not.exist' );
		} );

		it( 'Should save localStorage flag when "Don\'t ask again" is checked', () => {
			cy.visitFeatureSettings( 'language_processing/feature_title_generation' );
			cy.enableCredentialReuseModal();

			// Disable if already enabled.
			cy.disableFeatureIfEnabled();

			// Trigger modal
			cy.enableFeature( false );
			cy.wait( '@checkCredentials' );

			// Check "Don't ask again" checkbox
			cy.get( '.components-modal__content' ).within( () => {
				cy.get( 'input[type="checkbox"]' ).last().check();
			} );

			// Click Reuse
			cy.get( 'button' ).contains( 'Reuse' ).click();

			cy.wait( 3000 );

			// Verify localStorage was set
			cy.window().then( ( win ) => {
				expect( win.localStorage.getItem( 'classifai_dont_ask_credential_reuse' ) ).to.equal( 'true' );
			} );
		} );

		it( 'Should maintain feature enabled state after modal interaction', () => {
			cy.visitFeatureSettings( 'language_processing/feature_title_generation' );
			cy.enableCredentialReuseModal();

			cy.intercept( 'POST', '**/wp-json/classifai/v1/credential-reuse/copy**', {
				success: true
			} ).as( 'applyCredentials' );

			// Disable if already enabled.
			cy.disableFeatureIfEnabled();

			// Enable feature
			cy.enableFeature( false );
			cy.wait( '@checkCredentials' );

			// Check features are displayed
			cy.get( '.components-modal__content' ).within( () => {
				cy.get( 'label' ).should( 'contain', 'Excerpt Generation' );
				cy.get( 'label' ).should( 'contain', 'Ollama Embeddings' );
				cy.get( '.classifai-provider-selection label' ).should( 'have.length', 2 );
			} );

			// As this is a mock response, the creds will not be copied.
			cy.get( 'button' ).contains( 'Reuse' ).click();
			cy.get( '.components-modal__header' ).should( 'not.exist' );
		} );
	} );
} );
