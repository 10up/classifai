describe( 'Admin can login and make sure plugin is activated', () => {
	before( () => {
		cy.login();
		cy.disableElasticPress();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can deactivate and activate plugin', () => {
		cy.deactivatePlugin( 'classifai' );
		cy.activatePlugin( 'classifai' );
	} );

	it( 'Can see "ClassifAI" menu and Can visit "ClassifAI" settings page.', () => {
		cy.visit( '/wp-admin/' );

		// Check ClassifAI menu.
		cy.get( '#adminmenu li#menu-tools ul.wp-submenu li' ).contains(
			'ClassifAI'
		);

		// Check Heading
		cy.visit( '/wp-admin/tools.php?page=classifai' );
		cy.get( '.classifai-settings-wrapper' ).should( 'exist' );
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a' ).first().contains( 'Language Processing' );
	} );

	it( 'Can visit "Language Processing" settings page.', () => {
		// Check Selected Navigation menu
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a.active-tab' )
			.first()
			.contains( 'Language Processing' );
	} );

	it( 'Can see "Image Processing" menu and Can visit "Image Processing" settings page.', () => {
		// Check Selected Navigation menu
		cy.visitFeatureSettings(
			'image_processing/feature_descriptive_text_generator'
		);
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a.active-tab' )
			.first()
			.contains( 'Image Processing' );
	} );

	it( 'Can see "Usage Tracking" menu and Can visit "AI Usage Tracking" settings page.', () => {
		// Check Selected Navigation menu
		cy.visitFeatureSettings( 'usage_tracking/api_usage_tracking' );
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a.active-tab' )
			.first()
			.contains( 'Usage Tracking' );
	} );

	it( 'Can visit the general settings page and see all settings.', () => {
		// Check Selected Navigation menu
		cy.visitFeatureSettings( 'settings' );
		cy.get( '.classifai-tabs' ).should( 'exist' );
		cy.get( '.classifai-tabs a.active-tab' ).first().contains( 'Settings' );

		// Check that all settings are present.
		cy.get( '.components-input-control input[type="email"]' ).should(
			'exist'
		);
		cy.get( '.components-input-control input[type="password"]' ).should(
			'exist'
		);
		cy.get( '.classifai-enable-bot-block input' ).should( 'exist' );
		cy.get( '.classifai-enable-bot-block input' ).should(
			'not.be.checked'
		);
	} );

	it( 'Can turn on "Block AI Bots" setting and it works.', () => {
		cy.visitFeatureSettings( 'settings' );

		cy.get( '.classifai-enable-bot-block input' ).check();

		cy.saveGeneralSettings();

		// Check that the robots.txt file has bots blocked.
		cy.request( '/robots.txt' ).then( ( response ) => {
			expect( response.body ).to.contain(
				'User-agent: Applebot-Extended'
			);
			expect( response.body ).to.contain( 'User-agent: CCBot' );
			expect( response.body ).to.contain( 'User-agent: ClaudeBot' );
			expect( response.body ).to.contain( 'User-agent: FacebookBot' );
			expect( response.body ).to.contain( 'User-agent: Google-Extended' );
			expect( response.body ).to.contain( 'User-agent: GPTbot' );
			expect( response.body ).to.contain(
				'User-agent: Meta-ExternalAgent'
			);
		} );

		cy.get( '.classifai-enable-bot-block input' ).uncheck();

		cy.saveGeneralSettings();

		// Check that the robots.txt file has bots unblocked.
		cy.request( '/robots.txt' ).then( ( response ) => {
			expect( response.body ).to.not.contain(
				'User-agent: Applebot-Extended'
			);
			expect( response.body ).to.not.contain( 'User-agent: CCBot' );
			expect( response.body ).to.not.contain( 'User-agent: ClaudeBot' );
			expect( response.body ).to.not.contain( 'User-agent: FacebookBot' );
			expect( response.body ).to.not.contain(
				'User-agent: Google-Extended'
			);
			expect( response.body ).to.not.contain( 'User-agent: GPTbot' );
			expect( response.body ).to.not.contain(
				'User-agent: Meta-ExternalAgent'
			);
		} );
	} );
} );
