import { getWhisperData } from '../../plugins/functions';

describe( '[Language processing] Speech to Text Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save OpenAI Whisper "Language Processing" settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);

		cy.get( '#openai_api_key' ).clear().type( 'password' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	let audioEditLink = '';
	let mediaModalLink = '';
	it( 'Can see OpenAI Whisper language processing actions on edit media page and verify generated data.', () => {
		cy.visit( '/wp-admin/upload.php?mode=grid' ); // Ensure grid mode is enabled.
		cy.visit( '/wp-admin/media-new.php' );
		cy.get( '#plupload-upload-ui' ).should( 'exist' );
		cy.get( '#plupload-upload-ui input[type=file]' ).attachFile(
			'audio.mp3'
		);

		cy.get( '#media-items .media-item a.edit-attachment' ).should(
			'exist'
		);
		cy.get( '#media-items .media-item a.edit-attachment' )
			.invoke( 'attr', 'href' )
			.then( ( editLink ) => {
				audioEditLink = editLink;
				cy.visit( editLink );
			} );

		// Verify metabox has processing actions.
		cy.get( '.postbox-header h2, #attachment_meta_box h2' )
			.first()
			.contains( 'ClassifAI Audio Processing' );
		cy.get( '.misc-publishing-actions label[for=retranscribe]' ).contains(
			'Re-transcribe'
		);

		// Verify generated data.
		cy.get( '#attachment_content' ).should(
			'have.value',
			getWhisperData()
		);
	} );

	it( 'Can see OpenAI Whisper language processing actions on media model', () => {
		const audioId = audioEditLink.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
		mediaModalLink = `wp-admin/upload.php?item=${ audioId }`;
		cy.visit( mediaModalLink );
		cy.get( '.media-modal' ).should( 'exist' );

		// Verify language processing actions.
		cy.get( '#classifai-retranscribe' ).contains( 'Re-transcribe' );
	} );

	it( 'Can enable/disable OpenAI Whisper language processing features', () => {
		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Disable features
		cy.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifySpeechToTextEnabled( false, options );

		// Enable feature.
		cy.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifySpeechToTextEnabled( true, options );
	} );

	it( 'Can enable/disable speech to text feature by role', () => {
		// Enable feature.
		cy.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();

		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_audio_transcripts_generation', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifySpeechToTextEnabled( false, options );

		// Enable admin role.
		cy.enableFeatureForRoles( 'feature_audio_transcripts_generation', [
			'administrator',
		] );

		// Verify that the feature is available.
		cy.verifySpeechToTextEnabled( true, options );
	} );

	it( 'Can enable/disable speech to text feature by user', () => {
		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_audio_transcripts_generation', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifySpeechToTextEnabled( false, options );

		// Enable feature for admin user.
		cy.enableFeatureForUsers( 'feature_audio_transcripts_generation', [
			'admin',
		] );

		// Verify that the feature is available.
		cy.verifySpeechToTextEnabled( true, options );
	} );

	it( 'User can opt-out speech to text feature', () => {
		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Enable user based opt-out.
		cy.enableFeatureOptOut( 'feature_audio_transcripts_generation' );

		// opt-out
		cy.optOutFeature( 'feature_audio_transcripts_generation' );

		// Verify that the feature is not available.
		cy.verifySpeechToTextEnabled( false, options );

		// opt-in
		cy.optInFeature( 'feature_audio_transcripts_generation' );

		// Verify that the feature is available.
		cy.verifySpeechToTextEnabled( true, options );
	} );
} );
