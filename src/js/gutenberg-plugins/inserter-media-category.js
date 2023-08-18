/**
 * Some code here was copied from Jetpack's implementation of the inserter media category.
 * See https://github.com/Automattic/jetpack/pull/31914
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch, select, subscribe } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

const { classifaiDalleData } = window;

const isInserterOpened = () =>
	select( 'core/edit-post' )?.isInserterOpened() ||
	select( 'core/edit-site' )?.isInserterOpened() ||
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
	dispatch( 'core/block-editor' )?.registerInserterMediaCategory?.(
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
	fetch: debounce( imageFetcher, 700 ),
	isExternalResource: true,
} );
