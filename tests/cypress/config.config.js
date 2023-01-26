const { defineConfig } = require('cypress')

module.exports = defineConfig({
  fixturesFolder: __dirname+'/fixtures',
  screenshotsFolder: __dirname+'/screenshots',
  videosFolder: __dirname+'/videos',
  downloadsFolder: __dirname+'/downloads',
  video: false,
  e2e: {
    // We've imported your old cypress plugins here.
    // You may want to clean this up later by importing these.
    setupNodeEvents(on, config) {
      return require(__dirname+'/plugins/index.js')(on, config)
    },
    specPattern: __dirname+'/integration/**/*.test.{js,jsx,ts,tsx}',
    supportFile: __dirname+'/support/index.js',
  },
})
