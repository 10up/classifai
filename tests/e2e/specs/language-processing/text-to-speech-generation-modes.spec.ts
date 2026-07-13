/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/test';

/**
 * Covers the `generation_timing` setting (Automatic / Manual / On demand) and
 * the on-demand front-end "generate on first listen" flow. Uses the Microsoft
 * Azure provider since the test plugin mocks its synthesis endpoint, so
 * on-demand generation completes synchronously without a real API call.
 */
test.describe( '[Language Processing] Text to Speech generation modes', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch {
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

		// Select provider (initial).
		const editBtn = page.locator( '.classifai-settings-edit-provider' );
		if ( await editBtn.count() ) {
			await editBtn.first().click();
		}
		await page
			.locator( '.classifai-provider-select select' )
			.selectOption( 'ms_azure_text_to_speech' );

		await page.locator( '.settings-allowed-post-types input#post' ).check();

		// Select provider again (re-render after enabling the post type).
		const editBtn2 = page.locator( '.classifai-settings-edit-provider' );
		if ( await editBtn2.count() ) {
			await editBtn2.first().click();
		}
		await page
			.locator( '.classifai-provider-select select' )
			.selectOption( 'ms_azure_text_to_speech' );

		await page
			.locator( '#ms_azure_text_to_speech_endpoint_url' )
			.fill( '' );
		await page
			.locator( '#ms_azure_text_to_speech_endpoint_url' )
			.fill( 'https://service.com' );
		await page
			.locator( '#ms_azure_text_to_speech_api_key' )
			.fill( 'password' );

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

		// Voices populate after the first save; pick one and save again.
		await page
			.locator( '#ms_azure_text_to_speech_voice' )
			.selectOption( 'en-AU-AnnetteNeural|Female' );

		const responsePromise2 = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise2;

		// Opt in to all features for the admin user.
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

	test( 'Audio generation timing setting offers all three modes', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_text_to_speech_generation'
		);

		const select = page.locator( '#generation_timing' );
		await expect( select ).toBeVisible();
		await expect( select.locator( 'option' ) ).toHaveCount( 3 );
		await expect(
			select.locator( 'option[value="automatic"]' )
		).toHaveCount( 1 );
		await expect( select.locator( 'option[value="manual"]' ) ).toHaveCount(
			1
		);
		await expect(
			select.locator( 'option[value="on_demand"]' )
		).toHaveCount( 1 );

		// Switch to on-demand for the following tests.
		await select.selectOption( 'on_demand' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'On-demand: per-post toggle is enabled and on by default', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.createPost( {
			title: 'On demand toggle default',
			content: 'On-demand audio generation toggle should default on.',
			publish: false,
		} );
		await editor.openDocumentSettingsSidebar();
		await classifaiUtils.openClassifAIPostPanel();

		const enableToggle = page.getByRole( 'checkbox', {
			name: 'Enable audio generation',
		} );
		await expect( enableToggle ).toBeChecked();
		await expect( enableToggle ).toBeEnabled();
	} );

	test( 'On-demand: audio is generated on the first front-end listen', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.createPost( {
			title: 'On demand listen',
			content: 'This audio is generated the first time someone listens.',
		} );

		await page.goto( '/on-demand-listen/' );

		const controls = page.locator( '.class-post-audio-controls' );
		await expect( controls ).toBeVisible();
		// No audio exists yet, so the player advertises the on-demand state.
		await expect( controls ).toHaveAttribute( 'data-has-audio', '0' );

		// Clicking triggers synchronous generation against the mocked provider.
		const generation = page.waitForResponse(
			( res ) =>
				res
					.url()
					.includes( '/classifai/v1/synthesize-speech-on-demand/' ) &&
				res.request().method() === 'POST'
		);
		await controls.click();
		const response = await generation;
		expect( response.ok() ).toBeTruthy();

		// Once generated, the player flips to the "has audio" + playing state.
		await expect( controls ).toHaveAttribute( 'data-has-audio', '1' );
		await expect(
			page.locator( '.dashicons-controls-pause' )
		).toBeVisible();
	} );

	test( 'On-demand: a post can be opted out, hiding the player', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.createPost( {
			title: 'On demand opted out',
			content: 'This post opts out of on-demand audio generation.',
			publish: false,
		} );
		await editor.openDocumentSettingsSidebar();
		await classifaiUtils.openClassifAIPostPanel();

		// Turn the per-post toggle off, then publish.
		const enableToggle = page.getByRole( 'checkbox', {
			name: 'Enable audio generation',
		} );
		await expect( enableToggle ).toBeChecked();
		await enableToggle.uncheck();
		await editor.publishPost();
		await classifaiUtils.closePublishPanel();

		await page.goto( '/on-demand-opted-out/' );
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toHaveCount( 0 );
	} );

	test( 'Manual: no audio is generated until the toggle is turned on', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_text_to_speech_generation'
		);
		await page.locator( '#generation_timing' ).selectOption( 'manual' );
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.createPost( {
			title: 'Manual mode post',
			content: 'Manual mode should not generate audio automatically.',
		} );
		await editor.openDocumentSettingsSidebar();
		await classifaiUtils.openClassifAIPostPanel();

		// The toggle defaults off in manual mode.
		await expect(
			page.getByRole( 'checkbox', { name: 'Enable audio generation' } )
		).not.toBeChecked();

		// And with no audio generated, the front-end shows no player.
		await page.goto( '/manual-mode-post/' );
		await expect(
			page.locator( '.class-post-audio-controls' )
		).toHaveCount( 0 );

		// Reset to automatic so the suite leaves settings in the default mode.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_text_to_speech_generation'
		);
		await page.locator( '#generation_timing' ).selectOption( 'automatic' );
		await classifaiUtils.saveFeatureSettings();
	} );
} );
