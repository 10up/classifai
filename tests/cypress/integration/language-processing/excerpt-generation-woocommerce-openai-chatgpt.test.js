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

		const expectedResponse = 'Hello there, how may I assist you today?';

		// Create test product and wait for page load
		cy.visit( '/wp-admin/post-new.php?post_type=product' );

		// Ensure excerpt metabox is shown
		cy.get( '#show-settings-link' ).click();
		cy.get( '#postexcerpt-hide' ).check( { force: true } );

		// Verify button exists
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).should( 'exist' );

		// Click on button and wait for excerpt to be populated
		cy.get( '#classifai-excerpt-generation__excerpt-generate-btn' ).click();
		
		// Check both TinyMCE and textarea with retries
		cy.window().then( ( win ) => {
			if ( win.tinyMCE && win.tinyMCE.get( 'excerpt' ) ) {
				// Wait for content to be populated with retries
				cy.wrap( null, { timeout: 10000 } ).should(() => {
					const content = win.tinyMCE.get( 'excerpt' ).getContent();
					expect(content.replace(/<\/?p>/g, '')).to.equal(expectedResponse);
				});
			} else {
				// Wait for content to be populated with retries
				cy.get( '#excerpt', { timeout: 10000 } ).should('have.value', expectedResponse);
			}
		} );

		cy.disableClassicEditor();
	} );
} );
