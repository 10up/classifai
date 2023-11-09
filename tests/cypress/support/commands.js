// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
import { getNLUData } from '../plugins/functions';

/**
 * Verify that the post has the expected taxonomy terms.
 *
 * @param {string} taxonomy  The taxonomy to verify.
 * @param {number} threshold The threshold to use.
 */
Cypress.Commands.add( 'verifyPostTaxonomyTerms', ( taxonomy, threshold ) => {
	const taxonomyTitle =
		taxonomy.charAt( 0 ).toUpperCase() + taxonomy.slice( 1 );
	const panelTitle =
		taxonomy === 'tags' ? taxonomyTitle : `Watson ${ taxonomyTitle }`;
	const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("${ panelTitle }")`;
	let terms = [];
	if ( taxonomy === 'tags' ) {
		[ 'categories', 'keywords', 'concepts', 'entities' ].forEach(
			( taxo ) => {
				terms.push( ...getNLUData( taxo, threshold ) );
			}
		);
	} else {
		terms = getNLUData( taxonomy, threshold );
	}

	const taxonomySelector =
		'span.components-form-token-field__token-text span[aria-hidden="true"]';

	// Open Panel
	cy.get( panelButtonSelector ).then( ( $button ) => {
		// Find the panel container
		const $panel = $button.parents( '.components-panel__body' );

		// Close Panel.
		if ( ! $panel.hasClass( 'is-opened' ) ) {
			cy.wrap( $button ).click();
		}

		// Compare taxonomy terms with test data terms.
		cy.wrap( $panel ).find( taxonomySelector ).should( 'exist' );
		cy.wrap( $panel )
			.find( taxonomySelector )
			.each( ( term ) => {
				return expect( term.text() ).to.be.oneOf( terms );
			} )
			.then( ( postTerms ) => {
				expect( postTerms ).to.have.length( terms.length );
			} );

		// Close Panel.
		cy.wrap( $button ).click();
	} );
} );

/**
 * Opt out for a feature.
 *
 * @param {string} feature The feature to opt out.
 */
Cypress.Commands.add( 'optOutFeature', ( feature ) => {
	// Go to profile page and opt out.
	cy.visit( '/wp-admin/profile.php' );
	cy.get( `#classifai_opted_out_features_${ feature }` ).check();
	cy.get( '#submit' ).click();
	cy.get( '#message.notice' ).contains( 'Profile updated.' );
} );

/**
 * Opt in for a feature.
 *
 * @param {string} feature The feature to opt in.
 */
Cypress.Commands.add( 'optInFeature', ( feature ) => {
	// Go to profile page and opt in.
	cy.visit( '/wp-admin/profile.php' );
	cy.get( `#classifai_opted_out_features_${ feature }` ).uncheck();
	cy.get( '#submit' ).click();
	cy.get( '#message.notice' ).contains( 'Profile updated.' );
} );

/**
 * Opt in for all features.
 */
Cypress.Commands.add( 'optInAllFeatures', () => {
	// Go to profile page and opt in.
	cy.visit( '/wp-admin/profile.php' );
	cy.get( 'body' ).then( ( $body ) => {
		if (
			$body.find( 'input[name="classifai_opted_out_features[]"]' )
				.length > 0
		) {
			cy.get( 'input[name="classifai_opted_out_features[]"]' ).uncheck( {
				multiple: true,
			} );
			cy.get( '#submit' ).click();
			cy.get( '#message.notice' ).contains( 'Profile updated.' );
		}
	} );
} );

/**
 * Enable role based access for a feature.
 *
 * @param {string} feature  The feature to enable.
 * @param {string} roles    The roles to enable.
 * @param {string} provider The provider to enable.
 */
Cypress.Commands.add( 'enableFeatureForRoles', ( feature, roles, provider ) => {
	cy.visit(
		`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${ provider }`
	);
	cy.get( `#${ feature }_role_based_access` ).check();
	roles.forEach( ( role ) => {
		cy.get( `#${ provider }_${ feature }_roles_${ role }` ).check();
	} );
	cy.get( '#submit' ).click();
	cy.get( '.notice' ).contains( 'Settings saved.' );
} );

/**
 * Disable role based access for a feature.
 *
 * @param {string} feature  The feature to disable.
 * @param {string} roles    The roles to disable.
 * @param {string} provider The provider to disable.
 */
Cypress.Commands.add(
	'disableFeatureForRoles',
	( feature, roles, provider ) => {
		cy.visit(
			`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${ provider }`
		);
		cy.get( `#${ feature }_role_based_access` ).check();
		roles.forEach( ( role ) => {
			cy.get( `#${ provider }_${ feature }_roles_${ role }` ).uncheck();
		} );
		cy.get( '#submit' ).click();
		cy.get( '.notice' ).contains( 'Settings saved.' );
	}
);

/**
 * Enable user based access for a feature.
 *
 * @param {string} feature  The feature to enable.
 * @param {string} users    The users to enable.
 * @param {string} provider The provider to enable.
 */
Cypress.Commands.add( 'enableFeatureForUsers', ( feature, users, provider ) => {
	cy.visit(
		`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${ provider }`
	);
	cy.get( `#${ feature }_user_based_access` ).check();
	cy.get( 'body' ).then( ( $body ) => {
		if (
			$body.find(
				`#${ feature }_users-container .components-form-token-field__remove-token`
			).length > 0
		) {
			cy.get(
				`#${ feature }_users-container .components-form-token-field__remove-token`
			).click( {
				multiple: true,
			} );
		}
	} );

	users.forEach( ( user ) => {
		cy.get(
			`#${ feature }_users-container input.components-form-token-field__input`
		).type( user );
		cy.wait( 1000 );
		cy.get(
			'ul.components-form-token-field__suggestions-list li:nth-child(1)'
		).click();
	} );
	cy.get( '#submit' ).click();
	cy.get( '.notice' ).contains( 'Settings saved.' );
} );

/**
 * Enable user based opt-out for a feature.
 *
 * @param {string} feature  The feature to enable.
 * @param {string} provider The provider to enable.
 */
Cypress.Commands.add( 'enableFeatureOptOut', ( feature, provider ) => {
	cy.visit(
		`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${ provider }`
	);
	cy.get( `#${ feature }_role_based_access` ).check();
	cy.get( `#${ provider }_${ feature }_roles_administrator` ).check();
	cy.get( `#${ feature }_user_based_access` ).uncheck();
	cy.get( `#${ feature }_user_based_opt_out` ).check();

	cy.get( '#submit' ).click();
	cy.get( '.notice' ).contains( 'Settings saved.' );
} );

/**
 * Verify that the content classification feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyClassifyContentEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';
	cy.visit( '/wp-admin/edit.php' );
	cy.get( '#the-list tr:nth-child(1) td.title a.row-title' ).click();
	cy.closeWelcomeGuide();
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.classifai-panel' ).length > 0 ) {
			if (
				! $body
					.find( '.classifai-panel' )[ 0 ]
					.classList.contains( 'is-opened' )
			) {
				cy.get( '.classifai-panel' ).click();
			}
		}
	} );

	cy.contains(
		'.classifai-panel label.components-toggle-control__label',
		'Process content on update'
	).should( shouldExist );
} );

/**
 * Verify that the excerpt generation feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyExcerptGenerationEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';
	cy.visit( '/wp-admin/edit.php' );
	cy.get( '#the-list tr:nth-child(1) td.title a.row-title' ).click();

	// Find and open the excerpt panel.
	cy.closeWelcomeGuide();
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
			.should( shouldExist );
	} );
} );

/**
 * Verify that the resize content feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyResizeContentEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';
	cy.createPost( {
		title: 'Expand content',
		content: 'Are the resizing options hidden?',
	} );

	cy.get( '.classifai-resize-content-btn' ).should( shouldExist );
} );

/**
 * Verify that the speech to text feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add(
	'verifySpeechToTextEnabled',
	( enabled = true, options = {} ) => {
		const shouldExist = enabled ? 'exist' : 'not.exist';
		// Verify features are not present in attachment metabox.
		cy.visit( options.audioEditLink );
		cy.get( '.misc-publishing-actions label[for=retranscribe]' ).should(
			shouldExist
		);

		// Verify features are not present in media modal.
		cy.visit( options.mediaModalLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-retranscribe' ).should( shouldExist );
	}
);

/**
 * Verify that the text to speech feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyTextToSpeechEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';
	cy.visit( '/wp-admin/edit.php' );
	cy.get( '#the-list tr:nth-child(1) td.title a.row-title' ).click();
	cy.closeWelcomeGuide();
	cy.get( '.classifai-panel' ).click();
	cy.get( '#classifai-audio-controls__preview-btn' ).should( shouldExist );
} );

/**
 * Verify that the title generation feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyTitleGenerationEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';
	cy.visit( '/wp-admin/edit.php' );
	cy.get( '#the-list tr:nth-child(1) td.title a.row-title' ).click();

	// Find and open the summary panel.
	cy.closeWelcomeGuide();
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
			.should( shouldExist );
	} );
} );

/**
 * Verify that the image generation feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyImageGenerationEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';
	cy.visit( '/wp-admin/upload.php' );
	if ( enabled ) {
		cy.get( '.wp-has-current-submenu.wp-menu-open li:last-child a' ).should(
			'contain.text',
			'Generate Images'
		);
	} else {
		cy.get( '.wp-has-current-submenu.wp-menu-open li:last-child a' ).should(
			'not.contain.text',
			'Generate Images'
		);
	}

	cy.visit( '/wp-admin/edit.php' );
	cy.get( '#the-list tr:nth-child(1) td.title a.row-title' ).click();

	// Find and open the Featured image panel.
	cy.closeWelcomeGuide();
	const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Featured image")`;

	cy.get( panelButtonSelector ).then( ( $panelButton ) => {
		// Find the panel container.
		const $panel = $panelButton.parents( '.components-panel__body' );

		// Open panel.
		if ( ! $panel.hasClass( 'is-opened' ) ) {
			cy.wrap( $panelButton ).click();
		}

		// Click to open media modal.
		cy.wrap( $panel ).find( '.editor-post-featured-image__toggle' ).click();

		// Verify tab doesn't exist.
		cy.get( '#menu-item-generate' ).should( shouldExist );
	} );
} );

/**
 * Verify that the AI Vision features is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyAIVisionEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';
	cy.visit( '/wp-admin/upload.php?mode=list' );
	cy.get( '#the-list tr:nth-child(1) td.title a span.image-icon' ).click();

	// Verify that the feature is not available.
	cy.get( '.misc-publishing-actions label[for=rescan-captions]' ).should(
		shouldExist
	);
	cy.get( '.misc-publishing-actions label[for=rescan-tags]' ).should(
		shouldExist
	);
	cy.get( '.misc-publishing-actions label[for=rescan-ocr]' ).should(
		shouldExist
	);
	cy.get( '.misc-publishing-actions label[for=rescan-smart-crop]' ).should(
		shouldExist
	);
} );

/**
 * Deactivate the Classic Editor plugin.
 */
Cypress.Commands.add( 'disableClassicEditor', () => {
	cy.visit( '/wp-admin/plugins.php' );
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '#deactivate-classic-editor' ).length > 0 ) {
			cy.get( '#deactivate-classic-editor' ).click();
		}
	} );
} );

/**
 * Activate the Classic Editor plugin.
 */
Cypress.Commands.add( 'enableClassicEditor', () => {
	cy.visit( '/wp-admin/plugins.php' );
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '#activate-classic-editor' ).length > 0 ) {
			cy.get( '#activate-classic-editor' ).click();
		}
	} );
} );
