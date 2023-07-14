/* eslint-disable @wordpress/no-unsafe-wp-apis */
import { store as blockEditorStore, BlockControls } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import {
	select,
	dispatch,
	useSelect,
	createReduxStore,
	register,
} from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __unstableStripHTML as stripHTML } from '@wordpress/dom';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	Button,
	Icon,
	Modal,
	Spinner,
	ToolbarGroup,
	ToolbarItem,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import {
	count as getWordCount,
	count as getCharacterCount,
} from '@wordpress/wordcount';
import { close } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

import '../../scss/content-resizing-plugin.scss';

/**
 * Custom store to access common data in a block and a higer order
 * component created through filters.
 */
const DEFAULT_STATE = {
	clientId: '',
};

const aiIconSvg = (
	<svg width="20" height="15" viewBox="0 0 61 46" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path fillRule="evenodd" clipRule="evenodd" d="M3.51922 0C1.57575 0 0 1.5842 0 3.53846V42.4615C0 44.4158 1.57575 46 3.51922 46H57.4808C59.4243 46 61 44.4158 61 42.4615V3.53846C61 1.5842 59.4243 0 57.4808 0H3.51922ZM16.709 8.13836H21.4478L33.58 39.5542H27.524L24.0318 30.5144H13.9699L10.5169 39.5542H4.55669L16.709 8.13836ZM19.0894 16.7007C18.9846 17.041 18.878 17.3735 18.7702 17.698L18.7582 17.7344L15.9976 25.1398H22.1464L19.4013 17.6851L19.0894 16.7007ZM40.3164 8.13836H52.9056V12.1528L49.4929 12.9715V34.6306L52.9056 35.41V39.4338H40.3164V35.41L43.7291 34.6306V12.9715L40.3164 12.1528V8.13836Z" fill="#f82d2c"/>
	</svg>
)

const resizeContentStore = createReduxStore( 'resize-content-store', {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_CLIENT_ID':
				return {
					...state,
					clientId: action.clientId,
				};
		}

		return state;
	},
	actions: {
		setClientId( clientId ) {
			return {
				type: 'SET_CLIENT_ID',
				clientId,
			};
		},
	},
	selectors: {
		getClientId( state ) {
			return state.clientId;
		},
	},
} );

register( resizeContentStore );

const ResizeStat = ( { count = 0, countEntity = 'word' } ) => {
	if ( 0 === count ) {
		return (
			<div>
				{ 'word' === countEntity
					? __( 'No change in word count.', 'classifai' )
					: __( 'No change in character count.', 'classifai' ) }
			</div>
		);
	}

	if ( count < 0 ) {
		return (
			<div className="classifai-content-resize__shrink-stat">
				{ 'word' === countEntity ? (
					<>
						<strong>{ count }</strong>{ ' ' }
						{ __( 'words', 'classifai' ) }
					</>
				) : (
					<>
						<strong>{ count }</strong>{ ' ' }
						{ __( 'characters', 'classifai' ) }
					</>
				) }
			</div>
		);
	}

	return (
		<div className="classifai-content-resize__grow-stat">
			{ 'word' === countEntity ? (
				<>
					<strong>+{ count }</strong> { __( 'words', 'classifai' ) }
				</>
			) : (
				<>
					<strong>+{ count }</strong>{ ' ' }
					{ __( 'characters', 'classifai' ) }
				</>
			) }
		</div>
	);
};

/**
 * Strips-off all the HTML from a string and returns plain-text.
 *
 * @param {string} html Content with HTML.
 * @return {string} plain-text string.
 */
function toPlainText( html ) {
	// Manually handle BR tags as line breaks prior to `stripHTML` call.
	html = html.replace( /<br>/g, '\n' );

	const plainText = stripHTML( html ).trim();

	// Merge any consecutive line breaks.
	return plainText.replace( /\n\n+/g, '\n\n' );
}

/**
 * Adds an overlay on the block under process.
 */
const withContentResizingBlockToolbar = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		// Holds the original text of the block being procesed.
		const [ ogText, setOgText ] = useState( '' );

		// Holds the currently selected block data.
		const [ selectedBlock, setSelectedBlock ] = useState( null );

		// Holds the start selection index of the content selected in a block.
		// Defaults to `0` in cause the block is selected but no text is selected.
		const [ startIndex, setStartIndex ] = useState( 0 );

		// Holds the end selection index of the content selected in a block.
		// Defaults to `null` in cause the block is selected but no text is selected.
		const [ endIndex, setEndIndex ] = useState( null );

		// Holds the GPT response array.
		const [ textArray, setTextArray ] = useState( [] );

		// Holds the selected text content within the block.
		// If no text was selected, then this value is the same as `ogText`.
		const [ selectedText, setSelectedText ] = useState( '' );

		// Holds details of whether to `shrink` or `grow` the content.
		const [ resizeType, setResizeType ] = useState( 'grow' );

		// Indicates if content resixing is in progress.
		const [ isResizing, setIsResizing ] = useState( false );

		// Indicates if the modal window with the result is open/closed.
		const [ isModalOpen, setIsModalOpen ] = useState( false );

		// Indicates if multiple blocks are selected.
		const { isMultiBlocksSelected } = useSelect( ( __select ) => {
			return {
				isMultiBlocksSelected:
					__select( blockEditorStore ).hasMultiSelection(),
			};
		} );

		// Triggers AJAX request to resize the content.
		useEffect( () => {
			if ( isResizing ) {
				( async () => {
					const __textArray = await getResizedContent();
					setTextArray( __textArray );
					setIsModalOpen( true );
				} )();
			}
		}, [ isResizing ] );

		if ( 'core/paragraph' !== props.name ) {
			return <BlockEdit { ...props } />;
		}

		// Resets to default states.
		function resetStates() {
			setSelectedBlock( null );
			setStartIndex( 0 );
			setEndIndex( null );
			setTextArray( [] );
			setSelectedText( '' );
			setIsModalOpen( false );
		}

		/**
		 * Triggered when either `Grow content` or `Shrink content` is clicked from
		 * the Block's "more options" menu.
		 *
		 * @param {string} __resizeType The type of resizing. `grow` or `shrink`.
		 * @return {void}
		 */
		async function resizeContent( __resizeType = 'grow' ) {
			const start = select( blockEditorStore ).getSelectionStart();
			const end = select( blockEditorStore ).getSelectionEnd();
			const block = select( blockEditorStore ).getSelectedBlock();

			const blockContent = block.attributes.content;
			const blockContentPlainText = toPlainText( blockContent );

			setResizeType( __resizeType );

			if ( 0 === end.offset - start.offset ) {
				setSelectedBlock( block );
				setStartIndex( 0 );
				setEndIndex( null );
				setSelectedText( blockContentPlainText );
				setOgText( blockContentPlainText );
				setIsResizing( true );
				return;
			}

			setSelectedBlock( block );
			setStartIndex( start.offset );
			setEndIndex( end.offset );
			setSelectedText(
				blockContentPlainText.substring( start.offset, end.offset )
			);
			setOgText( blockContentPlainText );
			setIsResizing( true );
		}

		/**
		 * The AJAX callback that returns with an array of suggestion.
		 *
		 * @return {Array} Array of suggestions.
		 */
		async function getResizedContent() {
			let __textArray = [];
			const apiUrl = `${ wpApiSettings.root }classifai/v1/openai/resize-content`;
			const postId = select( editorStore ).getCurrentPostId();
			const formData = new FormData();

			formData.append( 'id', postId );
			formData.append( 'content', selectedText );
			formData.append( 'resize_type', resizeType );

			dispatch( resizeContentStore ).setClientId( selectedBlock.clientId );

			const response = await fetch( apiUrl, {
				method: 'POST',
				body: formData,
			} );

			if ( 200 === response.status ) {
				__textArray = await response.json();
			} else {
				setIsResizing( false );
				dispatch( resizeContentStore ).setClientId( '' );
				resetStates();
			}

			setIsResizing( false );
			dispatch( resizeContentStore ).setClientId( '' );

			return __textArray;
		}

		/**
		 * Updates the text selection.
		 *
		 * @param {string} updateWith The content that will be used to replace the selection.
		 */
		function updateContent( updateWith ) {
			const fullBlockContent = toPlainText( ogText );
			const beforeReplaceable = fullBlockContent.substring( 0, startIndex );
			const afterReplaceable = fullBlockContent.substring( endIndex );
			const updatedContent =
				beforeReplaceable + updateWith + afterReplaceable;

			dispatch( blockEditorStore ).updateBlockAttributes(
				selectedBlock.clientId,
				{
					content: endIndex ? updatedContent : updateWith,
				}
			);

			dispatch( blockEditorStore ).selectionChange(
				selectedBlock.clientId,
				'content',
				startIndex,
				startIndex + updateWith.length
			);
			resetStates();
		}

		return (
			<>
				{ textArray.length && isModalOpen ? (
					<Modal
						title={ __( 'Suggestions', 'classifai' ) }
						isFullScreen={ false }
						className="classifai-content-resize__suggestion-modal"
						isDismissible={ false }
					>
						<div className='classifai-content-resize__modal-close-btn'>
							<Button icon={ close } onClick={ () => {
								setIsModalOpen( false );
								resetStates();
							}} />
						</div>
						<p>{ __( 'Click on a row to apply:' ) }</p>
						<div className="classifai-content-resize__result-wrapper">
							<table className="classifai-content-resize__result-table">
								<thead>
									<tr>
										<th>{ __( 'Suggestion', 'classifai' ) }</th>
										<th className="classifai-content-resize__stat-header">
											{ __( 'Stats', 'classifai' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ textArray.map( ( textItem, index ) => {
										const selectedTextWordCount = getWordCount(
											selectedText,
											'words'
										);
										const selectedTextCharCount = getCharacterCount(
											selectedText,
											'characters_including_spaces'
										);
										const suggestionWordCount = getWordCount(
											textItem,
											'words'
										);
										const suggestionCharCount = getCharacterCount(
											textItem,
											'characters_including_spaces'
										);
		
										const wordDiff =
											suggestionWordCount - selectedTextWordCount;
										const charDiff =
											suggestionCharCount - selectedTextCharCount;
		
										return (
											<tr
												key={ index }
												onClick={ () => updateContent( textItem ) }
											>
												<td>{ textItem }</td>
												<td>
													<ResizeStat count={ wordDiff } />
													<ResizeStat
														count={ charDiff }
														countEntity="character"
													/>
												</td>
											</tr>
										);
									} ) }
								</tbody>
							</table>
						</div>
					</Modal>
				) : null }
				{
					! isResizing && ! isMultiBlocksSelected ? (
						<BlockControls>
							<ToolbarGroup>
								<ToolbarItem>
									{
										( prop ) => (
											<DropdownMenu
												icon={ () => aiIconSvg }
												popoverProps={
													{
														className: 'is-alternate'
													}
												}
											>
												{
													( { onClose } ) => (
														<MenuGroup>
															<MenuItem
																onClick={ () => {
																	resizeContent( 'grow' );
																	onClose();
																} }
															>
																{ __( 'Grow content', 'classifai' ) }
															</MenuItem>
															<MenuItem
																onClick={ () => {
																	resizeContent( 'shrink' );
																	onClose();
																} }
															>
																{ __( 'Shrink content', 'classifai' ) }
															</MenuItem>
														</MenuGroup>
													)
												}
											</DropdownMenu>
										)
									}
								</ToolbarItem>
							</ToolbarGroup>
						</BlockControls>
					): null
				}
				{
					isResizing ? (
						<div style={ { position: 'relative' } }>
							<div className="classifai-content-resize__overlay">
								<div>
									<Spinner />
									{ __( 'Processing content…' ) }
								</div>
							</div>
							<BlockEdit { ...props } />
						</div>
					) : <BlockEdit { ...props } />
				}
			</>
		);
	};
}, 'withInspectorControl' );

wp.hooks.addFilter(
	'editor.BlockEdit',
	'resize-content/lock-block-editing',
	withContentResizingBlockToolbar
);
