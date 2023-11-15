import { getChatGPTData, getWhisperData } from '../plugins/functions';

describe( 'Language processing Tests', () => {
	it( 'Can save IBM Watson "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

		cy.get( '#classifai-settings-watson_url' )
			.clear()
			.type( 'http://e2e-test-nlu-server.test/' );
		cy.get( '#classifai-settings-watson_password' )
			.clear()
			.type( 'password' );

		cy.get( '#classifai-settings-automatic_classification' ).check();
		cy.get( '#classifai-settings-post' ).check();
		cy.get( '#classifai-settings-page' ).check();
		cy.get( '#classifai-settings-draft' ).check();
		cy.get( '#classifai-settings-pending' ).check();
		cy.get( '#classifai-settings-private' ).check();
		cy.get( '#classifai-settings-publish' ).check();

		cy.get( '#classifai-settings-category' ).check();
		cy.get( '#classifai-settings-keyword' ).check();
		cy.get( '#classifai-settings-entity' ).check();
		cy.get( '#classifai-settings-concept' ).check();
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
		cy.get(panelButtonSelector).then(($button) => {
			// Find the panel container
			const $panel = $button.parents('.components-panel__body');

			// Open Panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap($button).click();
			}
		} );
		
		// Check the toggle button is off
		cy.get( '.classifai-panel .components-form-toggle' ).should(
			'not.have.class',
			'is-checked'
		);

		cy.get( '#classify-post-component button' ).click();
		
		// see if there is a label with "Watson Categories" text exists
		cy.get( '.components-form-token-field__label' ).contains( 'Watson Categories' );

		// check if a term can be removed
		cy.get('.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item')
			.then(listing => {
				const totalTerms = Cypress.$(listing).length;

				// Remove 1 term
				cy.get( '.classify-modal > div > div:nth-child(2) > div:first-of-type .components-flex-item:first-child .components-form-token-field__remove-token' ).click();

				// Now confirm if the term is reduced
				cy.get( listing ).should( 'have.length', totalTerms - 1 );

				// enter a new term as input and press enter key in js
				cy.get( '.classify-modal > div > div:nth-child(2) > div:first-of-type .components-form-token-field__input' ).type( 'NewTestTerm' );				
				
				// press enter key in js
				cy.get( '.classify-modal > div > div:nth-child(2) > div:first-of-type .components-form-token-field__input' ).type( '{enter}' );

				// Click the save button
				cy.get( '.classify-modal .components-button' ).contains( 'Save' ).click();

				// Save the post
				cy.get( '.editor-post-publish-button__button' ).click();
			}
		);
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
		cy.get(panelButtonSelector).then(($button) => {
			// Find the panel container
			const $panel = $button.parents('.components-panel__body');

			// Open Panel
			if ( !$panel.hasClass( 'is-opened' ) ) {
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

	// Skiping this until issue get fixed.
	it.skip( 'Can create post and tags get created by ClassifAI', () => {
		const threshold = 75;
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing'
		);

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

	it( 'Can save OpenAI ChatGPT "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		cy.get( '#api_key' ).clear().type( 'password' );

		cy.get( '#enable_excerpt' ).check();
		cy.get( '#openai_chatgpt_roles_administrator' ).check();
		cy.get( '#length' ).clear().type( 35 );
		cy.get( '#submit' ).click();
	} );

	it( 'Can see the generate excerpt button in a post', () => {
		const data = getChatGPTData();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT post',
			content: 'Test GPT content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the excerpt panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Excerpt")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button exists.
			cy.wrap( $panel )
				.find( '.editor-post-excerpt button' )
				.should( 'exist' );

			// Click on button and verify data loads in.
			cy.wrap( $panel ).find( '.editor-post-excerpt button' ).click();
			cy.wrap( $panel ).find( 'textarea' ).should( 'have.value', data );
		} );
	} );

	it( 'Can see the generate excerpt button in a post (Classic Editor)', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#activate-classic-editor' ).click();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get( '#enable_excerpt' ).check();
		cy.get( '#submit' ).click();

		const data = getChatGPTData();

		cy.classicCreatePost( {
			title: 'Excerpt test classic',
			content: 'Test GPT content.',
			postType: 'post',
		} );

		// Ensure excerpt metabox is shown.
		cy.get( '#show-settings-link' ).click();
		cy.get( '#postexcerpt-hide' ).check( { force: true } );

		// Verify button exists.
		cy.get( '#classifai-openai__excerpt-generate-btn' ).should( 'exist' );

		// Click on button and verify data loads in.
		cy.get( '#classifai-openai__excerpt-generate-btn' ).click();
		cy.get( '#excerpt' ).should( 'have.value', data );

		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#deactivate-classic-editor' ).click();
	} );

	it( 'Can set multiple custom excerpt generation prompts, select one as the default and delete one.', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Add three custom prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][0][default]"]'
		)
			.parents( 'td' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom excerpt prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom excerpt prompt' );

		// Set the third prompt as our default.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_excerpt_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );

		cy.get( '#submit' ).click();

		const data = getChatGPTData( 'excerpt' );

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT post',
			content: 'Test GPT content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the excerpt panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Excerpt")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button exists.
			cy.wrap( $panel )
				.find( '.editor-post-excerpt button' )
				.should( 'exist' );

			// Click on button and verify data loads in.
			cy.wrap( $panel ).find( '.editor-post-excerpt button' ).click();
			cy.wrap( $panel ).find( 'textarea' ).should( 'have.value', data );
		} );
	} );

	it( 'Can disable excerpt generation feature', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable features.
		cy.get( '#enable_excerpt' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT post disabled',
			content: 'Test GPT content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the excerpt panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Excerpt")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button doesn't exist.
			cy.wrap( $panel )
				.find( '.editor-post-excerpt button' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can disable excerpt generation feature by role', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable admin role.
		cy.get( '#enable_excerpt' ).check();
		cy.get( '#openai_chatgpt_roles_administrator' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT post admin disabled',
			content: 'Test GPT content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the excerpt panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Excerpt")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button doesn't exist.
			cy.wrap( $panel )
				.find( '.editor-post-excerpt button' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can save OpenAI Embeddings "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);

		cy.get( '#api_key' ).clear().type( 'password' );

		cy.get( '#enable_classification' ).check();
		cy.get( '#openai_embeddings_post_types_post' ).check();
		cy.get( '#openai_embeddings_post_statuses_publish' ).check();
		cy.get( '#openai_embeddings_taxonomies_category' ).check();
		cy.get( '#number' ).clear().type( 1 );
		cy.get( '#submit' ).click();
	} );

	it( 'Can create category and post and category will get auto-assigned', () => {
		// Remove custom taxonomies so those don't interfere with the test.
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=watson_nlu'
		);
		cy.get( '#classifai-settings-category' ).uncheck();
		cy.get( '#classifai-settings-keyword' ).uncheck();
		cy.get( '#classifai-settings-entity' ).uncheck();
		cy.get( '#classifai-settings-concept' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test term.
		cy.deleteAllTerms( 'category' );
		cy.createTerm( 'Test', 'category' );

		// Create test post.
		cy.createPost( {
			title: 'Test embeddings',
			content: 'Test embeddings content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the category panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Categories")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Ensure our test category is checked.
			cy.wrap( $panel )
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first input'
				)
				.should( 'be.checked' );
			cy.wrap( $panel )
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first label'
				)
				.contains( 'Test' );
		} );
	} );

	it( 'Can create category and post and category will not get auto-assigned if feature turned off', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);
		cy.get( '#enable_classification' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test term.
		cy.deleteAllTerms( 'category' );
		cy.createTerm( 'Test', 'category' );

		// Create test post.
		cy.createPost( {
			title: 'Test embeddings disabled',
			content: 'Test embeddings content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the category panel.
		const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Categories")`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Ensure our test category is not checked.
			cy.wrap( $panel )
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first input'
				)
				.should( 'be.checked' );
			cy.wrap( $panel )
				.find(
					'.editor-post-taxonomies__hierarchical-terms-list .editor-post-taxonomies__hierarchical-terms-choice:first label'
				)
				.contains( 'Uncategorized' );
		} );
	} );

	it( 'Can see the enable button in a post (Classic Editor)', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#activate-classic-editor' ).click();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_embeddings'
		);

		cy.get( '#enable_classification' ).check();
		cy.get( '#openai_embeddings_post_types_post' ).check();
		cy.get( '#openai_embeddings_post_statuses_publish' ).check();
		cy.get( '#openai_embeddings_taxonomies_category' ).check();
		cy.get( '#number' ).clear().type( 1 );
		cy.get( '#submit' ).click();

		cy.classicCreatePost( {
			title: 'Embeddings test classic',
			content: "This feature uses OpenAI's Embeddings capabilities.",
			postType: 'post',
		} );

		cy.get( '#classifai_language_processing_metabox' ).should( 'exist' );
		cy.get( '#classifai-process-content' ).check();

		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#deactivate-classic-editor' ).click();
	} );

	it( 'Can save OpenAI ChatGPT "Language Processing" title settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		cy.get( '#api_key' ).clear().type( 'password' );
		cy.get( '#enable_titles' ).check();
		cy.get( '#openai_chatgpt_title_roles_administrator' ).check();
		cy.get( '#number_titles' ).select( 1 );
		cy.get( '#submit' ).click();
	} );

	it( 'Can see the generate titles button in a post', () => {
		const data = getChatGPTData();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT generate titles',
			content: 'Test content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button exists.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.should( 'exist' );

			// Click on button and verify modal shows.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.click();
		} );

		cy.get( '.title-modal' ).should( 'exist' );

		// Click on button and verify data loads in.
		cy.get( '.title-modal .classifai-title' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );
		cy.get( '.title-modal .classifai-title' )
			.first()
			.find( 'button' )
			.click();

		cy.get( '.title-modal' ).should( 'not.exist' );
		cy.getBlockEditor()
			.find( '.editor-post-title__input' )
			.should( ( $el ) => {
				expect( $el.first() ).to.contain( data );
			} );
	} );

	it( 'Can see the generate titles button in a post (Classic Editor)', () => {
		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#activate-classic-editor' ).click();

		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get( '#enable_titles' ).check();
		cy.get( '#submit' ).click();

		const data = getChatGPTData();

		cy.visit( '/wp-admin/post-new.php' );

		cy.get( '#classifai-openai__title-generate-btn' ).click();
		cy.get( '#classifai-openai__modal' ).should( 'be.visible' );
		cy.get( '.classifai-openai__result-item' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );

		cy.get( '.classifai-openai__select-title' ).first().click();
		cy.get( '#classifai-openai__modal' ).should( 'not.be.visible' );
		cy.get( '#title' ).should( 'have.value', data );

		cy.visit( '/wp-admin/plugins.php' );
		cy.get( '#deactivate-classic-editor' ).click();
	} );

	it( 'Can set multiple custom title generation prompts, select one as the default and delete one.', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Add three custom prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom title prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom title prompt' );

		// Set the third prompt as our default.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[generate_title_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );

		cy.get( '#submit' ).click();

		const data = getChatGPTData( 'title' );

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT generate titles',
			content: 'Test content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button exists.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.should( 'exist' );

			// Click on button and verify modal shows.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.click();
		} );

		cy.get( '.title-modal' ).should( 'exist' );

		// Click on button and verify data loads in.
		cy.get( '.title-modal .classifai-title' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );
		cy.get( '.title-modal .classifai-title' )
			.first()
			.find( 'button' )
			.click();

		cy.get( '.title-modal' ).should( 'not.exist' );
		cy.getBlockEditor()
			.find( '.editor-post-title__input' )
			.should( ( $el ) => {
				expect( $el.first() ).to.contain( data );
			} );
	} );

	it( 'Can disable title generation feature', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable features.
		cy.get( '#enable_titles' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT generate titles disabled',
			content: 'Test content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button doesn't exist.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can disable title generation feature by role', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable admin role.
		cy.get( '#enable_titles' ).uncheck();
		cy.get( '#openai_chatgpt_title_roles_administrator' ).uncheck();
		cy.get( '#submit' ).click();

		// Create test post.
		cy.createPost( {
			title: 'Test ChatGPT generate titles role disabled',
			content: 'Test content',
		} );

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( closePanelSelector ).length > 0 ) {
				cy.get( closePanelSelector ).click();
			}
		} );

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get( panelButtonSelector ).then( ( $panelButton ) => {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button doesn't exist.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.should( 'not.exist' );
		} );
	} );

	it( 'Can save OpenAI Whisper "Language Processing" settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_whisper'
		);

		cy.get( '#api_key' ).clear().type( 'password' );

		cy.get( '#enable_transcripts' ).check();
		cy.get( '#openai_whisper_roles_administrator' ).check();
		cy.get( '#submit' ).click();
	} );

	let audioEditLink = '';
	let mediaModalLink = '';

	it( 'Can see OpenAI Whisper language processing actions on edit media page and verify generated data.', () => {
		cy.visit( '/wp-admin/media-new.php' );
		cy.get( '#plupload-upload-ui' ).should( 'exist' );
		cy.get( '#plupload-upload-ui input[type=file]' ).attachFile(
			'audio.mp3'
		);

		cy.get( '#media-items .media-item a.edit-attachment' ).should(
			'exist'
		);
		cy.get( '#media-items .media-item a.edit-attachment' )
			.invoke( 'attr', 'href' )
			.then( ( editLink ) => {
				audioEditLink = editLink;
				cy.visit( editLink );
			} );

		// Verify metabox has processing actions.
		cy.get( '.postbox-header h2, #attachment_meta_box h2' )
			.first()
			.contains( 'ClassifAI Audio Processing' );
		cy.get( '.misc-publishing-actions label[for=retranscribe]' ).contains(
			'Re-transcribe'
		);

		// Verify generated data.
		cy.get( '#attachment_content' ).should(
			'have.value',
			getWhisperData()
		);
	} );

	it( 'Can see OpenAI Whisper language processing actions on media model', () => {
		const audioId = audioEditLink.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
		mediaModalLink = `wp-admin/upload.php?item=${ audioId }`;
		cy.visit( mediaModalLink );
		cy.get( '.media-modal' ).should( 'exist' );

		// Verify language processing actions.
		cy.get( '#classifai-retranscribe' ).contains( 'Re-transcribe' );
	} );

	it( 'Can disable OpenAI Whisper language processing features', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_whisper'
		);

		// Disable features
		cy.get( '#enable_transcripts' ).uncheck();
		cy.get( '#submit' ).click();

		// Verify features are not present in attachment metabox.
		cy.visit( audioEditLink );
		cy.get( '.misc-publishing-actions label[for=retranscribe]' ).should(
			'not.exist'
		);

		// Verify features are not present in media modal.
		cy.visit( mediaModalLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-retranscribe' ).should( 'not.exist' );
	} );

	it( 'Can disable OpenAI Whisper language processing features by role', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_whisper'
		);

		// Disable admin role
		cy.get( '#enable_transcripts' ).check();
		cy.get( '#openai_whisper_roles_administrator' ).uncheck();
		cy.get( '#submit' ).click();

		// Verify features are not present in attachment metabox.
		cy.visit( audioEditLink );
		cy.get( '.misc-publishing-actions label[for=retranscribe]' ).should(
			'not.exist'
		);

		// Verify features are not present in media modal.
		cy.visit( mediaModalLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-retranscribe' ).should( 'not.exist' );
	} );

	it( 'Resize content feature can grow and shrink content', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		cy.get( '#enable_resize_content' ).check();
		cy.get( '#openai_chatgpt_resize_content_roles_administrator' ).check();
		cy.get( '#submit' ).click();

		cy.createPost( {
			title: 'Resize content',
			content: 'Hello, world.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Expand this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+7 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+40 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic building block of one narrative.'
			);

		cy.createPost( {
			title: 'Resize content',
			content:
				'Start with the basic building block of one narrative to begin with the editorial process.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Condense this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-6 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-36 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic building block of one narrative.'
			);
	} );

	it( 'Can set multiple custom resize generation prompts, select one as the default and delete one.', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Add three custom shrink prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Add three custom grow prompts.
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( 'button.js-classifai-add-prompt-fieldset:first' )
			.click()
			.click()
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 4 );

		// Set the data for each prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom shrink prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom shrink prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][1][title]"]'
		)
			.clear()
			.type( 'First custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][1][prompt]"]'
		)
			.clear()
			.type( 'This is our first custom grow prompt' );

		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][2][title]"]'
		)
			.clear()
			.type( 'Second custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][2][prompt]"]'
		)
			.clear()
			.type( 'This prompt should be deleted' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][3][title]"]'
		)
			.clear()
			.type( 'Third custom prompt' );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][3][prompt]"]'
		)
			.clear()
			.type( 'This is a custom grow prompt' );

		// Set the third prompt as our default.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][3][default]"]'
		)
			.parent()
			.find( 'a.action__set_default' )
			.click( { force: true } );

		// Delete the second prompt.
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[shrink_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][2][default]"]'
		)
			.parent()
			.find( 'a.action__remove_prompt' )
			.click( { force: true } );
		cy.get( 'div[aria-describedby="js-classifai--delete-prompt-modal"]' )
			.find( '.button-primary' )
			.click();
		cy.get(
			'[name="classifai_openai_chatgpt[grow_content_prompt][0][default]"]'
		)
			.parents( 'td:first' )
			.find( '.classifai-field-type-prompt-setting' )
			.should( 'have.length', 3 );

		cy.get( '#submit' ).click();

		cy.createPost( {
			title: 'Resize content',
			content: 'Hello, world.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Expand this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+6 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__grow-stat'
		).should( 'contain.text', '+31 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic block of one narrative.'
			);

		cy.createPost( {
			title: 'Resize content',
			content:
				'Start with the basic building block of one narrative to begin with the editorial process.',
		} );

		cy.get( '.classifai-resize-content-btn' ).click();
		cy.get( '.components-button' ).contains( 'Condense this text' ).click();
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-7 words' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first .classifai-content-resize__shrink-stat'
		).should( 'contain.text', '-45 characters' );
		cy.get(
			'.classifai-content-resize__result-table tbody tr:first button'
		).click();
		cy.getBlockEditor()
			.find( '[data-type="core/paragraph"]' )
			.should(
				'contain.text',
				'Start with the basic block of one narrative.'
			);
	} );

	it( 'Disabling Resize content feature by role does not render buttons in the UI', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);
		cy.get(
			'#openai_chatgpt_resize_content_roles_administrator'
		).uncheck();
		cy.get( '#submit' ).click();

		cy.createPost( {
			title: 'Expand content',
			content: 'Are the resizing options hidden?',
		} );

		cy.get( '.classifai-resize-content-btn' ).should( 'not.exist' );
	} );
} );
