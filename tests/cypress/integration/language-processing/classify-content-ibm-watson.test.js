describe( '[Language processing] Classify content (IBM Watson - NLU) Tests', () => {
	before( () => {
		cy.login();
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_classification'
		);
		cy.get( '#classifai_feature_classification_post_types_post' ).check();
		cy.get( '#classifai-settings-publish' ).check();
		cy.get( '#category' ).check();
		cy.get( '#watson_nlu_classification_method_recommended_terms' ).check();
		cy.get( '#classifai-settings-enable_content_classification' ).check();
		cy.get( '#submit' ).click();
		cy.optInAllFeatures();
		cy.disableClassicEditor();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Can save IBM Watson "Language Processing" settings', () => {
		// Disable content classification by openai.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);
		cy.get( '#enable_classification' ).uncheck();
		cy.get( '#submit' ).click();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-watson_url' )
			.clear()
			.type( 'http://e2e-test-nlu-server.test/' );
		cy.get( '#classifai-settings-watson_password' )
			.clear()
			.type( 'password' );

		cy.get( '#status' ).check();
		cy.get( '#classifai_feature_classification_post_types_post' ).check();
		cy.get( '#classifai_feature_classification_post_types_page' ).check();
		cy.get( '#classifai_feature_classification_post_statuses_draft' ).check();
		cy.get( '#classifai_feature_classification_post_statuses_pending' ).check();
		cy.get( '#classifai_feature_classification_post_statuses_private' ).check();
		cy.get( '#classifai_feature_classification_post_statuses_publish' ).check();

		cy.get( '#category' ).check();
		cy.get( '#keyword' ).check();
		cy.get( '#entity' ).check();
		cy.get( '#concept' ).check();
		cy.get( '#submit' ).click();
	} );

	it( 'Can select Watson taxonomies "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-category_taxonomy' ).select(
			'watson-category'
		);
		cy.get( '#classifai-settings-keyword_taxonomy' ).select(
			'watson-keyword'
		);
		cy.get( '#classifai-settings-entity_taxonomy' ).select(
			'watson-entity'
		);
		cy.get( '#classifai-settings-concept_taxonomy' ).select(
			'watson-concept'
		);
		cy.get( '#submit' ).click();
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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-manual_review' ).check();
		cy.get( '#submit' ).click();

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
		cy.get( '.classifai-panel .components-form-toggle' ).should(
			'not.have.class',
			'is-checked'
		);

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

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-automatic_classification' ).check();
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-category_threshold' )
			.clear()
			.type( threshold );
		cy.get( '#classifai-settings-keyword_threshold' )
			.clear()
			.type( threshold );
		cy.get( '#classifai-settings-entity_threshold' )
			.clear()
			.type( threshold );
		cy.get( '#classifai-settings-concept_threshold' )
			.clear()
			.type( threshold );
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#watson_nlu_classification_method_recommended_terms' ).check();
		cy.get( '#classifai-settings-category_threshold' )
			.clear()
			.type( threshold1 );
		cy.get( '#classifai-settings-keyword_threshold' )
			.clear()
			.type( threshold1 );
		cy.get( '#classifai-settings-entity_threshold' )
			.clear()
			.type( threshold1 );
		cy.get( '#classifai-settings-concept_threshold' )
			.clear()
			.type( threshold1 );
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#watson_nlu_classification_method_existing_terms' ).check();
		cy.get( '#classifai-settings-category_threshold' )
			.clear()
			.type( threshold2 );
		cy.get( '#classifai-settings-keyword_threshold' )
			.clear()
			.type( threshold2 );
		cy.get( '#classifai-settings-entity_threshold' )
			.clear()
			.type( threshold2 );
		cy.get( '#classifai-settings-concept_threshold' )
			.clear()
			.type( threshold2 );
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#watson_nlu_classification_method_recommended_terms' ).check();
		cy.get( '#submit' ).click();
	} );

	it( 'Can create post and tags get created by ClassifAI', () => {
		const threshold = 70;
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);
		cy.get( '#watson_nlu_classification_method_recommended_terms' ).check();
		cy.get( '#classifai-settings-category_taxonomy' ).select( 'post_tag' );
		cy.get( '#classifai-settings-keyword_taxonomy' ).select( 'post_tag' );
		cy.get( '#classifai-settings-entity_taxonomy' ).select( 'post_tag' );
		cy.get( '#classifai-settings-concept_taxonomy' ).select( 'post_tag' );
		cy.get( '#submit' ).click();

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
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get( '#classifai-settings-enable_content_classification' ).uncheck();
		cy.get( '#submit' ).click();

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get( '#classifai-settings-enable_content_classification' ).check();
		cy.get( '#submit' ).click();

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can limit Natural Language Understanding features by roles', () => {
		// Disable access to admin role.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get(
			'#classifai-settings-content_classification_role_based_access'
		).check();
		cy.get(
			'#watson_nlu_content_classification_roles_administrator'
		).uncheck();

		cy.get( '#submit' ).click();
		cy.get( '.notice' ).contains( 'Settings saved.' );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable access to admin role.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get(
			'#classifai-settings-content_classification_role_based_access'
		).check();
		cy.get(
			'#watson_nlu_content_classification_roles_administrator'
		).check();

		cy.get( '#submit' ).click();
		cy.get( '.notice' ).contains( 'Settings saved.' );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );
	} );

	it( 'Can limit Natural Language Understanding features by users', () => {
		// Disable access.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get(
			'#classifai-settings-content_classification_role_based_access'
		).uncheck();
		cy.get(
			'#classifai-settings-content_classification_user_based_access'
		).uncheck();
		cy.get( '#submit' ).click();
		cy.get( '.notice' ).contains( 'Settings saved.' );

		// Verify that the feature is not available.
		cy.verifyClassifyContentEnabled( false );

		// Enable access to user.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get(
			'#classifai-settings-content_classification_role_based_access'
		).uncheck();
		cy.get(
			'#classifai-settings-content_classification_user_based_access'
		).check();
		cy.get( 'body' ).then( ( $body ) => {
			if (
				$body.find(
					'#content_classification_users-container .components-form-token-field__remove-token'
				).length > 0
			) {
				cy.get(
					'#content_classification_users-container .components-form-token-field__remove-token'
				).click( {
					multiple: true,
				} );
			}
		} );
		cy.get(
			'#content_classification_users-container input.components-form-token-field__input'
		).type( 'admin' );
		cy.wait( 1000 );
		cy.get(
			'ul.components-form-token-field__suggestions-list li:nth-child(1)'
		).click();
		cy.get( '#submit' ).click();
		cy.get( '.notice' ).contains( 'Settings saved.' );

		// Verify that the feature is available.
		cy.verifyClassifyContentEnabled( true );

		// Enable access to admin role. (default)
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get(
			'#classifai-settings-content_classification_role_based_access'
		).check();
		cy.get(
			'#classifai-settings-content_classification_user_based_access'
		).uncheck();

		cy.get( '#submit' ).click();
		cy.get( '.notice' ).contains( 'Settings saved.' );
	} );

	it( 'Can enable user based opt out for Natural Language Understanding', () => {
		// Opt Out from feature.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get(
			'#classifai-settings-content_classification_role_based_access'
		).check();
		cy.get(
			'#classifai-settings-content_classification_user_based_access'
		).check();
		cy.get(
			'#classifai-settings-content_classification_user_based_opt_out'
		).check();

		cy.get( '#submit' ).click();
		cy.get( '.notice' ).contains( 'Settings saved.' );

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
