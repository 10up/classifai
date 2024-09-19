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
		'post-audio-controls': [ './src/js/post-audio-controls.js' ],
		'recommended-content-block': [
			'./includes/Classifai/Blocks/recommended-content-block/index.js',
		],
		'recommended-content-block-frontend': [
			'./includes/Classifai/Blocks/recommended-content-block/frontend.js',
		],
		'media-modal': [ './src/js/media-modal/index.js' ],
		'inserter-media-category': [
			'./src/js/gutenberg-plugins/inserter-media-category.js',
		],
		'generate-excerpt-classic': [
			'./src/js/openai/classic-editor-excerpt-generator.js',
		],
		'generate-title-classic': [
			'./src/js/openai/classic-editor-title-generator.js',
		],
		commands: [ './src/js/gutenberg-plugins/commands.js' ],
		'generate-image-media-upload': [
			'./src/js/media-modal/views/generate-image-media-upload.js',
		],
		'extend-image-blocks': './src/js/extend-image-block-generate-image.js',

		'classifai-plugin-classification': './src/js/plugins/classification/index.js',
		'classifai-plugin-fill': './src/js/plugins/slot-fill/index.js',
		'classifai-plugin-text-to-speech': './src/js/plugins/text-to-speech/index.js',
		'classifai-plugin-content-resizing': './src/js/plugins/content-resizing/index.js',
		'classifai-plugin-title-generation': './src/js/plugins/title-generation/index.js',
		'classifai-plugin-excerpt-generation': './src/js/plugins/excerpt-generation/index.js',
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
