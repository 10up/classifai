import { getBlockContent } from '@wordpress/blocks';

export const filterAndFlattenAllowedBlocks = ( blocks = [], allowedBlocks = [] ) => blocks.reduce(
	( acc, block ) => [
		...acc,
		...( allowedBlocks.includes( block.name )
			? [ block ]
			: [] ),
		...( block.innerBlocks
			? filterAndFlattenAllowedBlocks( block.innerBlocks )
			: [] ),
	],
	[]
);

/**
 * Retrieves the mapping of client IDs to block content.
 *
 * @param {Array} blocks
 * @returns {Object} An object where the keys are client IDs and the values are the corresponding block content.
 */
export const getClientIdToBlockContentMapping = ( blocks = [] ) => blocks.map( ( block ) => ( {
	clientId: block.clientId,
	content: getBlockContent( block ),
} ) );

/**
 * Returns HTML string without the outermost tags.
 *
 * @param {string} htmlContent HTML as string.
 * @return {string} HTML string without outermost tags stripped.
 */
export function stripOutermostTag( htmlContent = '' ) {
	// Parse the input HTML string into a DOM structure
	const parser = new DOMParser();
	const doc = parser.parseFromString( htmlContent, 'text/html' );

	// Get the first element within the body (this is the outermost element)
	const outermostElement = doc.body.firstElementChild;

	// Return the innerHTML of the outermost element, which removes the outermost tag
	return outermostElement ? outermostElement.innerHTML : htmlContent;
}
