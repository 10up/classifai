import { registerPlugin } from '@wordpress/plugins';
import { useRef, useState, useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as blockEditorStore, BlockEditorProvider, BlockList } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { createBlock } from '@wordpress/blocks';
import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useSelectedBlocks } from '../../hooks';
import { InjectIframeStyles } from '../../components';
import {
	filterAndFlattenAllowedBlocks,
	getClientIdToBlockContentMapping,
	stripOutermostTag,
	replaceBlocksInSource,
} from '../../utils';

const { ClassifaiEditorSettingPanel } = window;
const apiUrl = `${ wpApiSettings.root }classifai/v1/rewrite-tone`;
const allowedTextBlocks = [
	'core/paragraph',
	'core/heading',
	'core/list',
	'core/list-item',
];

const RewriteTonePlugin = () => {
	// Holds a reference to the original, unmodified editor blocks.
	const blocksBackup = useRef( null );

	// Flag indicating if the previewer modal is open.
	const [ isPopupVisible, setIsPopupVisible ] = useState( false );

	// Flag indicating if a rewrite is in progress.
	const [ isRewriteInProgress, setIsRewriteInProgress ] = useState( false );

	// Stores ChatGPT response.
	const [ response, setResponse ] = useState( null );

	// Stores all the editor blocks (modified and unmodified) that are created for preview.
	const [ blocksForPreview, setBlocksForPreview ] = useState( [] );

	// Stores the subset of editor blocks that have undergone tone rewriting.
	const [ modifiedBlocks, setModifiedBlocks ] = useState( [] );

	// We use this to replace blocks if the user is happy with the result.
	const { replaceBlock } = useDispatch( blockEditorStore );

	const allSelectedBlocks = useSelectedBlocks();

	/**
	 * Performs rewrite when triggered by the user on Button click.
	 *
	 * @return {void}
	 */
	async function rewriteTone() {
		try {
			// We backup the original blocks.
			blocksBackup.current = wp.data
				.select( blockEditorStore )
				.getBlocks();

			setIsPopupVisible( false );
			setIsRewriteInProgress( true );
			setBlocksForPreview( [] );

			const filteredBlocks = getClientIdToBlockContentMapping(
				filterAndFlattenAllowedBlocks( allSelectedBlocks, allowedTextBlocks )
			);

			let __response = await fetch( apiUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					id: wp.data.select( editorStore ).getCurrentPostId(),
					content: filteredBlocks,
				} ),
			} );

			setIsRewriteInProgress( false );

			if ( ! __response.ok ) {
				return;
			}

			__response = await __response.json();
			setResponse( JSON.parse( __response ) );
		} catch ( e ) {
			setIsRewriteInProgress( false );
		}
	}

	/**
	 * Applies the result to the editor canvas when the user
	 * accepts it.
	 */
	const applyResult = () => {
		modifiedBlocks.forEach( ( { clientId, blocks } ) => {
			replaceBlock( clientId, blocks );
		} );

		setIsPopupVisible( false );
	};

	useEffect( function reactToResponse() {
		if ( ! Array.isArray( response ) ) {
			return;
		}

		const __modifiedBlocks = response.map( ( { clientId, content } ) => {
			// We get the same block clientID in the response.
			// Get the block using the clientID from the block editor data store.
			const currentBlock = wp.data
				.select( blockEditorStore )
				.getBlock( clientId );

			// We apply the original block attributes to the current iterated block.
			currentBlock.attributes = wp.data
				.select( blockEditorStore )
				.getBlockAttributes( clientId );

			// This will automatically create a new block by detecting the HTML.
			let newBlock = wp.blocks.rawHandler( { HTML: content } );

			if (
				Array.isArray( newBlock ) &&
				1 === newBlock.length &&
				'core/html' === newBlock[ 0 ].name
			) {
				// If a List item block is selected (without selecting the List block), and
				// sent in the request, the response also returns the HTML with <li> ...content...</li>.
				// Gutenberg does not recognise <li> without <ul>, and hence rawHandler() returns a
				// generic `core/html` block instead of a `core/list-item` block.
				//
				// We handle this separately by using `createBlock()` isntead.
				newBlock = createBlock( currentBlock.name, {
					// The response contains `<li></li>` tags, which we remove here as they are added
					// by `createBlock()`. If we don't do this, then nested List item blocks will be
					// created.
					content: stripOutermostTag( content ),
				} );

				return {
					clientId,
					blocks: [ newBlock ],
				};
			}

			console.log( newBlock )

			return {
				clientId,
				blocks: newBlock,
			};
		} );

		const __blocksForPreview = replaceBlocksInSource(
			blocksBackup.current,
			__modifiedBlocks
		);

		setBlocksForPreview( __blocksForPreview );
		setModifiedBlocks( __modifiedBlocks );
		setIsPopupVisible( true );
	}, [ response ] );

	return (
		<>
		<ClassifaiEditorSettingPanel>
			<button onClick={ rewriteTone }>Rewrite</button>
		</ClassifaiEditorSettingPanel>

		{ isPopupVisible && (
			<Modal
				isFullScreen={ true }
				onRequestClose={ () => setIsPopupVisible( false ) }
			>
				<InjectIframeStyles>
					<BlockEditorProvider
						value={ blocksForPreview }
						settings={ {
							...wp.data
								.select( 'core/block-editor' )
								.getSettings(),
							inserter: false,
							templateLock: 'all',
						} }
					>
						<BlockList />
					</BlockEditorProvider>
					<div>
						<Button variant="secondary" onClick={ applyResult }>
							{ __( 'Apply this result', 'classifai' ) }
						</Button>
						<Button variant="link" onClick={ rewriteTone }>
							{ __( 'Regenerate', 'classifai' ) }
						</Button>
					</div>
				</InjectIframeStyles>
			</Modal>
		) }
		</>
	);
};

registerPlugin( 'classifai-rewrite-tone-plugin', {
	render: RewriteTonePlugin,
} );
