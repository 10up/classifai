/**
 * Extends the @wordpress/e2e-test-utils-playwright `test` with a ClassifAI
 * specific fixture exposing helpers that mirror the Cypress commands we used
 * historically (`cy.visitFeatureSettings`, `cy.enableFeature`, etc.).
 */
// @ts-expect-error The package export points to missing src files, so load the published build.
import { test as base, expect } from '../../../node_modules/@wordpress/e2e-test-utils-playwright/build/index.js';
import { ClassifAIUtils } from './classifai-utils';

const test = base.extend< { classifaiUtils: ClassifAIUtils } >( {
	classifaiUtils: async (
		{ page, admin, editor, requestUtils }: any,
		use: ( fixture: ClassifAIUtils ) => Promise< void >
	) => {
		await use(
			new ClassifAIUtils( { page, admin, editor, requestUtils } )
		);
	},
} );

export { test, expect };
