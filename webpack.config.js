const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		path: path.resolve(process.cwd(), 'dist'),
	},
	entry: {
		editor: path.resolve(process.cwd(), 'src/js', 'editor.js'),
		'editor-ocr': path.resolve(process.cwd(), 'src/js', 'editor-ocr.js'),
		media: path.resolve(process.cwd(), 'src/js', 'media.js'),
		admin: path.resolve(process.cwd(), 'src/js', 'admin.js'),
		'recommended-content-block': path.resolve(
			process.cwd(),
			'includes/Classifai/Blocks/recommended-content-block',
			'index.js'
		),
		'recommended-content-block-frontend': path.resolve(
			process.cwd(),
			'includes/Classifai/Blocks/recommended-content-block',
			'frontend.js'
		),
	},
};
