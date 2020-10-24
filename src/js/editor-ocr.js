/* global lodash */
const { useSelect, dispatch } = wp.data;
const { createBlock } = wp.blocks;
const { apiFetch } = wp;
const { find, debounce } = lodash;
const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;
const { InspectorControls } = wp.blockEditor; // eslint-disable-line no-unused-vars
const { PanelBody, PanelRow, Button, Modal, Flex, FlexItem } = wp.components; // eslint-disable-line no-unused-vars
const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
const { useState, Fragment } = wp.element; // eslint-disable-line no-unused-vars

/**
 * Get image description using media api.
 *
 * @param {int} imageId - Image ID.
*/
const getImageDescription = async ( imageId ) => {
	const media = await apiFetch( { path: `/wp/v2/media/${imageId}` } );

	if (
		! Object.prototype.hasOwnProperty.call( media, 'description' )
		|| ! Object.prototype.hasOwnProperty.call( media.description, 'rendered' )
		|| ! media.description.rendered
	) {
		return false;
	}

	return media.description.rendered.replace( /(<([^>]+)>)/gi, '' );
};

/**
 * Insert description as a paragraph block to the editor.
 *
 * @param {int} imageBlockIndex - Index of the image block in current editor.
 * @param {int} imageId - Image ID.
*/
const insertDescription = async ( imageBlockIndex, imageId ) => {
	const imageDescription = await getImageDescription( imageId );

	if( !imageDescription ) {
		return;
	}

	const newBlock = createBlock( 'core/paragraph', {
		content: imageDescription,
		anchor: `classifai-image-description-${imageId}`,
	} );

	dispatch( 'core/block-editor' ).insertBlock( newBlock, imageBlockIndex + 1 );
};

/**
 * An Modal allows user to insert description to block if detected.
 */
const imageOcrModal = () => {
	const [ isOpen, setOpen ] = useState( false );
	const [ imageId, setImageId ] = useState( 0 );
	const [ blockIndex, setBlockIndex ] = useState( 0 );
	const openModal = () => setOpen( true ); // eslint-disable-line require-jsdoc
	const closeModal = () => setOpen( false ); // eslint-disable-line require-jsdoc
	let currentBlocks;

	useSelect( debounce( ( select ) => {
		const { getSelectedBlock, getBlocks, getBlockIndex } = select( 'core/block-editor' );
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

		setBlockIndex( getBlockIndex( currentBlock.clientId ) );
		setImageId( currentBlock.attributes.id );
		openModal();
	}, 10 ) );

	return isOpen && <Modal title={__( 'ClassifAI detected text in your image', 'classifai' )}>
		<p>{__( 'Would you like you insert it as a paragraph under this image block?', 'classifai' )}</p>
		<Flex align='flex-end' justify='flex-end'>
			<FlexItem>
				<Button isPrimary onClick={() => {
					insertDescription( blockIndex, imageId );
					return closeModal();
				}}>
					{__( 'Insert text', 'classifai' )}
				</Button>
			</FlexItem>
			<FlexItem>
				<Button isSecondary onClick={ closeModal }>
					{__( 'Dismiss', 'classifai' )}
				</Button>
			</FlexItem>
		</Flex>
	</Modal>;
};

registerPlugin( 'tenup-classifai-ocr-modal', {
	render: imageOcrModal,
} );

/**
 * Insert ClassifAI panel to image settings sidebar.
*/
const imageOcrControl = createHigherOrderComponent( ( BlockEdit ) => { // eslint-disable-line no-unused-vars
	return ( props ) => {
		const { attributes, clientId, isSelected, name } = props;

		if ( ! isSelected || 'core/image' != name ) {
			return <BlockEdit {...props} />;
		}

		return (
			<Fragment>
				<BlockEdit {...props} />
				<InspectorControls>
					<PanelBody title={__( 'ClassifAI', 'classifai' )} initialOpen={true}>
						<PanelRow>
							<Button onClick={() => insertDescription( clientId, attributes.id )} isPrimary>
								{__( 'Insert scanned text into content', 'classifai' )}
							</Button>
						</PanelRow>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'imageOcrControl' );

addFilter(
	'editor.BlockEdit',
	'classifai/image-ocr-control',
	imageOcrControl
);
