import { getChatGPTData } from '../../plugins/functions';

describe( 'OpenAI Image Processing Tests', () => {
	let imageEditLink = '';
	let mediaModelLink = '';

	before( () => {
		cy.login();

		const imageProcessingFeatures = [
			'feature_descriptive_text_generator',
		];

		imageProcessingFeatures.forEach( ( feature ) => {
			cy.visitFeatureSettings( `image_processing/${ feature }` );
			cy.enableFeature();
			cy.selectProvider( 'openai_chatgpt' );
			cy.get( '#openai_chatgpt_api_key' ).clear().type( 'password' );
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

	it( 'Can see Image Processing actions on edit media page and verify generated data.', () => {
		cy.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		cy.get( '.classifai-descriptive-text-fields input#alt' ).check();
		cy.saveFeatureSettings();
		cy.visit( '/wp-admin/upload.php?mode=grid' ); // Ensure grid mode is enabled.
		cy.visit( '/wp-admin/media-new.php' );
		cy.get( '#plupload-upload-ui' ).should( 'exist' );
		cy.get( '#plupload-upload-ui input[type=file]' ).attachFile(
			'../../../assets/img/onboarding-1.png'
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

		// Verify generated Data.
		const imageData = getChatGPTData();
		cy.get( '#attachment_alt' ).should( 'have.value', imageData );
	} );

	it( 'Can see Image Processing actions on media modal', () => {
		const imageId = imageEditLink.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
		mediaModelLink = `wp-admin/upload.php?item=${ imageId }`;
		cy.visit( mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );

		// Verify Image processing actions.
		cy.get( '#classifai-rescan-alt-tags' ).contains( 'Rescan' );
	} );

	it( 'Can disable Image Processing features', () => {
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

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( 'not.exist' );
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( 'not.exist' );

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

		// Verify that the feature is available.
		cy.wait( 1000 );
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( 'exist' );
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( 'exist' );
	} );

	it( 'Can enable/disable Image Processing features by roles', () => {
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

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( 'not.exist' );
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( 'not.exist' );
	} );

	it( 'Can enable/disable Image Processing features by user', () => {
		const options = {
			imageEditLink,
			mediaModelLink,
		};

		// Disable access to admin role.
		cy.disableFeatureForRoles( 'feature_descriptive_text_generator', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( 'not.exist' );
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( 'not.exist' );

		cy.enableFeatureForUsers( 'feature_descriptive_text_generator', [
			'admin',
		] );

		// Verify that the feature is available.
		cy.wait( 1000 );
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( 'exist' );
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( 'exist' );
	} );

	it( 'User can opt-out of Image Processing features', () => {
		const options = {
			imageEditLink,
			mediaModelLink,
		};

		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_descriptive_text_generator' );

		// opt-out
		cy.optOutFeature( 'feature_descriptive_text_generator' );

		// Verify that the feature is not available.
		cy.wait( 1000 );
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( 'not.exist' );
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( 'not.exist' );

		// opt-in
		cy.optInFeature( 'feature_descriptive_text_generator' );

		// Verify that the feature is available.
		cy.wait( 1000 );
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( 'exist' );
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( 'exist' );
	} );
} );
