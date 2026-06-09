import { test, expect } from '../../fixtures/test';
import { getChatGPTData } from '../../fixtures/test-data';

test.describe( '[Language processing] Title Generation Tests', () => {
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

	test( 'Can save OpenAI ChatGPT "Language Processing" title settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await expect( page.locator( '#classifai-logo' ) ).toBeVisible();
		await classifaiUtils.selectProvider( 'openai_chatgpt' );
		await page.locator( '#openai_chatgpt_api_key' ).fill( 'password' );
		await classifaiUtils.enableFeature();
		await classifaiUtils.allowFeatureToAdmin();
		await page.locator( '#openai_chatgpt_number_of_suggestions' ).fill( '1' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see the generate titles button in a post', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		const data = getChatGPTData();

		await classifaiUtils.createPost( {
			title: 'Test ChatGPT generate titles',
			content: 'Test content',
		} );

		await classifaiUtils.closePublishPanel();
		await editor.openDocumentSettingsSidebar();

		await classifaiUtils.openLegacyPostStatusPanelIfPresent();

		const titleBtn = page
			.locator( '.classifai-post-status button.title' )
			.first();
		await expect( titleBtn ).toBeVisible();
		await titleBtn.click();

		await expect( page.locator( '.title-modal' ) ).toBeVisible();
		await expect(
			page
				.locator( '.title-modal .classifai-title' )
				.first()
				.locator( 'textarea' )
		).toHaveValue( data );

		await page
			.locator( '.title-modal .classifai-title' )
			.first()
			.locator( 'button' )
			.click();

		await expect( page.locator( '.title-modal' ) ).toHaveCount( 0 );
		await expect(
			editor.canvas.locator( '.editor-post-title__input' ).first()
		).toContainText( data );
	} );

	test( 'Can see the generate titles button in a post (Classic Editor)', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.enableClassicEditor();

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		const data = getChatGPTData();

		await page.goto( '/wp-admin/post-new.php' );

		await page
			.locator( '#classifai-title-generation__title-generate-btn' )
			.click();
		await expect(
			page.locator( '#classifai-title-generation__modal' )
		).toBeVisible();
		await expect(
			page
				.locator( '.classifai-title-generation__result-item' )
				.first()
				.locator( 'textarea' )
		).toHaveValue( data );

		await page
			.locator( '.classifai-title-generation__select-title' )
			.first()
			.click();
		await expect(
			page.locator( '#classifai-title-generation__modal' )
		).toBeHidden();
		await expect( page.locator( '#title' ) ).toHaveValue( data );

		await classifaiUtils.disableClassicEditor();
	} );

	test( 'Can set multiple custom title generation prompts, select one as the default and delete one.', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.disableClassicEditor();
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);

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

		await page
			.locator( '#classifai-prompt-setting-1 .classifai-prompt-title input' )
			.fill( 'First custom prompt' );
		await page
			.locator( '#classifai-prompt-setting-1 .classifai-prompt-text textarea' )
			.fill( 'This is our first custom title prompt' );

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
			.fill( 'This is a custom title prompt' );

		await page
			.locator(
				'#classifai-prompt-setting-3 .actions-rows button.action__set_default'
			)
			.click( { force: true } );

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

		const data = getChatGPTData( 'title' );

		await classifaiUtils.createPost( {
			title: 'Test ChatGPT generate titles',
			content: 'Test content',
		} );
		await classifaiUtils.closePublishPanel();
		await editor.openDocumentSettingsSidebar();
		await classifaiUtils.openLegacyPostStatusPanelIfPresent();

		const titleBtn = page
			.locator( '.classifai-post-status button.title' )
			.first();
		await expect( titleBtn ).toBeVisible();
		await titleBtn.click();

		await expect( page.locator( '.title-modal' ) ).toBeVisible();
		await expect(
			page
				.locator( '.title-modal .classifai-title' )
				.first()
				.locator( 'textarea' )
		).toHaveValue( data );
		await page
			.locator( '.title-modal .classifai-title' )
			.first()
			.locator( 'button' )
			.click();

		await expect( page.locator( '.title-modal' ) ).toHaveCount( 0 );
		await expect(
			editor.canvas.locator( '.editor-post-title__input' ).first()
		).toContainText( data );
	} );

	test( 'Can enable/disable title generation feature', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.verifyTitleGenerationEnabled( false );

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.verifyTitleGenerationEnabled( true );
	} );

	test( 'Can enable/disable title generation feature by role', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.disableFeatureForRoles( 'feature_title_generation', [
			'administrator',
		] );

		await classifaiUtils.verifyTitleGenerationEnabled( false );

		await classifaiUtils.enableFeatureForRoles( 'feature_title_generation', [
			'administrator',
		] );

		await classifaiUtils.verifyTitleGenerationEnabled( true );
	} );

	test( 'Can enable/disable title generation feature by user', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.disableFeatureForRoles( 'feature_title_generation', [
			'administrator',
		] );

		await classifaiUtils.verifyTitleGenerationEnabled( false );

		await classifaiUtils.enableFeatureForUsers( 'feature_title_generation', [
			'admin',
		] );

		await classifaiUtils.verifyTitleGenerationEnabled( true );
	} );

	test( 'User can opt-out title generation feature', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.enableFeatureOptOut( 'feature_title_generation' );

		await classifaiUtils.optOutFeature( 'feature_title_generation' );

		await classifaiUtils.verifyTitleGenerationEnabled( false );

		await classifaiUtils.optInFeature( 'feature_title_generation' );

		await classifaiUtils.verifyTitleGenerationEnabled( true );
	} );
} );
