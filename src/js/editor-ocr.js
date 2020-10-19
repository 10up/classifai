/* global lodash */
const { subscribe, select, dispatch } = wp.data;
const { createBlock } = wp.blocks;
const { apiFetch } = wp;
const { find, debounce } = lodash;

const { getSelectedBlock, getBlocks, getBlockIndex } = select( 'core/block-editor' );

let currentBlocks;

subscribe( debounce( async () => {
	const newBlocks = getBlocks();
	const prevBlocks = currentBlocks;
	currentBlocks = newBlocks;

	const currentBlock = getSelectedBlock();

	if ( ! currentBlock || 'core/image' !== currentBlock.name ) {
		return;
	}

	if ( ! currentBlock.attributes.id ) {
		return;
	}

	const prevBlock = find( prevBlocks, block => block.clientId === currentBlock.clientId );

	if ( ! prevBlock || prevBlock.attributes.id === currentBlock.attributes.id ) {
		return;
	}

	const imageBlockIndex = getBlockIndex( currentBlock.clientId );

	const media = await apiFetch( { path: `/wp/v2/media/${currentBlock.attributes.id}` } );

	if (
		! Object.prototype.hasOwnProperty.call( media, 'description' )
		|| ! Object.prototype.hasOwnProperty.call( media.description, 'rendered' )
		|| ! media.description.rendered
	) {
		return;
	}

	const newBlock = createBlock( 'core/paragraph', {
		content: media.description.rendered.replace( /(<([^>]+)>)/gi, '' )
	} );

	dispatch( 'core/block-editor' ).insertBlock( newBlock, imageBlockIndex + 1 );
} ), 100 );
