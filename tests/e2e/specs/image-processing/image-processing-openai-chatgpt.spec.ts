import path from 'path';
import { test, expect } from '../../fixtures/test';
import { getChatGPTData } from '../../fixtures/test-data';

for ( const provider of [ 'openai_chatgpt', 'xai_grok' ] as const ) {
	const providerName =
		'openai_chatgpt' === provider ? 'OpenAI ChatGPT' : 'xAI Grok';

	test.describe( `[${ providerName }] Descriptive Text Generator Tests`, () => {
		let imageEditLink = '';
		let mediaModelLink = '';

		test.beforeAll( async ( { browser } ) => {
			const page = await browser.newPage();

			const imageProcessingFeatures = [
				'feature_descriptive_text_generator',
			];

			for ( const feature of imageProcessingFeatures ) {
				await page.goto(
					`/wp-admin/tools.php?page=classifai#/image_processing/${ feature }`
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
				const toggle = page.locator(
					'.classifai-enable-feature-toggle input[type="checkbox"]'
				);
				if ( ! ( await toggle.isChecked() ) ) {
					await toggle.check();
				}

				// Select provider.
				const editBtn = page.locator(
					'.classifai-settings-edit-provider'
				);
				if ( await editBtn.count() ) {
					await editBtn.first().click();
				}
				await page
					.locator( '.classifai-provider-select select' )
					.selectOption( provider );

				await page.locator( `#${ provider }_api_key` ).fill( 'password' );
				await page
					.locator(
						'.processing-mode-radio-control input[value="automatic"]'
					)
					.check();

				// Allow feature to admin.
				const permButton = page.locator(
					'.components-panel__body.classifai-settings__user-permissions button'
				).first();
				await permButton.waitFor( { state: 'attached' } );
				const panelBody = permButton.locator(
					'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
				);
				const cls =
					( await panelBody.getAttribute( 'class' ) ) || '';
				if ( ! cls.includes( 'is-opened' ) ) {
					await permButton.click();
				}
				await page
					.locator( '.settings-allowed-roles input#administrator' )
					.check();
				await page
					.locator(
						'.classifai-settings__user-based-opt-out input'
					)
					.uncheck();

				// Disable access for all users. Dispatch directly to the store

				// because the token field's tokens render asynchronously after a

				// `/wp/v2/users?include=…` fetch, and the click-each-remove approach

				// races with React.

				await page.evaluate( () => {

					// eslint-disable-next-line @typescript-eslint/ban-ts-comment

					// @ts-ignore

					window.wp.data

						.dispatch( 'classifai-settings' )

						.setFeatureSettings( { users: [] } );

				} );

				const responsePromise = page.waitForResponse(
					( res ) =>
						res
							.url()
							.includes( '/wp-json/classifai/v1/settings/' ) &&
						res.request().method() === 'POST'
				);
				await page
					.locator(
						'.classifai-settings-footer button.save-settings-button'
					)
					.click();
				await responsePromise;
			}

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

		test( 'Can see Image Processing actions on edit media page and verify generated data.', async ( {
			classifaiUtils,
			page,
		} ) => {
			await classifaiUtils.visitFeatureSettings(
				'image_processing/feature_descriptive_text_generator'
			);
			await page
				.locator( '.classifai-descriptive-text-fields input#alt' )
				.check();
			await classifaiUtils.saveFeatureSettings();
			await page.goto( '/wp-admin/upload.php?mode=grid' ); // Ensure grid mode is enabled.
			await page.goto( '/wp-admin/media-new.php' );
			await expect(
				page.locator( '#plupload-upload-ui' )
			).toBeVisible();
			await page
				.locator( '#plupload-upload-ui input[type=file]' )
				.setInputFiles(
					path.resolve(
						__dirname,
						'../../../../assets/img/onboarding-1.png'
					)
				);

			await page
				.locator( '#media-items .media-item a.edit-attachment' )
				.waitFor( { timeout: 20000 } );
			const editLink = await page
				.locator( '#media-items .media-item a.edit-attachment' )
				.getAttribute( 'href' );
			imageEditLink = editLink as string;
			await page.goto( editLink as string );

			// Verify Metabox with Image processing actions.
			await expect(
				page
					.locator(
						'.postbox-header h2, #classifai_image_processing h2'
					)
					.first()
			).toContainText( 'ClassifAI Image Processing' );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toContainText( 'No descriptive text? Rescan image' );

			// Verify generated Data.
			const imageData = getChatGPTData();
			await expect( page.locator( '#attachment_alt' ) ).toHaveValue(
				imageData
			);
		} );

		test( 'Can see Image Processing actions on media modal', async ( {
			page,
		} ) => {
			const imageId = imageEditLink
				.split( 'post=' )[ 1 ]
				?.split( '&' )[ 0 ];
			mediaModelLink = `wp-admin/upload.php?item=${ imageId }`;
			await page.goto( mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();

			// Verify Image processing actions.
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toContainText( 'Rescan' );
		} );

		test( 'Can disable Image Processing features', async ( {
			classifaiUtils,
			page,
		} ) => {
			const options = {
				imageEditLink,
				mediaModelLink,
			};

			// Disable features
			await classifaiUtils.visitFeatureSettings(
				'image_processing/feature_descriptive_text_generator'
			);
			await page.waitForTimeout( 1000 );
			await page
				.locator( '.classifai-descriptive-text-fields input#alt' )
				.uncheck();
			await page
				.locator(
					'.classifai-descriptive-text-fields input#caption'
				)
				.uncheck();
			await page
				.locator(
					'.classifai-descriptive-text-fields input#description'
				)
				.uncheck();
			await classifaiUtils.saveFeatureSettings();

			// Verify that the feature is not available.
			await page.waitForTimeout( 1000 );
			await page.goto( options.imageEditLink );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toHaveCount( 0 );
			await page.goto( options.mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toHaveCount( 0 );

			// Enable features.
			await classifaiUtils.visitFeatureSettings(
				'image_processing/feature_descriptive_text_generator'
			);
			await page
				.locator( '.classifai-descriptive-text-fields input#alt' )
				.check();
			await page
				.locator(
					'.classifai-descriptive-text-fields input#caption'
				)
				.check();
			await page
				.locator(
					'.classifai-descriptive-text-fields input#description'
				)
				.check();
			await page.waitForTimeout( 1500 );
			await classifaiUtils.enableFeature();
			await classifaiUtils.saveFeatureSettings();

			// Verify that the feature is available.
			await page.waitForTimeout( 1000 );
			await page.goto( options.imageEditLink );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toBeVisible();
			await page.goto( options.mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toBeVisible();
		} );

		test( 'Can enable/disable Image Processing features by roles', async ( {
			classifaiUtils,
			page,
		} ) => {
			const options = {
				imageEditLink,
				mediaModelLink,
			};

			// Enable features.
			await classifaiUtils.visitFeatureSettings(
				'image_processing/feature_descriptive_text_generator'
			);
			await classifaiUtils.enableFeature();
			await page
				.locator( '.classifai-descriptive-text-fields input#alt' )
				.check();
			await page.waitForTimeout( 500 );
			await classifaiUtils.saveFeatureSettings();

			// Disable access to admin role.
			await classifaiUtils.disableFeatureForRoles(
				'feature_descriptive_text_generator',
				[ 'administrator' ]
			);

			// Verify that the feature is not available.
			await page.waitForTimeout( 1000 );
			await page.goto( options.imageEditLink );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toHaveCount( 0 );
			await page.goto( options.mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toHaveCount( 0 );
		} );

		test( 'Can enable/disable Image Processing features by user', async ( {
			classifaiUtils,
			page,
		} ) => {
			const options = {
				imageEditLink,
				mediaModelLink,
			};

			// Disable access to admin role.
			await classifaiUtils.disableFeatureForRoles(
				'feature_descriptive_text_generator',
				[ 'administrator' ]
			);

			// Verify that the feature is not available.
			await page.waitForTimeout( 1000 );
			await page.goto( options.imageEditLink );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toHaveCount( 0 );
			await page.goto( options.mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toHaveCount( 0 );

			await classifaiUtils.enableFeatureForUsers(
				'feature_descriptive_text_generator',
				[ 'admin' ]
			);

			// Verify that the feature is available.
			await page.waitForTimeout( 1000 );
			await page.goto( options.imageEditLink );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toBeVisible();
			await page.goto( options.mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toBeVisible();
		} );

		test( 'User can opt-out of Image Processing features', async ( {
			classifaiUtils,
			page,
		} ) => {
			const options = {
				imageEditLink,
				mediaModelLink,
			};

			// Enable user based opt-out.
			await classifaiUtils.enableFeatureOptOut(
				'feature_descriptive_text_generator'
			);

			// opt-out
			await classifaiUtils.optOutFeature(
				'feature_descriptive_text_generator'
			);

			// Verify that the feature is not available.
			await page.waitForTimeout( 1000 );
			await page.goto( options.imageEditLink );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toHaveCount( 0 );
			await page.goto( options.mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toHaveCount( 0 );

			// opt-in
			await classifaiUtils.optInFeature(
				'feature_descriptive_text_generator'
			);

			// Verify that the feature is available.
			await page.waitForTimeout( 1000 );
			await page.goto( options.imageEditLink );
			await expect(
				page.locator(
					'#classifai_image_processing label[for=rescan-captions]'
				)
			).toBeVisible();
			await page.goto( options.mediaModelLink );
			await expect( page.locator( '.media-modal' ) ).toBeVisible();
			await expect(
				page.locator( '#classifai-rescan-alt-tags' )
			).toBeVisible();
		} );
	} );
}

test.describe( 'OpenAI ChatGPT Image Tag and Text Generator Tests', () => {
	let imageEditLink = '';
	let mediaModelLink = '';

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();

		const imageProcessingFeatures = [
			'feature_image_tags_generator',
			'feature_image_to_text_generator',
		];

		for ( const feature of imageProcessingFeatures ) {
			await page.goto(
				`/wp-admin/tools.php?page=classifai#/image_processing/${ feature }`
			);
			await expect(
				page.locator( '.components-panel__header h2' ).first()
			).toBeVisible();
			await page.waitForTimeout( 100 );

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

			// Select provider.
			const editBtn = page.locator( '.classifai-settings-edit-provider' );
			if ( await editBtn.count() ) {
				await editBtn.first().click();
			}
			await page
				.locator( '.classifai-provider-select select' )
				.selectOption( 'openai_chatgpt' );

			await page
				.locator( '#openai_chatgpt_api_key' )
				.fill( 'password' );
			await page
				.locator(
					'.processing-mode-radio-control input[value="automatic"]'
				)
				.check();

			// Allow feature to admin.
			const permButton = page.locator(
				'.components-panel__body.classifai-settings__user-permissions button'
			).first();
			await permButton.waitFor( { state: 'attached' } );
			const panelBody = permButton.locator(
				'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
			);
			const cls = ( await panelBody.getAttribute( 'class' ) ) || '';
			if ( ! cls.includes( 'is-opened' ) ) {
				await permButton.click();
			}
			await page
				.locator( '.settings-allowed-roles input#administrator' )
				.check();
			await page
				.locator( '.classifai-settings__user-based-opt-out input' )
				.uncheck();

			// Disable access for all users. Dispatch directly to the store

			// because the token field's tokens render asynchronously after a

			// `/wp/v2/users?include=…` fetch, and the click-each-remove approach

			// races with React.

			await page.evaluate( () => {

				// eslint-disable-next-line @typescript-eslint/ban-ts-comment

				// @ts-ignore

				window.wp.data

					.dispatch( 'classifai-settings' )

					.setFeatureSettings( { users: [] } );

			} );

			const responsePromise = page.waitForResponse(
				( res ) =>
					res
						.url()
						.includes( '/wp-json/classifai/v1/settings/' ) &&
					res.request().method() === 'POST'
			);
			await page
				.locator(
					'.classifai-settings-footer button.save-settings-button'
				)
				.click();
			await responsePromise;
		}

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

	test( 'Can see Image Processing actions on edit media page and verify generated data.', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/upload.php?mode=grid' ); // Ensure grid mode is enabled.
		await page.goto( '/wp-admin/media-new.php' );
		await expect( page.locator( '#plupload-upload-ui' ) ).toBeVisible();
		await page
			.locator( '#plupload-upload-ui input[type=file]' )
			.setInputFiles(
				path.resolve(
					__dirname,
					'../../../../assets/img/onboarding-1.png'
				)
			);

		await page
			.locator( '#media-items .media-item a.edit-attachment' )
			.waitFor( { timeout: 20000 } );
		const editLink = await page
			.locator( '#media-items .media-item a.edit-attachment' )
			.getAttribute( 'href' );
		imageEditLink = editLink as string;
		await page.goto( editLink as string );

		// Verify Metabox with Image processing actions.
		await expect(
			page
				.locator( '.postbox-header h2, #classifai_image_processing h2' )
				.first()
		).toContainText( 'ClassifAI Image Processing' );
		await expect(
			page.locator(
				'#classifai_image_processing label[for=rescan-tags]'
			)
		).toContainText( 'Rescan image for new tags' );
		await expect(
			page.locator( '#classifai_image_processing label[for=rescan-ocr]' )
		).toContainText( 'Rescan for text' );

		// Verify generated Data.
		const tags = [ 'Hello there', 'how may I assist you today?' ];
		await expect( page.locator( '#attachment_content' ) ).toHaveValue(
			'Hello there, how may I assist you today?'
		);

		const tagLocators = page.locator(
			'#classifai-image-tags ul.tagchecklist li span.screen-reader-text'
		);
		const tagCount = await tagLocators.count();
		const observedTags: string[] = [];
		for ( let i = 0; i < tagCount; i++ ) {
			const text = ( await tagLocators.nth( i ).textContent() ) || '';
			const cleaned = text.replace( 'Remove term: ', '' );
			expect( tags ).toContain( cleaned );
			observedTags.push( cleaned );
		}
		expect( observedTags.length ).toBe( tags.length );
	} );

	test( 'Can see Image Processing actions on media modal', async ( {
		page,
	} ) => {
		const imageId = imageEditLink.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
		mediaModelLink = `wp-admin/upload.php?item=${ imageId }`;
		await page.goto( mediaModelLink );
		await expect( page.locator( '.media-modal' ) ).toBeVisible();

		// Verify Image processing actions.
		await expect(
			page.locator( '#classifai-rescan-image-tags' )
		).toContainText( 'Rescan' );
		await expect(
			page.locator( '#classifai-rescan-ocr' )
		).toContainText( 'Rescan' );
	} );
} );
