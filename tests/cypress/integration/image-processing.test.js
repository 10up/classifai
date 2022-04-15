describe('Image processing Tests', () => {
	before(() => {
		cy.login();
	});

	it('Can save "Image Processing" settings', () => {
		cy.visit('/wp-admin/admin.php?page=image_processing');

		cy.get('#classifai-settings-url').clear().type('http://image-processing.test');
		cy.get('#classifai-settings-api_key').clear().type('password');
		cy.get('#submit').click();

		cy.get('.notice').contains('Settings saved.');
	});
});
