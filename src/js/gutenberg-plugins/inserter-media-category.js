import { dispatch } from '@wordpress/data';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const { classifaiDalleData, wpApiSettings } = window;

class RegisterInserterMediaCategory extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			loaded: false,
		};
	}

	register() {
		if (
			typeof dispatch( 'core/block-editor' )
				.registerInserterMediaCategory !== 'function'
		) {
			return;
		}

		return dispatch( 'core/block-editor' ).registerInserterMediaCategory( {
			name: 'classifai-generate-image',
			labels: {
				name: classifaiDalleData.tabText,
				search_items: __( 'Enter a prompt', 'classifai' ),
			},
			mediaType: 'image',
			async fetch( { search = '' } ) {
				if ( ! search ) {
					return [];
				}

				const url = new URL(
					wpApiSettings.root + classifaiDalleData.endpoint
				);
				url.searchParams.set( 'prompt', search );
				url.searchParams.set( 'format', 'b64_json' );

				const response = await window.fetch( url, {
					headers: {
						'X-WP-Nonce': wpApiSettings.nonce,
					},
				} );

				if ( response.ok ) {
					const jsonResponse = await response.json();
					return jsonResponse.map( ( item ) => ( {
						title: search,
						url: `data:image/png;base64,${ item.url }`,
						previewUrl: `data:image/png;base64,${ item.url }`,
						id: undefined,
						alt: search,
						caption: classifaiDalleData.caption,
					} ) );
				}

				return [];
			},
			isExternalResource: true,
		} );
	}

	render() {
		if ( ! this.state.loaded ) {
			this.register();
		}

		return null;
	}
}

const registerInserterMediaCategory = () => {
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
		async fetch( { search = '' } ) {
			if ( ! search ) {
				return [];
			}

			const url = new URL(
				wpApiSettings.root + classifaiDalleData.endpoint
			);
			url.searchParams.set( 'prompt', search );
			url.searchParams.set( 'format', 'b64_json' );

			const response = await window.fetch( url, {
				headers: {
					'X-WP-Nonce': wpApiSettings.nonce,
				},
			} );

			if ( response.ok ) {
				const jsonResponse = await response.json();
				return jsonResponse.map( ( item ) => ( {
					title: search,
					url: `data:image/png;base64,${ item.url }`,
					previewUrl: `data:image/png;base64,${ item.url }`,
					id: undefined,
					alt: search,
					caption: classifaiDalleData.caption,
				} ) );
			}

			return [];
		},
		isExternalResource: true,
	} );

	return null;
};

registerPlugin( 'classifai-inserter-media-category', {
	render: registerInserterMediaCategory,
} );
