/**
 * External dependencies
 */
import { execFileSync } from 'child_process';
import { request } from '@playwright/test';
import type { FullConfig } from '@playwright/test';

/**
 * WordPress dependencies
 */
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

async function globalSetup( config: FullConfig ) {
	const { storageState, baseURL } = config.projects[ 0 ].use;
	const storageStatePath =
		typeof storageState === 'string' ? storageState : undefined;

	const requestContext = await request.newContext( {
		baseURL,
	} );

	const requestUtils = new RequestUtils( requestContext, {
		storageStatePath,
	} );

	// Authenticate and save the storageState to disk.
	await requestUtils.setupRest();

	// Reset ClassifAI feature options so each test run starts with default
	// provider settings, prompts, role permissions, etc.
	try {
		execFileSync(
			'npx',
			[
				'wp-env',
				'run',
				'tests-cli',
				'wp',
				'db',
				'query',
				"DELETE FROM wp_options WHERE option_name LIKE 'classifai_feature_%' OR option_name = 'classifai_settings'",
			],
			{ stdio: 'inherit' }
		);
	} catch ( err ) {
		// eslint-disable-next-line no-console
		console.warn( 'Failed to reset classifai options:', err );
	}

	// Force Classic Editor (when activated) to use the classic editor for all
	// posts. Without this the plugin defers to a per-user "block editor"
	// preference and `#content_ifr` never renders in our Classic Editor tests.
	try {
		execFileSync(
			'npx',
			[
				'wp-env',
				'run',
				'tests-cli',
				'wp',
				'option',
				'update',
				'classic-editor-replace',
				'classic',
			],
			{ stdio: 'inherit' }
		);
	} catch ( err ) {
		// eslint-disable-next-line no-console
		console.warn( 'Failed to set classic-editor-replace option:', err );
	}

	// Reset the test environment before running the tests.
	await Promise.all( [
		requestUtils.activateTheme( 'twentytwentyfive' ),
		requestUtils.activatePlugin( 'classifai-e2e-test-request-mock-plugin' ),
		requestUtils.deleteAllPosts(),
		requestUtils.deleteAllBlocks(),
		requestUtils.resetPreferences(),
	] );

	await requestContext.dispose();
}

export default globalSetup;
