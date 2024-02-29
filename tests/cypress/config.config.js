const { defineConfig } = require( 'cypress' );

module.exports = defineConfig( {
	viewportWidth: 1280,
	viewportHeight: 1280,
	chromeWebSecurity: false,
	fixturesFolder: __dirname + '/fixtures',
	screenshotsFolder: __dirname + '/screenshots',
	videosFolder: __dirname + '/videos',
	downloadsFolder: __dirname + '/downloads',
	video: false,
	reporter: 'mochawesome',
	reporterOptions: {
		reportFilename: 'mochawesome-[name]',
		reportDir: 'tests/cypress/reports',
		overwrite: false,
		html: false,
		json: true,
	},
	retries: {
		runMode: 2,
		openMode: 0,
	},
	e2e: {
		// We've imported your old cypress plugins here.
		// You may want to clean this up later by importing these.
		setupNodeEvents( on, config ) {
			return require( __dirname + '/plugins/index.js' )( on, config );
		},
		specPattern: __dirname + '/integration/**/*.test.{js,jsx,ts,tsx}',
		supportFile: __dirname + '/support/index.js',
	},
} );
