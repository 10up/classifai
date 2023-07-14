/* eslint jest/expect-expect: 0 */

import { getOCRData, getImageData, getDalleData } from '../plugins/functions';

describe('Image processing Tests', () => {
	let imageEditLink = '';
	let mediaModelLink = '';

	it('Can save Computer Vision "Image Processing" settings', () => {
		cy.visit('/wp-admin/tools.php?page=classifai&tab=image_processing');

		cy.get('#url')
			.clear()
			.type('http://e2e-test-image-processing.test');
		cy.get('#api_key').clear().type('password');
		cy.get('#computer_vision_enable_image_captions_alt').check();
		cy.get('#computer_vision_enable_image_captions_description').check();
		cy.get('#enable_image_tagging').check();
		cy.get('#enable_smart_cropping').check();
		cy.get('#enable_ocr').check();
		cy.get('#submit').click();

		cy.get('.notice').contains('Settings saved.');
	});

	it('Can see Computer Vision Image processing actions on edit media page and verify Generated data.', () => {
		cy.visit('/wp-admin/media-new.php');
		cy.get('#plupload-upload-ui').should('exist');
		cy.get('#plupload-upload-ui input[type=file]').attachFile(
			'../../../assets/img/banner-772x250.png'
		);

		cy.get('#media-items .media-item a.edit-attachment').should('exist');
		cy.get('#media-items .media-item a.edit-attachment')
			.invoke('attr', 'href')
			.then((editLink) => {
				imageEditLink = editLink;
				cy.visit(editLink);
			});

		// Verify Metabox with Image processing actions.
		cy.get('.postbox-header h2, #attachment_meta_box h2')
			.first()
			.contains('ClassifAI Image Processing');
		cy.get('.misc-publishing-actions label[for=rescan-captions]').contains(
			'No descriptive text? Rescan image'
		);
		cy.get('.misc-publishing-actions label[for=rescan-tags]').contains(
			'Rescan image for new tags'
		);
		cy.get('.misc-publishing-actions label[for=rescan-ocr]').contains(
			'Rescan for text'
		);
		cy.get('.misc-publishing-actions label[for=rescan-smart-crop]').should(
			'exist'
		);

		// Verify generated Data.
		const imageData = getImageData();
		cy.get('#attachment_alt').should('have.value', imageData.altText);
		cy.get('#attachment_content').should('have.value', getOCRData());
		cy.get(
			'#classifai-image-tags ul.tagchecklist li span.screen-reader-text'
		)
			.each((tag) => {
				return expect(
					tag.text().replace('Remove term: ', '')
				).to.be.oneOf(imageData.tags);
			})
			.then((imageTags) => {
				expect(imageTags).to.have.length(imageData.tags.length);
			});
	});

	it('Can see Computer Vision Image processing actions on media model', () => {
		const imageId = imageEditLink.split('post=')[1]?.split('&')[0];
		mediaModelLink = `wp-admin/upload.php?item=${imageId}`;
		cy.visit(mediaModelLink);
		cy.get('.media-modal').should('exist');

		// Verify Image processing actions.
		cy.get('#classifai-rescan-alt-tags').contains('Rescan');
		cy.get('#classifai-rescan-image-tags').contains('Rescan');
		cy.get('#classifai-rescan-ocr').contains('Rescan');
		cy.get('#classifai-rescan-smart-crop').should('exist');
	});

	it('Can disable Computer Vision Image processing features', () => {
		cy.visit('/wp-admin/tools.php?page=classifai&tab=image_processing');

		// Disable features
		cy.get('#computer_vision_enable_image_captions_alt').uncheck();
		cy.get('#computer_vision_enable_image_captions_caption').uncheck();
		cy.get('#computer_vision_enable_image_captions_description').uncheck();
		cy.get('#enable_image_tagging').uncheck();
		cy.get('#enable_smart_cropping').uncheck();
		cy.get('#enable_ocr').uncheck();
		cy.get('#submit').click();

		// Verify with Image processing features are not present in attachment metabox.
		cy.visit(imageEditLink);
		cy.get('.misc-publishing-actions label[for=rescan-captions]').should(
			'not.exist'
		);
		cy.get('.misc-publishing-actions label[for=rescan-tags]').should(
			'not.exist'
		);
		cy.get('.misc-publishing-actions label[for=rescan-ocr]').should(
			'not.exist'
		);
		cy.get('.misc-publishing-actions label[for=rescan-smart-crop]').should(
			'not.exist'
		);

		// Verify with Image processing features are not present in media model.
		cy.visit(mediaModelLink);
		cy.get('.media-modal').should('exist');
		cy.get('#classifai-rescan-alt-tags').should('not.exist');
		cy.get('#classifai-rescan-captions').should('not.exist');
		cy.get('#classifai-rescan-smart-crop').should('not.exist');
		cy.get('#classifai-rescan-ocr').should('not.exist');
	});

	it( 'Can save OpenAI "Image Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=image_processing&provider=openai_dalle'
		);

		cy.get( '#api_key' ).clear().type( 'password' );

		cy.get( '#enable_image_gen' ).check();
		cy.get( '#openai_dalle_roles_administrator' ).check();
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

	it( 'Can disable image generation by role', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=image_processing&provider=openai_dalle'
		);

		cy.get( '#enable_image_gen' ).check();
		cy.get( '#openai_dalle_roles_administrator' ).uncheck();
		cy.get( '#submit' ).click();

		cy.get(
			`.wp-has-current-submenu.wp-menu-open li a:contains("Generate Images")`
		).should( 'not.exist' );

		// Create test post.
		cy.createPost( {
			title: 'Test DALL-E post admin disabled',
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
		cy.get( '#openai_dalle_roles_administrator' ).check();
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
} );
