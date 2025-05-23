const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'dist' ),
	},
	entry: {
		admin: [ './src/js/admin.js' ],
		'key-takeaways-block': [
			'./includes/Classifai/Blocks/key-takeaways/index.js',
		],
		'recommended-content-block': [
			'./includes/Classifai/Blocks/recommended-content-block/index.js',
		],
		'recommended-content-block-frontend': [
			'./includes/Classifai/Blocks/recommended-content-block/frontend.js',
		],
		'recommended-content-block-variation': [
			'./src/js/features/recommended-content/variation.js',
		],

		'classifai-plugin-media-processing': './src/js/features/media-processing/media-upload.js',
		'classifai-plugin-editor-ocr': './src/js/features/media-processing/editor-ocr.js',
		'classifai-plugin-commands': './src/js/features/commands.js',
		'classifai-plugin-classification': './src/js/features/classification/index.js',
		'classifai-plugin-classification-previewer': './src/js/features/classification/previewer.js',
		'classifai-plugin-classification-ibm-watson': './src/js/features/classification/ibm-watson.js',
		'classifai-plugin-fill': './src/js/features/slot-fill/index.js',
		'classifai-plugin-text-to-speech': './src/js/features/text-to-speech/index.js',
		'classifai-plugin-text-to-speech-frontend': './src/js/features/text-to-speech/frontend/index.js',
		'classifai-plugin-content-resizing': './src/js/features/content-resizing/index.js',
		'classifai-plugin-title-generation': './src/js/features/title-generation/index.js',
		'classifai-plugin-classic-title-generation': './src/js/features/title-generation/classic/index.js',
		'classifai-plugin-excerpt-generation': './src/js/features/excerpt-generation/index.js',
		'classifai-plugin-classic-excerpt-generation': './src/js/features/excerpt-generation/classic/index.js',
		'classifai-plugin-content-generation': './src/js/features/content-generation/index.js',
		'classifai-plugin-inserter-media-category': './src/js/features/image-generation/inserter-media-category.js',
		'classifai-plugin-image-generation-media-modal': [
			'./src/js/features/image-generation/media-modal/index.js',
			'./src/js/features/image-generation/extend-image-block-generate-image.js'
		],
		'classifai-plugin-image-generation-generate-image-media-upload': './src/js/features/image-generation/media-modal/views/generate-image-media-upload.js',
		'classifai-plugin-recommended-content-feature-fields': './src/js/features/recommended-content/feature-fields-plugin.js',
		settings: './src/js/settings/index.js',
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
