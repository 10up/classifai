describe( 'Image processing Tests', () => {
	let imageEditLink = '';
	before( () => {
		cy.login();
	} );

	it( 'Can save "Image Processing" settings', () => {
		cy.visit( '/wp-admin/admin.php?page=image_processing' );

		cy.get( '#classifai-settings-url' ).clear().type( 'http://image-processing.test' );
		cy.get( '#classifai-settings-api_key' ).clear().type( 'password' );
		cy.get( '#classifai-settings-enable_smart_cropping' ).check();
		cy.get( '#classifai-settings-enable_ocr' ).check();
		cy.get( '#submit' ).click();

		cy.get( '.notice' ).contains( 'Settings saved.' );
	} );

	it( 'Can see Image processing actions on edit media page', () => {
		cy.visit( '/wp-admin/media-new.php' );
		cy.get( '#plupload-upload-ui' ).should( 'exist' );
		cy.get( '#plupload-upload-ui input[type=file]' )
			.attachFile( '../../../assets/img/banner-772x250.png' );

		cy.get( '#media-items .media-item a.edit-attachment' ).should( 'exist' );
		cy.get( '#media-items .media-item a.edit-attachment' ).invoke( 'attr', 'href' ).then( editLink => {
			imageEditLink = editLink;
			cy.visit( editLink );
		} );

		// Verify Metabox with Image processing actions.
		cy.get( '.postbox-header h2, #attachment_meta_box h2' ).first().contains( 'ClassifAI Image Processing' );
		cy.get( '.misc-publishing-actions label[for=rescan-captions]' ).contains( 'Generate alt text' );
		cy.get( '.misc-publishing-actions label[for=rescan-tags]' ).contains( 'Generate image tags' );
		cy.get( '.misc-publishing-actions label[for=rescan-ocr]' ).contains( 'Scan image for text' );
		cy.get( '.misc-publishing-actions label[for=rescan-smart-crop]' ).contains( 'Regenerate smart thumbnail' );
	} );

	it( 'Can disable Image processing features', () => {
		cy.visit( '/wp-admin/admin.php?page=image_processing' );

		// Disable features
		cy.get( '#classifai-settings-enable_smart_cropping' ).uncheck();
		cy.get( '#classifai-settings-enable_ocr' ).uncheck();
		cy.get( '#submit' ).click();

		// Verify with Image processing features are not present in attachment metabox.
		cy.visit( imageEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-ocr]' ).should( 'not.exist' );
		cy.get( '.misc-publishing-actions label[for=rescan-smart-crop]' ).should( 'not.exist' );
	} );
} );
