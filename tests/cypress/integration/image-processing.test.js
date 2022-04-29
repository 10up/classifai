describe( 'Image processing Tests', () => {
	let imageEditLink = '';
	let mediaModelLink = '';
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

	it( 'Can see Image processing actions on media model', () => {
		const imageId  = imageEditLink.split( 'post=' )[1]?.split( '&' )[0];
		mediaModelLink = `wp-admin/upload.php?item=${imageId}`;
		cy.visit( mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );

		// Verify Image processing actions.
		cy.get( '#classifai-rescan-alt-tags' ).contains( 'Generate' );
		cy.get( '#classifai-rescan-image-tags' ).contains( 'Generate' );
		cy.get( '#classifai-rescan-smart-crop' ).contains( 'Regenerate' );
		cy.get( '#classifai-rescan-ocr' ).contains( 'Scan' );
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

		// Verify with Image processing features are not present in media model.
		cy.visit( mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-smart-crop' ).should( 'not.exist' );
		cy.get( '#classifai-rescan-ocr' ).should( 'not.exist' );
	} );
} );
