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

Cypress.Commands.add( 'verifyPostTaxonomyTerms', ( taxonomy, threshold ) => {

	const panelTitle = `Watson ${taxonomy.charAt( 0 ).toUpperCase() + taxonomy.slice( 1 ) }`;
	const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("${panelTitle}")`;
	const terms = getNLUData( taxonomy, threshold );
	const taxonomySelector = 'span.components-form-token-field__token-text span[aria-hidden="true"]';

	// Open Panel
	cy.get( panelButtonSelector ).then( $button => {
		// Find the panel container
		const $panel = $button.parents( '.components-panel__body' );

		// Close Panel.
		if ( ! $panel.hasClass( 'is-opened' ) ) {
			cy.wrap( $button ).click();
		}

		// Compare taxonomy terms with test data terms.
		cy.wrap( $panel ).find( taxonomySelector ).should( 'exist' );
		cy.wrap( $panel ).find( taxonomySelector )
			.each( ( term ) => {
				return expect( term.text() ).to.be.oneOf( terms );
			} )
			.then( postTerms => {
				expect( postTerms ).to.have.length( terms.length );
			} );

		// Close Panel.
		cy.wrap( $button ).click();
	} );
} );
