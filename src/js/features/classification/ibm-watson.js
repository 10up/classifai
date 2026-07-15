/**
 * WordPress dependencies
 */
import { subscribe, select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';

let saveHappened = false;
let showingNotice = false;

subscribe( () => {
	if ( saveHappened === false ) {
		saveHappened = wp.data.select( editorStore ).isSavingPost() === true;
	}

	if (
		saveHappened &&
		wp.data.select( editorStore ).isSavingPost() === false &&
		showingNotice === false
	) {
		const meta = select( editorStore ).getCurrentPostAttribute( 'meta' );
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
