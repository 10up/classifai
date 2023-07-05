import { __ } from '@wordpress/i18n';

// Automatically open Media Modal on Generate Image dashboard
document.addEventListener( 'DOMContentLoaded', function () {
	if ( wp.media ) {
		const frame = wp.media( {
			title: __( 'Generate Images', 'classifai' ),
			button: { text: __( 'View Details', 'classifai' ) },
			multiple: false,
			frame: 'select',
		} );

		frame.on( 'open', function () {
			const uploadImageTab = frame.$el.find(
				'.media-menu-item#menu-item-upload'
			);
			const browseImageTab = frame.$el.find(
				'.media-menu-item#menu-item-browse'
			);
			const generateImageTab = frame.$el.find(
				'.media-menu-item#menu-item-generate'
			);

			// Remove unwanted items
			if ( uploadImageTab.length ) {
				uploadImageTab.hide();
			}

			if ( browseImageTab.length ) {
				// browseImageTab.hide();
			}

			// Open Generate Image Tab
			if ( generateImageTab.length ) {
				generateImageTab.trigger( 'click' );
			}
		} );

		frame.on( 'close', function () {
			// eslint-disable-next-line no-undef
			if ( classifaiGenerateImages ) {
				window.location.href = classifaiGenerateImages[ 'upload_url' ]; // eslint-disable-line no-undef
			}
		} );

		frame.on( 'select', function () {
			// eslint-disable-next-line no-undef
			if ( classifaiGenerateImages ) {
				const attachment = frame
					.state()
					.get( 'selection' )
					.first()
					.toJSON();
				window.location.href = `${ classifaiGenerateImages[ 'upload_url' ] }?item=${ attachment[ 'id' ] }`; // eslint-disable-line no-undef
			}
		} );

		frame.open();
	}
} );
