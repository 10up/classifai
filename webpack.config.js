const path = require('path');

module.exports = {
	entry: {
		'editor': './src/js/editor.js',
		'editor-ocr': './src/js/editor-ocr.js',
		'media': './src/js/media.js',
		'admin': './src/js/admin.js'
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
			}
		],
	}
};
