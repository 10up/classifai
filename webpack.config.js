const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'dist'),
	},
	entry: {
		editor: [ './src/js/editor.js'],
		'editor-ocr': [ './src/js/editor-ocr.js'],
		media: [ './src/js/media.js'],
		admin: [ './src/js/admin.js'],
		'gutenberg-plugin': [ './src/js/gutenberg-plugin.js'],
		'recommended-content-block': [ './includes/Classifai/Blocks/recommended-content-block/index.js' ],
		'recommended-content-block-frontend': [ './includes/Classifai/Blocks/recommended-content-block/frontend.js' ],
	},
	module: {
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.svg$/,
				use: [{
					loader: 'svg-react-loader'
				}]
			}
		],
	},
	externals: {
		react: 'React'
	},
};