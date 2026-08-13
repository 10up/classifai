import path from 'path';
import { test, expect } from '../../fixtures/test';
import { getPDFData } from '../../fixtures/test-data';

test.describe( 'PDF read Tests', () => {
	let pdfEditLink = '';

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/image_processing/feature_pdf_to_text_generation'
		);
		const toggle = page.locator(
			'.classifai-enable-feature-toggle input[type="checkbox"]'
		);
		if ( ! ( await toggle.isChecked() ) ) {
			await page.evaluate( () => {
				window.localStorage.setItem(
					'classifai_dont_ask_credential_reuse',
					'true'
				);
			} );
			await toggle.check();
		}
		const responsePromise = page.waitForResponse( ( res ) =>
			res.url().includes( '/wp-json/classifai/v1/settings/' )
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise;

		// Opt-in all features
		await page.goto( '/wp-admin/profile.php' );
		const optOuts = page.locator(
			'input[name="classifai_opted_out_features[]"]'
		);
		const total = await optOuts.count();
		let anyChecked = false;
		for ( let i = 0; i < total; i++ ) {
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

	test( 'Can save "PDF scanning" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_pdf_to_text_generation'
		);
		await classifaiUtils.selectProvider( 'ms_computer_vision' );
		await page
			.locator( '#ms_computer_vision_endpoint_url' )
			.fill( 'http://e2e-test-image-processing.test' );
		await page
			.locator( '#ms_computer_vision_api_key' )
			.fill( 'password' );
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see PDF scanning actions on edit media page and verify PDF read data.', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/media-new.php' );
		await expect( page.locator( '#plupload-upload-ui' ) ).toBeVisible();
		await page
			.locator( '#plupload-upload-ui input[type=file]' )
			.setInputFiles(
				path.resolve( __dirname, '../../assets/dummy.pdf' )
			);

		await page
			.locator( '#media-items .media-item a.edit-attachment' )
			.waitFor( { timeout: 20000 } );
		const editLink = await page
			.locator( '#media-items .media-item a.edit-attachment' )
			.getAttribute( 'href' );
		pdfEditLink = editLink as string;
		await page.goto( editLink as string );

		// Verify Metabox with Image processing actions.
		await expect(
			page
				.locator( '.postbox-header h2, #attachment_meta_box h2' )
				.first()
		).toContainText( 'ClassifAI PDF Processing' );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toContainText( 'Rescan PDF for text' );

		// Verify generated Data.
		await expect( page.locator( '#attachment_content' ) ).toHaveValue(
			getPDFData()
		);
	} );

	test( 'Can enable/disable PDF scanning feature', async ( {
		classifaiUtils,
		page,
	} ) => {
		// Disable feature.
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_pdf_to_text_generation'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toHaveCount( 0 );

		// Enable admin role.
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_pdf_to_text_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toBeVisible();
	} );

	test( 'Can enable/disable PDF scanning feature by role', async ( {
		classifaiUtils,
		page,
	} ) => {
		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'image_processing/feature_pdf_to_text_generation'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_pdf_to_text_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is not available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toHaveCount( 0 );

		// Enable admin role.
		await classifaiUtils.enableFeatureForRoles(
			'feature_pdf_to_text_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toBeVisible();
	} );

	test( 'Can enable/disable PDF scanning feature by user', async ( {
		classifaiUtils,
		page,
	} ) => {
		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles(
			'feature_pdf_to_text_generation',
			[ 'administrator' ]
		);

		// Verify that the feature is not available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toHaveCount( 0 );

		// Enable feature for admin user.
		await classifaiUtils.enableFeatureForUsers(
			'feature_pdf_to_text_generation',
			[ 'admin' ]
		);

		// Verify that the feature is available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toBeVisible();
	} );

	test( 'User can opt-out PDF scanning feature', async ( {
		classifaiUtils,
		page,
	} ) => {
		// Enable user based opt-out.
		await classifaiUtils.enableFeatureOptOut(
			'feature_pdf_to_text_generation'
		);

		// opt-out
		await classifaiUtils.optOutFeature(
			'feature_pdf_to_text_generation'
		);

		// Verify that the feature is not available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toHaveCount( 0 );

		// opt-in
		await classifaiUtils.optInFeature(
			'feature_pdf_to_text_generation'
		);

		// Verify that the feature is available.
		await page.goto( pdfEditLink );
		await expect(
			page.locator( '.misc-publishing-actions label[for=rescan-pdf]' )
		).toBeVisible();
	} );
} );
