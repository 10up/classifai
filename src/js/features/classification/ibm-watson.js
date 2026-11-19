/**
 * WordPress dependencies
 */
import { subscribe, select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';

let saveHappened = false;
let showingNotice = false;

subscribe( () => {
	const editor = select( editorStore );

	// Stop if the editor store is missing, for example outside the post editor.
	if ( typeof editor?.isSavingPost !== 'function' ) {
		return;
	}

	const isSavingPost = editor.isSavingPost();

	if ( saveHappened === false ) {
		saveHappened = isSavingPost === true;
	}

	if ( saveHappened && isSavingPost === false && showingNotice === false ) {
		const meta = editor.getCurrentPostAttribute( 'meta' );
		if ( meta && meta._classifai_error ) {
			showingNotice = true;
			const error = JSON.parse( meta._classifai_error );
			dispatch( noticesStore ).createErrorNotice(
				`Failed to classify content with the IBM Watson NLU API. Error: ${ error.code } - ${ error.message }`
			);
			saveHappened = false;
			showingNotice = false;
		}
	}
} );
