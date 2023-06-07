/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

const { loadConfig } = require( '@wordpress/env/lib/config' );
const getCacheDirectory = require( '@wordpress/env/lib/config/get-cache-directory' );

/**
 * Start Cypress.
 *
 * @param {Function}             on     function which used to register listeners on events.
 * @param {Cypress.PluginConfig} config Cypress Configuration.
 * @return {Cypress.PluginConfig} config.
 */
module.exports = async (on, config) => {
	const cacheDirectory = await getCacheDirectory();
	const wpEnvConfig = await loadConfig( cacheDirectory );

	if (wpEnvConfig) {
		const port = wpEnvConfig.env.tests.port || null;

		if (port) {
			config.baseUrl = wpEnvConfig.env.tests.config.WP_SITEURL;
		}
	}

	return config;
};
