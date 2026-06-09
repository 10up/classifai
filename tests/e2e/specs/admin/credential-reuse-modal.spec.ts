import { test, expect } from '../../fixtures/test';

test.describe( 'Credential Reuse Modal Tests', () => {
	test.describe( 'Modal Display Behavior', () => {
		test( 'Should display credential reuse modal when enabling a feature without localStorage flag', async ( {
			classifaiUtils,
			page,
		} ) => {
			await classifaiUtils.visitFeatureSettings(
				'language_processing/feature_title_generation'
			);

			await classifaiUtils.enableCredentialReuseModal();

			await page.route(
				/\/wp-json\/classifai\/v1\/credential-reuse\/feature_title_generation/,
				async ( route ) => {
					await route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify( {
							openai_chatgpt: {
								feature_id: 'feature_excerpt_generation',
								feature_label: 'Excerpt Generation',
								provider_display_name: 'OpenAI ChatGPT',
								credentials: { authenticated: true },
							},
						} ),
					} );
				}
			);

			await classifaiUtils.disableFeatureIfEnabled();

			const responsePromise = page.waitForResponse(
				( res ) =>
					res
						.url()
						.includes(
							'/wp-json/classifai/v1/credential-reuse/feature_title_generation'
						)
			);

			await classifaiUtils.enableFeature( false );
			await responsePromise;

			await expect(
				page.locator( '.components-modal__header' )
			).toBeVisible();
			await expect(
				page.locator( '.components-modal__header-heading' )
			).toContainText( 'Reuse Existing Credentials' );
		} );

		test( 'Should not display modal when localStorage flag is set', async ( {
			classifaiUtils,
			page,
		} ) => {
			await classifaiUtils.visitFeatureSettings(
				'language_processing/feature_title_generation'
			);
			await classifaiUtils.disableCredentialReuseModal();

			await page.route(
				/\/wp-json\/classifai\/v1\/credential-reuse\/feature_title_generation/,
				async ( route ) => {
					await route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify( {
							openai_chatgpt: {
								feature_id: 'feature_excerpt_generation',
								feature_label: 'Excerpt Generation',
								provider_display_name: 'OpenAI ChatGPT',
								credentials: { authenticated: true },
							},
						} ),
					} );
				}
			);

			await classifaiUtils.disableFeatureIfEnabled();
			await classifaiUtils.enableFeature( false );

			await expect(
				page.locator( '.components-modal__header' )
			).toHaveCount( 0 );
		} );

		test( 'Should not display modal when no compatible credentials are available', async ( {
			classifaiUtils,
			page,
		} ) => {
			await classifaiUtils.visitFeatureSettings(
				'language_processing/feature_title_generation'
			);
			await classifaiUtils.enableCredentialReuseModal();

			await page.route(
				/\/wp-json\/classifai\/v1\/credential-reuse\/feature_title_generation/,
				async ( route ) => {
					await route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify( {} ),
					} );
				}
			);

			await classifaiUtils.disableFeatureIfEnabled();

			const responsePromise = page.waitForResponse(
				( res ) =>
					res
						.url()
						.includes(
							'/wp-json/classifai/v1/credential-reuse/feature_title_generation'
						)
			);

			await classifaiUtils.enableFeature( false );
			await responsePromise;

			await expect(
				page.locator( '.components-modal__header' )
			).toHaveCount( 0 );
		} );
	} );

	test.describe( 'Modal Interaction', () => {
		test.beforeEach( async ( { page } ) => {
			await page.route(
				/\/wp-json\/classifai\/v1\/credential-reuse\/feature_title_generation/,
				async ( route ) => {
					await route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify( {
							openai_chatgpt: {
								feature_id: 'feature_excerpt_generation',
								feature_label: 'Excerpt Generation',
								provider_display_name: 'OpenAI ChatGPT',
								credentials: { authenticated: true },
							},
							ollama_embeddings: {
								feature_id: 'feature_excerpt_generation',
								feature_label: 'Excerpt Generation',
								provider_display_name: 'Ollama Embeddings',
								credentials: { authenticated: true },
							},
						} ),
					} );
				}
			);
		} );

		test( 'Should close modal when X button is clicked', async ( {
			classifaiUtils,
			page,
		} ) => {
			await classifaiUtils.visitFeatureSettings(
				'language_processing/feature_title_generation'
			);
			await classifaiUtils.enableCredentialReuseModal();

			await classifaiUtils.disableFeatureIfEnabled();

			const responsePromise = page.waitForResponse( ( res ) =>
				res
					.url()
					.includes(
						'/wp-json/classifai/v1/credential-reuse/feature_title_generation'
					)
			);
			await classifaiUtils.enableFeature( false );
			await responsePromise;

			await page.locator( 'button[aria-label="Close"]' ).click();

			await expect(
				page.locator( '.components-modal__header' )
			).toHaveCount( 0 );
		} );

		test( 'Should save localStorage flag when "Don\'t ask again" is checked', async ( {
			classifaiUtils,
			page,
		} ) => {
			await classifaiUtils.visitFeatureSettings(
				'language_processing/feature_title_generation'
			);
			await classifaiUtils.enableCredentialReuseModal();

			await classifaiUtils.disableFeatureIfEnabled();

			const responsePromise = page.waitForResponse( ( res ) =>
				res
					.url()
					.includes(
						'/wp-json/classifai/v1/credential-reuse/feature_title_generation'
					)
			);
			await classifaiUtils.enableFeature( false );
			await responsePromise;

			await page
				.locator( '.components-modal__content input[type="checkbox"]' )
				.last()
				.check();

			await page
				.locator( 'button', { hasText: 'Reuse' } )
				.first()
				.click();

			await page.waitForTimeout( 3000 );

			const flag = await page.evaluate( () =>
				window.localStorage.getItem(
					'classifai_dont_ask_credential_reuse'
				)
			);
			expect( flag ).toBe( 'true' );
		} );

		test( 'Should maintain feature enabled state after modal interaction', async ( {
			classifaiUtils,
			page,
		} ) => {
			await classifaiUtils.visitFeatureSettings(
				'language_processing/feature_title_generation'
			);
			await classifaiUtils.enableCredentialReuseModal();

			await page.route(
				/\/wp-json\/classifai\/v1\/credential-reuse\/copy/,
				async ( route ) => {
					await route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify( { success: true } ),
					} );
				}
			);

			await classifaiUtils.disableFeatureIfEnabled();

			const responsePromise = page.waitForResponse( ( res ) =>
				res
					.url()
					.includes(
						'/wp-json/classifai/v1/credential-reuse/feature_title_generation'
					)
			);
			await classifaiUtils.enableFeature( false );
			await responsePromise;

			const modalContent = page.locator( '.components-modal__content' );
			await expect(
				modalContent.locator( 'label', { hasText: 'Excerpt Generation' } ).first()
			).toBeVisible();
			await expect(
				modalContent.locator( 'label', { hasText: 'Ollama Embeddings' } )
			).toBeVisible();
			await expect(
				modalContent.locator( '.classifai-provider-selection label' )
			).toHaveCount( 2 );

			await page
				.locator( 'button', { hasText: 'Reuse' } )
				.first()
				.click();
			await expect(
				page.locator( '.components-modal__header' )
			).toHaveCount( 0 );
		} );
	} );
} );
