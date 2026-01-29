// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import { themes as prismThemes } from 'prism-react-renderer';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/** @type {import('@docusaurus/types').Config} */
const config = {
	title: 'ClassifAI Developer Documentation',
	tagline:
		'Documentation for actions, filters, and WP-CLI commands found in the ClassifAI plugin',
	favicon: 'img/favicon.png',

	// Future flags, see https://docusaurus.io/docs/api/docusaurus-config#future
	future: {
		v4: true, // Improve compatibility with the upcoming Docusaurus v4
	},

	url: 'https://classifaiplugin.com',
	baseUrl: '/classifai/',

	organizationName: '10up',
	projectName: 'classifai',

	onBrokenLinks: 'throw',
	onBrokenMarkdownLinks: 'warn',

	presets: [
		[
			'classic',
			/** @type {import('@docusaurus/preset-classic').Options} */
			{
				docs: {
					sidebarPath: './sidebars.js',
					routeBasePath: '/',
					breadcrumbs: true,
				},
				blog: false,
				theme: {
					customCss: './src/css/custom.css',
				},
			},
		],
	],

	themes: [
		[
			'@easyops-cn/docusaurus-search-local',
			{
				indexDocs: true,
				docsRouteBasePath: '/',
				docsDir: 'docs',
				hashed: true,
				highlightSearchTermsOnTargetPage: true,
				searchBarPosition: 'right',
			},
		],
		[ '@docusaurus/theme-mermaid', {} ],
	],

	themeConfig: {
		navbar: {
			title: 'Developer Documentation',
			logo: {
				alt: 'ClassifAI Logo',
				src: 'img/logo.svg',
			},
			items: [
				{
					type: 'docSidebar',
					sidebarId: 'hooksSidebar',
					position: 'left',
					label: 'Get Started',
					href: '/get-started/',
					sidebarCollapsed: false,
				},
				{
					type: 'docSidebar',
					sidebarId: 'hooksSidebar',
					position: 'left',
					label: 'Hooks',
					href: '/hooks',
					sidebarCollapsed: false,
				},
				{
					href: 'https://github.com/10up/classifai',
					label: 'GitHub',
					position: 'right',
				},
			],
		},
		colorMode: {
			defaultMode: 'light',
			disableSwitch: true,
			respectPrefersColorScheme: false,
		},
		footer: {
			style: 'dark',
			copyright:
				'Copyright © 2025 ClassifAI Developer Documentation. Built with WP Hooks Documentor.',
		},
		prism: {
			theme: prismThemes.github,
			darkTheme: prismThemes.dracula,
			additionalLanguages: [ 'php' ],
		},
	},
	markdown: {
		mermaid: true,
	},
};

export default config;
