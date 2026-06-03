/**
 * ESLint flat config.
 *
 * @see https://eslint.org/docs/latest/use/configure/configuration-files
 */

// @ts-ignore TS7016
import wordpress from '@wordpress/eslint-plugin';

export default [
	// Global ignores — these directories and config files are skipped entirely.
	{
		ignores: [
			'**/dist/**',
			'**/node_modules/**',
			'**/tests/**',
			'**/vendor/**',
			'**/wp-hooks-docs/**',
			// Config files (ESLint does not auto-ignore flat config files).
			'eslint.config.mjs',
			'webpack.config.js',
		],
		languageOptions: {
			globals: {
				wp: "readonly",
				jQuery: "readonly",
				describe: "readonly",
				before: "readonly",
				beforeEach: "readonly",
				expect: "readonly",
				it: "readonly",
				Cypress: "readonly",
				cy: "readonly",
				ajaxurl: "readonly",
				FormData: "readonly",
				classifaiMediaVars: "readonly",
				classifaiTextToSpeechData: "readonly",
				wpApiSettings: "readonly",
				classifaiDalleData: "readonly",
				Backbone: "readonly",
				_: "readonly",
				File: "readonly",
				Headers: "readonly",
				requestAnimationFrame: "readonly",
				React: "readonly",
				Block: "readonly",
				classifai_term_cleanup_params: "readonly",
				classifAISettings: "readonly",
				classifaiRecommendedContentSettings: "readonly",
			},
		},
		settings: {
			"import/core-modules": [
				"@wordpress/api-fetch",
				"@wordpress/block-editor",
				"@wordpress/block-library",
				"@wordpress/blocks",
				"@wordpress/commands",
				"@wordpress/components",
				"@wordpress/compose",
				"@wordpress/core-data",
				"@wordpress/data",
				"@wordpress/dom-ready",
				"@wordpress/dom",
				"@wordpress/editor",
				"@wordpress/element",
				"@wordpress/hooks",
				"@wordpress/html-entities",
				"@wordpress/i18n",
				"@wordpress/media-utils",
				"@wordpress/notices",
				"@wordpress/plugins",
				"@wordpress/url",
				"@wordpress/wordcount",
				"react-dom",
				"react",
			],
		}
	},

	// Base config.
	...wordpress.configs.recommended,

	// Project-specific customizations applied on top of the WP recommended set.
	{
		name: 'classifai/custom-rules',

		rules: {
			// --- React best practices ---
			'react/jsx-boolean-value': 'error',
			'react/jsx-curly-brace-presence': [
				'error',
				{ props: 'never', children: 'never' },
			],

			// --- WordPress-specific rules (not in recommended) ---
			'@wordpress/dependency-group': 'error',
			'@wordpress/data-no-store-string-literals': 'error',
			'@wordpress/wp-global-usage': 'error',
			'@wordpress/react-no-unsafe-timeout': 'error',

			// Override WP defaults.
			'@wordpress/i18n-text-domain': [
				'error',
				{
					allowedTextDomain: 'classifai',
				},
			],

			// --- Import rules (override WP defaults: warn → error) ---
			'import/default': 'error',
			'import/named': 'error',
			'import/no-extraneous-dependencies': [
				'error',
				{
					devDependencies: [
						'**/*.@(spec|test).@(j|t)s?(x)',
						'**/@(webpack|jest).config.@(j|t)s',
						'**/scripts/**',
						'**/tests/**',
						'.prettierrc.js',
					],
				},
			],

			// --- Restricted imports ---
			'no-restricted-imports': [
				'error',
				{
					paths: [
						{
							name: 'lodash',
							message: 'Please use native functionality instead.',
						},
						{
							name: 'classnames',
							message:
								"Please use `clsx` instead. It's a lighter and faster drop-in replacement for `classnames`.",
						},
						{
							name: 'redux',
							importNames: [ 'combineReducers' ],
							message:
								'Please use `combineReducers` from `@wordpress/data` instead.',
						},
					],
				},
			],

			// --- Restricted syntax ---
			'no-restricted-syntax': [
				'error',
				{
					selector:
						'ImportDeclaration[source.value=/^@wordpress\\u002F.+\\u002F/]',
					message:
						'Path access on WordPress dependencies is not allowed.',
				},
				{
					selector:
						'JSXAttribute[name.name="id"][value.type="Literal"]',
					message:
						'Do not use string literals for IDs; use withInstanceId instead.',
				},
				{
					selector:
						'CallExpression[callee.object.name="Math"][callee.property.name="random"]',
					message:
						"Do not use Math.random() to generate unique IDs; use withInstanceId instead. (If you're not generating unique IDs: ignore this message.)",
				},
			],
		},
	},

	// TypeScript-specific overrides.
	{
		name: 'classifai/typescript-overrides',
		files: [ '**/*.ts?(x)' ],

		rules: {
			'@typescript-eslint/consistent-type-imports': [
				'error',
				{
					prefer: 'type-imports',
					disallowTypeAnnotations: false,
				},
			],
			'jsdoc/require-param': 'off',
		},
	},
];
