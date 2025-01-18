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
