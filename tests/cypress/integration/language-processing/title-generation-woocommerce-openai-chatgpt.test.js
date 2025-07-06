/**
 * Internal dependencies
 */
import { getChatGPTData } from '../../plugins/functions';

describe( '[Language processing] WooCommerce Product Excerpt Generation Tests', () => {
	before( () => {
		cy.login();
		cy.optInAllFeatures();
	} );

	beforeEach( () => {
		cy.login();
	} );

	it( 'Enable OpenAI ChatGPT "Language Processing" title settings', () => {
		cy.visitFeatureSettings(
			'language_processing/feature_title_generation'
		);
		cy.selectProvider( 'openai_chatgpt' );
		cy.get( '#openai_chatgpt_api_key' ).clear().type( 'password' );
		cy.enableFeature();
		cy.allowFeatureToAdmin();
		cy.get( '#openai_chatgpt_number_of_suggestions' ).type( 1 );
		cy.saveFeatureSettings();
	} );

	it( 'Can generate and insert product title (Classic Editor)', () => {
		cy.activateWooCommerce();
		cy.enableClassicEditor();

		const data = getChatGPTData();

		// Create test product and wait for page load
		cy.visit( '/wp-admin/post-new.php?post_type=product' );

		// Wait for the page to be fully loaded and initialized
		cy.get( '#title' ).should( 'be.visible' );
		cy.get( '#content_ifr' ).should( 'exist' );

		cy.get( '#classifai-title-generation__title-generate-btn' ).click();
		cy.get( '#classifai-title-generation__modal' ).should( 'be.visible' );
		cy.get( '.classifai-title-generation__result-item' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );

		cy.get( '.classifai-title-generation__select-title' ).first().click();
		cy.get( '#classifai-title-generation__modal' ).should(
			'not.be.visible'
		);
		cy.get( '#title' ).should( 'have.value', data );

		cy.deactivateWooCommerce();
		cy.disableClassicEditor();
	} );
} );
