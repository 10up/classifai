import { getBlockContent } from '@wordpress/blocks';

export const filterAndFlattenAllowedBlocks = (
	blocks = [],
	allowedBlocks = []
) =>
	blocks.reduce(
		( acc, block ) => [
			...acc,
			...( allowedBlocks.includes( block.name ) ? [ block ] : [] ),
			...( block.innerBlocks
				? filterAndFlattenAllowedBlocks(
						block.innerBlocks,
						allowedBlocks
				  )
				: [] ),
		],
		[]
	);

/**
 * Removes the <!-- --> delimiters from the content.
 *
 * @param {string} content The block content.
 * @return {Array} Array of objects with content without delimiters.
 */
export const removeBlockDelimiters = ( content ) => {
	return content.replace( /<!--[\s\S]*?-->/g, '' );
};

/**
 * Retrieves the mapping of client IDs to block content.
 *
 * @param {Array} blocks
 * @return {Object} An object where the keys are client IDs and the values are the corresponding block content.
 */
export const getClientIdToBlockContentMapping = ( blocks = [] ) =>
	blocks.map( ( block ) => ( {
		clientId: block.clientId,
		content: removeBlockDelimiters( getBlockContent( block ) ),
	} ) );

/**
 * Returns HTML string without the outermost tags.
 *
 * @param {string} htmlContent HTML as string.
 * @return {string} HTML string without outermost tags stripped.
 */
export const stripOutermostTag = ( htmlContent = '' ) => {
	// Parse the input HTML string into a DOM structure
	const parser = new DOMParser();
	const doc = parser.parseFromString( htmlContent, 'text/html' );

	// Get the first element within the body (this is the outermost element)
	const outermostElement = doc.body.firstElementChild;

	// Return the innerHTML of the outermost element, which removes the outermost tag
	return outermostElement ? outermostElement.innerHTML : htmlContent;
};

/**
 * Replaces subset of blocks in the copy of the editor's original blocks with
 * modified blocks and returns a new array.
 *
 * Suppose the editor originally has 6 blocks and blocks 3 & 4 have undergone tone
 * rewriting which returns blocks 3' and 4'. This function returns 1-2-3'-4'-5-6.
 *
 * @param {Array} sourceBlocks   Array of original, unmodified editor blocks.
 * @param {Array} modifiedBlocks Subset of editor blocks which have undergone tone rewriting.
 * @return {Array} Array of blocks that include original and modified blocks.
 */
export const replaceBlocksInSource = (
	sourceBlocks = [],
	modifiedBlocks = []
) => {
	const updateBlock = ( blocks ) => {
		return blocks.map( ( block ) => {
			const modified = modifiedBlocks.find(
				( modifiedBlock ) => modifiedBlock.clientId === block.clientId
			);

			if ( modified ) {
				return modified.blocks[ 0 ];
			}

			return {
				...block,
				innerBlocks: block.innerBlocks
					? updateBlock( block.innerBlocks )
					: [],
			};
		} );
	};

	return updateBlock( sourceBlocks );
};
