/* eslint jest/expect-expect: 0 */

import { getPDFData } from '../plugins/functions';

describe('PDF read Tests', () => {
	before(() => {
		cy.login();
	});

	it('Can save "PDF scanning" settings', () => {
		cy.visit('/wp-admin/admin.php?page=image_processing');

		cy.get('#url')
			.clear()
			.type('http://e2e-test-image-processing.test');
		cy.get('#api_key').clear().type('password');
		cy.get('#enable_read_pdf').check();
		cy.get('#submit').click();

		cy.get('.notice').contains('Settings saved.');
	});

	it('Can see PDF scanning actions on edit media page and verify PDF read data.', () => {
		cy.visit('/wp-admin/media-new.php');
		cy.get('#plupload-upload-ui').should('exist');
		cy.get('#plupload-upload-ui input[type=file]').attachFile('dummy.pdf');

		cy.get('#media-items .media-item a.edit-attachment').should('exist');
		cy.get('#media-items .media-item a.edit-attachment')
			.invoke('attr', 'href')
			.then((editLink) => {
				cy.visit(editLink);
			});

		// Verify Metabox with Image processing actions.
		cy.get('.postbox-header h2, #attachment_meta_box h2')
			.first()
			.contains('ClassifAI PDF Processing');
		cy.get('.misc-publishing-actions label[for=rescan-pdf]').contains(
			'Rescan PDF for text'
		);

		// Verify generated Data.
		cy.get('#attachment_content').should('have.value', getPDFData());
	});
});
