const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'dist' ),
	},
	entry: {
		editor: [ './src/js/editor.js' ],
		'editor-ocr': [ './src/js/editor-ocr.js' ],
		media: [ './src/js/media.js' ],
		admin: [ './src/js/admin.js' ],
		'language-processing': [ './src/js/language-processing.js' ],
		'gutenberg-plugin': [ './src/js/gutenberg-plugin.js' ],
		'post-audio-controls': [ './src/js/post-audio-controls.js' ],
		'post-status-info': [
			'./src/js/gutenberg-plugins/post-status-info.js',
		],
		'recommended-content-block': [
			'./includes/Classifai/Blocks/recommended-content-block/index.js',
		],
		'recommended-content-block-frontend': [
			'./includes/Classifai/Blocks/recommended-content-block/frontend.js',
		],
		'post-excerpt': [ './src/js/post-excerpt/index.js' ],
		'media-modal': [ './src/js/media-modal/index.js' ],
		'generate-title-classic': [ './src/js/openai/classic-editor-title-generator.js' ],
	},
	module: {
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.svg$/,
				use: [
					{
						loader: 'svg-react-loader',
					},
				],
			},
		],
	},
	externals: {
		react: 'React',
	},
};
