import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Moderation Tests', () => {
	let commentId: number;

	test.beforeAll( async ( { browser, requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		// Ensure a comment exists for moderation tests (global setup deletes
		// the default Hello World post + its comment).
		const post = await requestUtils.createPost( {
			title: 'Moderation test post',
			status: 'publish',
		} );
		const comment = await requestUtils.createComment( {
			post: post.id,
			content: 'I will kill you',
			status: 'approved',
		} );
		commentId = comment.id;

		const page = await browser.newPage();

		// Visit feature settings & save (mirrors cy.visitFeatureSettings then
		// cy.saveFeatureSettings without any modifications).
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_moderation'
		);
		await expect(
			page.locator( '.components-panel__header h2' ).first()
		).toBeVisible();
		await page.evaluate( () => {
			window.localStorage.setItem(
				'classifai_dont_ask_credential_reuse',
				'true'
			);
		} );
		const savePromise = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await savePromise;

		// Opt in all features.
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

	test( 'Can save OpenAI Moderation "Language Processing" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_moderation'
		);

		await classifaiUtils.selectProvider( 'openai_moderation' );
		await page.locator( '#openai_api_key' ).fill( 'password' );
		await classifaiUtils.enableFeature();
		await page
			.locator( '.settings-moderation-content-types input#comments' )
			.check();
		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can run moderation on a comment', async ( { page } ) => {
		await page.goto( '/wp-admin/edit-comments.php' );

		await page.locator( `#cb-select-${ commentId }` ).check();
		await page
			.locator( '#bulk-action-selector-top' )
			.selectOption( 'feature_moderation' );
		await page.locator( '#doaction' ).click();

		await expect(
			page.locator( `#comment-${ commentId } .column-moderation_flagged div` )
		).toContainText( 'Yes' );
		await expect(
			page.locator( `#comment-${ commentId } .column-moderation_flags div` )
		).toContainText( 'harassment/threatening, violence' );
	} );

	test( 'Can enable/disable moderation feature', async ( {
		classifaiUtils,
	} ) => {
		// Disable features.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_moderation'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.verifyModerationEnabled( false );

		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_moderation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await classifaiUtils.verifyModerationEnabled( true );
	} );

	test( 'Can enable/disable moderation feature by role', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_moderation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles( 'feature_moderation', [
			'administrator',
		] );

		// Verify that the feature is not available.
		await classifaiUtils.verifyModerationEnabled( false );

		// enable admin role.
		await classifaiUtils.enableFeatureForRoles( 'feature_moderation', [
			'administrator',
		] );

		// Verify that the feature is available.
		await classifaiUtils.verifyModerationEnabled( true );
	} );

	test( 'Can enable/disable moderation feature by user', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_moderation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles( 'feature_moderation', [
			'administrator',
		] );

		await classifaiUtils.enableFeatureForUsers( 'feature_moderation', [] );

		// Verify that the feature is not available.
		await classifaiUtils.verifyModerationEnabled( false );

		// Enable feature for admin user.
		await classifaiUtils.enableFeatureForUsers( 'feature_moderation', [
			'admin',
		] );

		// Verify that the feature is available.
		await classifaiUtils.verifyModerationEnabled( true );
	} );

	test( 'User can opt-out of moderation feature', async ( {
		classifaiUtils,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_moderation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Enable user based opt-out.
		await classifaiUtils.enableFeatureOptOut( 'feature_moderation' );

		// opt-out
		await classifaiUtils.optOutFeature( 'feature_moderation' );

		// Verify that the feature is not available.
		await classifaiUtils.verifyModerationEnabled( false );

		// opt-in
		await classifaiUtils.optInFeature( 'feature_moderation' );

		// Verify that the feature is available.
		await classifaiUtils.verifyModerationEnabled( true );
	} );
} );
