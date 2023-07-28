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

const registerGenerateImageMediaCategory = () => ( {
	name: 'classifai-generate-image',
	labels: {
		name: classifaiDalleData.tabText,
		search_items: __( 'Enter a prompt', 'classifai' ),
	},
	mediaType: 'image',
	fetch: async ( { search = '' } ) => {
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
	},
	isExternalResource: true,
} );
