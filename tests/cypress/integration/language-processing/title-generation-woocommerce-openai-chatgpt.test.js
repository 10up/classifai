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
		cy.enableClassicEditor();

		const data = getChatGPTData();

		// Create test product
		cy.classicCreateProduct( {
			title: 'Test ChatGPT generate titles',
			content: 'Test product content',
		} );

		cy.visit( '/wp-admin/post-new.php?post_type=product' );

		cy.get( '#classifai-title-generation__title-generate-btn' ).click();
		cy.get( '#classifai-title-generation__modal' ).should( 'be.visible' );
		cy.get( '.classifai-title-generation__result-item' )
			.first()
			.find( 'textarea' )
			.should( 'have.value', data );

		cy.get( '.classifai-title-generation__select-title' ).first().click();
		cy.get( '#classifai-title-generation__modal' ).should( 'not.be.visible' );
		cy.get( '#title' ).should( 'have.value', data );
	} );
} );
