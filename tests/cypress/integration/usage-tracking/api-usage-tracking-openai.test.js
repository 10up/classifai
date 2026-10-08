describe( '[Usage Tracking] API Usage Tracking (OpenAI) Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
		// Clear any usage data cached from previous test runs so the widget starts
		// in its default "Updating…" state for the widget-visibility test below.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-usage-data',
			body: {},
		} );
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save AI Usage Tracking settings', () => {
		cy.visitFeatureSettings( 'usage_tracking/api_usage_tracking' );

		cy.enableFeature();
		cy.selectProvider( 'openai_usage_tracking' );
		cy.get( '#openai_usage_tracking_api_key' )
			.clear()
			.type( 'sk-admin-password' );
		cy.get( '#openai_usage_tracking_project_id' )
			.clear()
			.type( 'proj_cypress_openai_1' );

		cy.allowFeatureToAdmin();
		cy.saveFeatureSettings();
	} );

	it( 'Can see "AI Usage Tracking" Widget on WP Dashboard', () => {
		cy.visit( `/wp-admin/index.php` );

		const aiUsageWidget = cy.get(
			'#dashboard-widgets #classifai_api_usage'
		);

		aiUsageWidget.should( 'exist' );
		aiUsageWidget.get( 'h2' ).should( 'contain', 'AI Usage Tracking' );
		aiUsageWidget
			.get( '.classifai-api-usage-list li:first-child' )
			.should( 'contain', 'This month:' );
		aiUsageWidget
			.get( '.classifai-api-usage-list li:first-child' )
			.should( 'contain', 'Updating…' );
		aiUsageWidget
			.get( '.classifai-api-usage-list li:nth-child(2)' )
			.should( 'contain', 'Year to date:' );
		aiUsageWidget
			.get( '.classifai-api-usage-list li:nth-child(2)' )
			.should( 'contain', 'Updating…' );
		aiUsageWidget
			.get( '.classifai-api-usage-list li:nth-child(3)' )
			.should( 'contain', 'All time:' );
		aiUsageWidget
			.get( '.classifai-api-usage-list li:nth-child(3)' )
			.should( 'contain', 'Updating…' );
		aiUsageWidget
			.get( '.classifai-api-usage-updated' )
			.should( 'not.exist' );
	} );

	it( 'Can run cron job and verify usage data is updated from mock data', () => {
		// Trigger a synchronous usage refresh via the test helper endpoint.
		cy.request( 'POST', '/wp-json/classifai/v1/run-usage-refresh' ).then(
			( response ) => {
				expect( response.status ).to.eq( 200 );
				expect( response.body.success ).to.be.true;
				expect( response.body.data.mtd ).to.be.greaterThan( 0 );
				expect( response.body.data.last_updated ).to.be.greaterThan(
					0
				);
			}
		);

		// The dashboard widget should now show real values instead of "Updating…".
		cy.visit( '/wp-admin/index.php' );

		cy.get(
			'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:first-child'
		).should( 'not.contain', 'Updating…' );
		cy.get(
			'#dashboard-widgets #classifai_api_usage .classifai-api-usage-updated'
		)
			.should( 'exist' )
			.should( 'contain', 'Last updated:' );
	} );

	it( 'Verify widget data shows correct data, from mock data', () => {
		const now = Math.floor( Date.now() / 1000 );

		// Inject known usage values via the test helper endpoint.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-usage-data',
			body: {
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

		cy.visit( '/wp-admin/index.php' );

		// Use independent cy.get() calls to avoid Cypress chaining off a saved variable.
		cy.get(
			'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:first-child'
		).should( 'contain', '$5.50 USD' );
		cy.get(
			'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:nth-child(2)'
		).should( 'contain', '$38.50 USD' );
		cy.get(
			'#dashboard-widgets #classifai_api_usage .classifai-api-usage-list li:nth-child(3)'
		).should( 'contain', '$71.50 USD' );
		cy.get(
			'#dashboard-widgets #classifai_api_usage .classifai-api-usage-updated'
		)
			.should( 'exist' )
			.should( 'contain', 'Last updated:' );
	} );

	it( 'Verify force refresh button works', () => {
		// apiFetch appends ?_locale=user to all requests, so use a glob wildcard.
		cy.intercept(
			'POST',
			'/wp-json/classifai/v1/api-usage-tracking/force-refresh*',
			{
				statusCode: 200,
				body: { success: true },
			}
		).as( 'forceRefresh' );

		cy.visit( '/wp-admin/index.php' );

		cy.get( '#api_usage_tracking_force_refresh_data' )
			.should( 'exist' )
			.should( 'not.be.disabled' )
			.click();

		cy.wait( '@forceRefresh' )
			.its( 'response.statusCode' )
			.should( 'eq', 200 );
	} );

	it( 'Enable soft/hard limit and confirm notice', () => {
		const now = Math.floor( Date.now() / 1000 );

		// --- Soft threshold notice ---
		// Enable the soft threshold via the test helper endpoint.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-provider-settings',
			body: {
				feature_option: 'classifai_api_usage_tracking',
				provider: 'openai_usage_tracking',
				settings: {
					soft_threshold_enabled: true,
					soft_threshold_amount: 1,
					soft_threshold_scope: 'current_month',
				},
			},
		} );

		// Set usage data so the MTD amount exceeds the soft threshold.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-usage-data',
			body: {
				mtd: 5.0,
				ytd: 5.0,
				all_time: 5.0,
				currency: 'USD',
				last_updated: now,
			},
		} );

		// The admin notice should be a warning indicating the soft threshold was exceeded.
		cy.visit( '/wp-admin/index.php' );
		cy.get( '.notice-warning' ).should( 'contain', 'soft threshold' );

		// --- Hard threshold notice ---
		// Enable the hard threshold settings.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-provider-settings',
			body: {
				feature_option: 'classifai_api_usage_tracking',
				provider: 'openai_usage_tracking',
				settings: {
					hard_threshold_enabled: true,
					hard_threshold_amount: 2,
					hard_threshold_scope: 'current_month',
				},
			},
		} );

		// Mark the hard limit as reached.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-hard-limit',
			body: { reached: true },
		} );

		// Reload to pick up the updated options.
		cy.reload();

		// The admin notice should now be an error indicating the hard threshold was exceeded.
		cy.get( '.notice-error' ).should( 'contain', 'hard threshold' );

		// Clean up: clear the hard limit and threshold settings.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-hard-limit',
			body: { reached: false },
		} );
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-provider-settings',
			body: {
				feature_option: 'classifai_api_usage_tracking',
				provider: 'openai_usage_tracking',
				settings: {
					soft_threshold_enabled: false,
					hard_threshold_enabled: false,
				},
			},
		} );
	} );

	it( "Verify tts feature can't be used when hard limit reached", () => {
		// Mark the hard limit as reached.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-hard-limit',
			body: { reached: true },
		} );

		// The test helper endpoint runs the TTS feature's run() method using the OpenAI
		// provider so that the classifai_pre_fetch_feature_response filter applies.
		cy.request( {
			method: 'GET',
			url: '/wp-json/classifai/v1/test-tts',
			failOnStatusCode: false,
		} ).then( ( response ) => {
			expect( response.body.success ).to.be.false;
			expect( response.body.code ).to.eq(
				'classifai_hard_limit_reached'
			);
		} );

		// Clean up: clear the hard limit.
		cy.request( {
			method: 'POST',
			url: '/wp-json/classifai/v1/set-hard-limit',
			body: { reached: false },
		} );
	} );
} );
