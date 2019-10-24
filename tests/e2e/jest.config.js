const path = require( 'path' );

module.exports = {
	preset: 'jest-puppeteer',
	setupFilesAfterEnv: [
		'<rootDir>/config/bootstrap.js',
		'expect-puppeteer',
	],
	transform: {
		'^.+\\.[jt]sx?$': path.join( __dirname, 'babel-transform' ),
	},
	transformIgnorePatterns: [
		'node_modules',
	],
	testPathIgnorePatterns: [
		'.git',
		'node_modules',
	],
};
