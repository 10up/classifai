/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/test';

test.describe( '[Usage Tracking] API Usage Tracking (OpenAI) Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		// Disable the Classic Editor plugin so the block editor is used.
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch {
			// already inactive
		}

		// Opt in to all features and clear any cached usage data from previous
		// test runs so the widget starts in its default "Updating…" state.
		const page = await browser.newPage();

		await page.goto( '/wp-admin/profile.php' );
		const optOuts = page.locator(
			'input[name="classifai_opted_out_features[]"]'
		);
		if ( ( await optOuts.count() ) > 0 ) {
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
		}

		await page.request.post( '/wp-json/classifai/v1/set-usage-data', {
			data: {},
		} );

		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		// Reset the hard limit, thresholds and usage data so a failed assertion
		// can't leave state behind that affects other specs.
		const page = await browser.newPage();

		await page.request.post( '/wp-json/classifai/v1/set-hard-limit', {
			data: { reached: false },
		} );
		await page.request.post(
			'/wp-json/classifai/v1/set-provider-settings',
			{
				data: {
					feature_option: 'classifai_api_usage_tracking',
					provider: 'openai_usage_tracking',
					settings: {
						soft_threshold_enabled: false,
						hard_threshold_enabled: false,
					},
				},
			}
		);
		await page.request.post( '/wp-json/classifai/v1/set-usage-data', {
			data: {},
		} );

		await page.close();
	} );

	test( 'Can save AI Usage Tracking settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'usage_tracking/api_usage_tracking'
		);

		await classifaiUtils.enableFeature();
		await classifaiUtils.selectProvider( 'openai_usage_tracking' );
		await page
			.locator( '#openai_usage_tracking_api_key' )
			.fill( 'sk-admin-password' );
		await page
			.locator( '#openai_usage_tracking_project_id' )
			.fill( 'proj_cypress_openai_1' );

		await classifaiUtils.allowFeatureToAdmin();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see "AI Usage Tracking" Widget on WP Dashboard', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/index.php' );

		const aiUsageWidget = page.locator(
			'#dashboard-widgets #classifai_api_usage'
		);

		await expect( aiUsageWidget ).toBeAttached();
		await expect( aiUsageWidget.locator( 'h2' ) ).toContainText(
			'AI Usage Tracking'
		);
		await expect(
			aiUsageWidget.locator( '.classifai-api-usage-list li:first-child' )
		).toContainText( 'This month:' );
		await expect(
			aiUsageWidget.locator( '.classifai-api-usage-list li:first-child' )
		).toContainText( 'Updating…' );
		await expect(
			aiUsageWidget.locator( '.classifai-api-usage-list li:nth-child(2)' )
		).toContainText( 'Year to date:' );
		await expect(
			aiUsageWidget.locator( '.classifai-api-usage-list li:nth-child(2)' )
		).toContainText( 'Updating…' );
		await expect(
			aiUsageWidget.locator( '.classifai-api-usage-list li:nth-child(3)' )
		).toContainText( 'All time:' );
		await expect(
			aiUsageWidget.locator( '.classifai-api-usage-list li:nth-child(3)' )
		).toContainText( 'Updating…' );
		await expect(
			aiUsageWidget.locator( '.classifai-api-usage-updated' )
		).toHaveCount( 0 );
	} );

	test( 'Can run cron job and verify usage data is updated from mock data', async ( {
		page,
	} ) => {
		// Trigger a synchronous usage refresh via the test helper endpoint.
		const refreshResponse = await page.request.post(
			'/wp-json/classifai/v1/run-usage-refresh'
		);
		expect( refreshResponse.status() ).toBe( 200 );
		const refreshBody = await refreshResponse.json();
		expect( refreshBody.success ).toBe( true );
		expect( refreshBody.data.mtd ).toBeGreaterThan( 0 );
		expect( refreshBody.data.last_updated ).toBeGreaterThan( 0 );

		// The dashboard widget should now show real values instead of "Updating…".
		await page.goto( '/wp-admin/index.php' );

		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:first-child'
			)
		).not.toContainText( 'Updating…' );
		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-updated'
			)
		).toBeVisible();
		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-updated'
			)
		).toContainText( 'Last updated:' );
	} );

	test( 'Can verify widget data shows correct data, from mock data', async ( {
		page,
	} ) => {
		const now = Math.floor( Date.now() / 1000 );

		// Inject known usage values via the test helper endpoint.
		await page.request.post( '/wp-json/classifai/v1/set-usage-data', {
			data: {
				mtd: 5.5,
				ytd: 38.5,
				all_time: 71.5,
				currency: 'USD',
				last_updated: now,
				years: {},
				years_total: 33.0,
				months: {},
				months_total: 33.0,
				start_year: 2020,
			},
		} );

		await page.goto( '/wp-admin/index.php' );

		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:first-child'
			)
		).toContainText( '$5.50 USD' );
		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:nth-child(2)'
			)
		).toContainText( '$38.50 USD' );
		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:nth-child(3)'
			)
		).toContainText( '$71.50 USD' );
		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-updated'
			)
		).toBeVisible();
		await expect(
			page.locator(
				'#dashboard-widgets #classifai_api_usage .classifai-api-usage-updated'
			)
		).toContainText( 'Last updated:' );
	} );

	test( 'Can verify force refresh button works', async ( { page } ) => {
		// Intercept the force-refresh endpoint and return a mocked 200 response.
		// apiFetch appends ?_locale=user to all requests, so use a wildcard pattern.
		await page.route(
			'**/wp-json/classifai/v1/api-usage-tracking/force-refresh**',
			async ( route ) => {
				await route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( { success: true } ),
				} );
			}
		);

		await page.goto( '/wp-admin/index.php' );

		const responsePromise = page.waitForResponse( ( res ) =>
			res
				.url()
				.includes(
					'/wp-json/classifai/v1/api-usage-tracking/force-refresh'
				)
		);

		const forceRefreshBtn = page.locator(
			'#api_usage_tracking_force_refresh_data'
		);
		await expect( forceRefreshBtn ).toBeAttached();
		await expect( forceRefreshBtn ).not.toBeDisabled();
		await forceRefreshBtn.click();

		const response = await responsePromise;
		expect( response.status() ).toBe( 200 );
	} );

	test( 'Can enable soft/hard limit and confirm notice', async ( {
		page,
	} ) => {
		const now = Math.floor( Date.now() / 1000 );

		// --- Soft threshold notice ---
		// Enable the soft threshold via the test helper endpoint.
		await page.request.post(
			'/wp-json/classifai/v1/set-provider-settings',
			{
				data: {
					feature_option: 'classifai_api_usage_tracking',
					provider: 'openai_usage_tracking',
					settings: {
						soft_threshold_enabled: true,
						soft_threshold_amount: 1,
						soft_threshold_scope: 'current_month',
					},
				},
			}
		);

		// Set usage data so the MTD amount exceeds the soft threshold.
		await page.request.post( '/wp-json/classifai/v1/set-usage-data', {
			data: {
				mtd: 5.0,
				ytd: 5.0,
				all_time: 5.0,
				currency: 'USD',
				last_updated: now,
			},
		} );

		// The admin notice should be a warning indicating the soft threshold was exceeded.
		await page.goto( '/wp-admin/index.php' );
		await expect(
			page.locator(
				'.notice-warning[data-notice="api_threshold_reached"]'
			)
		).toContainText( 'soft threshold' );

		// --- Hard threshold notice ---
		// Enable the hard threshold settings.
		await page.request.post(
			'/wp-json/classifai/v1/set-provider-settings',
			{
				data: {
					feature_option: 'classifai_api_usage_tracking',
					provider: 'openai_usage_tracking',
					settings: {
						hard_threshold_enabled: true,
						hard_threshold_amount: 2,
						hard_threshold_scope: 'current_month',
					},
				},
			}
		);

		// Mark the hard limit as reached.
		await page.request.post( '/wp-json/classifai/v1/set-hard-limit', {
			data: { reached: true },
		} );

		// Reload to pick up the updated options.
		await page.reload();

		// The admin notice should now be an error indicating the hard threshold was exceeded.
		await expect(
			page.locator( '.notice-error[data-notice="api_threshold_reached"]' )
		).toContainText( 'hard threshold' );
	} );

	test( "Can verify tts feature can't be used when hard limit reached", async ( {
		page,
	} ) => {
		// Mark the hard limit as reached.
		await page.request.post( '/wp-json/classifai/v1/set-hard-limit', {
			data: { reached: true },
		} );

		// The test helper endpoint runs the TTS feature's run() method using the OpenAI
		// provider so that the classifai_pre_fetch_feature_response filter applies.
		const ttsResponse = await page.request.get(
			'/wp-json/classifai/v1/test-tts'
		);
		const ttsBody = await ttsResponse.json();
		expect( ttsBody.success ).toBe( false );
		expect( ttsBody.code ).toBe( 'classifai_hard_limit_reached' );
	} );
} );
