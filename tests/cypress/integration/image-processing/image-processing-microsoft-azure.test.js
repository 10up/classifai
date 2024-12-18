import { getOCRData, getImageData } from '../../plugins/functions';

describe( 'Image processing Tests', () => {
	let imageEditLink = '';
	let mediaModelLink = '';

	before( () => {
		cy.login();

		const imageProcessingFeatures = [
			'feature_descriptive_text_generator',
			'feature_image_tags_generator',
			'feature_image_cropping',
			'feature_image_to_text_generator',
			'feature_pdf_to_text_generation',
		];

		imageProcessingFeatures.forEach( ( feature ) => {
			cy.visitFeatureSettings( `image_processing/${ feature }` );
			cy.wait( 100 );
			cy.enableFeature();
			cy.selectProvider( 'ms_computer_vision' );
			cy.get( '#ms_computer_vision_endpoint_url' )
				.clear()
				.type( 'http://e2e-test-image-processing.test' );
			cy.get( '#ms_computer_vision_api_key' ).clear().type( 'password' );
			cy.allowFeatureToAdmin();
			cy.get( '.classifai-settings__user-based-opt-out input' ).uncheck();
			// Disable access for all users.
			cy.disableFeatureForUsers();

			cy.saveFeatureSettings();
		} );

		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can see Azure AI Vision Image processing actions on edit media page and verify Generated data.', () => {
		cy.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		cy.get( '.classifai-descriptive-text-fields input#alt' ).check();
		cy.saveFeatureSettings();
		cy.visit( '/wp-admin/upload.php?mode=grid' ); // Ensure grid mode is enabled.
		cy.visit( '/wp-admin/media-new.php' );
		cy.get( '#plupload-upload-ui' ).should( 'exist' );
		cy.get( '#plupload-upload-ui input[type=file]' ).attachFile(
			'../../../assets/img/banner-772x250.png'
		);

		cy.get( '#media-items .media-item a.edit-attachment', {
			timeout: 20000,
		} ).should( 'exist' );
		cy.get( '#media-items .media-item a.edit-attachment' )
			.invoke( 'attr', 'href' )
			.then( ( editLink ) => {
				imageEditLink = editLink;
				cy.visit( editLink );
			} );

		// Verify Metabox with Image processing actions.
		cy.get( '.postbox-header h2, #classifai_image_processing h2' )
			.first()
			.contains( 'ClassifAI Image Processing' );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).contains( 'No descriptive text? Rescan image' );
		cy.get( '#classifai_image_processing label[for=rescan-tags]' ).contains(
			'Rescan image for new tags'
		);
		cy.get( '#classifai_image_processing label[for=rescan-ocr]' ).contains(
			'Rescan for text'
		);
		cy.get(
			'#classifai_image_processing label[for=rescan-smart-crop]'
		).should( 'exist' );

		// Verify generated Data.
		const imageData = getImageData();
		cy.get( '#attachment_alt' ).should( 'have.value', imageData.altText );
		cy.get( '#attachment_content' ).should( 'have.value', getOCRData() );
		cy.get(
			'#classifai-image-tags ul.tagchecklist li span.screen-reader-text'
		)
			.each( ( tag ) => {
				return expect(
					tag.text().replace( 'Remove term: ', '' )
				).to.be.oneOf( imageData.tags );
			} )
			.then( ( imageTags ) => {
				expect( imageTags ).to.have.length( imageData.tags.length );
			} );
	} );

	it( 'Can see Azure AI Vision Image processing actions on media modal', () => {
		const imageId = imageEditLink.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
		mediaModelLink = `wp-admin/upload.php?item=${ imageId }`;
		cy.visit( mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );

		// Verify Image processing actions.
		cy.get( '#classifai-rescan-alt-tags' ).contains( 'Rescan' );
		cy.get( '#classifai-rescan-image-tags' ).contains( 'Rescan' );
		cy.get( '#classifai-rescan-ocr' ).contains( 'Rescan' );
		cy.get( '#classifai-rescan-smart-crop' ).should( 'exist' );
	} );

	it( 'Can disable Azure AI Vision Image processing features', () => {
		const options = {
			imageEditLink,
			mediaModelLink,
		};

		// Disable features
		cy.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		cy.wait( 1000 );
		cy.get( '.classifai-descriptive-text-fields input#alt' ).uncheck();
		cy.get( '.classifai-descriptive-text-fields input#caption' ).uncheck();
		cy.get(
			'.classifai-descriptive-text-fields input#description'
		).uncheck();
		cy.saveFeatureSettings();

		cy.visitFeatureSettings(
			'image_processing/feature_image_tags_generator'
		);
		cy.wait( 1000 );
		cy.disableFeature();
		cy.saveFeatureSettings();

		cy.visitFeatureSettings( 'image_processing/feature_image_cropping' );
		cy.wait( 1000 );
		cy.disableFeature();
		cy.saveFeatureSettings();

		cy.visitFeatureSettings(
			'image_processing/feature_image_to_text_generator'
		);
		cy.wait( 1500 ); // Add delay to avoid flaky test in WP minimum environment.
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( false, options );

		// Enable features.
		cy.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		cy.get( '.classifai-descriptive-text-fields input#alt' ).check();
		cy.get( '.classifai-descriptive-text-fields input#caption' ).check();
		cy.get(
			'.classifai-descriptive-text-fields input#description'
		).check();
		cy.wait( 1500 );
		cy.enableFeature();
		cy.saveFeatureSettings();

		cy.visitFeatureSettings(
			'image_processing/feature_image_tags_generator'
		);
		cy.wait( 1500 );
		cy.enableFeature();
		cy.saveFeatureSettings();

		cy.visitFeatureSettings( 'image_processing/feature_image_cropping' );
		cy.wait( 1500 );
		cy.enableFeature();
		cy.saveFeatureSettings();

		cy.visitFeatureSettings(
			'image_processing/feature_image_to_text_generator'
		);
		cy.wait( 1500 );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( true, options );
	} );

	it( 'Can enable/disable AI Vision features by roles', () => {
		const options = {
			imageEditLink,
			mediaModelLink,
		};

		// Enable features.
		cy.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		cy.enableFeature();
		cy.get( '.classifai-descriptive-text-fields input#alt' ).check();
		cy.wait( 500 );
		cy.saveFeatureSettings();

		// Disable access to admin role.
		cy.disableFeatureForRoles( 'feature_descriptive_text_generator', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.disableFeatureForRoles( 'feature_image_tags_generator', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.disableFeatureForRoles( 'feature_image_cropping', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.disableFeatureForRoles( 'feature_image_to_text_generator', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( false, options );

		// Enable access to admin role.
		cy.enableFeatureForRoles( 'feature_descriptive_text_generator', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.enableFeatureForRoles( 'feature_image_tags_generator', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.enableFeatureForRoles( 'feature_image_cropping', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.enableFeatureForRoles( 'feature_image_to_text_generator', [
			'administrator',
		] );

		// Verify that the feature is available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( true, options );
	} );

	it( 'Can enable/disable AI Vision features by user', () => {
		const options = {
			imageEditLink,
			mediaModelLink,
		};

		// Disable access to admin role.
		cy.disableFeatureForRoles( 'feature_descriptive_text_generator', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.disableFeatureForRoles( 'feature_image_tags_generator', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.disableFeatureForRoles( 'feature_image_cropping', [
			'administrator',
		] );
		cy.wait( 500 );
		cy.disableFeatureForRoles( 'feature_image_to_text_generator', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( false, options );

		cy.enableFeatureForUsers( 'feature_descriptive_text_generator', [
			'admin',
		] );
		cy.wait( 500 );
		cy.enableFeatureForUsers( 'feature_image_tags_generator', [ 'admin' ] );
		cy.wait( 500 );
		cy.enableFeatureForUsers( 'feature_image_cropping', [ 'admin' ] );
		cy.wait( 500 );
		cy.enableFeatureForUsers( 'feature_image_to_text_generator', [
			'admin',
		] );

		// Verify that the feature is available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( true, options );
	} );

	it( 'User can opt-out AI Vision features', () => {
		const options = {
			imageEditLink,
			mediaModelLink,
		};

		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_descriptive_text_generator' );
		cy.enableFeatureOptOut( 'feature_image_tags_generator' );
		cy.enableFeatureOptOut( 'feature_image_cropping' );
		cy.enableFeatureOptOut( 'feature_image_to_text_generator' );

		// opt-out
		cy.optOutFeature( 'feature_descriptive_text_generator' );
		cy.optOutFeature( 'feature_image_tags_generator' );
		cy.optOutFeature( 'feature_image_cropping' );
		cy.optOutFeature( 'feature_image_to_text_generator' );

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( false, options );

		// opt-in
		cy.optInFeature( 'feature_descriptive_text_generator' );
		cy.optInFeature( 'feature_image_tags_generator' );
		cy.optInFeature( 'feature_image_cropping' );
		cy.optInFeature( 'feature_image_to_text_generator' );

		// Verify that the feature is available.
		cy.wait( 1000 );
		cy.verifyAIVisionEnabled( true, options );
	} );
} );
