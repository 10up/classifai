import { execFileSync } from 'child_process';
import { test, expect } from '../../fixtures/test';

test.describe( '[Language Processing] Text to Speech (ElevenLabs) Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		const page = await browser.newPage();
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_text_to_speech_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await expect(
			page.locator( '.classifai-loading-settings' )
		).toHaveCount( 0 );

		await page.locator( '.settings-allowed-post-types input#post' ).check();

		const editBtn = page.locator( '.classifai-settings-edit-provider' );
		if ( await editBtn.count() ) {
			await editBtn.first().click();
		}
		await page
			.locator( '.classifai-provider-select select' )
			.selectOption( 'elevenlabs_text_to_speech' );

		await page.locator( '#elevenlabs_api_key' ).fill( 'password' );

		// Enable feature.
		await page.evaluate( () => {
			window.localStorage.setItem(
				'classifai_dont_ask_credential_reuse',
				'true'
			);
		} );
		const toggle = page.locator(
			'.classifai-enable-feature-toggle input[type="checkbox"]'
		);
		if ( ! ( await toggle.isChecked() ) ) {
			await toggle.check();
		}

		const responsePromise1 = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise1;

		await page
			.locator( '#elevenlabs_text_to_speech_model' )
			.selectOption( 'eleven_multilingual_v2' );
		await page
			.locator( '#elevenlabs_text_to_speech_voice' )
			.selectOption( '21m00Tcm4TlvDq8ikWAM' );

		const responsePromise2 = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise2;

		// Opt in to all features.
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

	test( 'Generates audio from text', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.createPost( {
			title: 'Text to Speech test',
			content:
				"This feature uses ElevenLabs's Text to Speech capabilities.",
		} );
		await editor.openDocumentSettingsSidebar();
		await classifaiUtils.openClassifAIPostPanel();
		await expect( page.locator( '.classifai-panel' ) ).toContainText(
			'Audio generation is in progress…'
		);
		execFileSync(
			'npx',
			[
				'wp-env',
				'run',
				'tests-cli',
				'wp',
				'action-scheduler',
				'run',
				'--hooks=classifai_schedule_text_to_speech_job',
			],
			{ timeout: 20000, stdio: 'inherit' }
		);
		await page.reload();
		await expect( page.locator( '.classifai-panel' ) ).not.toContainText(
			'Audio generation is in progress…'
		);
		await expect(
			page.locator( '#classifai-audio-controls__preview-btn' )
		).toBeVisible();
	} );

	test( 'Audio controls are visible if supported by post type', async ( {
		page,
	} ) => {
		await page.goto( '/text-to-speech-test/' );
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toBeVisible();
	} );

	test( 'a11y - aria-labels', async ( { page } ) => {
		await page.goto( '/text-to-speech-test/' );
		await expect(
			page.locator( '.dashicons-controls-play' )
		).toBeVisible();
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toHaveAttribute( 'aria-label', 'Play audio' );

		await page.locator( '.class-post-audio-controls' ).click();

		await expect(
			page.locator( '.dashicons-controls-play' )
		).not.toBeVisible();
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toHaveAttribute( 'aria-label', 'Pause audio' );

		await page.locator( '.class-post-audio-controls' ).click();
		await expect(
			page.locator( '.dashicons-controls-play' )
		).toBeVisible();
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toHaveAttribute( 'aria-label', 'Play audio' );
	} );

	test( 'a11y - keyboard accessibility', async ( { page } ) => {
		await page.goto( '/text-to-speech-test/' );
		await page.locator( '.class-post-audio-controls' ).focus();
		await page.keyboard.press( 'Enter' );
		await expect(
			page.locator( '.dashicons-controls-pause' )
		).toBeVisible();
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toHaveAttribute( 'aria-label', 'Pause audio' );

		await page.keyboard.press( 'Enter' );
		await expect(
			page.locator( '.dashicons-controls-play' )
		).toBeVisible();
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toHaveAttribute( 'aria-label', 'Play audio' );
	} );

	test( 'Can see the enable button in a post (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.enableClassicEditor();

		await page.goto( '/wp-admin/post-new.php?post_type=post' );
		await page
			.locator( '#title' )
			.fill( 'Text to Speech test classic' );
		await page.locator( '#content-html' ).click();
		await page
			.locator( '#content' )
			.fill(
				"This feature uses ElevenLabs's Text to Speech capabilities."
			);
		await page.locator( '#publish' ).click();

		await expect(
			page.locator( '#classifai-text-to-speech-meta-box' )
		).toContainText( 'Audio generation is in progress…' );
		execFileSync(
			'npx',
			[
				'wp-env',
				'run',
				'tests-cli',
				'wp',
				'action-scheduler',
				'run',
				'--hooks=classifai_schedule_text_to_speech_job',
			],
			{ timeout: 20000, stdio: 'inherit' }
		);
		await page.reload();
		await expect(
			page.locator( '#classifai-text-to-speech-meta-box' )
		).not.toContainText( 'Audio generation is in progress…' );

		await expect(
			page.locator( '#classifai-text-to-speech-meta-box' )
		).toBeVisible();
		await page.locator( '#classifai_synthesize_speech' ).check();
		await expect(
			page.locator( '#classifai-audio-preview' )
		).toBeVisible();

		await page.goto( '/text-to-speech-test/' );
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toBeVisible();

		await classifaiUtils.disableClassicEditor();
	} );
} );
