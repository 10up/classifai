import { test, expect } from '../../fixtures/test';

test.describe( 'Admin can login and make sure plugin is activated', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		try {
			await requestUtils.deactivatePlugin( 'elasticpress' );
		} catch ( _ ) {
			// already inactive
		}
	} );

	test( 'Can deactivate and activate plugin', async ( { requestUtils } ) => {
		await requestUtils.deactivatePlugin( 'classifai' );
		await requestUtils.activatePlugin( 'classifai' );
	} );

	test( 'Can see "ClassifAI" menu and Can visit "ClassifAI" settings page.', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/' );

		// Check ClassifAI menu.
		await expect(
			page.locator( '#adminmenu li#menu-tools ul.wp-submenu li', {
				hasText: 'ClassifAI',
			} )
		).toBeVisible();

		// Check Heading.
		await page.goto( '/wp-admin/tools.php?page=classifai' );
		await expect( page.locator( '.classifai-settings-wrapper' ) ).toBeVisible();
		await expect( page.locator( '.classifai-tabs' ) ).toBeVisible();
		await expect(
			page.locator( '.classifai-tabs a' ).first()
		).toContainText( 'Language Processing' );
	} );

	test( 'Can visit "Language Processing" settings page.', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);
		await expect( page.locator( '.classifai-tabs' ) ).toBeVisible();
		await expect(
			page.locator( '.classifai-tabs a.active-tab' ).first()
		).toContainText( 'Language Processing' );
	} );

	test( 'Can see "Image Processing" menu and Can visit "Image Processing" settings page.', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		await expect( page.locator( '.classifai-tabs' ) ).toBeVisible();
		await expect(
			page.locator( '.classifai-tabs a.active-tab' ).first()
		).toContainText( 'Image Processing' );
	} );

	test( 'Can visit the general settings page and see all settings.', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings( 'settings' );
		await expect( page.locator( '.classifai-tabs' ) ).toBeVisible();
		await expect(
			page.locator( '.classifai-tabs a.active-tab' ).first()
		).toContainText( 'Settings' );

		await expect(
			page.locator( '.components-input-control input[type="email"]' )
		).toBeVisible();
		await expect(
			page.locator( '.components-input-control input[type="password"]' )
		).toBeVisible();
		await expect(
			page.locator( '.classifai-enable-bot-block input' )
		).toBeVisible();
		await expect(
			page.locator( '.classifai-enable-bot-block input' )
		).not.toBeChecked();
	} );

	test( 'Can turn on "Block AI Bots" setting and it works.', async ( {
		classifaiUtils,
		page,
		baseURL,
	} ) => {
		await classifaiUtils.visitFeatureSettings( 'settings' );

		const enableBotBlock = page.locator(
			'.classifai-enable-bot-block input'
		);
		if ( ! ( await enableBotBlock.isChecked() ) ) {
			await enableBotBlock.check();
		}

		await classifaiUtils.saveGeneralSettings();

		// Check that the robots.txt file has bots blocked.
		const blockedResponse = await page.request.get(
			new URL( '/robots.txt', baseURL ).toString()
		);
		const blockedBody = await blockedResponse.text();
		for ( const bot of [
			'User-agent: Applebot-Extended',
			'User-agent: CCBot',
			'User-agent: ClaudeBot',
			'User-agent: FacebookBot',
			'User-agent: Google-Extended',
			'User-agent: GPTbot',
			'User-agent: Meta-ExternalAgent',
		] ) {
			expect( blockedBody ).toContain( bot );
		}

		await classifaiUtils.visitFeatureSettings( 'settings' );
		if ( await enableBotBlock.isChecked() ) {
			await enableBotBlock.uncheck();
		}
		await classifaiUtils.saveGeneralSettings();

		const unblockedResponse = await page.request.get(
			new URL( '/robots.txt', baseURL ).toString()
		);
		const unblockedBody = await unblockedResponse.text();
		for ( const bot of [
			'User-agent: Applebot-Extended',
			'User-agent: CCBot',
			'User-agent: ClaudeBot',
			'User-agent: FacebookBot',
			'User-agent: Google-Extended',
			'User-agent: GPTbot',
			'User-agent: Meta-ExternalAgent',
		] ) {
			expect( unblockedBody ).not.toContain( bot );
		}
	} );
} );
