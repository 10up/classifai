const { defineConfig } = require( 'cypress' );

module.exports = defineConfig( {
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
		specPattern: [
			__dirname + '/integration/admin.test.js',
			__dirname + '/integration/language-processing.test.js',
			__dirname + '/integration/image-processing.test.js',
			__dirname + '/integration/pdf-reading.test.js',
			__dirname + '/integration/text-to-speach.test.js',
		],
		supportFile: __dirname + '/support/index.js',
	},
} );
