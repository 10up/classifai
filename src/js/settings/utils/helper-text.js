/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export const thresholdInfo = {
    'helper': (
        <div className="settings-helper-text display-container">
            <div className="helper-text-content">
                <p>{ __( 'Determines how confident the AI must be before suggesting a term.', 'classifai' ) }</p>
                <p>{ __( 'Higher % = More precise (fewer, more accurate terms)', 'classifai' ) }</p>
                <p>{ __( 'Lower % = More verbose (more suggestions, including lower-confidence terms)', 'classifai' ) }</p>
            </div>
        </div>
    ),
};

export const nluHelperText = {
    'category': __(
        '<p>IBM Watson analyzes your content and assigns a broad topic hierarchy that best describes the overall subject.</p>'+
        '<p>Example:<code>/technology and computing/software</code></p>'+
        '<p>Categories are useful for general classification and site-wide content grouping.</p>'+
        '<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#categories" target="_blank">Learn more</a></p>',
        'classifai'
    ),
    'keyword': __(
        '<p>Keywords represent important terms in your content that are contextually significant.</p>'+
        '<p>Watson extracts these to help identify core concepts, topics, and SEO-friendly tags.</p>'+
        '<p>Keywords often map well to WordPress tags.</p>'+
        '<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#keywords" target="_blank">Learn more</a></p>',
        'classifai'
    ),
    'entity': __(
        '<p>Entities are named people, places, brands, and other proper nouns mentioned in your content.</p>'+
        '<p>Watson identifies and classifies these by type (e.g., Person, Company, Location) and optionally links them to known databases like Wikipedia.</p>'+
        '<p>Entities are helpful for structured data and enhancing rich snippets or metadata.</p>'+
        '<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#entities" target="_blank">Learn more</a></p>',
        'classifai'
    ),
    'concept': __(
        '<p>Concepts reflect high-level abstract ideas Watson identifies in your content, even if the term isn\'t explicitly used.</p>'+
        '<p>For example, an article about "the iPhone" might be linked to the concept of "Apple Inc."</p>'+
        '<p>Concepts are great for semantic tagging and content recommendation systems.</p>'+
        '<p><a href="https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-about#concepts" target="_blank">Learn more</a></p>',
        'classifai'
    ),
};
