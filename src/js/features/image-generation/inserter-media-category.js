/**
 * Some code here was copied from Jetpack's implementation of the inserter media category.
 * See https://github.com/Automattic/jetpack/pull/31914
 */
/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch, select, subscribe } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

const { classifaiDalleData } = window;

const isInserterOpened = () =>
	select( editorStore )?.isInserterOpened() ||
	// The edit-widgets store is referenced by string literal on purpose: a
	// static `@wordpress/edit-widgets` import would add `wp-edit-widgets` as a
	// script dependency, which triggers a `_doing_it_wrong` notice when this
	// script is enqueued in the post editor alongside `wp-editor`.
	// eslint-disable-next-line @wordpress/data-no-store-string-literals
	select( 'core/edit-widgets' )?.isInserterOpened?.();

const waitFor = async ( selector ) =>
	new Promise( ( resolve ) => {
		const unsubscribe = subscribe( () => {
			if ( selector() ) {
				unsubscribe();
				resolve();
			}
		} );
	} );

waitFor( isInserterOpened ).then( () =>
	dispatch( blockEditorStore )?.registerInserterMediaCategory?.(
		registerGenerateImageMediaCategory()
	)
);

/**
 * A slightly modified debounced function to add delay
 * to an already debounced function.
 *
 * {@link https://github.com/10up/classifai/issues/561}
 * {@link https://github.com/10up/classifai/pull/535}
 *
 * @param {Function} func    The function to be debounced.
 * @param {number}   timeout The delay in milliseconds.
 * @return {Function} The debounced function.
 */
const debounce = ( func, timeout = 250 ) => {
	let timer;

	return ( ...args ) => {
		clearTimeout( timer );

		return new Promise( ( resolve ) => {
			timer = setTimeout( () => {
				resolve( func.apply( this, args ) );
			}, timeout );
		} );
	};
};

const imageFetcher = async ( { search = '' } ) => {
	if ( ! search ) {
		return [];
	}

	const images = await apiFetch( {
		path: addQueryArgs( classifaiDalleData.endpoint, {
			prompt: search,
			format: 'b64_json',
		} ),
		method: 'GET',
	} )
		.then( ( response ) =>
			response.map( ( item ) => ( {
				title: search,
				url: `data:image/png;base64,${ item.url }`,
				previewUrl: `data:image/png;base64,${ item.url }`,
				id: undefined,
				alt: search,
				caption: classifaiDalleData.caption,
			} ) )
		)
		.catch( () => [] );

	return images;
};

const registerGenerateImageMediaCategory = () => ( {
	name: 'classifai-generate-image',
	labels: {
		name: classifaiDalleData.tabText,
		search_items: __( 'Enter a prompt', 'classifai' ),
	},
	mediaType: 'image',
	fetch: debounce( imageFetcher, 2500 ),
	isExternalResource: true,
} );
