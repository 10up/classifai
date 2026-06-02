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

const imageProcessingFeatures = [
	'feature_descriptive_text_generator',
	'feature_image_tags_generator',
	'feature_image_cropping',
	'feature_image_to_text_generator',
	'feature_image_generation',
	'feature_pdf_to_text_generation',
];

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

		// Open Panel.
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
 * @param {string} feature The feature to enable.
 * @param {string} roles   The roles to enable.
 */
Cypress.Commands.add( 'enableFeatureForRoles', ( feature, roles ) => {
	let tab = 'language_processing';
	if ( imageProcessingFeatures.includes( feature ) ) {
		tab = 'image_processing';
	}
	cy.visitFeatureSettings( `${ tab }/${ feature }` );
	cy.get( '#classifai-logo' ).should( 'exist' );

	cy.openUserPermissionsPanel();

	// Disable access for all roles.
	cy.get( '.settings-allowed-roles input[type="checkbox"]' ).uncheck( {
		multiple: true,
	} );

	// Disable access for all users.
	cy.disableFeatureForUsers();

	roles.forEach( ( role ) => {
		cy.get( `.settings-allowed-roles input#${ role }` ).check();
	} );
	cy.wait( 100 );
	cy.saveFeatureSettings();
} );

/**
 * Disable role based access for a feature.
 *
 * @param {string} feature The feature to disable.
 * @param {string} roles   The roles to disable.
 */
Cypress.Commands.add( 'disableFeatureForRoles', ( feature, roles ) => {
	let tab = 'language_processing';
	if ( imageProcessingFeatures.includes( feature ) ) {
		tab = 'image_processing';
	}
	cy.visitFeatureSettings( `${ tab }/${ feature }` );
	cy.wait( 100 );
	cy.enableFeature();
	cy.openUserPermissionsPanel();

	roles.forEach( ( role ) => {
		cy.get( `.settings-allowed-roles input#${ role }` ).uncheck( {
			force: true,
		} );
	} );

	// Disable access for all users.
	cy.disableFeatureForUsers();

	cy.wait( 100 );
	cy.saveFeatureSettings();
} );

/**
 * Enable user based access for a feature.
 *
 * @param {string} feature The feature to enable.
 * @param {string} users   The users to enable.
 */
Cypress.Commands.add( 'enableFeatureForUsers', ( feature, users ) => {
	let tab = 'language_processing';
	if ( imageProcessingFeatures.includes( feature ) ) {
		tab = 'image_processing';
	}
	cy.visitFeatureSettings( `${ tab }/${ feature }` );
	cy.openUserPermissionsPanel();

	// Disable access for all roles.
	cy.get( '.settings-allowed-roles input[type="checkbox"]' ).uncheck( {
		multiple: true,
	} );

	// Disable access for all users.
	cy.disableFeatureForUsers();

	users.forEach( ( user ) => {
		cy.get(
			`.classifai-settings__users input.components-form-token-field__input`
		).type( user );

		cy.get( '[aria-label="admin (admin)"]' ).click();
	} );
	cy.saveFeatureSettings();
} );

/**
 * Disable user based access of all users for a feature.
 */
Cypress.Commands.add( 'disableFeatureForUsers', () => {
	cy.openUserPermissionsPanel();
	// Disable access for all users.
	cy.get( '.classifai-settings__users' ).then( ( $body ) => {
		if (
			$body.find( `.components-form-token-field__remove-token` ).length >
			0
		) {
			cy.get( `.components-form-token-field__remove-token` ).click( {
				multiple: true,
			} );
		}
	} );
} );

/**
 * Enable user based opt-out for a feature.
 *
 * @param {string} feature The feature to enable.
 */
Cypress.Commands.add( 'enableFeatureOptOut', ( feature ) => {
	let tab = 'language_processing';
	if ( imageProcessingFeatures.includes( feature ) ) {
		tab = 'image_processing';
	}
	cy.visitFeatureSettings( `${ tab }/${ feature }` );
	cy.wait( 100 );
	cy.openUserPermissionsPanel();
	cy.get( '.settings-allowed-roles input#administrator' ).check();
	cy.get( '.classifai-settings__user-based-opt-out input' ).check();

	cy.saveFeatureSettings();
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
		'Automatically tag content on update'
	).should( shouldExist );
} );

/**
 * Verify that the excerpt generation feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyModerationEnabled', ( enabled = true ) => {
	const shouldExist = enabled ? 'exist' : 'not.exist';

	cy.visit( '/wp-admin/edit-comments.php' );

	cy.get( '#bulk-action-selector-top option:contains(Moderate)' ).should(
		shouldExist
	);
	cy.get( '#moderation_flagged' ).should( shouldExist );
	cy.get( '#moderation_flags' ).should( shouldExist );
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

	// Find and open the excerpt panel.
	const panelButtonSelector = `.editor-post-excerpt__dropdown button`;

	cy.get( panelButtonSelector ).then( ( $panelButton ) => {
		// Open panel.
		cy.wrap( $panelButton ).click();

		if ( enabled ) {
			cy.get( '.classifai-excerpt-generation button' ).should( shouldExist );
		} else {
			cy.get( '.classifai-excerpt-generation button' ).should( 'not.exist' );
		}
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
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.classifai-panel' ).length ) {
			$body.find( '.classifai-panel' ).click();
		}
	} );
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
	const panelButtonSelector = `.components-panel__body.edit-post-post-status .components-panel__body-title button,.editor-sidebar__panel .editor-post-panel__section .editor-post-card-panel`;

	cy.get( panelButtonSelector ).then( ( $panelButton ) => {
		// Support pre WP 6.6+.
		const $newPanel = $panelButton.parents( '.editor-post-panel__section' );

		if ( $newPanel.length === 0 ) {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Verify button either exists or doesn't.
			cy.wrap( $panel )
				.find( '.classifai-post-status button.title' )
				.should( shouldExist );
		} else {
			// Verify button either exists or doesn't.
			cy.wrap( $newPanel )
				.find( '.classifai-post-status button.title' )
				.should( shouldExist );
		}
	} );
} );

/**
 * Verify that the content generation feature is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 */
Cypress.Commands.add( 'verifyContentGenerationEnabled', ( enabled = true ) => {
	cy.visit( '/wp-admin/edit.php' );
	cy.get( '#the-list tr:nth-child(1) td.title a.row-title' ).click();

	// Find and open the summary panel.
	cy.closeWelcomeGuide();

	if ( enabled ) {
		cy.get( '.classifai-chat-button' ).first().click( { force: true } );
		cy.get( '.classifai-chat-ui' ).should( 'exist' );
	} else {
		cy.get( '.classifai-chat-button' ).should( 'not.exist' );
	}

	cy.visit( '/wp-admin/index.php' );
	if ( enabled ) {
		cy.get( '#classifai-generate-content' ).should( 'exist' );
	} else {
		cy.get( '#classifai-generate-content' ).should( 'not.exist' );
	}
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
	const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("Featured image"),.editor-sidebar__panel .editor-post-panel__section .editor-post-featured-image`;

	cy.get( panelButtonSelector ).then( ( $panelButton ) => {
		// Support pre WP 6.6+.
		const $newPanel = $panelButton.parents( '.editor-post-panel__section' );

		if ( $newPanel.length === 0 ) {
			// Find the panel container.
			const $panel = $panelButton.parents( '.components-panel__body' );

			// Open panel.
			if ( ! $panel.hasClass( 'is-opened' ) ) {
				cy.wrap( $panelButton ).click();
			}

			// Click to open media modal.
			cy.wrap( $panel )
				.find( '.editor-post-featured-image__toggle' )
				.click();
		} else {
			cy.wrap( $newPanel )
				.find(
					'.editor-post-featured-image .editor-post-featured-image__container button'
				)
				.click();
		}

		// Verify tab doesn't exist.
		cy.get( '#menu-item-generate' ).should( shouldExist );
	} );
} );

/**
 * Verify that the AI Vision features is enabled or disabled.
 *
 * @param {boolean} enabled Whether the feature should be enabled or disabled.
 * @param {Object}  options Options to pass to the command.
 */
Cypress.Commands.add(
	'verifyAIVisionEnabled',
	( enabled = true, options = {} ) => {
		const shouldExist = enabled ? 'exist' : 'not.exist';
		// Verify with Image processing features in attachment metabox.
		cy.visit( options.imageEditLink );
		cy.get(
			'#classifai_image_processing label[for=rescan-captions]'
		).should( shouldExist );
		cy.get( '#classifai_image_processing label[for=rescan-tags]' ).should(
			shouldExist
		);
		cy.get( '#classifai_image_processing label[for=rescan-ocr]' ).should(
			shouldExist
		);
		cy.get(
			'#classifai_image_processing label[for=rescan-smart-crop]'
		).should( shouldExist );

		// Verify with Image processing features in media model.
		cy.visit( options.mediaModelLink );
		cy.get( '.media-modal' ).should( 'exist' );
		cy.get( '#classifai-rescan-alt-tags' ).should( shouldExist );
		cy.get( '#classifai-rescan-image-tags' ).should( shouldExist );
		cy.get( '#classifai-rescan-smart-crop' ).should( shouldExist );
		cy.get( '#classifai-rescan-ocr' ).should( shouldExist );
	}
);

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

/**
 * Select feature Provider.
 */
Cypress.Commands.add( 'selectProvider', ( provider ) => {
	cy.get( '#classifai-logo' ).should( 'exist' );
	cy.get( '.classifai-loading-settings' ).should( 'not.exist' );
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.classifai-settings-edit-provider' ).length > 0 ) {
			cy.get( '.classifai-settings-edit-provider' ).click();
		}
	} );
	cy.get( '.classifai-provider-select select' ).select( provider );
} );

/**
 * Save the feature settings.
 */
Cypress.Commands.add( 'saveFeatureSettings', () => {
	cy.intercept( 'POST', '/wp-json/classifai/v1/settings/*' ).as(
		'saveSettings'
	);
	cy.get( '.classifai-settings-footer button.save-settings-button' ).click();
	cy.wait( '@saveSettings' );
} );

/**
 * Save general settings.
 */
Cypress.Commands.add( 'saveGeneralSettings', () => {
	cy.intercept( 'POST', '/wp-json/classifai/v1/registration/*' ).as(
		'saveSettings'
	);
	cy.get( '.classifai-settings-footer button.is-primary' ).click();
	cy.wait( '@saveSettings' );
} );

/**
 * Enable Feature.
 */
Cypress.Commands.add( 'enableFeature', ( disableCredentialReuseModal = true ) => {
	if ( disableCredentialReuseModal ) {
		cy.disableCredentialReuseModal();
	}
	cy.get( '.classifai-enable-feature-toggle input[type="checkbox"]' ).check();
} );

/**
 * Disable Feature.
 */
Cypress.Commands.add( 'disableFeature', () => {
	cy.get(
		'.classifai-enable-feature-toggle input[type="checkbox"]'
	).uncheck();
} );

/**
 * Activate the ElasticPress plugin.
 */
Cypress.Commands.add( 'enableElasticPress', () => {
	cy.visit( '/wp-admin/plugins.php' );
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '#activate-elasticpress' ).length > 0 ) {
			cy.get( '#activate-elasticpress' ).click();
		}
	} );
} );

/**
 * Deactivate the Classic Editor plugin.
 */
Cypress.Commands.add( 'disableElasticPress', () => {
	cy.visit( '/wp-admin/plugins.php' );
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '#deactivate-elasticpress' ).length > 0 ) {
			cy.get( '#deactivate-elasticpress' ).click();
		}
	} );
} );

/**
 * Visit the settings page for a feature.
 */
Cypress.Commands.add( 'visitFeatureSettings', ( featurePath ) => {
	cy.visit( `/wp-admin/tools.php?page=classifai#/${ featurePath }` );
	if ( ! featurePath.includes( 'feature_smart_404' ) ) {
		cy.get( '.components-panel__header h2' ).should( 'exist' );
	}
} );

Cypress.Commands.add( 'openUserPermissionsPanel', () => {
	cy.get(
		'.components-panel__body.classifai-settings__user-permissions button'
	).then( ( $panelButton ) => {
		// Find the panel container.
		const $panel = $panelButton.parents( '.components-panel__body' );

		// Open panel.
		if ( ! $panel.hasClass( 'is-opened' ) ) {
			cy.wrap( $panelButton ).click();
		}
	} );
} );

Cypress.Commands.add( 'allowFeatureToAdmin', () => {
	cy.openUserPermissionsPanel();
	cy.get( '.settings-allowed-roles input#administrator' ).check();
} );

/**
 * Custom insertBlock command until upstream changes are in place.
 */
Cypress.Commands.add( 'insertBlockCustom', ( type, name ) => {
	const [ namespace = '', ...blockNameRest ] = type.split( '/' );
	let blockNames = [
		blockNameRest.join( '/' ).replace( /\//g, '-' ),
		blockNameRest.join( '/' ).replace( /\//g, String.raw`\/` ),
	];

	blockNames = blockNames.filter( ( x, i, a ) => a.indexOf( x ) === i );

	let inserterBtn;
	let search = '';

	if ( typeof name === 'string' && name.length ) {
		search = name;
	} else {
		search = type;
	}

	// Start of block inserter toggle button click logic.
	cy.get( 'body' ).then( ( $body ) => {
		const selectors = [
			'button[aria-label="Add block"]', // 5.7
			'button[aria-label="Toggle block inserter"]', // 6.4
			'button[aria-label="Block Inserter"]', // 6.8
		];

		selectors.forEach( ( selector ) => {
			if ( $body.find( selector ).length ) {
				cy.get( selector ).then( ( $button ) => {
					if ( $button.length ) {
						inserterBtn = cy.wrap( $button );
						inserterBtn.first().click();
					}
				} );
			}
		} );
	} );
	// End of block inserter toggle button click logic.

	// Start of Block tab click logic.
	cy.get( 'button[role="tab"]' )
		.contains( 'Blocks' )
		.then( ( $tab ) => {
			if ( $tab.length ) {
				cy.wrap( $tab ).click();
			}
		} );
	// End of Block tab click logic.

	// Start of Block search logic.
	cy.get( 'input[placeholder="Search"]' ).then( ( $input ) => {
		if ( $input.length ) {
			cy.wrap( $input ).type( search );
		}
	} );
	// End of Block search logic.

	blockNames.forEach( ( blockName ) => {
		const blockSelector = `.editor-block-list-item-${
			'core' === namespace ? '' : namespace + '-'
		}${ blockName }`;

		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( blockSelector ).length ) {
				// Start of Block insertion by click logic.
				cy.get( blockSelector ).then( ( $block ) => {
					if ( $block.length ) {
						cy.wrap( $block ).click();
						inserterBtn.click();

						const [ ns, rest ] = type.split( '/' );

						cy.get( 'body' ).then( ( $body ) => {
							if (
								$body.find( 'iframe[name="editor-canvas"]' )
									.length
							) {
								// Works with WP 6.4
								getIframe(
									'iframe[name="editor-canvas"]'
								).then( ( $iframe ) => {
									const blockInIframe = $iframe.find(
										`.wp-block[data-type="${ ns }/${ rest }"]`
									);
									if ( blockInIframe.length > 0 ) {
										cy.wrap(
											blockInIframe.last().prop( 'id' )
										);
									}
								} );
							} else if (
								$body.find(
									`.wp-block[data-type="${ ns }/${ rest }"]`
								).length
							) {
								// Works with WP 5.7
								cy.get(
									`.wp-block[data-type="${ ns }/${ rest }"]`
								).then( ( $blockInEditor ) => {
									expect( $blockInEditor.length ).to.equal(
										1
									);
									cy.wrap( $blockInEditor.prop( 'id' ) );
								} );
							} else {
								throw new Error(
									`${ ns }/${ rest } not found.`
								);
							}
						} );
					}
				} );
				// End of Block insertion by click logic.
			}
		} );
	} );
} );

/**
 * Activate WooCommerce plugin.
 */
Cypress.Commands.add( 'activateWooCommerce', () => {
	cy.visit( '/wp-admin/plugins.php' );
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '#activate-woocommerce' ).length > 0 ) {
			cy.get( '#activate-woocommerce' ).click();
		}
	} );
} );

/**
 * Deactivate WooCommerce plugin.
 */
Cypress.Commands.add( 'deactivateWooCommerce', () => {
	cy.visit( '/wp-admin/plugins.php' );
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '#deactivate-woocommerce' ).length > 0 ) {
			cy.get( '#deactivate-woocommerce' ).click();
		}
	} );
} );

/**
 * Create a product in the block editor.
 */
Cypress.Commands.add( 'createProduct', ( { title, content } ) => {
	cy.visit( '/wp-admin/post-new.php?post_type=product' );
	cy.get( '.editor-post-title__input' ).type( title );
	cy.get( '.block-editor-rich-text__editable' ).type( content );
} );

/**
 * Create a product in the classic editor.
 */
Cypress.Commands.add( 'classicCreateProduct', ( { title, content } ) => {
	cy.visit( '/wp-admin/post-new.php?post_type=product' );
	cy.get( '#title' ).type( title );
	cy.get( '#content' ).type( content );
} );

/**
 * Disable credential reuse modal for all tests.
 * This prevents the modal from appearing when enabling features.
 */
Cypress.Commands.add( 'disableCredentialReuseModal', () => {
	cy.window().then( ( win ) => {
		win.localStorage.setItem( 'classifai_dont_ask_credential_reuse', 'true' );
	} );
} );

/**
 * Enable credential reuse modal (for testing the modal itself).
 * This allows the modal to appear when enabling features.
 */
Cypress.Commands.add( 'enableCredentialReuseModal', () => {
	cy.window().then( ( win ) => {
		win.localStorage.removeItem( 'classifai_dont_ask_credential_reuse' );
	} );
} );

/**
 * Disable feature if already enabled.
 */
Cypress.Commands.add( 'disableFeatureIfEnabled', () => {
	cy.get( '.components-form-toggle__input' ).then( ( $toggle ) => {
		if ( $toggle.is( ':checked' ) ) {
			cy.wrap( $toggle ).click();
		}
	} );
} );
