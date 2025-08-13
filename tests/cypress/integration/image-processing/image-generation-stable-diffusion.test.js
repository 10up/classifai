describe( 'Image Generation (Stable Diffusion)', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'image_processing/feature_image_generation' );
		cy.enableFeature();
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save settings', () => {
		cy.visitFeatureSettings( 'image_processing/feature_image_generation' );
		cy.selectProvider( 'stable_diffusion' );
		cy.get( 'select#stable_diffusion_number_of_images' ).select( '2' );
		cy.get( 'select#stable_diffusion_image_size' ).select( '1024x1536' );

		cy.allowFeatureToAdmin();

		cy.saveFeatureSettings();

		cy.selectProvider( 'stable_diffusion' );
		cy.get( '#stable_diffusion_model' ).select(
			'sd-v1-4.ckpt [fe4efff1e1]'
		);
		cy.saveFeatureSettings();
	} );

	it( 'Can generate images in the media modal', () => {
		// Create test post.
		cy.createPost( {
			title: 'Test Stable Diffusion post',
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
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Featured image"),.editor-sidebar__panel .editor-post-panel__section .editor-post-featured-image`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Support pre WP 6.6+.
			const $newPanel = $panelButton.parents(
				'.editor-post-panel__section'
			);

			if ( $newPanel.length === 0 ) {
				// Find the panel container.
				const $panel = $panelButton.parents(
					'.components-panel__body'
				);

				// Open panel.
				if ( ! $panel.hasClass( 'is-opened' ) ) {
					cy.wrap( $panelButton ).click();
				}

				// Click to open media modal.
				cy.wrap( $panel )
					.find( '.editor-post-featured-image__toggle' )
					.click();
			} else {
				cy.wrap( $newPanel )
					.find(
						'.editor-post-featured-image .editor-post-featured-image__container button'
					)
					.click();
			}

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

	it( 'Can generate image directly in media library', () => {
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
} );
