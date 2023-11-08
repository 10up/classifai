/* eslint jest/expect-expect: 0 */

describe('Admin can login and make sure plugin is activated', () => {
	beforeEach( () => {
		cy.login();
	} );

	it('Can deactivate and activate plugin', () => {
		cy.deactivatePlugin('classifai');
		cy.activatePlugin('classifai');
	});

	it('Can see "ClassifAI" menu and Can visit "ClassifAI" settings page.', () => {
		cy.visit('/wp-admin/');

		// Check ClassifAI menu.
		cy.get('#adminmenu li#menu-tools ul.wp-submenu li').contains(
			'ClassifAI'
		);

		// Check Heading
		cy.visit('/wp-admin/tools.php?page=classifai');
		cy.get('#wpbody h2').contains('ClassifAI Settings');
		cy.get('label[for="email"]').contains('Registered Email');
		cy.get('label[for="license_key"]').contains('Registration Key');
	});

	it('Can visit "Language Processing" settings page.', () => {
		// Check Heading
		cy.visit('/wp-admin/tools.php?page=classifai&tab=language_processing');
		cy.get('#wpbody h2').contains('Language Processing');
	});

	it('Can see "Image Processing" menu and Can visit "Image Processing" settings page.', () => {
		// Check Heading
		cy.visit('/wp-admin/tools.php?page=classifai&tab=image_processing');
		cy.get('#wpbody h2').contains('Image Processing');
	});
});
