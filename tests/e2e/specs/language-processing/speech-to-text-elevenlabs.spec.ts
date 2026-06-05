import path from 'path';
import { test, expect } from '../../fixtures/test';
import { getWhisperData } from '../../fixtures/test-data';

test.describe( 'Speech to Text ElevenLabs Tests', () => {
	let audioEditLink = '';
	let mediaModalLink = '';

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await page.goto( '/wp-admin/profile.php' );
		const optOuts = page.locator(
			'input[name="classifai_opted_out_features[]"]'
		);
		const count = await optOuts.count();
		let anyChecked = false;
		for ( let i = 0; i < count; i++ ) {
			const cb = optOuts.nth( i );
			if ( await cb.isChecked() ) {
				await cb.uncheck();
				anyChecked = true;
			}
		}
		if ( anyChecked ) {
			await page.locator( '#submit' ).click();
		}
		await page.close();
	} );

	test( 'Can save settings', async ( { classifaiUtils, page } ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);

		await classifaiUtils.selectProvider( 'elevenlabs_speech_to_text' );
		await page.locator( '#elevenlabs_api_key' ).fill( 'password' );
		await classifaiUtils.saveFeatureSettings();

		await page
			.locator( '#elevenlabs_speech_to_text_model' )
			.selectOption( 'scribe_v1_experimental' );

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see Audio Transcription actions on edit media page and verify generated data.', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/upload.php?mode=grid' ); // Ensure grid mode is enabled.
		await page.goto( '/wp-admin/media-new.php' );
		await expect( page.locator( '#plupload-upload-ui' ) ).toBeVisible();
		await page
			.locator( '#plupload-upload-ui input[type=file]' )
			.setInputFiles(
				path.resolve( __dirname, '../../assets/audio.mp3' )
			);

		await page
			.locator( '#media-items .media-item a.edit-attachment' )
			.waitFor( { timeout: 20000 } );
		const editLink = await page
			.locator( '#media-items .media-item a.edit-attachment' )
			.getAttribute( 'href' );
		audioEditLink = editLink || '';
		await page.goto( audioEditLink );

		// Verify metabox has processing actions.
		await expect(
			page
				.locator( '.postbox-header h2, #attachment_meta_box h2' )
				.first()
		).toContainText( 'ClassifAI Audio Processing' );
		await expect(
			page.locator( '.misc-publishing-actions label[for=retranscribe]' )
		).toContainText( 'Re-transcribe' );

		// Verify generated data.
		await expect( page.locator( '#attachment_content' ) ).toHaveValue(
			getWhisperData()
		);
	} );

	test( 'Can see Audio Transcription actions on media modal', async ( {
		page,
	} ) => {
		const audioId = audioEditLink.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
		mediaModalLink = `/wp-admin/upload.php?item=${ audioId }`;
		await page.goto( mediaModalLink );
		await expect( page.locator( '.media-modal' ) ).toBeVisible();

		// Verify language processing actions.
		await expect(
			page.locator( '#classifai-retranscribe' )
		).toContainText( 'Re-transcribe' );
	} );
} );
