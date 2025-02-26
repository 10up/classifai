import { registerPlugin } from '@wordpress/plugins';
import { useRef, useState } from '@wordpress/element';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';

import { useSelectedBlocks, useEditorCanvas } from '../../hooks';
import {
	filterAndFlattenAllowedBlocks,
	getClientIdToBlockContentMapping,
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

	// Stores all the editor blocks (modified and unmodified) that are created for preview.
	const [ previewBlocks, setPreviewBlocks ] = useState( [] );

	// Stores ChatGPT response.
	const [ response, setResponse ] = useState( null );

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
			setPreviewBlocks( [] );

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

	return (
		<ClassifaiEditorSettingPanel>
			<button onClick={ rewriteTone }>Rewrite</button>
		</ClassifaiEditorSettingPanel>
	);
};

registerPlugin( 'classifai-rewrite-tone-plugin', {
	render: RewriteTonePlugin,
} );
