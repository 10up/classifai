import { test, expect } from '../../fixtures/test';
import { getChatGPTData } from '../../fixtures/test-data';

test.describe( '[Language processing] Excerpt Generation Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		const page = await browser.newPage();

		// Visit excerpt generation settings, enable feature and allow post type "post".
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_excerpt_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();

		// Disable credential reuse modal so enableFeature doesn't trip on it.
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
		await page.locator( '.settings-allowed-post-types input#post' ).check();

		const responsePromise = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise;

		// Opt in to all features (uncheck any opted-out feature checkboxes).
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

	test( 'Can save OpenAI ChatGPT "Language Processing" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await classifaiUtils.selectProvider( 'openai_chatgpt' );
		await page.locator( '#openai_chatgpt_api_key' ).fill( 'password' );

		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await page.locator( '#excerpt_length' ).fill( '35' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see the generate excerpt button in a post', async ( {
		classifaiUtils,
		page,
	} ) => {
		await page.goto( '/wp-admin/plugins.php' );
		await classifaiUtils.disableClassicEditor();

		const data = getChatGPTData();

		// Create test post.
		await classifaiUtils.createPost( {
			title: 'Test ChatGPT post',
			content: 'Test GPT content',
		} );

		// Close post publish panel.
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar.
		await classifaiUtils.editor.openDocumentSettingsSidebar();

		// Find and open the excerpt panel.
		await page.locator( '.editor-post-excerpt__dropdown button' ).click();

		// Click on button and verify data loads in.
		await page.locator( '.classifai-excerpt-generation button' ).click();
		await expect(
			page.locator(
				'.editor-post-excerpt .editor-post-excerpt__textarea textarea'
			)
		).toHaveValue( data );
	} );

	test( 'Can see the generate excerpt button in a post (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.enableClassicEditor();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		const data = getChatGPTData();

		// Create post in classic editor.
		await page.goto( '/wp-admin/post-new.php?post_type=post' );
		await page.locator( '#title' ).fill( 'Excerpt test classic' );
		// Ensure Visual mode (default), since previous tests may have switched to Text.
		if ( await page.locator( '#content-tmce' ).count() ) {
			await page.locator( '#content-tmce' ).click();
		}
		const contentFrame = page.frameLocator( '#content_ifr' );
		await contentFrame
			.locator( 'body#tinymce' )
			.fill( 'Test GPT content.' );

		// Ensure excerpt metabox is shown.
		await page.locator( '#show-settings-link' ).click();
		await page.locator( '#postexcerpt-hide' ).check( { force: true } );

		// Verify button exists.
		await expect(
			page.locator( '#classifai-excerpt-generation__excerpt-generate-btn' )
		).toBeAttached();

		// Click on button and verify data loads in.
		await page
			.locator( '#classifai-excerpt-generation__excerpt-generate-btn' )
			.click();
		await expect( page.locator( '#excerpt' ) ).toHaveValue( data );

		await classifaiUtils.disableClassicEditor();
	} );

	test( 'Can set multiple custom excerpt generation prompts, select one as the default and delete one.', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.disableClassicEditor();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);

		// Add three custom prompts.
		for ( let i = 0; i < 3; i++ ) {
			await page
				.locator( 'button.components-button.action__add_prompt' )
				.click();
		}
		await expect(
			page.locator(
				'.classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 4 );

		// Set the data for each prompt.
		await page
			.locator( '#classifai-prompt-setting-1 .classifai-prompt-title input' )
			.fill( 'First custom prompt' );
		await page
			.locator( '#classifai-prompt-setting-1 .classifai-prompt-text textarea' )
			.fill( 'This is our first custom excerpt prompt' );

		await page
			.locator( '#classifai-prompt-setting-2 .classifai-prompt-title input' )
			.fill( 'Second custom prompt' );
		await page
			.locator( '#classifai-prompt-setting-2 .classifai-prompt-text textarea' )
			.fill( 'This prompt should be deleted' );
		await page
			.locator( '#classifai-prompt-setting-3 .classifai-prompt-title input' )
			.fill( 'Third custom prompt' );
		await page
			.locator( '#classifai-prompt-setting-3 .classifai-prompt-text textarea' )
			.fill( 'This is a custom excerpt prompt' );

		// Set the third prompt as our default.
		await page
			.locator(
				'#classifai-prompt-setting-3 .actions-rows button.action__set_default'
			)
			.click( { force: true } );

		// Delete the second prompt.
		await page
			.locator(
				'#classifai-prompt-setting-2 .actions-rows button.action__remove_prompt'
			)
			.click( { force: true } );
		await page
			.locator( 'div.components-confirm-dialog button.is-primary' )
			.click();
		await expect(
			page.locator(
				'.classifai-prompts div.classifai-field-type-prompt-setting'
			)
		).toHaveCount( 3 );

		await classifaiUtils.saveFeatureSettings();

		const data = getChatGPTData( 'excerpt' );

		// Create test post.
		await classifaiUtils.createPost( {
			title: 'Test ChatGPT post',
			content: 'Test GPT content',
		} );

		// Close post publish panel.
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar.
		await classifaiUtils.editor.openDocumentSettingsSidebar();

		// Find and open the excerpt panel.
		await page.locator( '.editor-post-excerpt__dropdown button' ).click();

		// Click on button and verify data loads in.
		await page.locator( '.classifai-excerpt-generation button' ).click();
		await expect(
			page.locator(
				'.editor-post-excerpt .editor-post-excerpt__textarea textarea'
			)
		).toHaveValue( data );
	} );

	test( 'Can enable/disable excerpt generation feature', async ( {
		classifaiUtils,
	} ) => {
		// Disable features.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.verifyExcerptGenerationEnabled( false );

		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await classifaiUtils.verifyExcerptGenerationEnabled( true );
	} );

	test( 'Can enable/disable excerpt generation feature by role', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_excerpt_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is not available.
		await classifaiUtils.verifyExcerptGenerationEnabled( false );

		// Enable admin role.
		await classifaiUtils.enableFeatureForRoles(
			'feature_excerpt_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is available.
		await classifaiUtils.verifyExcerptGenerationEnabled( true );
	} );

	test( 'Can enable/disable excerpt generation feature by user', async ( {
		classifaiUtils,
	} ) => {
		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_excerpt_generation',
			[ 'administrator' ]
		);

		await classifaiUtils.enableFeatureForUsers(
			'feature_excerpt_generation',
			[]
		);

		// Verify that the feature is not available.
		await classifaiUtils.verifyExcerptGenerationEnabled( false );

		// Enable feature for admin user.
		await classifaiUtils.enableFeatureForUsers(
			'feature_excerpt_generation',
			[ 'admin' ]
		);

		// Verify that the feature is available.
		await classifaiUtils.verifyExcerptGenerationEnabled( true );
	} );

	test( 'User can opt-out excerpt generation feature', async ( {
		classifaiUtils,
	} ) => {
		// Enable user based opt-out.
		await classifaiUtils.enableFeatureOptOut(
			'feature_excerpt_generation'
		);

		// opt-out
		await classifaiUtils.optOutFeature( 'feature_excerpt_generation' );

		// Verify that the feature is not available.
		await classifaiUtils.verifyExcerptGenerationEnabled( false );

		// opt-in
		await classifaiUtils.optInFeature( 'feature_excerpt_generation' );

		// Verify that the feature is available.
		await classifaiUtils.verifyExcerptGenerationEnabled( true );
	} );
} );
