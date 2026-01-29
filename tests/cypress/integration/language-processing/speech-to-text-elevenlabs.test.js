import { getWhisperData } from '../../plugins/functions';

describe( 'Speech to Text ElevenLabs Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		cy.enableFeature();
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);

		cy.selectProvider( 'elevenlabs_speech_to_text' );
		cy.get( '#elevenlabs_api_key' ).clear().type( 'password' );
		cy.saveFeatureSettings();

		cy.get( '#elevenlabs_speech_to_text_model' ).select(
			'scribe_v1_experimental'
		);

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	let audioEditLink = '';
	let mediaModalLink = '';

	it( 'Can see Audio Transcription actions on edit media page and verify generated data.', () => {
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

	it( 'Can see Audio Transcription actions on media modal', () => {
		const audioId = audioEditLink.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
		mediaModalLink = `wp-admin/upload.php?item=${ audioId }`;
		cy.visit( mediaModalLink );
		cy.get( '.media-modal' ).should( 'exist' );

		// Verify language processing actions.
		cy.get( '#classifai-retranscribe' ).contains( 'Re-transcribe' );
	} );
} );
