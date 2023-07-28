import apiFetch from '@wordpress/api-fetch';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { addQueryArgs } from '@wordpress/url';

const { classifaiDalleData } = window;

const RegisterInserterMediaCategory = () => {
	const { inserterMediaCategories } = useSelect(
		( select ) => select( 'core/block-editor' ).getSettings(),
		[]
	);

	// If we have no categories yet, assume things aren't ready yet.
	if ( ! inserterMediaCategories ) {
		return null;
	}

	// If our custom category is already registered, don't register it again.
	if (
		inserterMediaCategories.some(
			( { name } ) => name === 'classifai-generate-image'
		)
	) {
		return null;
	}

	// Ensure the function we want to use is available.
	if (
		typeof dispatch( 'core/block-editor' ).registerInserterMediaCategory !==
		'function'
	) {
		return null;
	}

	dispatch( 'core/block-editor' ).registerInserterMediaCategory( {
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

	return null;
};

registerPlugin( 'classifai-inserter-media-category', {
	render: RegisterInserterMediaCategory,
} );
