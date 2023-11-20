import { getPDFData } from '../../plugins/functions';

describe( 'PDF read Tests', () => {
	before( () => {
		cy.login();
		cy.visit( '/wp-admin/tools.php?page=classifai&tab=image_processing' );
		cy.get( '#enable_read_pdf' ).check();
		cy.get( '#submit' ).click();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	let pdfEditLink = '';
	it( 'Can save "PDF scanning" settings', () => {
		cy.visit( '/wp-admin/tools.php?page=classifai&tab=image_processing' );

		cy.get( '#url' )
			.clear()
			.type( 'http://e2e-test-image-processing.test' );
		cy.get( '#api_key' ).clear().type( 'password' );
		cy.get( '#enable_read_pdf' ).check();
		cy.get( '#submit' ).click();

		cy.get( '.notice' ).contains( 'Settings saved.' );
	} );

	it( 'Can see PDF scanning actions on edit media page and verify PDF read data.', () => {
		cy.visit( '/wp-admin/media-new.php' );
		cy.get( '#plupload-upload-ui' ).should( 'exist' );
		cy.get( '#plupload-upload-ui input[type=file]' ).attachFile(
			'dummy.pdf'
		);

		cy.get( '#media-items .media-item a.edit-attachment', {
			timeout: 20000,
		} ).should( 'exist' );
		cy.get( '#media-items .media-item a.edit-attachment' )
			.invoke( 'attr', 'href' )
			.then( ( editLink ) => {
				pdfEditLink = editLink;
				cy.visit( editLink );
			} );

		// Verify Metabox with Image processing actions.
		cy.get( '.postbox-header h2, #attachment_meta_box h2' )
			.first()
			.contains( 'ClassifAI PDF Processing' );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).contains(
			'Rescan PDF for text'
		);

		// Verify generated Data.
		cy.get( '#attachment_content' ).should( 'have.value', getPDFData() );
	} );

	it( 'Can enable/disable PDF scanning feature', () => {
		// Disable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=computer_vision'
		);
		cy.get( '#enable_read_pdf' ).uncheck();
		cy.get( '#submit' ).click();

		// Verify that the feature is not available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'not.exist'
		);

		// Enable admin role.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=computer_vision'
		);
		cy.get( '#enable_read_pdf' ).check();
		cy.get( '#submit' ).click();

		// Verify that the feature is available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'exist'
		);
	} );

	it( 'Can enable/disable PDF scanning feature by role', () => {
		// Enable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=computer_vision'
		);
		cy.get( '#enable_read_pdf' ).check();
		cy.get( '#submit' ).click();

		// Disable admin role.
		cy.disableFeatureForRoles(
			'read_pdf',
			[ 'administrator' ],
			'computer_vision'
		);

		// Verify that the feature is not available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'not.exist'
		);

		// Enable admin role.
		cy.enableFeatureForRoles(
			'read_pdf',
			[ 'administrator' ],
			'computer_vision'
		);

		// Verify that the feature is available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'exist'
		);
	} );

	it( 'Can enable/disable PDF scanning feature by user', () => {
		// Disable admin role.
		cy.disableFeatureForRoles(
			'read_pdf',
			[ 'administrator' ],
			'computer_vision'
		);

		// Verify that the feature is not available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'not.exist'
		);

		// Enable feature for admin user.
		cy.enableFeatureForUsers( 'read_pdf', [ 'admin' ], 'computer_vision' );

		// Verify that the feature is available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'exist'
		);
	} );

	it( 'User can opt-out PDF scanning feature', () => {
		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'read_pdf', 'computer_vision' );

		// opt-out
		cy.optOutFeature( 'read_pdf' );

		// Verify that the feature is not available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'not.exist'
		);

		// opt-in
		cy.optInFeature( 'read_pdf' );

		// Verify that the feature is available.
		cy.visit( pdfEditLink );
		cy.get( '.misc-publishing-actions label[for=rescan-pdf]' ).should(
			'exist'
		);
	} );
} );
