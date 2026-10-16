/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';

import type { Admin, Editor, RequestUtils } from '@wordpress/e2e-test-utils-playwright';

import { getNLUData } from './test-data';

type NluTaxonomy = 'categories' | 'keywords' | 'concepts' | 'entities';

const imageProcessingFeatures = [
	'feature_descriptive_text_generator',
	'feature_image_tags_generator',
	'feature_image_cropping',
	'feature_image_to_text_generator',
	'feature_image_generation',
	'feature_pdf_to_text_generation',
];

type FeatureKey = string;

type VerifyOptions = {
	imageEditLink?: string;
	mediaModelLink?: string;
	audioEditLink?: string;
	mediaModalLink?: string;
};

export class ClassifAIUtils {
	page: Page;
	admin: Admin;
	editor: Editor;
	requestUtils: RequestUtils;

	constructor( {
		page,
		admin,
		editor,
		requestUtils,
	}: {
		page: Page;
		admin: Admin;
		editor: Editor;
		requestUtils: RequestUtils;
	} ) {
		this.page = page;
		this.admin = admin;
		this.editor = editor;
		this.requestUtils = requestUtils;
	}

	// ---------- Settings navigation / save ----------

	async visitFeatureSettings( featurePath: string ): Promise< void > {
		await this.page.goto(
			`/wp-admin/tools.php?page=classifai#/${ featurePath }`
		);
		if ( ! featurePath.includes( 'feature_smart_404' ) ) {
			await expect(
				this.page.locator( '.components-panel__header h2' ).first()
			).toBeVisible();
		}
	}

	async selectProvider( provider: string ): Promise< void > {
		const logo = this.page.locator( '#classifai-logo' );
		await expect( logo ).toBeVisible();
		await expect(
			this.page.locator( '.classifai-loading-settings' )
		).toHaveCount( 0 );

		const editBtn = this.page.locator(
			'.classifai-settings-edit-provider'
		);
		if ( await editBtn.count() ) {
			await editBtn.first().click();
		}

		await this.page
			.locator( '.classifai-provider-select select' )
			.selectOption( provider );
	}

	async saveFeatureSettings(): Promise< void > {
		const responsePromise = this.page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/settings/' ) &&
				res.request().method() === 'POST'
		);
		await this.page
			.locator( '.classifai-settings-footer button.save-settings-button' )
			.click();
		await responsePromise;
	}

	async saveGeneralSettings(): Promise< void > {
		const responsePromise = this.page.waitForResponse(
			( res ) =>
				res.url().includes( '/wp-json/classifai/v1/registration/' ) &&
				res.request().method() === 'POST'
		);
		await this.page
			.locator( '.classifai-settings-footer button.is-primary' )
			.click();
		await responsePromise;
	}

	async enableFeature( disableCredentialReuseModal = true ): Promise< void > {
		if ( disableCredentialReuseModal ) {
			await this.disableCredentialReuseModal();
		}
		const toggle = this.page.locator(
			'.classifai-enable-feature-toggle input[type="checkbox"]'
		);
		if ( ! ( await toggle.isChecked() ) ) {
			await toggle.check();
		}
	}

	async disableFeature(): Promise< void > {
		const toggle = this.page.locator(
			'.classifai-enable-feature-toggle input[type="checkbox"]'
		);
		if ( await toggle.isChecked() ) {
			await toggle.uncheck();
		}
	}

	async disableFeatureIfEnabled(): Promise< void > {
		const toggle = this.page.locator(
			'.components-form-toggle__input'
		).first();
		if ( ( await toggle.count() ) && ( await toggle.isChecked() ) ) {
			await toggle.click();
		}
	}

	async openUserPermissionsPanel(): Promise< void > {
		const panel = this.page
			.locator(
				'.components-panel__body.classifai-settings__user-permissions'
			)
			.first();
		await panel.waitFor( { state: 'visible' } );
		const cls = ( await panel.getAttribute( 'class' ) ) || '';
		if ( ! cls.includes( 'is-opened' ) ) {
			await panel
				.locator( '.components-panel__body-title button' )
				.first()
				.click();
			// Wait for the React class update so subsequent reads see the
			// expanded panel (token field, role checkboxes, etc.).
			await expect( panel ).toHaveClass( /is-opened/ );
		}
	}

	async allowFeatureToAdmin(): Promise< void > {
		await this.openUserPermissionsPanel();
		await this.page
			.locator( '.settings-allowed-roles input#administrator' )
			.check();
	}

	// ---------- Role/User access management ----------

	private tabForFeature( feature: FeatureKey ): string {
		return imageProcessingFeatures.includes( feature )
			? 'image_processing'
			: 'language_processing';
	}

	async enableFeatureForRoles(
		feature: FeatureKey,
		roles: string[]
	): Promise< void > {
		const tab = this.tabForFeature( feature );
		await this.visitFeatureSettings( `${ tab }/${ feature }` );
		await expect( this.page.locator( '#classifai-logo' ) ).toBeVisible();

		await this.openUserPermissionsPanel();

		// Disable all role checkboxes first.
		const roleBoxes = this.page.locator(
			'.settings-allowed-roles input[type="checkbox"]'
		);
		const total = await roleBoxes.count();
		for ( let i = 0; i < total; i++ ) {
			const cb = roleBoxes.nth( i );
			if ( await cb.isChecked() ) {
				await cb.uncheck();
			}
		}

		await this.disableFeatureForUsers();

		for ( const role of roles ) {
			await this.page
				.locator( `.settings-allowed-roles input#${ role }` )
				.check();
		}

		await this.page.waitForTimeout( 100 );
		await this.saveFeatureSettings();
	}

	async disableFeatureForRoles(
		feature: FeatureKey,
		roles: string[]
	): Promise< void > {
		const tab = this.tabForFeature( feature );
		await this.visitFeatureSettings( `${ tab }/${ feature }` );
		await this.page.waitForTimeout( 100 );
		await this.enableFeature();
		await this.openUserPermissionsPanel();

		// Clear users BEFORE toggling role checkboxes. Toggling a role triggers
		// a React re-render of the permissions panel which momentarily strips
		// the user tokens from the DOM, making the subsequent removal a no-op.
		await this.disableFeatureForUsers();

		for ( const role of roles ) {
			await this.page
				.locator( `.settings-allowed-roles input#${ role }` )
				.uncheck( { force: true } );
		}

		await this.page.waitForTimeout( 100 );
		await this.saveFeatureSettings();
	}

	async enableFeatureForUsers(
		feature: FeatureKey,
		users: string[]
	): Promise< void > {
		const tab = this.tabForFeature( feature );
		await this.visitFeatureSettings( `${ tab }/${ feature }` );
		await this.openUserPermissionsPanel();

		// Disable all role checkboxes first.
		const roleBoxes = this.page.locator(
			'.settings-allowed-roles input[type="checkbox"]'
		);
		const total = await roleBoxes.count();
		for ( let i = 0; i < total; i++ ) {
			const cb = roleBoxes.nth( i );
			if ( await cb.isChecked() ) {
				await cb.uncheck();
			}
		}

		// Resolve usernames to IDs via WP REST so we can dispatch the user
		// array directly. The token field's autocomplete is debounced and
		// flaky to drive through the UI.
		const userIds: number[] = [];
		for ( const username of users ) {
			const resp = await this.page.request.get(
				`/wp-json/wp/v2/users?search=${ encodeURIComponent(
					username
				) }&context=view&__fields=id,slug`
			);
			const data = ( await resp.json() ) as Array< {
				id: number;
				slug: string;
			} >;
			const match = data.find( ( u ) => u.slug === username ) ?? data[ 0 ];
			if ( match ) {
				userIds.push( match.id );
			}
		}
		await this.page.evaluate( ( ids: number[] ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			window.wp.data
				.dispatch( 'classifai-settings' )
				.setFeatureSettings( { users: ids } );
		}, userIds );

		await this.saveFeatureSettings();
	}

	async disableFeatureForUsers(): Promise< void > {
		// Clear the users array directly via the classifai-settings store. The
		// token field's tokens are populated asynchronously after fetching user
		// details via `/wp/v2/users?include=…`, so the click-each-remove-token
		// approach races with the React render and often no-ops. Dispatching to
		// the store is deterministic and the subsequent saveFeatureSettings
		// call persists it.
		await this.page.evaluate( () => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			window.wp.data
				.dispatch( 'classifai-settings' )
				.setFeatureSettings( { users: [] } );
		} );
	}

	async enableFeatureOptOut( feature: FeatureKey ): Promise< void > {
		const tab = this.tabForFeature( feature );
		await this.visitFeatureSettings( `${ tab }/${ feature }` );
		await this.page.waitForTimeout( 100 );
		await this.openUserPermissionsPanel();
		await this.page
			.locator( '.settings-allowed-roles input#administrator' )
			.check();
		await this.page
			.locator( '.classifai-settings__user-based-opt-out input' )
			.check();
		await this.saveFeatureSettings();
	}

	async optOutFeature( feature: FeatureKey ): Promise< void > {
		await this.page.goto( '/wp-admin/profile.php' );
		await this.page
			.locator( `#classifai_opted_out_features_${ feature }` )
			.check();
		await this.page.locator( '#submit' ).click();
		await expect(
			this.page.locator( '#message.notice' )
		).toContainText( 'Profile updated.' );
	}

	async optInFeature( feature: FeatureKey ): Promise< void > {
		await this.page.goto( '/wp-admin/profile.php' );
		await this.page
			.locator( `#classifai_opted_out_features_${ feature }` )
			.uncheck();
		await this.page.locator( '#submit' ).click();
		await expect(
			this.page.locator( '#message.notice' )
		).toContainText( 'Profile updated.' );
	}

	async optInAllFeatures(): Promise< void > {
		await this.page.goto( '/wp-admin/profile.php' );
		const optOuts = this.page.locator(
			'input[name="classifai_opted_out_features[]"]'
		);
		if ( ( await optOuts.count() ) === 0 ) {
			return;
		}
		const total = await optOuts.count();
		for ( let i = 0; i < total; i++ ) {
			const cb = optOuts.nth( i );
			if ( await cb.isChecked() ) {
				await cb.uncheck();
			}
		}
		await this.page.locator( '#submit' ).click();
		await expect(
			this.page.locator( '#message.notice' )
		).toContainText( 'Profile updated.' );
	}

	// ---------- Credential reuse modal toggle ----------

	async disableCredentialReuseModal(): Promise< void > {
		await this.page.evaluate( () => {
			window.localStorage.setItem(
				'classifai_dont_ask_credential_reuse',
				'true'
			);
		} );
	}

	async enableCredentialReuseModal(): Promise< void > {
		await this.page.evaluate( () => {
			window.localStorage.removeItem(
				'classifai_dont_ask_credential_reuse'
			);
		} );
	}

	// ---------- Plugin activation helpers ----------

	async disableClassicEditor(): Promise< void > {
		try {
			await this.requestUtils.deactivatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// already inactive
		}
	}

	async enableClassicEditor(): Promise< void > {
		try {
			await this.requestUtils.activatePlugin( 'classic-editor' );
		} catch ( _ ) {
			// already active
		}
	}

	async enableElasticPress(): Promise< void > {
		try {
			await this.requestUtils.activatePlugin( 'elasticpress' );
		} catch ( _ ) {
			// already active
		}
	}

	async disableElasticPress(): Promise< void > {
		try {
			await this.requestUtils.deactivatePlugin( 'elasticpress' );
		} catch ( _ ) {
			// already inactive
		}
	}

	async activateWooCommerce(): Promise< void > {
		try {
			await this.requestUtils.activatePlugin( 'woocommerce' );
		} catch ( _ ) {
			// already active
		}
	}

	async deactivateWooCommerce(): Promise< void > {
		try {
			await this.requestUtils.deactivatePlugin( 'woocommerce' );
		} catch ( _ ) {
			// already inactive
		}
	}

	// ---------- Post / Product creation ----------

	async closeWelcomeGuide(): Promise< void > {
		const closeBtn = this.page.locator(
			'button[aria-label="Close"][class*="edit-post-welcome-guide"]'
		);
		try {
			if ( await closeBtn.count() ) {
				await closeBtn.first().click( { timeout: 1000 } );
			}
		} catch ( _ ) {
			// Welcome guide already dismissed.
		}
	}

	async closePublishPanel(): Promise< void > {
		const closeBtn = this.page.locator(
			'button[aria-label="Close panel"]'
		);
		if ( await closeBtn.count() ) {
			await closeBtn.first().click();
		}
	}

	async createPost( {
		title,
		content,
		publish = true,
	}: {
		title: string;
		content?: string;
		publish?: boolean;
	} ): Promise< void > {
		// Title-only navigation: URL-param `content` makes WP wrap the content
		// in a Classic (`core/freeform`) block, which breaks ClassifAI features
		// that only target paragraph blocks (resize, etc.). We type the
		// content into the default block appender instead.
		await this.admin.createNewPost( { title } );
		await this.closeWelcomeGuide();

		if ( content ) {
			const appender = this.editor.canvas
				.locator( '.block-editor-default-block-appender__content' )
				.first();
			await appender.waitFor( { state: 'visible', timeout: 10000 } );
			await appender.click();
			const editable = this.editor.canvas
				.locator( '.block-editor-rich-text__editable' )
				.first();
			await editable.waitFor( { state: 'visible', timeout: 10000 } );
			await editable.fill( content );
		}

		if ( publish ) {
			await this.editor.publishPost();
			// The "Post is now live" panel covers the editor after publish.
			await this.closePublishPanel();
		}

		// Typing content focuses the paragraph block, which auto-switches the
		// sidebar to the "Block" tab. Most ClassifAI controls (title/excerpt
		// generation buttons, classify-on-update toggle, etc.) live in the
		// "Post" tab, so clear the block selection here. Tests that need the
		// block toolbar can call `focusFirstParagraph()`.
		try {
			await this.page.evaluate( () => {
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				window.wp.data
					.dispatch( 'core/block-editor' )
					.clearSelectedBlock();
			} );
		} catch ( _ ) {
			// wp.data not available outside the editor.
		}
	}

	/**
	 * Focus the first paragraph block and pin the toolbar so its BlockControls
	 * (ClassifAI resize, etc.) are visible. Tests that need to interact with
	 * block-toolbar dropdowns should call this after `createPost`.
	 */
	async focusFirstParagraph(): Promise< void > {
		await this.editor.setIsFixedToolbar( true );
		const para = this.editor.canvas
			.locator( '[data-type="core/paragraph"]' )
			.first();
		await para.waitFor( { state: 'visible', timeout: 5000 } );
		await para.click();
	}

	async createProduct( {
		title,
		content,
	}: {
		title: string;
		content: string;
	} ): Promise< void > {
		await this.page.goto( '/wp-admin/post-new.php?post_type=product' );
		await this.closeWelcomeGuide();
		await this.page
			.locator( '.editor-post-title__input' )
			.first()
			.fill( title );
		await this.page
			.locator( '.block-editor-rich-text__editable' )
			.first()
			.fill( content );
	}

	async classicCreateProduct( {
		title,
		content,
	}: {
		title: string;
		content: string;
	} ): Promise< void > {
		await this.page.goto( '/wp-admin/post-new.php?post_type=product' );
		await this.page.locator( '#title' ).fill( title );
		await this.page.locator( '#content' ).fill( content );
	}

	// ---------- Feature verification ----------

	/**
	 * Open the post sidebar's ClassifAI section, rendered as a collapsed
	 * `.classifai-panel` whose `<h2><button>ClassifAI</button></h2>` toggle
	 * expands it.
	 */
	async openClassifAIPostPanel(): Promise< void > {
		await this.activatePostTab();

		const toggle = this.page
			.locator( '.classifai-panel .components-panel__body-toggle' )
			.first();
		try {
			await toggle.waitFor( { timeout: 5000 } );
		} catch {
			return;
		}

		if ( ( await toggle.getAttribute( 'aria-expanded' ) ) !== 'true' ) {
			await toggle.click();
		}
	}

	async verifyClassifyContentEnabled( enabled = true ): Promise< void > {
		await this.editFreshPostInBlockEditor( 'Verify ClassifAI panel' );
		await this.openClassifAIPostPanel();
		const target = this.page.locator(
			'label.components-toggle-control__label',
			{ hasText: 'Automatically tag content on update' }
		);
		if ( enabled ) {
			await expect( target.first() ).toBeVisible();
		} else {
			await expect( target ).toHaveCount( 0 );
		}
	}

	async verifyModerationEnabled( enabled = true ): Promise< void > {
		await this.page.goto( '/wp-admin/edit-comments.php' );
		const moderateOpt = this.page.locator(
			'#bulk-action-selector-top option',
			{ hasText: 'Moderate' }
		);
		const flagged = this.page.locator( '#moderation_flagged' );
		const flags = this.page.locator( '#moderation_flags' );
		if ( enabled ) {
			await expect( moderateOpt ).toHaveCount( 1 );
			await expect( flagged ).toBeVisible();
			await expect( flags ).toBeVisible();
		} else {
			await expect( moderateOpt ).toHaveCount( 0 );
			await expect( flagged ).toHaveCount( 0 );
			await expect( flags ).toHaveCount( 0 );
		}
	}

	/**
	 * Create a publish-state post via REST and navigate to its edit page in the
	 * block editor. Verify helpers use this so they don't depend on whatever
	 * post (or post type) happens to be first on /wp-admin/edit.php.
	 */
	async editFreshPostInBlockEditor( title: string ): Promise< void > {
		const post = ( await this.requestUtils.createPost( {
			title,
			status: 'publish',
		} ) ) as { id: number };
		await this.page.goto(
			`/wp-admin/post.php?post=${ post.id }&action=edit`
		);
		await this.closeWelcomeGuide();
	}

	/**
	 * Open the first post listed on /wp-admin/edit.php in the block editor.
	 */
	async openFirstPostInList(): Promise< void > {
		await this.page.goto( '/wp-admin/edit.php' );
		await this.page.locator( '#the-list .row-title' ).first().click();
		await this.closeWelcomeGuide();
	}

	async verifyExcerptGenerationEnabled( enabled = true ): Promise< void > {
		await this.openFirstPostInList();

		// Ensure document settings sidebar is open so the Excerpt panel is in the DOM.
		await this.editor.openDocumentSettingsSidebar();
		await this.activatePostTab();

		// Find and open the excerpt panel.
		await this.page
			.locator( '.editor-post-excerpt__dropdown button' )
			.click();

		const btn = this.page.locator( '.classifai-excerpt-generation button' );
		if ( enabled ) {
			await expect( btn.first() ).toBeVisible();
		} else {
			await expect( btn ).toHaveCount( 0 );
		}
	}

	async verifyResizeContentEnabled( enabled = true ): Promise< void > {
		await this.createPost( {
			title: 'Expand content',
			content: 'Are the resizing options hidden?',
		} );
		if ( enabled ) {
			await this.focusFirstParagraph();
			await expect(
				this.page.locator( '.classifai-resize-content-btn' )
			).toBeVisible();
		} else {
			// Try to focus the paragraph anyway so the absence of the toolbar
			// control proves the feature is hidden.
			try {
				await this.focusFirstParagraph();
			} catch ( _ ) {
				// noop
			}
			await expect(
				this.page.locator( '.classifai-resize-content-btn' )
			).toHaveCount( 0 );
		}
	}

	async verifySpeechToTextEnabled(
		enabled = true,
		options: VerifyOptions = {}
	): Promise< void > {
		await this.page.goto( options.audioEditLink as string );
		const retranscribe = this.page.locator(
			'.misc-publishing-actions label[for=retranscribe]'
		);
		if ( enabled ) {
			await expect( retranscribe ).toBeVisible();
		} else {
			await expect( retranscribe ).toHaveCount( 0 );
		}

		await this.page.goto( options.mediaModalLink as string );
		await expect( this.page.locator( '.media-modal' ) ).toBeVisible();
		const action = this.page.locator( '#classifai-retranscribe' );
		if ( enabled ) {
			await expect( action ).toBeVisible();
		} else {
			await expect( action ).toHaveCount( 0 );
		}
	}

	async verifyTextToSpeechEnabled( enabled = true ): Promise< void > {
		await this.openFirstPostInList();
		await this.openClassifAIPostPanel();
		const btn = this.page.locator( '#classifai-audio-controls__preview-btn' );
		if ( enabled ) {
			await expect( btn ).toBeVisible();
		} else {
			await expect( btn ).toHaveCount( 0 );
		}
	}

	async verifyTitleGenerationEnabled( enabled = true ): Promise< void > {
		await this.openFirstPostInList();
		await this.openLegacyPostStatusPanelIfPresent();
		const btn = this.page.locator( '.classifai-post-status button.title' );
		if ( enabled ) {
			await expect( btn.first() ).toBeVisible();
		} else {
			await expect( btn ).toHaveCount( 0 );
		}
	}

	/**
	 * Switch the editor's settings sidebar to the "Post" tab. createPost selects
	 * the first paragraph block which auto-switches the sidebar to "Block"; any
	 * test that wants post-level controls (title generation button, excerpt
	 * button, etc.) needs to flip back.
	 */
	async activatePostTab(): Promise< void > {
		const postTab = this.page
			.getByRole( 'region', { name: 'Editor settings' } )
			.getByRole( 'tab', { name: 'Post', exact: true } );
		if ( await postTab.count() ) {
			const isActive =
				( await postTab.getAttribute( 'aria-selected' ) ) === 'true';
			if ( ! isActive ) {
				await postTab.click();
			}
		}
	}

	/**
	 * In WP < 6.6 the post-status group lives inside a collapsible
	 * `.components-panel__body.edit-post-post-status` panel that needs to be
	 * opened before its child buttons (Generate titles, Generate excerpt, etc.)
	 * are visible. WP 6.6+ shows everything inline; this helper is a no-op there.
	 */
	async openLegacyPostStatusPanelIfPresent(): Promise< void > {
		await this.activatePostTab();
		const legacyBtn = this.page
			.locator(
				'.components-panel__body.edit-post-post-status .components-panel__body-title button'
			)
			.first();
		if ( ( await legacyBtn.count() ) === 0 ) {
			return;
		}
		const panel = legacyBtn.locator(
			'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
		);
		const cls = ( await panel.getAttribute( 'class' ) ) || '';
		if ( ! cls.includes( 'is-opened' ) ) {
			await legacyBtn.click();
		}
	}

	async verifyContentGenerationEnabled( enabled = true ): Promise< void > {
		await this.openFirstPostInList();

		const chatBtn = this.page.locator( '.classifai-chat-button' ).first();
		if ( enabled ) {
			await chatBtn.click( { force: true } );
			await expect( this.page.locator( '.classifai-chat-ui' ) ).toBeVisible();
		} else {
			await expect( chatBtn ).toHaveCount( 0 );
		}

		await this.page.goto( '/wp-admin/index.php' );
		const draftBtn = this.page.locator( '#classifai-generate-content' );
		if ( enabled ) {
			await expect( draftBtn ).toBeVisible();
		} else {
			await expect( draftBtn ).toHaveCount( 0 );
		}
	}

	async verifyImageGenerationEnabled( enabled = true ): Promise< void > {
		await this.page.goto( '/wp-admin/upload.php' );
		const generateMenu = this.page
			.locator( '.wp-has-current-submenu.wp-menu-open li:last-child a' )
			.first();
		if ( enabled ) {
			await expect( generateMenu ).toContainText( 'Generate Images' );
		} else {
			await expect( generateMenu ).not.toContainText( 'Generate Images' );
		}

		await this.openFirstPostInList();

		await this.openFeaturedImageModal();

		const tab = this.page.locator( '#menu-item-generate' );
		if ( enabled ) {
			await expect( tab ).toBeVisible();
		} else {
			await expect( tab ).toHaveCount( 0 );
		}
	}

	/**
	 * Open the featured image media modal from whatever panel the current WP
	 * version renders (legacy collapsible panel or the WP 6.6+ inline section).
	 */
	async openFeaturedImageModal(): Promise< void > {
		// First, if a legacy panel wraps the featured image control, open it.
		const legacyBtn = this.page
			.locator(
				'.components-panel__body .components-panel__body-title button:has-text("Featured image")'
			)
			.first();
		if ( await legacyBtn.count() ) {
			const panel = legacyBtn.locator(
				'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
			);
			const cls = ( await panel.getAttribute( 'class' ) ) || '';
			if ( ! cls.includes( 'is-opened' ) ) {
				await legacyBtn.click();
			}
		}

		// Click whichever featured image button exists. Newer WP renders a single
		// "Set featured image" button; the legacy panel exposes
		// `.editor-post-featured-image__toggle` once the panel is opened.
		const toggle = this.page.locator(
			'.editor-post-featured-image__toggle, .editor-post-featured-image__container button, button:has-text("Set featured image")'
		);
		await toggle.first().click();
	}

	async verifyAIVisionEnabled(
		enabled = true,
		options: VerifyOptions = {}
	): Promise< void > {
		await this.page.goto( options.imageEditLink as string );

		const captions = this.page.locator(
			'#classifai_image_processing label[for=rescan-captions]'
		);
		const tags = this.page.locator(
			'#classifai_image_processing label[for=rescan-tags]'
		);
		const ocr = this.page.locator(
			'#classifai_image_processing label[for=rescan-ocr]'
		);
		const smartCrop = this.page.locator(
			'#classifai_image_processing label[for=rescan-smart-crop]'
		);
		if ( enabled ) {
			await expect( captions ).toBeVisible();
			await expect( tags ).toBeVisible();
			await expect( ocr ).toBeVisible();
			await expect( smartCrop ).toBeVisible();
		} else {
			await expect( captions ).toHaveCount( 0 );
			await expect( tags ).toHaveCount( 0 );
			await expect( ocr ).toHaveCount( 0 );
			await expect( smartCrop ).toHaveCount( 0 );
		}

		await this.page.goto( options.mediaModelLink as string );
		await expect( this.page.locator( '.media-modal' ) ).toBeVisible();

		const altTags = this.page.locator( '#classifai-rescan-alt-tags' );
		const imgTags = this.page.locator( '#classifai-rescan-image-tags' );
		const smart = this.page.locator( '#classifai-rescan-smart-crop' );
		const ocrAction = this.page.locator( '#classifai-rescan-ocr' );
		if ( enabled ) {
			await expect( altTags ).toBeVisible();
			await expect( imgTags ).toBeVisible();
			await expect( smart ).toBeVisible();
			await expect( ocrAction ).toBeVisible();
		} else {
			await expect( altTags ).toHaveCount( 0 );
			await expect( imgTags ).toHaveCount( 0 );
			await expect( smart ).toHaveCount( 0 );
			await expect( ocrAction ).toHaveCount( 0 );
		}
	}

	// ---------- Taxonomy term verification ----------

	async verifyPostTaxonomyTerms(
		taxonomy: 'tags' | NluTaxonomy,
		threshold: number
	): Promise< void > {
		const taxonomyTitle =
			taxonomy.charAt( 0 ).toUpperCase() + taxonomy.slice( 1 );
		const panelTitle =
			taxonomy === 'tags' ? taxonomyTitle : `Watson ${ taxonomyTitle }`;

		let terms: string[] = [];
		if ( taxonomy === 'tags' ) {
			(
				[ 'categories', 'keywords', 'concepts', 'entities' ] as const
			).forEach( ( taxo ) => {
				terms.push( ...getNLUData( taxo, threshold ) );
			} );
		} else {
			terms = getNLUData( taxonomy, threshold );
		}

		const buttonLocator = this.page
			.locator(
				`.components-panel__body .components-panel__body-title button:has-text("${ panelTitle }")`
			)
			.first();
		await buttonLocator.waitFor();

		const panel = buttonLocator.locator(
			'xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " components-panel__body ")][1]'
		);
		const cls = ( await panel.getAttribute( 'class' ) ) || '';
		if ( ! cls.includes( 'is-opened' ) ) {
			await buttonLocator.click();
		}

		const tokenSelector =
			'span.components-form-token-field__token-text span[aria-hidden="true"]';
		const tokens = panel.locator( tokenSelector );
		await expect( tokens.first() ).toBeVisible();

		const tokenCount = await tokens.count();
		const observed: string[] = [];
		for ( let i = 0; i < tokenCount; i++ ) {
			const text = ( await tokens.nth( i ).textContent() ) || '';
			expect( terms ).toContain( text );
			observed.push( text );
		}
		expect( observed.length ).toBe( terms.length );

		// Close panel again.
		await buttonLocator.click();
	}
}

