import { test, expect } from '../../fixtures/test';

test.describe( '[Language processing] Classify content (IBM Watson - NLU) Tests', () => {
	test.beforeAll( async ( { browser, requestUtils } ) => {
		const page = await browser.newPage();

		// Configure feature settings.
		await page.goto(
			'/wp-admin/tools.php?page=classifai#/language_processing/feature_classification'
		);
		await expect(
			page.locator( '.components-panel__header h2' ).first()
		).toBeVisible();
		// Enable feature.
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
			.selectOption( 'ibm_watson_nlu' );
		const savePromise1 = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await savePromise1;

		// Reselect provider (after save) and fill settings.
		const editBtn2 = page.locator( '.classifai-settings-edit-provider' );
		if ( await editBtn2.count() ) {
			await editBtn2.first().click();
		}
		await page
			.locator( '.classifai-provider-select select' )
			.selectOption( 'ibm_watson_nlu' );
		await page
			.locator( '#ibm_watson_nlu_endpoint_url' )
			.fill( 'http://e2e-test-nlu-server.test/' );
		await page.locator( '#ibm_watson_nlu_password' ).fill( 'password' );
		await page.locator( '.classifai-ibm-watson-toggle-api-key' ).click();
		await page.locator( '.settings-allowed-post-types input#post' ).check();
		await page
			.locator(
				'.settings-allowed-post-statuses input#post_status_publish'
			)
			.check();
		await page
			.locator(
				'.classification-method-radio-control input[value="recommended_terms"]'
			)
			.check();
		await page.locator( '#category-enabled' ).check();
		await page.locator( '#category-threshold' ).fill( '70' );
		await page.locator( '#keyword-threshold' ).fill( '70' );
		await page.locator( '#entity-threshold' ).fill( '70' );
		await page.locator( '#concept-threshold' ).fill( '70' );
		const savePromise2 = page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await savePromise2;

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

		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		await page.close();
	} );

	test( 'Can save IBM Watson "Language Processing" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		// Disable content classification by openai.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		await classifaiUtils.enableFeature();
		await page.locator( '.settings-allowed-post-types input#post' ).check();
		await page.locator( '.settings-allowed-post-types input#page' ).check();

		await page
			.locator( '.settings-allowed-post-statuses input#post_status_draft' )
			.check();
		await page
			.locator(
				'.settings-allowed-post-statuses input#post_status_pending'
			)
			.check();
		await page
			.locator(
				'.settings-allowed-post-statuses input#post_status_private'
			)
			.check();
		await page
			.locator(
				'.settings-allowed-post-statuses input#post_status_publish'
			)
			.check();

		await page.locator( '#category-enabled' ).check();
		await page.locator( '#keyword-enabled' ).check();
		await page.locator( '#entity-enabled' ).check();
		await page.locator( '#concept-enabled' ).check();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can select Watson taxonomies "Language Processing" settings', async ( {
		classifaiUtils,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await classifaiUtils.enableFeature();
		await page
			.locator( '#category-taxonomy' )
			.selectOption( 'watson-category' );
		await page
			.locator( '#keyword-taxonomy' )
			.selectOption( 'watson-keyword' );
		await page.locator( '#entity-taxonomy' ).selectOption( 'watson-entity' );
		await page
			.locator( '#concept-taxonomy' )
			.selectOption( 'watson-concept' );
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can see Watson taxonomies under "Posts" Menu.', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/edit.php' );

		await expect(
			page.locator( '#menu-posts ul.wp-submenu li', {
				hasText: 'Watson Categories',
			} )
		).toHaveCount( 1 );
		await expect(
			page.locator( '#menu-posts ul.wp-submenu li', {
				hasText: 'Watson Keywords',
			} )
		).toHaveCount( 1 );
		await expect(
			page.locator( '#menu-posts ul.wp-submenu li', {
				hasText: 'Watson Entities',
			} )
		).toHaveCount( 1 );
		await expect(
			page.locator( '#menu-posts ul.wp-submenu li', {
				hasText: 'Watson Concepts',
			} )
		).toHaveCount( 1 );
	} );

	test( 'Check Classification Mode toggle button is off, display popup, then add/remove terms', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await classifaiUtils.selectProvider( 'ibm_watson_nlu' );
		await page
			.locator(
				'.classification-mode-radio-control input[value="manual_review"]'
			)
			.check();
		await classifaiUtils.saveFeatureSettings();

		// Create Test Post
		await classifaiUtils.createPost( {
			title: 'Test Classification Mode post',
			content: 'Test Classification Mode post',
		} );

		// Close post publish panel
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar
		await editor.openDocumentSettingsSidebar();

		// Open Panel
		const panelButton = page
			.locator(
				'.components-panel__body .components-panel__body-title button:has-text("ClassifAI")'
			)
			.first();
		await panelButton.waitFor();
		const panel = panelButton.locator(
			'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
		);
		const cls = ( await panel.getAttribute( 'class' ) ) || '';
		if ( ! cls.includes( 'is-opened' ) ) {
			await panelButton.click();
		}

		// Check the toggle button is off
		await expect(
			page.locator( '.classifai-panel .components-form-toggle' ).first()
		).not.toHaveClass( /is-checked/ );

		await page.locator( '#classify-post-component button' ).click();

		// see if there is a label with "Watson Categories" text exists
		await expect(
			page
				.locator( '.components-form-token-field__label' )
				.filter( { hasText: 'Watson Categories' } )
				.first()
		).toBeVisible();

		// check if a term can be removed
		const listing = page.locator(
			'.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item'
		);
		const totalTerms = await listing.count();

		// Remove 1 term
		await page
			.locator(
				'.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item:first-child .components-form-token-field__remove-token'
			)
			.click();

		// Now confirm if the term is reduced
		await expect( listing ).toHaveCount( totalTerms - 1 );

		// enter a new term as input and press enter key
		const input = page.locator(
			'.classify-modal > div > div:nth-child(2) > div:first-of-type .components-form-token-field__input'
		);
		await input.fill( 'NewTestTerm' );
		await input.press( 'Enter' );

		// Click the save button
		await page
			.locator( '.classify-modal .components-button', {
				hasText: 'Save',
			} )
			.click();

		// Save the post
		await page.locator( '.editor-post-publish-button__button' ).click();
	} );

	test( 'Check Classification Mode toggle button is on', async ( {
		classifaiUtils,
		editor,
		page,
		requestUtils,
	} ) => {
		try {
			await requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// noop
		}

		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await classifaiUtils.selectProvider( 'ibm_watson_nlu' );
		await page
			.locator(
				'.classification-mode-radio-control input[value="automatic_classification"]'
			)
			.check();
		await classifaiUtils.saveFeatureSettings();

		// Create Test Post
		await classifaiUtils.createPost( {
			title: 'Test Classification Mode Post',
			content: 'Test Classification Mode Post',
		} );

		// Close post publish panel
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar
		await editor.openDocumentSettingsSidebar();

		// Open Panel
		const panelButton = page
			.locator(
				'.components-panel__body .components-panel__body-title button:has-text("ClassifAI")'
			)
			.first();
		await panelButton.waitFor();
		const panel = panelButton.locator(
			'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
		);
		const cls = ( await panel.getAttribute( 'class' ) ) || '';
		if ( ! cls.includes( 'is-opened' ) ) {
			await panelButton.click();
		}

		// Check the toggle button is on
		await expect(
			page.locator( '.classifai-panel .components-form-toggle' ).first()
		).toHaveClass( /is-checked/ );
	} );

	test( 'Can create post and taxonomy terms get created by ClassifAI (with default threshold)', async ( {
		classifaiUtils,
		editor,
	} ) => {
		const threshold = 0.7;

		// Create Test Post
		await classifaiUtils.createPost( {
			title: 'Test NLU post',
			content: 'Test NLU Content',
		} );

		// Close post publish panel
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar
		await editor.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		const taxonomies: Array< 'categories' | 'keywords' | 'concepts' | 'entities' > = [
			'categories',
			'keywords',
			'concepts',
			'entities',
		];
		for ( const taxonomy of taxonomies ) {
			await classifaiUtils.verifyPostTaxonomyTerms( taxonomy, threshold );
		}
	} );

	test( 'Can create post and taxonomy terms get created by ClassifAI (with 75 threshold)', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		const threshold = 75;

		// Update Threshold to 75.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await page
			.locator( '#category-threshold' )
			.fill( String( threshold ) );
		await page.locator( '#keyword-threshold' ).fill( String( threshold ) );
		await page.locator( '#entity-threshold' ).fill( String( threshold ) );
		await page.locator( '#concept-threshold' ).fill( String( threshold ) );
		await classifaiUtils.saveFeatureSettings();

		// Create Test Post
		await classifaiUtils.createPost( {
			title: 'Test NLU post with 75 Threshold',
			content: 'Test NLU Content with 75 Threshold',
		} );

		// Close post publish panel
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar
		await editor.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		const taxonomies: Array< 'categories' | 'keywords' | 'concepts' | 'entities' > = [
			'categories',
			'keywords',
			'concepts',
			'entities',
		];
		for ( const taxonomy of taxonomies ) {
			await classifaiUtils.verifyPostTaxonomyTerms(
				taxonomy,
				threshold / 100
			);
		}
	} );

	// Test Classification Method.
	test( 'Check classification method', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		// Remove all terms.
		await page.request.get(
			'/wp-json/classifai/v1/clean/taxonomy-terms'
		);

		const threshold1 = 75;
		// Update classification method to "Add recommended terms" and threshold value.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await classifaiUtils.selectProvider( 'ibm_watson_nlu' );
		await page
			.locator(
				'.classification-method-radio-control input[value="recommended_terms"]'
			)
			.check();
		await page.locator( '#category-threshold' ).fill( String( threshold1 ) );
		await page.locator( '#keyword-threshold' ).fill( String( threshold1 ) );
		await page.locator( '#entity-threshold' ).fill( String( threshold1 ) );
		await page.locator( '#concept-threshold' ).fill( String( threshold1 ) );
		await classifaiUtils.saveFeatureSettings();

		// Create Test Post
		await classifaiUtils.createPost( {
			title: 'Test classification method',
			content: 'Test classification method "Add recommended terms"',
		} );

		// Close post publish panel
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar
		await editor.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies with threshold 75.
		const taxonomies: Array< 'categories' | 'keywords' | 'concepts' | 'entities' > = [
			'categories',
			'keywords',
			'concepts',
			'entities',
		];
		for ( const taxonomy of taxonomies ) {
			await classifaiUtils.verifyPostTaxonomyTerms(
				taxonomy,
				threshold1 / 100
			);
		}

		// Now create terms with threshold 70 and verify it with threshold 75 to
		// make only existing terms are used in classification and not new terms.
		const threshold2 = 70;
		// Update classification method to "Only classify based on existing terms" and threshold value.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await classifaiUtils.selectProvider( 'ibm_watson_nlu' );
		await page
			.locator(
				'.classification-method-radio-control input[value="existing_terms"]'
			)
			.check();
		await page.locator( '#category-threshold' ).fill( String( threshold2 ) );
		await page.locator( '#keyword-threshold' ).fill( String( threshold2 ) );
		await page.locator( '#entity-threshold' ).fill( String( threshold2 ) );
		await page.locator( '#concept-threshold' ).fill( String( threshold2 ) );
		await classifaiUtils.saveFeatureSettings();

		// Create Test Post
		await classifaiUtils.createPost( {
			title: 'Test classification method',
			content:
				'Test classification method "Only classify based on existing terms"',
		} );

		// Close post publish panel
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar
		await editor.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies with threshold 75 as we have already created terms with threshold 75. So, those are existing terms.
		for ( const taxonomy of taxonomies ) {
			await classifaiUtils.verifyPostTaxonomyTerms(
				taxonomy,
				threshold1 / 100
			);
		}

		// Update classification method back to "Add recommended terms".
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		await page
			.locator(
				'.classification-method-radio-control input[value="recommended_terms"]'
			)
			.check();
		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can create post and tags get created by ClassifAI', async ( {
		classifaiUtils,
		editor,
		page,
	} ) => {
		const threshold = 70;
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);
		await classifaiUtils.selectProvider( 'ibm_watson_nlu' );
		await page
			.locator(
				'.classification-method-radio-control input[value="recommended_terms"]'
			)
			.check();
		await page.locator( '#category-taxonomy' ).selectOption( 'post_tag' );
		await page.locator( '#keyword-taxonomy' ).selectOption( 'post_tag' );
		await page.locator( '#entity-taxonomy' ).selectOption( 'post_tag' );
		await page.locator( '#concept-taxonomy' ).selectOption( 'post_tag' );
		await page.locator( '#category-threshold' ).fill( String( threshold ) );
		await page.locator( '#keyword-threshold' ).fill( String( threshold ) );
		await page.locator( '#entity-threshold' ).fill( String( threshold ) );
		await page.locator( '#concept-threshold' ).fill( String( threshold ) );
		await classifaiUtils.saveFeatureSettings();

		// Create Test Post
		await classifaiUtils.createPost( {
			title: 'Test NLU post for tags',
			content: 'Test NLU Content for tags',
		} );

		// Close post publish panel
		await classifaiUtils.closePublishPanel();

		// Open post settings sidebar
		await editor.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		await classifaiUtils.verifyPostTaxonomyTerms(
			'tags',
			threshold / 100
		);
	} );

	test( 'Can enable/disable Natural Language Understanding features.', async ( {
		classifaiUtils,
	} ) => {
		// Disable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);
		await classifaiUtils.disableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.verifyClassifyContentEnabled( false );

		// Enable feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);
		await classifaiUtils.enableFeature();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await classifaiUtils.verifyClassifyContentEnabled( true );
	} );

	test( 'Can limit Natural Language Understanding features by roles', async ( {
		classifaiUtils,
	} ) => {
		// Disable access to admin role.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		// Disable access for all users.
		await classifaiUtils.disableFeatureForUsers();
		await classifaiUtils.saveFeatureSettings();

		// Disable admin role.
		await classifaiUtils.disableFeatureForRoles( 'feature_classification', [
			'administrator',
		] );

		// Verify that the feature is not available.
		await classifaiUtils.verifyClassifyContentEnabled( false );

		// Enable access to admin role.
		await classifaiUtils.enableFeatureForRoles( 'feature_classification', [
			'administrator',
		] );

		// Verify that the feature is available.
		await classifaiUtils.verifyClassifyContentEnabled( true );
	} );

	test( 'Can limit Natural Language Understanding features by users', async ( {
		classifaiUtils,
		page,
	} ) => {
		// Disable access.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		// Disable access for all roles.
		await classifaiUtils.openUserPermissionsPanel();
		{
			const roleBoxes = page.locator(
				'.settings-allowed-roles input[type="checkbox"]'
			);
			const total = await roleBoxes.count();
			for ( let i = 0; i < total; i++ ) {
				const cb = roleBoxes.nth( i );
				if ( await cb.isChecked() ) {
					await cb.uncheck();
				}
			}
		}

		// Disable access for all users.
		await classifaiUtils.disableFeatureForUsers();

		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is not available.
		await classifaiUtils.verifyClassifyContentEnabled( false );

		// Enable access to user.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		// Disable access for all roles.
		{
			const roleBoxes = page.locator(
				'.settings-allowed-roles input[type="checkbox"]'
			);
			const total = await roleBoxes.count();
			for ( let i = 0; i < total; i++ ) {
				const cb = roleBoxes.nth( i );
				if ( await cb.isChecked() ) {
					await cb.uncheck();
				}
			}
		}

		const removeBtns = page.locator(
			'.classifai-settings__users .components-form-token-field__remove-token'
		);
		const removeCount = await removeBtns.count();
		for ( let i = removeCount - 1; i >= 0; i-- ) {
			await removeBtns.nth( i ).click();
		}
		await page
			.locator(
				'.classifai-settings__users input.components-form-token-field__input'
			)
			.fill( 'admin' );
		await page.waitForTimeout( 1000 );
		await page
			.locator(
				'ul.components-form-token-field__suggestions-list li:nth-child(1)'
			)
			.click();
		await classifaiUtils.saveFeatureSettings();

		// Verify that the feature is available.
		await classifaiUtils.verifyClassifyContentEnabled( true );

		// Enable access to admin role. (default)
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);

		// Enable access for all roles.
		await classifaiUtils.openUserPermissionsPanel();
		{
			const roleBoxes = page.locator(
				'.settings-allowed-roles input[type="checkbox"]'
			);
			const total = await roleBoxes.count();
			for ( let i = 0; i < total; i++ ) {
				const cb = roleBoxes.nth( i );
				if ( ! ( await cb.isChecked() ) ) {
					await cb.check();
				}
			}
		}

		// Disable access for all users.
		await classifaiUtils.disableFeatureForUsers();

		await classifaiUtils.saveFeatureSettings();
	} );

	test( 'Can enable user based opt out for Natural Language Understanding', async ( {
		classifaiUtils,
		page,
	} ) => {
		// Opt Out from feature.
		await classifaiUtils.visitFeatureSettings(
			'language_processing/feature_classification'
		);
		// Enable access for all roles.
		await classifaiUtils.openUserPermissionsPanel();
		{
			const roleBoxes = page.locator(
				'.settings-allowed-roles input[type="checkbox"]'
			);
			const total = await roleBoxes.count();
			for ( let i = 0; i < total; i++ ) {
				const cb = roleBoxes.nth( i );
				if ( ! ( await cb.isChecked() ) ) {
					await cb.check();
				}
			}
		}

		// Disable access for all users.
		await classifaiUtils.disableFeatureForUsers();
		await page
			.locator(
				'.classifai-settings__user-based-opt-out input[type="checkbox"]'
			)
			.check();

		await classifaiUtils.saveFeatureSettings();

		// opt-out
		await classifaiUtils.optOutFeature( 'feature_classification' );

		// Verify that the feature is not available.
		await classifaiUtils.verifyClassifyContentEnabled( false );

		// opt-in
		await classifaiUtils.optInFeature( 'feature_classification' );

		// Verify that the feature is available.
		await classifaiUtils.verifyClassifyContentEnabled( true );
	} );
} );
