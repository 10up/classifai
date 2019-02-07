const { subscribe, select, dispatch } = wp.data;

let saveHappened = false;
let showingNotice = false;

subscribe( () => {

	if ( false === saveHappened ) {
		saveHappened = true === wp.data.select( 'core/editor' ).isSavingPost();
	}

	console.log( 'saveHappened', saveHappened );
	if ( saveHappened && false === wp.data.select( 'core/editor' ).isSavingPost() && false === showingNotice ) {

		const meta = select( 'core/editor' ).getCurrentPostAttribute( 'meta' );
		console.log( meta, meta._klasifai_error );
		showingNotice = true;
		dispatch( 'core/notices' ).createErrorNotice( 'test' );
		saveHappened = false;
		showingNotice = false;
	}
} );
