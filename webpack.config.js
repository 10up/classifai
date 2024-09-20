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
		'recommended-content-block': [
			'./includes/Classifai/Blocks/recommended-content-block/index.js',
		],
		'recommended-content-block-frontend': [
			'./includes/Classifai/Blocks/recommended-content-block/frontend.js',
		],
		commands: [ './src/js/gutenberg-plugins/commands.js' ],
		'extend-image-blocks': './src/js/extend-image-block-generate-image.js',

		'classifai-plugin-classification': './src/js/plugins/classification/index.js',
		'classifai-plugin-fill': './src/js/plugins/slot-fill/index.js',
		'classifai-plugin-text-to-speech': './src/js/plugins/text-to-speech/index.js',
		'classifai-plugin-text-to-speech-frontend': './src/js/plugins/text-to-speech/frontend/index.js',
		'classifai-plugin-content-resizing': './src/js/plugins/content-resizing/index.js',
		'classifai-plugin-title-generation': './src/js/plugins/title-generation/index.js',
		'classifai-plugin-classic-title-generation': './src/js/plugins/title-generation/classic/index.js',
		'classifai-plugin-excerpt-generation': './src/js/plugins/excerpt-generation/index.js',
		'classifai-plugin-classic-excerpt-generation': './src/js/plugins/excerpt-generation/classic/index.js',
		'classifai-plugin-inserter-media-category': './src/js/plugins/image-generation/inserter-media-category.js',
		'classifai-plugin-image-generation-media-modal': './src/js/plugins/image-generation/media-modal/index.js',
		'classifai-plugin-image-generation-generate-image-media-upload': './src/js/plugins/image-generation/media-modal/views/generate-image-media-upload.js',
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
