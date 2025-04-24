/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getChatGPTData } from '../../plugins/functions';

describe( '[Language processing] WooCommerce Product Excerpt Generation Tests', () => {
	before( () => {
		cy.login();
		cy.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		cy.get( '#classifai-logo' ).should( 'exist' );
		cy.enableFeature();
		cy.get( '.settings-allowed-post-types input#post' ).check();
		cy.saveFeatureSettings();
		cy.optInAllFeatures();
		cy.installWooCommerce();
		cy.activateWooCommerce();
	} );

    beforeEach( () => {
		cy.login();
	} );

	after( () => {
		cy.deactivateWooCommerce();
	} );

	it( 'Enable OpenAI ChatGPT "Language Processing" excerpt settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_excerpt_generation'
		);
		cy.selectProvider( 'openai_chatgpt' );
		cy.get( '#openai_chatgpt_api_key' ).clear().type( 'password' );

		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.get( '#excerpt_length' ).clear().type( 35 );
		cy.saveFeatureSettings();
	} );

	it( 'Can generate and insert product short description (Classic Editor)', () => {
		cy.enableClassicEditor();

		const data = getChatGPTData();

		// Create test product
		cy.classicCreateProduct( {
			title: 'Excerpt test classic',
			content: 'Test ChatGPT content.',
		} );

		// Ensure excerpt metabox is shown
		cy.get( '#show-settings-link' ).click();
		cy.get( '#postexcerpt-hide' ).check( { force: true } );

		// Verify button exists
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).should( 'exist' );

		// Click on button and verify data loads in
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).click();
		
		// Check both TinyMCE and textarea
		cy.window().then( ( win ) => {
			if ( win.tinyMCE && win.tinyMCE.get( 'excerpt' ) ) {
				cy.wrap( win.tinyMCE.get( 'excerpt' ).getContent() ).should( 'eq', data );
			} else {
				cy.get( '#excerpt' ).should( 'have.value', data );
			}
		} );

		cy.disableClassicEditor();
	} );
} );
