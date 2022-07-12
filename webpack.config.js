const path = require('path');

module.exports = {
	entry: {
		'editor': './src/js/editor.js',
		'editor-ocr': './src/js/editor-ocr.js',
		'media': './src/js/media.js',
		'admin': './src/js/admin.js',
		'gutenberg-plugin': './src/js/gutenberg-plugin.js'
	},
	output: {
		filename: '[name].min.js',
		path: path.resolve( './dist/js')
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /(node_modules)/,
				use: {
					loader: 'babel-loader'
				},
			},
			{
				test: /\.js$/,
				exclude: /(node_modules)/,
				loader: 'eslint-loader',
				query: {
					configFile: './.eslintrc.json'
				}
			},
			{
				test: /\.svg$/,
				use: [{
					loader: 'svg-react-loader'
				}]
			},
		],
	},
	externals: {
		react: 'React'
	},
};
