/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/test';

/**
 * Covers the Key Takeaways `generation_timing` setting (Manual / On demand) and
 * the on-demand front-end "generate on first request" flow. Uses the OpenAI
 * ChatGPT provider since the test plugin mocks its chat completions endpoint,
 * so on-demand generation completes synchronously without a real API call.
 */
test.describe( '[Language Processing] Key Takeaways On Demand mode', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch {
			// noop
		}

		const page = await browser.newPage();

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

	test( 'Processing mode setting offers both modes and reveals On Demand fields', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_key_takeaways'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();

		// Configure and enable the feature so it is fully set up.
		await classifaiUtils.selectProvider( 'openai_chatgpt' );
		await page.locator( '#openai_chatgpt_api_key' ).fill( 'password' );
		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();

		// The processing mode select offers Manual and On demand.
		const select = page.locator(
			'.settings-key-takeaways-generation-timing select'
		);
		await expect( select ).toBeVisible();
		await expect( select.locator( 'option[value="manual"]' ) ).toHaveCount(
			1
		);
		await expect(
			select.locator( 'option[value="on_demand"]' )
		).toHaveCount( 1 );

		// On-demand-only fields are hidden in Manual mode.
		await select.selectOption( 'manual' );
		await expect(
			page.locator( '.settings-key-takeaways-button-label' )
		).toHaveCount( 0 );

		// Switching to On demand reveals the post types, button label and
		// display format controls.
		await select.selectOption( 'on_demand' );
		await expect(
			page.locator( '.settings-allowed-post-types' )
		).toBeVisible();
		await expect(
			page.locator( '.settings-key-takeaways-button-label input' )
		).toBeVisible();
		await expect(
			page.locator( '.settings-key-takeaways-render select' )
		).toBeVisible();

		// Set a custom button label and make sure Posts are enabled.
		await page
			.locator( '.settings-key-takeaways-button-label input' )
			.fill( 'Summarize' );
		const postType = page.locator(
			'.settings-allowed-post-types input#post'
		);
		if ( ! ( await postType.isChecked() ) ) {
			await postType.check();
		}

		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'On Demand settings persist after reload', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_key_takeaways'
		);

		await expect(
			page.locator( '.settings-key-takeaways-generation-timing select' )
		).toHaveValue( 'on_demand' );
		await expect(
			page.locator( '.settings-key-takeaways-button-label input' )
		).toHaveValue( 'Summarize' );
	} );

	test( 'Button generates takeaways on the front-end and toggles open/closed', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.createPost( {
			title: 'KT On Demand Front End',
			content:
				'This article should produce key takeaways on the first request.',
		} );

		await page.goto( '/kt-on-demand-front-end/' );

		const toggle = page.locator( '.classifai-key-takeaways-toggle' );
		await expect( toggle ).toBeVisible();
		await expect(
			toggle.locator( '.classifai-key-takeaways-toggle__label' )
		).toHaveText( 'Summarize' );

		// Nothing generated yet: collapsed and flagged as having no takeaways.
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( toggle ).toHaveAttribute( 'data-has-takeaways', '0' );

		// Clicking triggers synchronous generation against the mocked provider.
		const generation = page.waitForResponse(
			( res ) =>
				res
					.url()
					.includes( '/classifai/v1/key-takeaways-on-demand/' ) &&
				res.request().method() === 'POST'
		);
		await toggle.click();
		const response = await generation;
		expect( response.ok() ).toBeTruthy();

		// Takeaways render in the panel and the control reflects the open state.
		const items = page.locator( '.classifai-key-takeaways-panel ul li' );
		await expect( items ).toHaveCount( 4 );
		await expect( items.first() ).toContainText(
			'Spring symbolizes renewal and beauty, inspiring creativity and reflection.'
		);
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( toggle ).toHaveAttribute( 'data-has-takeaways', '1' );

		// Clicking again collapses the panel.
		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
	} );

	test( 'Generated takeaways are reused (cached) and work for logged-out visitors', async ( {
		page,
		context,
	} ) => {
		await context.clearCookies();
		await page.goto( '/kt-on-demand-front-end/' );

		const toggle = page.locator( '.classifai-key-takeaways-toggle' );
		await expect( toggle ).toBeVisible();
		// Takeaways already exist, so the panel is pre-populated server-side.
		await expect( toggle ).toHaveAttribute( 'data-has-takeaways', '1' );

		await toggle.click();
		const items = page.locator( '.classifai-key-takeaways-panel ul li' );
		await expect( items ).toHaveCount( 4 );
	} );

	test( 'Button is suppressed when the post already contains the block', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.createPost( {
			title: 'KT On Demand With Block',
			content: 'This post already includes the Key Takeaways block.',
			publish: false,
		} );
		await editor.insertBlock( { name: 'classifai/key-takeaways' } );
		await editor.publishPost();
		await classifaiUtils.closePublishPanel();

		await page.goto( '/kt-on-demand-with-block/' );

		// The block renders its own takeaways; the on-demand button should not
		// also appear.
		await expect(
			page.locator( '.wp-block-classifai-key-takeaways' )
		).toBeVisible();
		await expect(
			page.locator( '.classifai-key-takeaways-toggle' )
		).toHaveCount( 0 );
	} );

	test( 'Reset to Manual mode', async ( { classifaiUtils, page } ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_key_takeaways'
		);
		await page
			.locator( '.settings-key-takeaways-generation-timing select' )
			.selectOption( 'manual' );
		await classifaiUtils.saveFeatureSettings();
	} );
} );
