import path from 'path';
import { test, expect } from '../../fixtures/test';
import { getWhisperData } from '../../fixtures/test-data';

test.describe( '[Language processing] Speech to Text Tests', () => {
	let audioEditLink = '';
	let mediaModalLink = '';

	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}
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

	test( 'Can save OpenAI Audio Transcription settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);

		await classifaiUtils.selectProvider( 'openai_whisper' );
		await page.locator( '#openai_api_key' ).fill( 'password' );
		await page
			.locator( '#openai_whisper_model' )
			.selectOption( 'whisper-1' );

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see OpenAI Audio Transcription actions on edit media page and verify generated data.', async ( {
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

	test( 'Can see OpenAI Audio Transcription actions on media model', async ( {
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

	test( 'Can enable/disable OpenAI Audio Transcription features', async ( {
		classifaiUtils,
	} ) => {
		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Disable features
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.verifySpeechToTextEnabled( false, options );

		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await classifaiUtils.verifySpeechToTextEnabled( true, options );
	} );

	test( 'Can enable/disable speech to text feature by role', async ( {
		classifaiUtils,
	} ) => {
		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_audio_transcripts_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_audio_transcripts_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is not available.
		await classifaiUtils.verifySpeechToTextEnabled( false, options );

		// Enable admin role.
		await classifaiUtils.enableFeatureForRoles(
			'feature_audio_transcripts_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is available.
		await classifaiUtils.verifySpeechToTextEnabled( true, options );
	} );

	test( 'Can enable/disable speech to text feature by user', async ( {
		classifaiUtils,
	} ) => {
		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_audio_transcripts_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is not available.
		await classifaiUtils.verifySpeechToTextEnabled( false, options );

		// Enable feature for admin user.
		await classifaiUtils.enableFeatureForUsers(
			'feature_audio_transcripts_generation',
			[ 'admin' ]
		);

		// Verify that the feature is available.
		await classifaiUtils.verifySpeechToTextEnabled( true, options );
	} );

	test( 'User can opt-out speech to text feature', async ( {
		classifaiUtils,
	} ) => {
		const options = {
			audioEditLink,
			mediaModalLink,
		};

		// Enable user based opt-out.
		await classifaiUtils.enableFeatureOptOut(
			'feature_audio_transcripts_generation'
		);

		// opt-out
		await classifaiUtils.optOutFeature(
			'feature_audio_transcripts_generation'
		);

		// Verify that the feature is not available.
		await classifaiUtils.verifySpeechToTextEnabled( false, options );

		// opt-in
		await classifaiUtils.optInFeature(
			'feature_audio_transcripts_generation'
		);

		// Verify that the feature is available.
		await classifaiUtils.verifySpeechToTextEnabled( true, options );
	} );
} );
