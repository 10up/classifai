import { registerPlugin } from '@wordpress/plugins';
import { PluginBlockSettingsMenuItem } from '@wordpress/edit-post';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { select, dispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __unstableStripHTML as stripHTML } from '@wordpress/dom';
import { createHigherOrderComponent } from '@wordpress/compose';
import { createReduxStore, register } from '@wordpress/data';
import { Modal, Button } from '@wordpress/components';
import { count as getWordCount, count as getCharacterCount } from '@wordpress/wordcount';
import { __ } from '@wordpress/i18n';

import '../../scss/content-resizing-plugin.scss';

const DEFAULT_STATE = {
	clientId: '',
	isResizing: false,
};

const resizeContentStore = createReduxStore( 'resize-content-store', {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'IS_RESIZING':
				return {
					...state,
					isResizing: action.isResizing,
					clientId: action.clientId,
				};
		}

		return state;
	},
	actions: {
		setIsResizing( clientId, isResizing ) {
			return {
				type: 'IS_RESIZING',
				isResizing,
				clientId,
			};
		},
	},
	selectors: {
		isResizing( state ) {
			return state.isResizing;
		},
		getClientId( state ) {
			return state.clientId;
		}
	},
} );

register( resizeContentStore );

const ContentResizingPlugin = () => {
	const [ ogText, setOgText ] = useState( '' );
	const [ textArray, setTextArray ] = useState( [] );
	const { isMultiBlocksSelected, isResizing } = useSelect( ( select ) => {
		return {
			isMultiBlocksSelected: select( blockEditorStore ).hasMultiSelection(),
			isResizing: select( resizeContentStore ).isResizing(),
		}
	} );

	async function resizeContent( resize_type = 'grow' ) {
		const { startIndex, endIndex, selectedText, block, ogText: originalText } = getSelectedText();
		const textArray = await getResizedContent( block.clientId, selectedText, resize_type );
		setTextArray( textArray );
		setOgText( originalText );
	}

	function selectSuggestion( suggestionText = '' ) {
		updateContent( block, startIndex, endIndex, suggestionText );
	}

	async function getResizedContent( clientId = '', content = '', resize_type = 'grow' ) {
		let textArray = [];
		const apiUrl = `${ wpApiSettings.root }classifai/v1/openai/resize-content`;
		const postId = select( editorStore ).getCurrentPostId();
		const formData = new FormData();

		formData.append( 'id', postId );
		formData.append( 'content', content );
		formData.append( 'resize_type', resize_type );

		dispatch( resizeContentStore ).setIsResizing( clientId, true );

		const response = await fetch(
			apiUrl,
			{
				method: 'POST',
				body: formData
			}
		)

		if ( 200 === response.status ) {
			textArray = await response.json();
		}

		dispatch( resizeContentStore ).setIsResizing( clientId, false );

		return textArray;
	}

	/**
	 * Updates the text selection.
	 *
	 * @param {object} block Gutenberg block object.
	 * @param {Number} startIndex The starting index of the selection.
	 * @param {Number} endIndex The ending index of the selection.
	 * @param {string} updateWith The content that will be used to replace the selection.
	 */
	function updateContent( block, startIndex, endIndex, updateWith ) {
		const fullBlockContent = toPlainText( block.attributes.content );
		const beforeReplaceable = fullBlockContent.substring( 0, startIndex );
		const afterReplaceable = fullBlockContent.substring( endIndex );
		const updatedContent = beforeReplaceable + updateWith + afterReplaceable;

		dispatch( blockEditorStore ).updateBlockAttributes(
			block.clientId,
			{
				content: endIndex ? updatedContent : updateWith
			}
		)

		dispatch( blockEditorStore ).selectionChange( block.clientId, 'content', startIndex, startIndex + updateWith.length );
	}

	function getSelectedText() {
		const start = select( blockEditorStore ).getSelectionStart();
		const end   = select( blockEditorStore ).getSelectionEnd();
		const block = select( blockEditorStore ).getSelectedBlock();

		let blockContent = block.attributes.content;
		let blockContentPlainText = toPlainText( blockContent );

		if ( 0 === end.offset - start.offset ) {
			return {
				block,
				startIndex: 0,
				endIndex: null,
				selectedText: blockContentPlainText,
				ogText: blockContentPlainText,
			};
		}

		return {
			block,
			startIndex: start.offset,
			endIndex: end.offset,
			selectedText: blockContentPlainText.substring( start.offset, end.offset ),
			ogText: blockContentPlainText,
		}
	}

	if ( isMultiBlocksSelected || isResizing ) {
		return null;
	}

	const suggestionModal = ! isResizing && textArray.length && (
		<Modal
			title={ __( 'Select a suggestion', 'classifai' ) }
			isFullScreen={ false }
			className="title-modal"
		>
			<div className='classifai-content-resize__result-wrapper'>
				<table className='classifai-content-resize__result-table'>
					<thead>
						<th>{ __( 'Suggestion', 'classifai' ) }</th>
						<th className='classifai-content-resize__stat-header'>{ __( 'Stats', 'classifai' ) }</th>
						<th>{ __( 'Action', 'classifai' ) }</th>
					</thead>
					<tbody>
						{
							textArray.map( ( textItem, index ) => {
								const ogWordCount = getWordCount( ogText, 'words' );
								const ogCharCount = getCharacterCount( ogText, 'characters_including_spaces' );
								const suggestionWordCount = getWordCount( textItem, 'words' );
								const suggestionCharCount = getCharacterCount( textItem, 'characters_including_spaces' );

								const wordDiff = suggestionWordCount - ogWordCount;
								const charDiff = suggestionCharCount - ogCharCount;

								return (
									<tr key={ index }>
										<td>{ textItem }</td>
										<td>
											<ResizeStat count={ wordDiff } />
											<ResizeStat count={ charDiff } countEntity='character' />
										</td>
										<td className='classifai-content-resize__action'>
											<Button
												variant='secondary'
												onClick={ () => selectSuggestion( textItem ) }
											>
												{ __( 'Select', 'classifai' ) }
											</Button>
										</td>
									</tr>
								)
							} )
						}
					</tbody>
				</table>
			</div>
		</Modal>
	);

	return (
		<>
			{ suggestionModal }
			<PluginBlockSettingsMenuItem
				allowedBlocks={ [ 'core/paragraph' ] }
				icon="smiley"
				label={ __( 'Grow content', 'classifai' ) }
				onClick={ () => resizeContent( 'grow' ) }
			/>
			<PluginBlockSettingsMenuItem
				allowedBlocks={ [ 'core/paragraph' ] }
				icon="smiley"
				label={ __( 'Shrink content', 'classifai' ) }
				onClick={ () => resizeContent( 'shrink' ) }
			/>
		</>
	)
};

const ResizeStat = ( { count = 0, countEntity = 'word' } ) => {
	if ( 0 === count ) {
		return (
			<div>
				{
					'word' === countEntity
					? __( 'No change in word count.', 'classifai' )
					: __( 'No change in character count.', 'classifai' )
				}
			</div>
		)
	}

	if ( count < 0 ) {
		return (
			<div className='classifai-content-resize__shrink-stat'>
				{
					'word' === countEntity
					? <><strong>{ count }</strong> { __( 'words', 'classifai' ) }</>
					: <><strong>{ count }</strong> { __( 'characters', 'classifai' ) }</>
				}
			</div>
		)
	}

	return (
		<div className='classifai-content-resize__grow-stat'>
			{
				'word' === countEntity
				? <strong>+{ count }</strong> + __( 'words', 'classifai' )
				: <strong>+{ count }</strong> + __( 'characters', 'classifai' )
			}
		</div>
	)
};


function toPlainText( html ) {
	// Manually handle BR tags as line breaks prior to `stripHTML` call
	html = html.replace( /<br>/g, '\n' );

	const plainText = stripHTML( html ).trim();

	// Merge any consecutive line breaks
	return plainText.replace( /\n\n+/g, '\n\n' );
}

registerPlugin( 'tenup-openai-expand-reduce-content', {
	render: ContentResizingPlugin,
} );

const withInspectorControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { isResizing, currentClientId } = useSelect( ( select ) => {
			const isResizing = select( resizeContentStore ).isResizing();
			const currentClientId = select( resizeContentStore ).getClientId();

			return { isResizing, currentClientId }
		} );

		if ( currentClientId !== props.clientId ) {
			return <BlockEdit { ...props } />;
		}

		if ( 'core/paragraph' !== props.name ) {
			return <BlockEdit { ...props } />;
		}

		if ( ! isResizing ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<div style={ { background: 'red' } }>
					<BlockEdit { ...props } />
				</div>
			</>
		);
	};
}, 'withInspectorControl' );


wp.hooks.addFilter(
	'editor.BlockEdit',
	'resize-content/lock-block-editing',
	withInspectorControls
);
