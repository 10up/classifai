/* eslint jest/expect-expect: 0 */

import { getChatGPTData } from '../plugins/functions';

describe('Language processing Tests', () => {
	before(() => {
		cy.login();
	} );

	it( 'Can save IBM Watson "Language Processing" settings', () => {
		cy.visit( '/wp-admin/tools.php?page=classifai&tab=language_processing' );

		cy.get( '#classifai-settings-watson_url' ).clear().type( 'http://e2e-test-nlu-server.test/' );
		cy.get( '#classifai-settings-watson_password' ).clear().type( 'password' );

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
		cy.visit( '/wp-admin/tools.php?page=classifai&tab=language_processing' );

		cy.get( '#classifai-settings-category_taxonomy' ).select( 'watson-category' );
		cy.get( '#classifai-settings-keyword_taxonomy' ).select( 'watson-keyword' );
		cy.get( '#classifai-settings-entity_taxonomy' ).select( 'watson-entity' );
		cy.get( '#classifai-settings-concept_taxonomy' ).select( 'watson-concept' );
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

	it( 'Can create post and taxonomy terms get created by ClassifAI (with default threshold)', () => {
		const threshold = 0.70;
		// Create Test Post
		cy.createPost({
			title: 'Test NLU post',
			content: 'Test NLU Content',
		});

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get('body').then(($body) => {
			if ($body.find(closePanelSelector).length > 0) {
				cy.get(closePanelSelector).click();
			}
		});

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		['categories', 'keywords', 'concepts', 'entities'].forEach(
			(taxonomy) => {
				cy.verifyPostTaxonomyTerms(taxonomy, threshold);
			}
		);
	});

	it('Can create post and taxonomy terms get created by ClassifAI (with 75 threshold)', () => {
		const threshold = 75;

		// Update Threshold to 75.
		cy.visit('/wp-admin/tools.php?page=classifai&tab=language_processing');

		cy.get('#classifai-settings-category_threshold')
			.clear()
			.type(threshold);
		cy.get('#classifai-settings-keyword_threshold').clear().type(threshold);
		cy.get('#classifai-settings-entity_threshold').clear().type(threshold);
		cy.get('#classifai-settings-concept_threshold').clear().type(threshold);
		cy.get('#submit').click();

		// Create Test Post
		cy.createPost({
			title: 'Test NLU post with 75 Threshold',
			content: 'Test NLU Content with 75 Threshold',
		});

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get('body').then(($body) => {
			if ($body.find(closePanelSelector).length > 0) {
				cy.get(closePanelSelector).click();
			}
		});

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		['categories', 'keywords', 'concepts', 'entities'].forEach(
			(taxonomy) => {
				cy.verifyPostTaxonomyTerms(taxonomy, threshold / 100);
			}
		);
	});

	// Skiping this until issue get fixed.
	it.skip('Can create post and tags get created by ClassifAI', () => {
		const threshold = 75;
		cy.visit('/wp-admin/tools.php?page=classifai&tab=language_processing');

		cy.get('#classifai-settings-category_taxonomy').select('post_tag');
		cy.get('#classifai-settings-keyword_taxonomy').select('post_tag');
		cy.get('#classifai-settings-entity_taxonomy').select('post_tag');
		cy.get('#classifai-settings-concept_taxonomy').select('post_tag');
		cy.get('#submit').click();

		// Create Test Post
		cy.createPost({
			title: 'Test NLU post for tags',
			content: 'Test NLU Content for tags',
		});

		// Close post publish panel
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get('body').then(($body) => {
			if ($body.find(closePanelSelector).length > 0) {
				cy.get(closePanelSelector).click();
			}
		});

		// Open post settings sidebar
		cy.openDocumentSettingsSidebar();

		// Verify Each Created taxonomies.
		cy.verifyPostTaxonomyTerms('tags', threshold / 100);
	});

	it( 'Can save OpenAI "Language Processing" settings', () => {
		cy.visit( '/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt' );

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
			cy.wrap( $panel ).find( '.editor-post-excerpt button' ).should( 'exist' );

			// Click on button and verify data loads in.
			cy.wrap( $panel ).find( '.editor-post-excerpt button' ).click();
			cy.wrap( $panel ).find( 'textarea' ).should( 'have.value', data );
		} );
	} );

	it( 'Can disable excerpt generation feature', () => {
		cy.visit( '/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt' );

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
		cy.visit( '/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt' );

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

	it('Can save OpenAI ChatGPT "Language Processing" title settings', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		cy.get('#api_key').clear().type('password');

		cy.get('#enable_titles').check();
		cy.get('#openai_chatgpt_title_roles_administrator').check();
		cy.get('#number_titles').select(3);
		cy.get('#submit').click();
	});

	it('Can see the generate titles button in a post', () => {
		const data = getChatGPTData();

		// Create test post.
		cy.createPost({
			title: 'Test ChatGPT generate titles',
			content: 'Test content',
		});

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get('body').then(($body) => {
			if ($body.find(closePanelSelector).length > 0) {
				cy.get(closePanelSelector).click();
			}
		});

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get(panelButtonSelector).then(($panelButton) => {
			// Find the panel container.
			const $panel = $panelButton.parents('.components-panel__body');

			// Open panel.
			if (!$panel.hasClass('is-opened')) {
				cy.wrap($panelButton).click();
			}

			// Verify button exists.
			cy.wrap($panel)
				.find('.classifai-post-status button.title')
				.should('exist');

			// Click on button and verify modal shows.
			cy.wrap($panel).find('.classifai-post-status button.title').click();
			cy.get('.title-modal').should('exist');

			// Click on button and verify data loads in.
			cy.get('.title-modal').then(($modal) => {
				const titles = cy.wrap($modal.find('.classifai-title'));
				titles.length.should('eq', 3);
				titles.first().find('textarea').should('have.value', data);
				titles.first().find('button').click();
			});

			cy.get('.title-modal').should('not.exist');
			cy.get('.editor-post-title__input').should('have.value', data);
		});
	});

	it('Can disable title generation feature', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable features.
		cy.get('#enable_titles').uncheck();
		cy.get('#submit').click();

		// Create test post.
		cy.createPost({
			title: 'Test ChatGPT generate titles disabled',
			content: 'Test content',
		});

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get('body').then(($body) => {
			if ($body.find(closePanelSelector).length > 0) {
				cy.get(closePanelSelector).click();
			}
		});

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get(panelButtonSelector).then(($panelButton) => {
			// Find the panel container.
			const $panel = $panelButton.parents('.components-panel__body');

			// Open panel.
			if (!$panel.hasClass('is-opened')) {
				cy.wrap($panelButton).click();
			}

			// Verify button doesn't exist.
			cy.wrap($panel)
				.find('.classifai-post-status button.title')
				.should('not.exist');
		});
	});

	it('Can disable title generation feature by role', () => {
		cy.visit(
			'/wp-admin/tools.php?page=classifai&tab=language_processing&provider=openai_chatgpt'
		);

		// Disable admin role.
		cy.get('#enable_titles').uncheck();
		cy.get('#openai_chatgpt_title_roles_administrator').uncheck();
		cy.get('#submit').click();

		// Create test post.
		cy.createPost({
			title: 'Test ChatGPT generate titles role disabled',
			content: 'Test content',
		});

		// Close post publish panel.
		const closePanelSelector = 'button[aria-label="Close panel"]';
		cy.get('body').then(($body) => {
			if ($body.find(closePanelSelector).length > 0) {
				cy.get(closePanelSelector).click();
			}
		});

		// Open post settings sidebar.
		cy.openDocumentSettingsSidebar();

		// Find and open the summary panel.
		const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button`;

		cy.get(panelButtonSelector).then(($panelButton) => {
			// Find the panel container.
			const $panel = $panelButton.parents('.components-panel__body');

			// Open panel.
			if (!$panel.hasClass('is-opened')) {
				cy.wrap($panelButton).click();
			}

			// Verify button doesn't exist.
			cy.wrap($panel)
				.find('.classifai-post-status button.title')
				.should('not.exist');
		});
	});
});
