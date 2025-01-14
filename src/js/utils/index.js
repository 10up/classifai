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
