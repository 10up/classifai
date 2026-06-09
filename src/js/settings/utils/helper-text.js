/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Threshold information.
 *
 * @type {Object}
 */
export const thresholdInfo = {
	helper: (
		<>
			<p>
				{ __(
					'Determines how confident the AI must be before suggesting a term.',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Higher % = More precise (fewer, more accurate terms)',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Lower % = More verbose (more suggestions, including lower-confidence terms)',
					'classifai'
				) }
			</p>
		</>
	),
};

/**
 * NLU helper text.
 *
 * @type {Object}
 */
export const nluHelperText = {
	category: (
		<>
			<p>
				{ __(
					'IBM Watson analyzes your content and assigns a broad topic hierarchy that best describes the overall subject.',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Example: /technology and computing/software',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Categories are useful for general classification and site-wide content grouping.',
					'classifai'
				) }
			</p>
		</>
	),
	keyword: (
		<>
			<p>
				{ __(
					'Keywords represent important terms in your content that are contextually significant.',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Watson extracts these to help identify core concepts, topics, and SEO-friendly tags.',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Keywords often map well to WordPress tags.',
					'classifai'
				) }
			</p>
		</>
	),
	entity: (
		<>
			<p>
				{ __(
					'Entities are named people, places, brands, and other proper nouns mentioned in your content.',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Watson identifies and classifies these by type (e.g., Person, Company, Location).',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Entities are helpful for structured data and enhancing rich snippets or metadata.',
					'classifai'
				) }
			</p>
		</>
	),
	concept: (
		<>
			<p>
				{ __(
					"Concepts reflect high-level abstract ideas Watson identifies in your content, even if the term isn't explicitly used.",
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'For example, an article about "the iPhone" might be linked to the concept of "Apple Inc."',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Concepts are great for semantic tagging and content recommendation systems.',
					'classifai'
				) }
			</p>
		</>
	),
};

/**
 * Moderation helper text.
 *
 * @type {Object}
 */
export const moderationHelperText = {
	content_types: (
		<>
			<p>
				{ __(
					'The OpenAI moderation endpoint will check if text is potentially harmful.',
					'classifai'
				) }
			</p>
			<p>
				{ __(
					'Text will be checked against certain categories, like hate, threatening, harassment, self-harm, sexual, violence, and more. Each category is scored on a scale of 0 to 1, with 0 indicating no harm and 1 indicating the highest level of harm. If something is found to be harmful, it will be flagged and blocked.',
					'classifai'
				) }
			</p>
		</>
	),
};
