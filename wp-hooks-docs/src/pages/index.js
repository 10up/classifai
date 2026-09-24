/* eslint-disable @typescript-eslint/explicit-function-return-type */
import React from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';

/**
 * @typedef {Object} Feature
 * @property {string}   title         - The feature title
 * @property {string}   description   - The feature description
 * @property {string[]} tags          - Array of feature tags
 * @property {string}   configureLink - Link to feature configuration
 * @property {string}   docsLink      - Link to feature documentation
 */

/**
 * @typedef {Object} Platform
 * @property {string}   name         - Platform name
 * @property {string[]} features     - Platform features
 * @property {string[]} capabilities - Platform capabilities
 */

import styles from './index.module.css';

/**
 * Homepage header component
 *
 * @return {JSX.Element} The header component
 */
function HomepageHeader() {
	return (
		<header className={ clsx( 'hero', styles.heroBanner ) }>
			<div className="container">
				<div className={ styles.headerContent }>
					<p className={ styles.wpPluginLabel }>
						Developer Documentation
					</p>
					<h1 className={ styles.heroTitle }>
						ClassifAI Documentation
					</h1>
					<p className={ styles.heroSubtitle }>
						Your complete guide to ClassifAI&apos;s hooks and APIs. Build and extend AI-powered features the WordPress way.
					</p>
					<div className={ styles.buttons }>
						<Link
							className="button button--primary button--lg"
							to="/get-started/"
						>
							Get Started
						</Link>
						<Link
							className="button button--outline button--lg"
							to="/hooks"
						>
							Hooks Reference
						</Link>
					</div>
				</div>
			</div>
		</header>
	);
}

/**
 * Feature section component
 *
 * @param {Object}    props             - Component props
 * @param {string}    props.title       - Section title
 * @param {string}    props.description - Section description
 * @param {Feature[]} props.features    - Array of features
 * @return {JSX.Element} The feature section component
 */
function FeatureSection( { title, description, features } ) {
	return (
		<section className={ styles.featureSection }>
			<div className="container">
				<h2 className={ styles.sectionTitle }>{ title }</h2>
				<p className={ styles.sectionDescription }>{ description }</p>
				<div className={ styles.featureGrid }>
					{ features.map( ( feature, idx ) => (
						<div key={ idx } className={ styles.featureCard }>
							<h3>{ feature.title }</h3>
							<p>{ feature.description }</p>
							<div className={ styles.featureActions }>
								<Link
									className="button button--outline"
									to={ feature.configureLink }
								>
									Configuration Guide
								</Link>
							</div>
						</div>
					) ) }
				</div>
				<div className={ styles.platformAction }>
					<Link
						className="button button--secondary button--lg"
						to="/advanced-docs/feature-provider-support"
					>
						View Platform Compatibility Guide
					</Link>
				</div>
			</div>
		</section>
	);
}

/**
 * Homepage component
 *
 * @return {JSX.Element} The homepage component
 */
export default function Home() {
	const { siteConfig } = useDocusaurusContext();
	const features = [
		{
			title: 'Title Generation',
			tagline: 'Better Post Titles',
			description:
				'Generate AI-recommended and alternative post titles based on your content. Each title is crafted to be SEO-friendly, compelling, and SEO-friendly.',
			configureLink: '/feature-configuration/title-generation',
		},
		{
			title: 'Writing Tools',
			tagline: 'Say More, Say Less',
			description:
				'Expand, condense or fix grammar in your written content to create the ideal version that is precise and effective, with length indicators.',
			configureLink: '/feature-configuration/writing-tools',
		},
		{
			title: 'Key Takeaways',
			tagline: 'Generate a Summary',
			description:
				'Automatically generate clear, concise summaries using the "Key Takeaways" block or craft punchy one-line summaries for previews.',
			configureLink: '/feature-configuration/excerpt-generation',
		},
		{
			title: 'Excerpt Generation',
			tagline: 'Generate a Summary',
			description:
				'Automatically generate clear, concise and SEO-friendly excerpts.',
			configureLink: '/feature-configuration/excerpt-generation',
		},
		{
			title: 'Content Generation',
			tagline: 'Generate Content',
			description: 'Generate content using various AI providers',
			configureLink: '/feature-configuration/content-generation',
		},
		{
			title: 'Moderation',
			tagline: 'Moderate Content',
			description: 'Moderate content using various AI providers',
			configureLink: '/feature-configuration/moderation',
		},
		{
			title: 'Term Cleanup',
			tagline: 'Clean Up Terms',
			description:
				'Clean up duplicate and similar terms by merging or deleting them.',
			configureLink: '/feature-configuration/term-cleanup',
		},
		{
			title: 'Image Generation',
			tagline: 'Generate Images',
			description:
				'Generate images on the fly using DALL-E and Google AI Imagen integration. Generate images on the fly using written prompts with automatic attribution.',
			configureLink: '/feature-configuration/image-generation',
		},
		{
			title: 'Image Cropping',
			tagline: 'Smart Focal Point Cropping',
			description:
				'Intelligent cropping and resizing that identifies focal points and preserves visual integrity across various image sizes.',
			configureLink: '/feature-configuration/image-cropping',
		},
		{
			title: 'Descriptive Text Generation',
			tagline: 'Automatic Image Descriptions',
			description:
				'Automatically generate descriptive alt text for images using AI vision services. Includes OCR for text in images.',
			configureLink: '/feature-configuration/descriptive-text-generator',
		},
		{
			title: 'Classification',
			tagline: 'Smart Content Tagging',
			description:
				'Automatic content classification with tag and category recommendations.',
			configureLink: '/feature-configuration/classification',
		},
		{
			title: 'Image Tags Generator',
			tagline: 'Automatic Image Tagging',
			description:
				'Supercharge the media library with automatic tag application using AI vision services. Enhanced search and asset management.',
			configureLink: '/feature-configuration/image-tags-generator',
		},
		{
			title: 'Smart 404',
			tagline: 'Smart 404 Page Recovery',
			description:
				'Dynamically suggest related content based on URL path using a new editor block. Integrates with ElasticPress.',
			configureLink: '/feature-configuration/smart-404',
		},
		{
			title: 'Text to Speech',
			tagline: 'Text-to-Speech',
			description:
				'Add "read to me" option to posts and pages with multiple language support. Improves accessibility and user experience.',
			configureLink: '/feature-configuration/text-to-speech',
		},
		{
			title: 'Audio Transcripts Generation',
			tagline: 'Audio Transcription',
			description:
				'Convert audio clips to clean, accurate transcripts using OpenAI Whisper. Stored as post content for SEO and accessibility.',
			configureLink: '/feature-configuration/audio-transcripts-generation',
		},
		{
			title: 'Recommended Content',
			tagline: 'Recommended Content Block',
			description:
				'Render recommended content based on embeddings by utilizing vector similarity search to identify semantically relevant items.',
			configureLink: '/feature-configuration/recommended-content',
		},
		{
			title: 'Image Text Extraction',
			tagline: 'Image Text Extraction',
			description:
				'OCR detects text in images (e.g., handwritten notes) and saves that as content with the image.',
			configureLink: '/feature-configuration/image-text-extraction',
		},
		{
			title: 'PDF Text Extraction',
			tagline: 'PDF Text Extraction',
			description:
				'Extract visible text from multi-pages PDF documents. Store the result as the attachment description.',
			configureLink: '/feature-configuration/pdf-text-extraction',
		},
	];

	return (
		<Layout title={ siteConfig.title } description={ siteConfig.tagline }>
			<HomepageHeader />
			<main>
				<FeatureSection
					title="Plugin Features"
					description="Explore all ClassifAI features with direct links to configuration guides"
					features={ features }
				/>
			</main>
		</Layout>
	);
}
