/* eslint jest/expect-expect: 0 */
describe('Image Generation (OpenAI DALL·E) Tests', () => {
	it( 'Can save OpenAI "Image Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=image_processing&provider=openai_dalle'
		);

		cy.get( '#api_key' ).clear().type( 'password' );

		cy.get( '#enable_image_gen' ).check();
		cy.get( '#openai_dalle_image_generation_roles_administrator' ).check();
		cy.get( '#number' ).select( '2' );
		cy.get( '#size' ).select( '512x512' );
		cy.get( '#submit' ).click();
	} );

	it( 'Can generate images in the media modal', () => {
		// Create test post.
		cy.createPost( {
			title: 'Test DALL-E post',
			content: 'Test content',
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

		// Find and open the Featured image panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Featured image")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Click to open media modal.
			cy.wrap( $panel )
				.find( '.editor-post-featured-image__toggle' )
				.click();

			// Verify tab exists.
			cy.get( '#menu-item-generate' ).should( 'exist' );

			// Click into the tab and submit a prompt.
			cy.get( '#menu-item-generate' ).click();
			cy.get( '.prompt-view .prompt' ).type(
				'A sunset over the mountains'
			);
			cy.get( '.prompt-view .button-generate' ).click();

			// Verify images show up.
			cy.get( '.generated-images ul li' ).should( 'have.length', 2 );
		} );
	} );

	it( 'Can disable image generation feature', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=image_processing&provider=openai_dalle'
		);

		cy.get( '#enable_image_gen' ).uncheck();
		cy.get( '#submit' ).click();

		cy.get(
			`.wp-has-current-submenu.wp-menu-open li a:contains("Generate Images")`
		).should( 'not.exist' );

		// Create test post.
		cy.createPost( {
			title: 'Test DALL-E post disabled',
			content: 'Test content',
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

		// Find and open the Featured image panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Featured image")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Click to open media modal.
			cy.wrap( $panel )
				.find( '.editor-post-featured-image__toggle' )
				.click();

			// Verify tab doesn't exist.
			cy.get( '#menu-item-generate' ).should( 'not.exist' );
		} );
	} );

	it( 'Can generate image directly in media library', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=image_processing&provider=openai_dalle'
		);

		cy.get( '#enable_image_gen' ).check();
		cy.get( '#openai_dalle_image_generation_roles_administrator' ).check();
		cy.get( '#submit' ).click();

		cy.visit( '/wp-admin/upload.php' );
		cy.get(
			`.wp-has-current-submenu.wp-menu-open li a:contains("Generate Images")`
		).click();

		// Verify tab exists.
		cy.get( '#menu-item-generate' ).should( 'exist' );

		// Click into the tab and submit a prompt.
		cy.get( '#menu-item-generate' ).click();
		cy.get( '.prompt-view .prompt' ).type( 'A sunset over the mountains' );
		cy.get( '.prompt-view .button-generate' ).click();

		// Verify images show up.
		cy.get( '.generated-images ul li' ).should( 'have.length', 2 );
	} );

	it( 'Can enable/disable image generation feature by role', () => {
		// Enable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=image_processing&provider=openai_dalle'
		);
		cy.get( '#enable_image_gen' ).check();
		cy.get( '#submit' ).click();

		// Disable admin role.
		cy.disableFeatureForRoles('image_generation', ['administrator'], 'openai_dalle');

		// Verify that the feature is not available.
		cy.verifyImageGenerationEnabled(false);

		// Enable admin role.
		cy.enableFeatureForRoles('image_generation', ['administrator'], 'openai_dalle');

		// Verify that the feature is available.
		cy.verifyImageGenerationEnabled(true);
	} );

	it( 'Can enable/disable image generation feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles('image_generation', ['administrator'], 'openai_dalle');

		// Verify that the feature is not available.
		cy.verifyImageGenerationEnabled(false);

		// Enable feature for admin user.
		cy.enableFeatureForUsers('image_generation', ['admin'], 'openai_dalle');

		// Verify that the feature is available.
		cy.verifyImageGenerationEnabled(true);
	} );

	it( 'User can opt-out image generation feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut('image_generation', 'openai_dalle');

		// opt-out
		cy.optOutFeature('image_generation');

		// Verify that the feature is not available.
		cy.verifyImageGenerationEnabled(false);

		// opt-in
		cy.optInFeature('image_generation');

		// Verify that the feature is available.
		cy.verifyImageGenerationEnabled(true);
	} );
} );
