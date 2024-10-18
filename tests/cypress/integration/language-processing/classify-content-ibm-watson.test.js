describe( '[Language processing] Classify content (IBM Watson - NLU) Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.enableFeature();
		cy.selectProvider( 'ibm_watson_nlu' );
		cy.saveFeatureSettings();

		cy.selectProvider( 'ibm_watson_nlu' );
		cy.get( '#ibm_watson_nlu_endpoint_url' )
			.clear()
			.type( 'http://e2e-test-nlu-server.test/' );
		cy.get( '#ibm_watson_nlu_password' ).clear().type( 'password' );
		cy.get( '.classifai-ibm-watson-toggle-api-key' ).click();
		cy.get( '.settings-allowed-post-types input#post' ).check();
		cy.get(
			'.settings-allowed-post-statuses input#post_status_publish'
		).check();
		cy.get(
			'.classification-method-radio-control input[value="recommended_terms"]'
		).check();
		cy.get( '#category-enabled' ).check();
		cy.get( '#category-threshold' ).clear().type( 70 );
		cy.get( '#keyword-threshold' ).clear().type( 70 );
		cy.get( '#entity-threshold' ).clear().type( 70 );
		cy.get( '#concept-threshold' ).clear().type( 70 );
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save IBM Watson "Language Processing" settings', () => {
		// Disable content classification by openai.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.disableFeature();
		cy.saveFeatureSettings();

		cy.enableFeature();
		cy.get( '.settings-allowed-post-types input#post' ).check();
		cy.get( '.settings-allowed-post-types input#page' ).check();

		cy.get(
			'.settings-allowed-post-statuses input#post_status_draft'
		).check();
		cy.get(
			'.settings-allowed-post-statuses input#post_status_pending'
		).check();
		cy.get(
			'.settings-allowed-post-statuses input#post_status_private'
		).check();
		cy.get(
			'.settings-allowed-post-statuses input#post_status_publish'
		).check();

		cy.get( '#category-enabled' ).check();
		cy.get( '#keyword-enabled' ).check();
		cy.get( '#entity-enabled' ).check();
		cy.get( '#concept-enabled' ).check();
		cy.saveFeatureSettings();
	} );

	it( 'Can select Watson taxonomies "Language Processing" settings', () => {
		cy.intercept( '/wp-json/wp/v2/taxonomies*' ).as( 'getTaxonomies' );
		cy.visitFeatureSettings( 'language_processing' );
		cy.wait( '@getTaxonomies' );

		cy.enableFeature();
		cy.get( '#category-taxonomy' ).select( 'watson-category' );
		cy.get( '#keyword-taxonomy' ).select( 'watson-keyword' );
		cy.get( '#entity-taxonomy' ).select( 'watson-entity' );
		cy.get( '#concept-taxonomy' ).select( 'watson-concept' );
		cy.saveFeatureSettings();
	} );

	it( 'Can see Watson taxonomies under "Posts" Menu.', () => {
		cy.visit( '/wp-admin/edit.php' );

		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Categories")' )
			.should( 'have.length', 1 );
		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Keywords")' )
			.should( 'have.length', 1 );
		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Entities")' )
			.should( 'have.length', 1 );
		cy.get( '#menu-posts ul.wp-submenu li' )
			.filter( ':contains("Watson Concepts")' )
			.should( 'have.length', 1 );
	} );

	it( 'Check Classification Mode toggle button is off, display popup, then add/remove terms', () => {
		cy.visitFeatureSettings( 'language_processing' );

		cy.selectProvider( 'ibm_watson_nlu' );
		cy.get(
			'.classification-mode-radio-control input[value="manual_review"]'
		).check();
		cy.saveFeatureSettings();

		// Create Test Post
		cy.createPost( {
			title: 'Test Classification Mode post',
			content: 'Test Classification Mode post',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Open Panel
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("ClassifAI")`;
		cy.get( panelButtonSelector ).then( ( $button ) => {
			// Find the panel container
			const $panel = $button.parents( '.components-panel__body' );

			// Open Panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $button ).click();
			}
		} );

		// Check the toggle button is off
		cy.get( '.classifai-panel .components-form-toggle' )
			.first()
			.should( 'not.have.class', 'is-checked' );

		cy.get( '#classify-post-component button' ).click();

		// see if there is a label with "Watson Categories" text exists
		cy.get( '.components-form-token-field__label' ).contains(
			'Watson Categories'
		);

		// check if a term can be removed
		cy.get(
			'.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item'
		).then( ( listing ) => {
			const totalTerms = Cypress.$( listing ).length;

			// Remove 1 term
			cy.get(
				'.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item:first-child .components-form-token-field__remove-token'
			).click();

			// Now confirm if the term is reduced
			cy.get( listing ).should( 'have.length', totalTerms - 1 );

			// enter a new term as input and press enter key in js
			cy.get(
				'.classify-modal > div > div:nth-child(2) > div:first-of-type .components-form-token-field__input'
			).type( 'NewTestTerm' );

			// press enter key in js
			cy.get(
				'.classify-modal > div > div:nth-child(2) > div:first-of-type .components-form-token-field__input'
			).type( '{enter}' );

			// Click the save button
			cy.get( '.classify-modal .components-button' )
				.contains( 'Save' )
				.click();

			// Save the post
			cy.get( '.editor-post-publish-button__button' ).click();
		} );
	} );

	it( 'Check Classification Mode toggle button is on', () => {
		cy.deactivatePlugin( 'classic-editor' );

		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.selectProvider( 'ibm_watson_nlu' );
		cy.get(
			'.classification-mode-radio-control input[value="automatic_classification"]'
		).check();
		cy.saveFeatureSettings();

		// Create Test Post
		cy.createPost( {
			title: 'Test Classification Mode Post',
			content: 'Test Classification Mode Post',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Open Panel
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("ClassifAI")`;
		cy.get( panelButtonSelector ).then( ( $button ) => {
			// Find the panel container
			const $panel = $button.parents( '.components-panel__body' );

			// Open Panel
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $button ).click();
			}
		} );

		// Check the toggle button is on
		cy.get( '.classifai-panel .components-form-toggle' ).should(
			'have.class',
			'is-checked'
		);
	} );

	it( 'Can create post and taxonomy terms get created by ClassifAI (with default threshold)', () => {
		const threshold = 0.7;

		// Create Test Post
		cy.createPost( {
			title: 'Test NLU post',
			content: 'Test NLU Content',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		[ 'categories', 'keywords', 'concepts', 'entities' ].forEach(
			( taxonomy ) => {
				cy.verifyPostTaxonomyTerms( taxonomy, threshold );
			}
		);
	} );

	it( 'Can create post and taxonomy terms get created by ClassifAI (with 75 threshold)', () => {
		const threshold = 75;

		// Update Threshold to 75.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.get( '#category-threshold' ).clear().type( threshold );
		cy.get( '#keyword-threshold' ).clear().type( threshold );
		cy.get( '#entity-threshold' ).clear().type( threshold );
		cy.get( '#concept-threshold' ).clear().type( threshold );
		cy.saveFeatureSettings();

		// Create Test Post
		cy.createPost( {
			title: 'Test NLU post with 75 Threshold',
			content: 'Test NLU Content with 75 Threshold',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		[ 'categories', 'keywords', 'concepts', 'entities' ].forEach(
			( taxonomy ) => {
				cy.verifyPostTaxonomyTerms( taxonomy, threshold / 100 );
			}
		);
	} );

	// Test Classification Method.
	it( 'Check classification method', () => {
		// Remove all terms.
		cy.request( {
			url: '/wp-json/classifai/v1/clean/taxonomy-terms',
		} );

		const threshold1 = 75;
		// Update classification method to "Add recommended terms" and threshold value.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.selectProvider( 'ibm_watson_nlu' );
		cy.get(
			'.classification-method-radio-control input[value="recommended_terms"]'
		).check();
		cy.get( '#category-threshold' ).clear().type( threshold1 );
		cy.get( '#keyword-threshold' ).clear().type( threshold1 );
		cy.get( '#entity-threshold' ).clear().type( threshold1 );
		cy.get( '#concept-threshold' ).clear().type( threshold1 );
		cy.saveFeatureSettings();

		// Create Test Post
		cy.createPost( {
			title: 'Test classification method',
			content: 'Test classification method "Add recommended terms"',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies with threshold 75.
		[ 'categories', 'keywords', 'concepts', 'entities' ].forEach(
			( taxonomy ) => {
				cy.verifyPostTaxonomyTerms( taxonomy, threshold1 / 100 );
			}
		);

		// Now create terms with threshold 70 and verify it with threshold 75 to make only existing terms are used in classification and not new terms.
		const threshold2 = 70;
		// Update classification method to "Only classify based on existing terms" and threshold value.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.selectProvider( 'ibm_watson_nlu' );
		cy.get(
			'.classification-method-radio-control input[value="existing_terms"]'
		).check();
		cy.get( '#category-threshold' ).clear().type( threshold2 );
		cy.get( '#keyword-threshold' ).clear().type( threshold2 );
		cy.get( '#entity-threshold' ).clear().type( threshold2 );
		cy.get( '#concept-threshold' ).clear().type( threshold2 );
		cy.saveFeatureSettings();

		// Create Test Post
		cy.createPost( {
			title: 'Test classification method',
			content:
				'Test classification method "Only classify based on existing terms"',
		} );

		// Close post publish panel
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( 'button[aria-label="Close panel"]' ).length > 0 ) {
				cy.get( 'button[aria-label="Close panel"]' ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies with threshold 75 as we have already created terms with threshold 75. So, those are existing terms.
		[ 'categories', 'keywords', 'concepts', 'entities' ].forEach(
			( taxonomy ) => {
				cy.verifyPostTaxonomyTerms( taxonomy, threshold1 / 100 );
			}
		);

		// Update classification method back to "Add recommended terms".
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		cy.get(
			'.classification-method-radio-control input[value="recommended_terms"]'
		).check();
		cy.saveFeatureSettings();
	} );

	it( 'Can create post and tags get created by ClassifAI', () => {
		const threshold = 70;
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.selectProvider( 'ibm_watson_nlu' );
		cy.get(
			'.classification-method-radio-control input[value="recommended_terms"]'
		).check();
		cy.get( '#category-taxonomy' ).select( 'post_tag' );
		cy.get( '#keyword-taxonomy' ).select( 'post_tag' );
		cy.get( '#entity-taxonomy' ).select( 'post_tag' );
		cy.get( '#concept-taxonomy' ).select( 'post_tag' );
		cy.get( '#category-threshold' ).clear().type( threshold );
		cy.get( '#keyword-threshold' ).clear().type( threshold );
		cy.get( '#entity-threshold' ).clear().type( threshold );
		cy.get( '#concept-threshold' ).clear().type( threshold );
		cy.saveFeatureSettings();

		// Create Test Post
		cy.createPost( {
			title: 'Test NLU post for tags',
			content: 'Test NLU Content for tags',
		} );

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		cy.verifyPostTaxonomyTerms( 'tags', threshold / 100 );
	} );

	it( 'Can enable/disable Natural Language Understanding features.', () => {
		// Disable feature.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.disableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable feature.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );
		cy.enableFeature();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can limit Natural Language Understanding features by roles', () => {
		// Disable access to admin role.
		cy.visitFeatureSettings( 'language_processing/feature_classification' );

		// Disable access for all users.
		cy.disableFeatureForUsers();
		cy.saveFeatureSettings();

		// Disable admin role.
		cy.disableFeatureForRoles( 'feature_classification', [
			'administrator',
		] );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable access to admin role.
		cy.enableFeatureForRoles( 'feature_classification', [
			'administrator',
		] );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can limit Natural Language Understanding features by users', () => {
		// Disable access.
		cy.visitFeatureSettings( 'language_processing&provider=watson_nlu' );

		// Disable access for all roles.
		cy.openUserPermissionsPanel();
		cy.get( '.settings-allowed-roles input[type="checkbox"]' ).uncheck( {
			multiple: true,
		} );

		// Disable access for all users.
		cy.disableFeatureForUsers();

		cy.saveFeatureSettings();

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable access to user.
		cy.visitFeatureSettings( 'language_processing&provider=watson_nlu' );

		// Disable access for all roles.
		cy.get( '.settings-allowed-roles input[type="checkbox"]' ).uncheck( {
			multiple: true,
		} );

		cy.get( 'body' ).then( ( $body ) => {
			if (
				$body.find(
					'.classifai-settings__users .components-form-token-field__remove-token'
				).length > 0
			) {
				cy.get(
					'.classifai-settings__users .components-form-token-field__remove-token'
				).click( {
					multiple: true,
				} );
			}
		} );
		cy.get(
			'.classifai-settings__users input.components-form-token-field__input'
		).type( 'admin' );
		cy.wait( 1000 );
		cy.get(
			'ul.components-form-token-field__suggestions-list li:nth-child(1)'
		).click();
		cy.saveFeatureSettings();

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );

		// Enable access to admin role. (default)
		cy.visitFeatureSettings( 'language_processing&provider=watson_nlu' );

		// Enable access for all roles.
		cy.openUserPermissionsPanel();
		cy.get( '.settings-allowed-roles input[type="checkbox"]' ).check( {
			multiple: true,
		} );

		// Disable access for all users.
		cy.disableFeatureForUsers();

		cy.saveFeatureSettings();
	} );

	it( 'Can enable user based opt out for Natural Language Understanding', () => {
		// Opt Out from feature.
		cy.visitFeatureSettings( 'language_processing&provider=watson_nlu' );
		// Enable access for all roles.
		cy.openUserPermissionsPanel();
		cy.get( '.settings-allowed-roles input[type="checkbox"]' ).check( {
			multiple: true,
		} );

		// Disable access for all users.
		cy.disableFeatureForUsers();
		cy.get(
			'.classifai-settings__user-based-opt-out input[type="checkbox"]'
		).check();

		cy.saveFeatureSettings();

		// opt-out
		cy.optOutFeature( 'feature_classification' );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// opt-in
		cy.optInFeature( 'feature_classification' );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );
} );
