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

Cypress.Commands.add('verifyPostTaxonomyTerms', (taxonomy, threshold) => {
	const taxonomyTitle = taxonomy.charAt(0).toUpperCase() + taxonomy.slice(1);
	const panelTitle =
		taxonomy === 'tags' ? taxonomyTitle : `Watson ${taxonomyTitle}`;
	const panelButtonSelector = `.components-panel__body .components-panel__body-title button:contains("${panelTitle}")`;
	let terms = [];
	if (taxonomy === 'tags') {
		['categories', 'keywords', 'concepts', 'entities'].forEach((taxo) => {
			terms.push(...getNLUData(taxo, threshold));
		});
	} else {
		terms = getNLUData(taxonomy, threshold);
	}

	const taxonomySelector =
		'span.components-form-token-field__token-text span[aria-hidden="true"]';

	// Open Panel
	cy.get(panelButtonSelector).then(($button) => {
		// Find the panel container
		const $panel = $button.parents('.components-panel__body');

		// Close Panel.
		if (!$panel.hasClass('is-opened')) {
			cy.wrap($button).click();
		}

		// Compare taxonomy terms with test data terms.
		cy.wrap($panel).find(taxonomySelector).should('exist');
		cy.wrap($panel)
			.find(taxonomySelector)
			.each((term) => {
				return expect(term.text()).to.be.oneOf(terms);
			})
			.then((postTerms) => {
				expect(postTerms).to.have.length(terms.length);
			});

		// Close Panel.
		cy.wrap($button).click();
	});
});


Cypress.Commands.add('optOutFeature', (feature) => {
	// Go to profile page and opt out.
	cy.visit('/wp-admin/profile.php');
	cy.get(`#classifai_opted_out_features_${feature}`).check();
	cy.get('#submit').click();
	cy.get('#message.notice').contains('Profile updated.');
});

Cypress.Commands.add('optInFeature', (feature) => {
	// Go to profile page and opt in.
	cy.visit('/wp-admin/profile.php');
	cy.get(`#classifai_opted_out_features_${feature}`).uncheck();
	cy.get('#submit').click();
	cy.get('#message.notice').contains('Profile updated.');
});


Cypress.Commands.add('enableFeatureForRoles', (feature, roles, provider) => {
	cy.visit(
		`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${provider}`
	);
	cy.get(`#${feature}_role_based_access`).check();
	roles.forEach(role => {
		cy.get( `#${provider}_${feature}_roles_${role}` ).check();
	});
	cy.get('#submit').click();
	cy.get('.notice').contains('Settings saved.');
});

Cypress.Commands.add('disableFeatureForRoles', (feature, roles, provider) => {
	cy.visit(
		`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${provider}`
	);
	cy.get(`#${feature}_role_based_access`).check();
	roles.forEach(role => {
		cy.get( `#${provider}_${feature}_roles_${role}` ).uncheck();
	});
	cy.get('#submit').click();
	cy.get('.notice').contains('Settings saved.');
});

Cypress.Commands.add('enableFeatureForUsers', (feature, users, provider) => {
	cy.visit(
		`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${provider}`
	);
	cy.get(`#${feature}_user_based_access`).check();
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.components-form-token-field__remove-token' ).length > 0 ) {
			cy.get('.components-form-token-field__remove-token').click({
				multiple: true,
			});
		}
	} );

	users.forEach(user => {
		cy.get(`#${feature}_users-container input.components-form-token-field__input`).type(user);
		cy.wait( 1000 );
		cy.get('ul.components-form-token-field__suggestions-list li:nth-child(1)').click();
	});
	cy.get('#submit').click();
	cy.get('.notice').contains('Settings saved.');
});


Cypress.Commands.add('enableFeatureOptOut', (feature, provider) => {
	cy.visit(
		`/wp-admin/tools.php?page=classifai&tab=language_processing&provider=${provider}`
	);
	cy.get( `#${feature}_role_based_access` ).check();
	cy.get( `#${provider}_${feature}_roles_administrator` ).check();
	cy.get( `#${feature}_user_based_access` ).uncheck();
	cy.get( `#${feature}_user_based_opt_out` ).check();

	cy.get('#submit').click();
	cy.get('.notice').contains('Settings saved.');
});
